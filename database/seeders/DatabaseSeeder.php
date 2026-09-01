<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\Group;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Models\WorkType;
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

        AcademicPeriod::where('active', true)->update(['active' => false]);
        AcademicPeriod::updateOrCreate(
            ['academic_year' => '2026/2027', 'semester' => 1],
            ['starts_at' => '2026-09-01', 'ends_at' => '2026-12-31', 'active' => true]
        );

        foreach ([
            ['Ответ на занятии','CLASS',1.0],
            ['Домашняя работа','HOMEWORK',1.0],
            ['Лабораторная работа','LAB',1.5],
            ['Самостоятельная работа','SELF',1.5],
            ['Контрольная работа','TEST',2.0],
            ['Зачёт','CREDIT',2.5],
            ['Экзамен','EXAM',3.0],
        ] as [$name,$code,$weight]) {
            WorkType::updateOrCreate(['code'=>$code], ['name'=>$name,'default_weight'=>$weight,'active'=>true]);
        }

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Администратор', 'password' => Hash::make('password'), 'role' => 'admin', 'student_id' => null]
        );

        $teacher = User::updateOrCreate(
            ['email' => 'teacher@example.com'],
            ['name' => 'Иванов Иван Иванович', 'password' => Hash::make('password'), 'role' => 'teacher', 'student_id' => null]
        );
        $teacher->groups()->syncWithoutDetaching([$group->id => ['subject_id' => $subject1->id]]);

        $student = Student::where('student_number', 'ST-1')->first();
        if ($student) {
            User::updateOrCreate(
                ['email' => 'student@example.com'],
                ['name' => $student->full_name, 'password' => Hash::make('password'), 'role' => 'student', 'student_id' => $student->id]
            );
        }
    }
}
