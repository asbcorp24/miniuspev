<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schedule_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->nullOnDelete();
            $table->unsignedTinyInteger('weekday'); // 1=Mon ... 7=Sun
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room')->nullable();
            $table->string('lesson_type')->nullable();
            $table->string('note')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['group_id','weekday','starts_at']);
            $table->index(['teacher_id','weekday','starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_entries');
    }
};
