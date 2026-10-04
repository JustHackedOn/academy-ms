<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20)->index(); // Normalized 92XXXXXXXXXX
            $table->string('alt_phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->enum('fee_type', ['full', 'installments'])->default('installments');
            $table->decimal('total_fee', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('net_fee', 12, 2);
            $table->date('join_date');
            $table->enum('status', ['active', 'dropped', 'completed', 'paused'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
