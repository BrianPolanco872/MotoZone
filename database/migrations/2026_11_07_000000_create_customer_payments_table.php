<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // empleado que registró el abono
            $table->foreignId('daily_cut_id')->nullable()->constrained()->nullOnDelete(); // corte al que pertenece (se asigna al cerrar día)
            $table->decimal('amount', 12, 2);
            $table->string('method'); // cash, card, transfer
            $table->text('reason')->nullable(); // motivo o nota (ej. "abono a cuenta", "liquidación")
            $table->string('customer_name'); // nombre del cliente (texto libre)
            $table->unsignedBigInteger('customer_id')->nullable(); // FK opcional; tabla customers aún no existe
            $table->timestamp('happened_at');
            $table->timestamps();

            $table->index(['happened_at', 'user_id']);
            $table->index('daily_cut_id');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
