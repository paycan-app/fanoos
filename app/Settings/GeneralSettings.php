<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;
    public string $contact_email;
    public bool $maintenance_mode;
    public bool $rfm_enable;
    public int $rfm_bins;

    public static function group(): string
    {
        return 'general';
    }
}