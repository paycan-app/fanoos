<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'campaign_send_id',
        'event_type',
        'event_data',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_data' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function campaignSend(): BelongsTo
    {
        return $this->belongsTo(CampaignSend::class);
    }
}
