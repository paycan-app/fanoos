<?php

namespace App\Settings;

interface RfmSettingsContract
{
    public function getRfmBins(): int;

    public function getRfmSegments(): int;

    public function getRfmTimeframeDays(): int;
}
