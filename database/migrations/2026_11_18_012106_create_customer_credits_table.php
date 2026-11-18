<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_credits', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->text('description');
            $table->unsignedBigInteger('total_cents');
            $table->unsignedBigInteger('balance_cents');
            $table->string('status')->default('pending');
            // Protege también las altas sin abono inicial, donde aún no existe un pago.
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_name', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        // Reversión honesta: no borrar deudas con historial monetario.
        if (Schema::hasTable('customer_credits') && DB::table('customer_credits')->exists()) {
            throw new RuntimeException('No se revierte el esquema de cartera porque contiene cuentas registradas. Desactiva el módulo conservando los datos.');
        }

        Schema::dropIfExists('customer_credits');
    }
};
