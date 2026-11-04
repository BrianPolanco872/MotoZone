<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashMovement extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    /**
     * Cash movement categories and their natural labels.
     *
     * @var array<string, array{type: string, label: string, manual: bool}>
     */
    public const CATEGORIES = [
        'initial' => ['type' => self::TYPE_IN, 'label' => 'Dinero inicial en caja', 'manual' => true],
        'change' => ['type' => self::TYPE_IN, 'label' => 'Entrada de dinero (cambio)', 'manual' => true],
        'customer_payment' => ['type' => self::TYPE_IN, 'label' => 'Pago de cliente', 'manual' => true],
        'sale' => ['type' => self::TYPE_IN, 'label' => 'Venta en efectivo', 'manual' => false],
        'supplier_payment' => ['type' => self::TYPE_OUT, 'label' => 'Pago a proveedor', 'manual' => true],
        'expense' => ['type' => self::TYPE_OUT, 'label' => 'Gasto', 'manual' => true],
        'withdrawal' => ['type' => self::TYPE_OUT, 'label' => 'Retiro de efectivo', 'manual' => true],
        'refund' => ['type' => self::TYPE_OUT, 'label' => 'Devolución', 'manual' => false],
        'other' => ['type' => self::TYPE_IN, 'label' => 'Otro movimiento', 'manual' => true],
    ];

    protected $fillable = [
        'user_id',
        'sale_id',
        'type',
        'category',
        'amount',
        'concept',
        'happened_at',
        'archivado_at',
        'archivado_por',
        'deletion_reason',
    ];

    protected $dates = [
        'happened_at',
        'archivado_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'happened_at' => 'datetime',
            'archivado_at' => 'datetime',
            'archivado_por' => 'integer',
            'deleted_at' => 'datetime',
            'deleted_by' => 'integer',
            'deletion_reason' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archivado_por');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Manual categories available in the cash movement form.
     *
     * @return array<string, array{type: string, label: string, manual: bool}>
     */
    public static function manualCategories(): array
    {
        return array_filter(self::CATEGORIES, fn (array $category): bool => $category['manual']);
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_map(fn (array $category): string => $category['label'], self::CATEGORIES);
    }

    public static function categoryType(string $category): ?string
    {
        return self::CATEGORIES[$category]['type'] ?? null;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category]['label'] ?? $this->category;
    }

    public function isIn(): bool
    {
        return $this->type === self::TYPE_IN;
    }

    public function isArchived(): bool
    {
        return $this->archivado_at !== null;
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    /**
     * Verifica si este movimiento pertenece a un corte ya cerrado (daily_cuts).
     * Si pertenece a un corte, no se puede eliminar, solo archivar.
     */
    public function belongsToClosedCut(): bool
    {
        if (! $this->sale_id) {
            return false;
        }

        $sale = $this->sale;

        if (! $sale) {
            return false;
        }

        $paidAt = $sale->paid_at;

        if (! $paidAt) {
            return false;
        }

        $cutDate = $paidAt->toDateString();

        return DailyCut::query()
            ->whereDate('cut_date', $cutDate)
            ->where('scope', 'global')
            ->exists();
    }

    /**
     * Determina si el movimiento puede eliminarse.
     * Solo se puede eliminar si no pertenece a un corte cerrado.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->belongsToClosedCut() && ! $this->isDeleted();
    }
}
