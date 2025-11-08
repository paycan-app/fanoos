<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.rfm_segments', 5);
        $this->migrator->add('general.rfm_timeframe_days', 365);
    }
};
