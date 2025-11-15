<?php

namespace Tests\Support;

use App\Settings\RfmSettingsContract;

class FakeRfmSettings implements RfmSettingsContract
{
    public function __construct(
        protected int $bins = 5,
        protected int $segments = 5,
        protected int $timeframeDays = 120,
    ) {}

    public function getRfmBins(): int
    {
        return $this->bins;
    }

    public function getRfmSegments(): int
    {
        return $this->segments;
    }

    public function getRfmTimeframeDays(): int
    {
        return $this->timeframeDays;
    }
}
