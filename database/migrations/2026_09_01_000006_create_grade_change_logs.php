<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grade_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type', 30); // journal|homework|final
            $table->unsignedBigInteger('source_id');
            $table->unsignedTinyInteger('old_grade')->nullable();
            $table->unsignedTinyInteger('new_grade')->nullable();
            $table->string('reason')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->index(['student_id','created_at']);
            $table->index(['source_type','source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_change_logs');
    }
};
