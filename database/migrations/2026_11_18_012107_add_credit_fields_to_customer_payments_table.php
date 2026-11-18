<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->foreignId('customer_credit_id')->nullable()->after('customer_id')
                ->constrained('customer_credits')->nullOnDelete();
            $table->string('idempotency_key', 64)->nullable()->unique()->after('customer_credit_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('customer_payments', 'customer_credit_id')
            && DB::table('customer_payments')->whereNotNull('customer_credit_id')->exists()) {
            throw new RuntimeException('No se revierte el vínculo de pagos porque existen abonos de créditos registrados. Desactiva el módulo conservando los datos.');
        }

        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropConstrainedForeignId('customer_credit_id');
            $table->dropColumn('idempotency_key');
        });
    }
};
