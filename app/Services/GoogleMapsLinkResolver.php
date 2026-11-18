<?php

namespace App\Services;

use App\Models\HomePage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GoogleMapsLinkResolver
{
    public static function supportsShortUrl(string $url): bool
    {
        return strlen($url) <= 8192 && HomePage::isGoogleMapsUrl($url)
            && strtolower((string) parse_url($url, PHP_URL_HOST)) === 'maps.app.goo.gl';
    }

    public function resolve(string $url): ?string
    {
        if (! self::supportsShortUrl($url)) {
            return null;
        }

        $deadline = microtime(true) + 8;
        $visited = [];

        try {
            for ($hop = 0; $hop < 3; $hop++) {
                if (isset($visited[$url])) {
                    return null;
                }

                $visited[$url] = true;
                $target = $this->redirectTarget($url, $deadline, 'HEAD');
                if ($target === null) {
                    return null;
                }

                $coordinates = $this->coordinates($target);
                if ($coordinates !== null) {
                    $mapUrl = 'https://maps.google.com/maps?'.http_build_query([
                        'q' => implode(',', $coordinates), 'z' => 16, 'output' => 'embed',
                    ], '', '&', PHP_QUERY_RFC3986);
                    $embed = $this->redirectTarget($mapUrl, $deadline, 'GET');

                    return $embed !== null && HomePage::isGoogleMapsUrl($embed, true) ? $embed : null;
                }

                $url = $target;
            }
        } catch (ConnectionException) {
            return null;
        }

        return null;
    }

    private function redirectTarget(string $url, float $deadline, string $method): ?string
    {
        $remaining = min(3, $deadline - microtime(true));
        if ($remaining <= 0 || strlen($url) > 8192 || ! HomePage::isGoogleMapsUrl($url)) {
            return null;
        }

        $response = Http::withoutRedirecting()->connectTimeout(min(2, $remaining))->timeout($remaining)
            ->withOptions(['stream' => true])->send($method, $url);
        $target = $response->redirect() ? $response->header('Location') : null;

        return is_string($target) && strlen($target) <= 8192 && HomePage::isGoogleMapsUrl($target) ? $target : null;
    }

    /** @return array{string, string}|null */
    private function coordinates(string $url): ?array
    {
        if (in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), ['maps.app.goo.gl', 'goo.gl'], true)) {
            return null;
        }

        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        if (str_starts_with($path, '/maps/place/')) {
            if (preg_match_all('~!3d(-?[0-9]+(?:\.[0-9]+)?)!4d(-?[0-9]+(?:\.[0-9]+)?)(?=!|/|$)~', $path, $matches, PREG_SET_ORDER) !== 1) {
                return null;
            }

            $point = $matches[0];
        } elseif (in_array(rtrim($path, '/'), ['', '/maps', '/maps/search'], true)) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $value = $query['q'] ?? $query['query'] ?? null;
            if (! is_string($value) || preg_match('/\A(-?[0-9]+(?:\.[0-9]+)?),\s*(-?[0-9]+(?:\.[0-9]+)?)\z/', trim($value), $point) !== 1) {
                return null;
            }
        } else {
            return null;
        }

        return abs((float) $point[1]) <= 90 && abs((float) $point[2]) <= 180 ? [$point[1], $point[2]] : null;
    }
}
