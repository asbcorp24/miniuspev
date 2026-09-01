<?php

namespace App\Providers;

use App\Models\JournalRecord;
use App\Observers\JournalRecordObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        JournalRecord::observe(JournalRecordObserver::class);
    }
}
