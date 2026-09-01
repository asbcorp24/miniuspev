<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $group = Group::firstOrCreate(['name' => 'ССА-21'], ['course' => 2, 'speciality' => 'Сетевое и системное администрирование']);
        foreach ([
            ['Иванов', 'Иван', 'Иванович'],
            ['Петров', 'Пётр', 'Сергеевич'],
            ['Сидорова', 'Анна', 'Алексеевна'],
        ] as $i => $fio) {
            Student::firstOrCreate(
                ['student_number' => 'ST-'.($i + 1)],
                ['group_id' => $group->id, 'last_name' => $fio[0], 'first_name' => $fio[1], 'middle_name' => $fio[2], 'active' => true]
            );
        }

        $subject1 = Subject::firstOrCreate(['name' => 'Введение в специальность'], ['code' => 'VS']);
        Subject::firstOrCreate(['name' => 'Компьютерные сети'], ['code' => 'NET']);

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Администратор', 'password' => Hash::make('password'), 'role' => 'admin']
        );

        $teacher = User::updateOrCreate(
            ['email' => 'teacher@example.com'],
            ['name' => 'Иванов Иван Иванович', 'password' => Hash::make('password'), 'role' => 'teacher']
        );

        $teacher->groups()->syncWithoutDetaching([
            $group->id => ['subject_id' => $subject1->id]
        ]);
    }
}
