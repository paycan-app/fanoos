<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;
    public string $contact_email;
    public bool $maintenance_mode;

    // Removed: public bool $rfm_enable;

    public int $rfm_bins;
    public int $rfm_segments;
    public int $rfm_timeframe_days;

    public static function group(): string
    {
        return 'general';
    }
}
