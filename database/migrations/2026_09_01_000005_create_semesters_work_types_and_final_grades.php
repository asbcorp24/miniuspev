<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20); // 2026/2027
            $table->unsignedTinyInteger('semester'); // 1|2
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();
            $table->unique(['academic_year', 'semester']);
        });

        Schema::create('work_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->decimal('default_weight', 5, 2)->default(1.00);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('academic_period_id')->nullable()->after('subject_id')->constrained('academic_periods')->nullOnDelete();
            $table->foreignId('work_type_id')->nullable()->after('academic_period_id')->constrained('work_types')->nullOnDelete();
            $table->decimal('grade_weight', 5, 2)->default(1.00)->after('work_type_id');
        });

        Schema::table('homeworks', function (Blueprint $table) {
            $table->foreignId('academic_period_id')->nullable()->after('subject_id')->constrained('academic_periods')->nullOnDelete();
            $table->foreignId('work_type_id')->nullable()->after('academic_period_id')->constrained('work_types')->nullOnDelete();
            $table->decimal('grade_weight', 5, 2)->default(1.00)->after('work_type_id');
        });

        Schema::create('final_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->decimal('calculated_grade', 4, 2)->nullable();
            $table->unsignedTinyInteger('final_grade')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'subject_id', 'academic_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_grades');
        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_type_id');
            $table->dropConstrainedForeignId('academic_period_id');
            $table->dropColumn('grade_weight');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_type_id');
            $table->dropConstrainedForeignId('academic_period_id');
            $table->dropColumn('grade_weight');
        });
        Schema::dropIfExists('work_types');
        Schema::dropIfExists('academic_periods');
    }
};
