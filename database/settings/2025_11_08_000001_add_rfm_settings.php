<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        // Removed: $this->migrator->add('general.rfm_enable', true);
        $this->migrator->add('general.rfm_bins', 5);
    }
};