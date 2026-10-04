<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->date('promise_date')->nullable()->index();
            $table->enum('type', ['note', 'call', 'follow_up', 'promise'])->default('note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_notes');
    }
};
