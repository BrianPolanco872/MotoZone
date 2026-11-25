<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class TicketSettings
{
    public function current(): array
    {
        $disk = Storage::disk('local');
        $saved = $disk->exists('pos/ticket-settings.json')
            ? json_decode($disk->get('pos/ticket-settings.json'), true, 512, JSON_THROW_ON_ERROR)
            : [];

        return array_replace_recursive(config('pos'), ['ticket' => ['width_mm' => 58]], $saved);
    }

    public function save(array $settings): void
    {
        $path = Storage::disk('local')->path('pos/ticket-settings.json');
        File::ensureDirectoryExists(dirname($path));
        File::replace($path, json_encode($settings, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

    }
}
