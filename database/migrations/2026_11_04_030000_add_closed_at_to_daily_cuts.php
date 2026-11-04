<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_cuts', function (Blueprint $table): void {
            $table->timestamp('closed_at')->nullable()->after('generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('daily_cuts', function (Blueprint $table): void {
            $table->dropColumn('closed_at');
        });
    }
};
