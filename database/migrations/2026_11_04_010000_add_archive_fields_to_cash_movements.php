<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_movements', function (Blueprint $table): void {
            $table->timestamp('archivado_at')->nullable()->after('happened_at');
            $table->foreignId('archivado_por')->nullable()->constrained('users')->nullOnDelete()->after('archivado_at');
            $table->timestamp('deleted_at')->nullable()->after('archivado_por');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete()->after('deleted_at');
            $table->text('deletion_reason')->nullable()->after('deleted_by');

            $table->index('archivado_at');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('cash_movements', function (Blueprint $table): void {
            $table->dropIndex(['archivado_at']);
            $table->dropIndex(['deleted_at']);
            $table->dropForeign(['archivado_por']);
            $table->dropForeign(['deleted_by']);
            $table->dropColumn([
                'archivado_at',
                'archivado_por',
                'deleted_at',
                'deleted_by',
                'deletion_reason',
            ]);
        });
    }
};
