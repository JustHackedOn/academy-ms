<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained('installments')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_on')->index();
            $table->string('for_month', 7)->index(); // YYYY-MM
            $table->enum('method', ['cash', 'jazzcash', 'easypaisa', 'bank', 'other'])->default('cash');
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_no')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        // Allocation pivot for splitting payments across multiple installments
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('installment_id')->constrained('installments')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};
