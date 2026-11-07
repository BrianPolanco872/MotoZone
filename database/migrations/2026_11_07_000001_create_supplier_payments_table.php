<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // empleado que registró el pago
            $table->foreignId('daily_cut_id')->nullable()->constrained()->nullOnDelete(); // corte al que pertenece (se asigna al cerrar día)
            $table->decimal('amount', 12, 2);
            $table->string('method'); // cash, card, transfer
            $table->text('reason'); // motivo o concepto obligatorio (ej. "pago de mercancía", "factura #123")
            $table->string('supplier_name'); // nombre del proveedor (texto libre)
            $table->unsignedBigInteger('supplier_id')->nullable(); // FK opcional; tabla suppliers aún no existe
            $table->timestamp('happened_at');
            $table->timestamps();

            $table->index(['happened_at', 'user_id']);
            $table->index('daily_cut_id');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
