<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
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
}
