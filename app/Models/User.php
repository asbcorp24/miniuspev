<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name','email','password','role'];
    protected $hidden = ['password','remember_token'];

    public function isAdmin(): bool { return $this->role === 'admin'; }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'teacher_assignments')->withPivot('subject_id')->withTimestamps();
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teacher_assignments')->withPivot('group_id')->withTimestamps();
    }
}
