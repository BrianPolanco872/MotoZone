<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Assignable permissions grouped by module, with plain-language labels.
     *
     * @var array<string, array<string, array{label: string, description: string}>>
     */
    public const PERMISSION_CATALOG = [
        'Ventas' => [
            'pos.access' => [
                'label' => 'Usar el punto de venta',
                'description' => 'Abrir la caja para cobrar y registrar ventas.',
            ],
            'pos.sell' => [
                'label' => 'Cobrar y registrar ventas',
                'description' => 'Registrar ventas y cobrar en efectivo.',
            ],
            'pos.price.edit' => [
                'label' => 'Cambiar precios al cobrar',
                'description' => 'Modificar el precio de un producto al momento de la venta.',
            ],
            'pos.discount' => [
                'label' => 'Aplicar descuentos al cobrar',
                'description' => 'Aplicar un descuento sobre el total de una venta.',
            ],
            'pos.cancel.sale' => [
                'label' => 'Cancelar ventas pendientes',
                'description' => 'Anular una venta que aún no se ha cobrado.',
            ],
            'pos.wholesale' => [
                'label' => 'Vender a precio de mayoreo',
                'description' => 'Cobrar el precio de mayoreo cuando el producto lo tenga.',
            ],
        ],
        'Caja y corte' => [
            'pos.cash.manage' => [
                'label' => 'Registrar entradas y salidas de efectivo',
                'description' => 'Anotar dinero inicial, cambio, pagos a proveedores, gastos y retiros.',
            ],
            'pos.cut.manage' => [
                'label' => 'Hacer el corte del día',
                'description' => 'Generar, consultar e imprimir el corte global y por empleado.',
            ],
            'pos.cut.own' => [
                'label' => 'Ver y hacer su propio corte',
                'description' => 'Consultar y generar el corte del día de su propio usuario.',
            ],
        ],
        'Tickets y consultas' => [
            'pos.tickets.print.own' => [
                'label' => 'Imprimir sus propios tickets',
                'description' => 'Reimprimir los tickets de sus propias ventas.',
            ],
            'pos.tickets.print.any' => [
                'label' => 'Imprimir tickets de cualquier empleado',
                'description' => 'Reimprimir tickets de ventas hechas por otras personas.',
            ],
            'pos.sales.day.own' => [
                'label' => 'Ver sus ventas del día',
                'description' => 'Consultar sus ventas y su corte del día.',
            ],
            'pos.sales.day.any' => [
                'label' => 'Ver las ventas de todos',
                'description' => 'Consultar ventas y cortes de todos los empleados.',
            ],
        ],
        'Devoluciones' => [
            'pos.returns.request' => [
                'label' => 'Solicitar devoluciones',
                'description' => 'Pedir la devolución de un producto de una venta.',
            ],
            'pos.returns.approve' => [
                'label' => 'Autorizar devoluciones',
                'description' => 'Aprobar o rechazar solicitudes de devolución.',
            ],
            'pos.return.do' => [
                'label' => 'Registrar y procesar devoluciones',
                'description' => 'Solicitar y gestionar la devolución de productos de una venta.',
            ],
        ],
        'Productos' => [
            'admin.products.manage' => [
                'label' => 'Ver y editar productos',
                'description' => 'Consultar el catálogo y actualizar productos, precios y existencias.',
            ],
            'pos.products.view' => [
                'label' => 'Ver productos en el punto de venta',
                'description' => 'Buscar y consultar productos al atender una venta.',
            ],
            'pos.products.edit' => [
                'label' => 'Editar productos',
                'description' => 'Crear, editar y eliminar productos del catálogo.',
            ],
        ],
    ];

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function permissionCatalog(): array
    {
        return array_merge(...array_values(self::PERMISSION_CATALOG));
    }

    /**
     * @return array<string, string>
     */
    public static function permissionLabels(): array
    {
        return array_map(
            fn (array $permission): string => $permission['label'],
            self::permissionCatalog(),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function assignablePermissions(): array
    {
        return array_keys(self::permissionCatalog());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
