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

    private function ensureStudentManager(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isGroupLeader()), 403);
        if ($user->isGroupLeader()) abort_unless($user->managedGroupId(), 403);
    }

    private function ensureStudentInScope(Student $student): void
    {
        $this->ensureStudentManager();
        $user = auth()->user();
        if ($user->isGroupLeader()) abort_unless($student->group_id === $user->managedGroupId(), 403);
    }

    private function generateLogin(Student $student): string
    {
        $base = $student->student_number ?: 'student'.$student->id;
        $base = Str::lower(preg_replace('/[^a-zA-Z0-9._-]/u', '', $base) ?: 'student'.$student->id);
        $email = $base.'@student.local';
        $suffix = 1;
        while (User::where('email', $email)->exists()) {
            $email = $base.$suffix.'@student.local';
            $suffix++;
        }
        return $email;
    }

    private function generatePassword(): string
    {
        return Str::upper(Str::random(2)).Str::lower(Str::random(4)).random_int(1000, 9999).'!';
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
        $this->ensureStudentManager();
        $user = auth()->user();
        $groups = $user->isAdmin()
            ? Group::orderBy('name')->get()
            : Group::whereKey($user->managedGroupId())->get();
        $groupId = $user->isAdmin()
            ? ($request->integer('group_id') ?: optional($groups->first())->id)
            : $user->managedGroupId();

        $students = Student::with(['group'])
            ->when($groupId, fn($q) => $q->where('group_id', $groupId))
            ->orderBy('last_name')->orderBy('first_name')->get();

        $accounts = User::whereIn('role', ['student','group_leader'])
            ->whereIn('student_id', $students->pluck('id'))
            ->get()->keyBy('student_id');

        return view('admin.students', compact('groups','groupId','students','accounts'));
    }

    public function createStudentAccount(Request $request, Student $student): RedirectResponse
    {
        $this->ensureStudentInScope($student);
        abort_if(User::where('student_id', $student->id)->whereIn('role',['student','group_leader'])->exists(), 422, 'У студента уже есть учетная запись.');

        $data = $request->validate([
            'email' => ['nullable','email','unique:users,email'],
            'password' => ['nullable','string','min:6','max:100'],
            'auto' => ['nullable','boolean'],
        ]);

        $email = $data['email'] ?? $this->generateLogin($student);
        $password = $data['password'] ?? $this->generatePassword();

        User::create([
            'name' => $student->full_name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'student',
            'student_id' => $student->id,
        ]);

        return back()->with('success', 'Доступ для '.$student->full_name.' создан.')
            ->with('generated_accounts', [['name'=>$student->full_name,'email'=>$email,'password'=>$password]]);
    }

    public function resetStudentPassword(Request $request, User $user): RedirectResponse
    {
        $this->ensureStudentManager();
        abort_unless(in_array($user->role,['student','group_leader'],true) && $user->student_id, 422);
        $student = Student::findOrFail($user->student_id);
        $this->ensureStudentInScope($student);
        $data = $request->validate([
            'password' => ['nullable','string','min:6','max:100'],
            'auto' => ['nullable','boolean'],
        ]);
        $password = $data['password'] ?? $this->generatePassword();
        $user->update(['password' => Hash::make($password)]);
        return back()->with('success', 'Пароль студента '.$user->name.' изменен.')
            ->with('generated_accounts', [['name'=>$user->name,'email'=>$user->email,'password'=>$password]]);
    }

    public function bulkCreateStudentAccounts(Request $request): RedirectResponse
    {
        $this->ensureStudentManager();
        $user = auth()->user();
        $data = $request->validate(['group_id' => ['required','exists:groups,id']]);
        if ($user->isGroupLeader()) abort_unless((int)$data['group_id'] === $user->managedGroupId(), 403);

        $students = Student::where('group_id', $data['group_id'])->where('active', true)->orderBy('last_name')->get();
        $created = [];

        foreach ($students as $student) {
            if (User::where('student_id', $student->id)->whereIn('role',['student','group_leader'])->exists()) continue;
            $email = $this->generateLogin($student);
            $password = $this->generatePassword();
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

    public function promoteGroupLeader(User $user): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless($user->student_id && in_array($user->role,['student','group_leader'],true), 422);
        User::where('role','group_leader')
            ->whereHas('student', fn($q) => $q->where('group_id', $user->student?->group_id))
            ->where('id','!=',$user->id)
            ->update(['role'=>'student']);
        $user->update(['role'=>'group_leader']);
        return back()->with('success','Староста назначен: '.$user->name.'.');
    }

    public function demoteGroupLeader(User $user): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless($user->role === 'group_leader', 422);
        $user->update(['role'=>'student']);
        return back()->with('success','Роль старосты снята с '.$user->name.'.');
    }
}
