<?php

namespace App\Providers;

use App\Models\File\File;
use App\Models\File\FileLog;
use App\Models\Group\Group;
use App\Observers\FileObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // Group::preventLazyLoading(!app()->isProduction());
        // File::preventLazyLoading(!app()->isProduction());
        // FileLog::preventLazyLoading(!app()->isProduction());
    }
}
