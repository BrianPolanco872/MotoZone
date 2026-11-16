<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HttpsImageUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = is_string($value) ? parse_url($value) : false;

        if (! is_array($parts)
            || filter_var($value, FILTER_VALIDATE_URL) === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || $this->isUnsafeHost($parts['host'])) {
            $fail('Pega la dirección HTTPS directa de una imagen, sin credenciales ni hosts locales.');
        }
    }

    private function isUnsafeHost(string $host): bool
    {
        $host = strtolower(rtrim(trim($host, '[]'), '.'));

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return true;
        }

        return filter_var($host, FILTER_VALIDATE_IP) !== false
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
