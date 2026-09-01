<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Старые записи сохраняем как есть. Новые занятия будут создаваться со статусом unmarked.
    }

    public function down(): void
    {
        DB::table('journal_records')->where('attendance', 'unmarked')->update(['attendance' => 'present']);
    }
};
