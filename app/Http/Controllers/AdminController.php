<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function teachers(): View
    {
        $this->ensureAdmin();
        return view('admin.teachers', [
            'teachers' => User::where('role','teacher')->with(['groups','subjects'])->orderBy('name')->get(),
            'groups' => Group::orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function storeTeacher(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','unique:users,email'],
            'password' => ['required','string','min:6'],
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'teacher';
        User::create($data);
        return back()->with('success','Преподаватель создан.');
    }

    public function assign(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless($user->role === 'teacher', 422);
        $data = $request->validate([
            'group_id' => ['required','exists:groups,id'],
            'subject_id' => ['required','exists:subjects,id'],
        ]);

        $user->groups()->syncWithoutDetaching([
            $data['group_id'] => ['subject_id' => $data['subject_id']]
        ]);
        return back()->with('success','Назначение добавлено.');
    }

    public function students(Request $request): View
    {
        $this->ensureAdmin();
        $groups = Group::orderBy('name')->get();
        $groupId = $request->integer('group_id') ?: optional($groups->first())->id;

        $students = Student::with(['group'])
            ->when($groupId, fn($q) => $q->where('group_id', $groupId))
            ->orderBy('last_name')->orderBy('first_name')->get();

        $accounts = User::where('role', 'student')
            ->whereIn('student_id', $students->pluck('id'))
            ->get()->keyBy('student_id');

        return view('admin.students', compact('groups','groupId','students','accounts'));
    }

    public function createStudentAccount(Request $request, Student $student): RedirectResponse
    {
        $this->ensureAdmin();
        abort_if(User::where('student_id', $student->id)->where('role','student')->exists(), 422, 'У студента уже есть учетная запись.');

        $data = $request->validate([
            'email' => ['required','email','unique:users,email'],
            'password' => ['required','string','min:6','max:100'],
        ]);

        User::create([
            'name' => $student->full_name,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
            'student_id' => $student->id,
        ]);

        return back()->with('success', 'Доступ для '.$student->full_name.' создан.');
    }

    public function resetStudentPassword(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless($user->role === 'student' && $user->student_id, 422);
        $data = $request->validate([
            'password' => ['required','string','min:6','max:100'],
        ]);
        $user->update(['password' => Hash::make($data['password'])]);
        return back()->with('success', 'Пароль студента '.$user->name.' изменен.');
    }

    public function bulkCreateStudentAccounts(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['group_id' => ['required','exists:groups,id']]);
        $students = Student::where('group_id', $data['group_id'])->where('active', true)->orderBy('last_name')->get();
        $created = [];

        foreach ($students as $student) {
            if (User::where('student_id', $student->id)->where('role','student')->exists()) continue;

            $base = $student->student_number ?: 'student'.$student->id;
            $base = Str::lower(preg_replace('/[^a-zA-Z0-9._-]/u', '', $base) ?: 'student'.$student->id);
            $email = $base.'@student.local';
            $suffix = 1;
            while (User::where('email', $email)->exists()) {
                $email = $base.$suffix.'@student.local';
                $suffix++;
            }

            $password = Str::upper(Str::random(2)).Str::lower(Str::random(4)).random_int(1000, 9999).'!';
            User::create([
                'name' => $student->full_name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'student',
                'student_id' => $student->id,
            ]);
            $created[] = ['name' => $student->full_name, 'email' => $email, 'password' => $password];
        }

        if (!$created) return back()->with('success', 'У всех активных студентов этой группы доступ уже создан.');
        return back()->with('success', 'Создано учетных записей: '.count($created))->with('generated_accounts', $created);
    }
}
