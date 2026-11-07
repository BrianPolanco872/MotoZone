<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Usuarios con sesión activa en los últimos 5 minutos.
 *
 * Se basa en la tabla `sessions` (driver database). No requiere migraciones ni
 * escritura por request, por lo que es apto para entornos multi-caja donde
 * varios cajeros están conectados al mismo tiempo.
 */
class PresenceService
{
    /**
     * Ventana de actividad en minutos para considerar a un usuario "en línea".
     */
    public const ACTIVE_WINDOW_MINUTES = 5;

    /**
     * @return Collection<int, array{id: int, name: string, email: string|null, last_activity: Carbon}>
     */
    public function onlineUsers(): Collection
    {
        if (config('session.driver', 'file') !== 'database') {
            return collect();
        }

        $threshold = Carbon::now()->subMinutes(self::ACTIVE_WINDOW_MINUTES)->getTimestamp();

        $rows = DB::table('sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->whereNull('users.deleted_at')
            ->where('users.is_active', true)
            ->where('sessions.last_activity', '>=', $threshold)
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('MAX(sessions.last_activity) as last_activity')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('last_activity')
            ->get();

        return collect($rows)->map(fn ($row) => [
            'id' => (int) $row->id,
            'name' => $row->name,
            'email' => $row->email,
            'last_activity' => Carbon::createFromTimestamp((int) $row->last_activity),
        ]);
    }
}
