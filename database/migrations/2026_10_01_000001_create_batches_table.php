<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('default_fee', 12, 2)->default(0);
            $table->unsignedInteger('duration_months')->default(4);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['upcoming', 'running', 'completed', 'archived'])->default('upcoming');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
