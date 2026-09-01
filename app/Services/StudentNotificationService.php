<?php

namespace App\Services;

use App\Models\Homework;
use App\Models\StudentNotification;
use App\Models\User;

class StudentNotificationService
{
    public static function createForUser(User $user, string $type, string $title, ?string $message = null, ?string $url = null, ?string $uniqueKey = null, array $data = []): void
    {
        if (!$user->isStudent()) return;

        $payload = [
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'unique_key' => $uniqueKey,
            'data' => $data ?: null,
        ];

        if ($uniqueKey) {
            StudentNotification::firstOrCreate(['unique_key' => $uniqueKey], $payload);
        } else {
            StudentNotification::create($payload);
        }
    }

    public static function createForGroup(int $groupId, string $type, string $title, ?string $message = null, ?string $url = null, ?string $uniquePrefix = null, array $data = []): void
    {
        User::where('role', 'student')
            ->whereHas('student', fn($q) => $q->where('group_id', $groupId))
            ->get()
            ->each(function (User $user) use ($type, $title, $message, $url, $uniquePrefix, $data) {
                self::createForUser(
                    $user,
                    $type,
                    $title,
                    $message,
                    $url,
                    $uniquePrefix ? $uniquePrefix.':'.$user->id : null,
                    $data
                );
            });
    }

    public static function syncDeadlineReminders(User $user): void
    {
        if (!$user->isStudent() || !$user->student_id || !$user->student) return;

        $student = $user->student;
        $homeworks = Homework::with('subject')
            ->where('group_id', $student->group_id)
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now(), now()->addDays(3)])
            ->whereDoesntHave('submissions', fn($q) => $q->where('student_id', $student->id))
            ->get();

        foreach ($homeworks as $homework) {
            $hours = max(1, now()->diffInHours($homework->due_at));
            self::createForUser(
                $user,
                'deadline',
                'Приближается срок сдачи',
                "{$homework->subject->name}: {$homework->title}. До дедлайна примерно {$hours} ч.",
                route('homeworks.index'),
                'deadline:'.$homework->id.':'.$user->id,
                ['homework_id' => $homework->id]
            );
        }
    }
}
