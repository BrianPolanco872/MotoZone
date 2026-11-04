<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyCut extends Model
{
    use HasFactory;

    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_EMPLOYEE = 'employee';

    protected $fillable = [
        'cut_key',
        'cut_date',
        'scope',
        'user_id',
        'version',
        'totals',
        'generated_by',
        'generated_at',
        'closed_at',
        'reason',
        'cash_counted',
        'cash_counted_at',
        'difference',
        'is_reconciled',
        'previous_cut_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cut_date' => 'date',
            'totals' => 'array',
            'version' => 'integer',
            'generated_at' => 'datetime',
            'closed_at' => 'datetime',
            'cash_counted' => 'decimal:2',
            'cash_counted_at' => 'datetime',
            'difference' => 'decimal:2',
            'is_reconciled' => 'boolean',
            'previous_cut_id' => 'integer',
            'notes' => 'string',
        ];
    }

    /**
     * Verifica si este corte representa un día ya cerrado.
     */
    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * Scope para obtener solo cortes de días aún abiertos.
     */
    public function scopeOpenDays($query)
    {
        return $query->whereNull('closed_at');
    }

    /**
     * Verifica si una fecha dada ya tiene un corte global cerrado.
     */
    public static function isDayClosed(string $date): bool
    {
        return self::query()
            ->where('scope', self::SCOPE_GLOBAL)
            ->whereDate('cut_date', $date)
            ->whereNotNull('closed_at')
            ->exists();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function previousCut(): BelongsTo
    {
        return $this->belongsTo(DailyCut::class, 'previous_cut_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DailyCut::class, 'previous_cut_id')->orderByDesc('version');
    }

    public function isLatestVersion(): bool
    {
        return $this->previousCut?->version < $this->version || ! $this->previousCut;
    }

    public static function keyFor(string $date, string $scope, ?int $userId = null): string
    {
        return $scope === self::SCOPE_GLOBAL
            ? "{$date}|global"
            : "{$date}|employee:{$userId}";
    }

    /**
     * Calcula la siguiente versión disponible para un corte dado.
     */
    public static function nextVersionFor(string $date, string $scope, ?int $userId = null): int
    {
        $cutKey = self::keyFor($date, $scope, $userId);

        $maxVersion = self::query()
            ->where('cut_key', $cutKey)
            ->max('version');

        return (int) ($maxVersion ?? 0) + 1;
    }

    /**
     * Devuelve la última versión guardada para un corte dado.
     */
    public static function latestVersion(string $date, ?User $employee = null): ?self
    {
        $cutDate = Carbon::parse($date)->toDateString();
        $scope = $employee ? self::SCOPE_EMPLOYEE : self::SCOPE_GLOBAL;

        return self::query()
            ->where('cut_key', self::keyFor($cutDate, $scope, $employee?->id))
            ->orderByDesc('version')
            ->first();
    }
}
