<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('lesson_kind')->nullable()->after('topic');
            $table->text('description')->nullable()->after('lesson_kind');
        });

        Schema::create('lesson_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('type'); // file|link|video
            $table->string('title')->nullable();
            $table->string('url')->nullable();
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
            $table->index(['lesson_id','type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_materials');
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['lesson_kind','description']);
        });
    }
};
