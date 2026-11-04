<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_cuts', function (Blueprint $table): void {
            // Quitar el unique simple de cut_key (se reemplaza por compuesto cut_key + version)
            $table->dropUnique(['cut_key']);

            $table->unsignedInteger('version')->default(1)->after('cut_key');
            $table->text('reason')->nullable()->after('notes');
            $table->decimal('cash_counted', 12, 2)->nullable()->after('generated_at');
            $table->timestamp('cash_counted_at')->nullable()->after('cash_counted');
            $table->decimal('difference', 12, 2)->nullable()->after('cash_counted_at');
            $table->boolean('is_reconciled')->default(false)->after('difference');
            $table->foreignId('previous_cut_id')->nullable()->constrained('daily_cuts')->nullOnDelete()->after('difference');
        });

        // Índice único compuesto para permitir múltiples versiones del mismo corte
        Schema::table('daily_cuts', function (Blueprint $table): void {
            $table->unique(['cut_key', 'version'], 'daily_cuts_cut_key_version_unique');
        });

        // Índice para navegar versiones rápidamente
        Schema::table('daily_cuts', function (Blueprint $table): void {
            $table->index(['cut_date', 'scope', 'user_id', 'version'], 'daily_cuts_date_scope_user_version_idx');
        });
    }

    public function down(): void
    {
        Schema::table('daily_cuts', function (Blueprint $table): void {
            $table->dropForeign(['previous_cut_id']);
            $table->dropColumn([
                'version',
                'reason',
                'cash_counted',
                'cash_counted_at',
                'difference',
                'is_reconciled',
                'previous_cut_id',
            ]);
        });

        // Restaurar el unique simple de cut_key
        Schema::table('daily_cuts', function (Blueprint $table): void {
            $table->dropUnique(['daily_cuts_cut_key_version_unique']);
            $table->unique(['cut_key'], 'daily_cuts_cut_key_unique');
        });
    }
};
