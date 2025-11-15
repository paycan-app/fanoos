<?php

namespace App\Providers;

use App\Settings\GeneralSettings;
use App\Settings\RfmSettingsContract;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->runningUnitTests()) {
            config(['settings.migrations_paths' => []]);
        }

        $this->app->bind(RfmSettingsContract::class, GeneralSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
