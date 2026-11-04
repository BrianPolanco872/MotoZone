<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_cuts', function (Blueprint $table) {
            $table->id();
            $table->string('cut_key')->unique();
            $table->date('cut_date');
            $table->string('scope');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('totals');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cut_date', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_cuts');
    }
};
