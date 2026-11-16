<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GalleryService
{
    /**
     * @param  array<int, UploadedFile>  $files
     * @return list<string>
     */
    public function store(array $files, string $directory): array
    {
        $paths = [];

        foreach ($files as $file) {
            $path = $file->store($directory, 'public');

            if ($path === false) {
                $this->delete($paths);

                throw new RuntimeException('No se pudo guardar una de las fotos.');
            }

            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * @param  list<string>  $current
     * @param  array<int, string>  $remove
     * @return list<string>
     */
    public function keep(array $current, array $remove): array
    {
        return array_values(array_diff($current, $remove));
    }

    /** @param  array<int, string>  $paths */
    public function deleteAfterCommit(array $paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            DB::afterCommit(fn () => $this->delete($paths));
        }
    }

    /** @param  array<int, string>  $paths */
    public function delete(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            if (str_starts_with(strtolower($path), 'https://')) {
                continue;
            }

            rescue(fn () => Storage::disk('public')->delete($path));
        }
    }
}
