<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Acciones sensibles que se registran en auditoría.
     */
    public const ACTION_CANCEL_SALE = 'cancelar_venta';

    public const ACTION_RETURN_SALE = 'devolver_venta';

    public const ACTION_ARCHIVE_CASH_MOVEMENT = 'archivar_movimiento';

    public const ACTION_DELETE_CASH_MOVEMENT = 'eliminar_movimiento';

    public const ACTION_MAKE_CUT = 'hacer_corte';

    public const ACTION_RECORD_CASH_COUNT = 'rehacer_corte';

    public const ACTION_CHANGE_PRICE = 'cambiar_precio';

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'before',
        'after',
        'ip_address',
        'user_agent',
    ];

    protected $dates = ['deleted_at'];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
