<?php

namespace App\Services;

class FolioGenerator
{
    public function forId(int $id): string
    {
        $prefix = (string) config('pos.folio.prefix', 'V');
        $padding = (int) config('pos.folio.padding', 6);

        return $prefix.'-'.str_pad((string) $id, $padding, '0', STR_PAD_LEFT);
    }
}
