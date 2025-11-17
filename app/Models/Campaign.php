<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

class Campaign extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'channel',
        'status',
        'subject',
        'content',
        'filter_type',
        'filter_config',
        'scheduled_at',
        'sent_at',
        'created_by',
        'total_recipients',
        'total_sent',
        'total_failed',
    ];

    protected function casts(): array
    {
        return [
            'filter_config' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function events(): HasManyThrough
    {
        return $this->hasManyThrough(CampaignEvent::class, CampaignSend::class);
    }

    public function getOpenRateAttribute(): float
    {
        if ($this->total_sent === 0) {
            return 0.0;
        }

        $opens = $this->events()->where('event_type', 'opened')->distinct('campaign_send_id')->count();

        return round(($opens / $this->total_sent) * 100, 2);
    }

    public function getClickRateAttribute(): float
    {
        if ($this->total_sent === 0) {
            return 0.0;
        }

        $clicks = $this->events()->where('event_type', 'clicked')->distinct('campaign_send_id')->count();

        return round(($clicks / $this->total_sent) * 100, 2);
    }

    public function getDeliveryRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0.0;
        }

        return round(($this->total_sent / $this->total_recipients) * 100, 2);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function getFilteredCustomers(): Collection
    {
        $query = Customer::query();

        return match ($this->filter_type) {
            'all' => $query->get(),
            'segment' => $query->whereIn('segment', $this->filter_config['segments'] ?? [])->get(),
            'individual' => $query->whereIn('id', $this->filter_config['customer_ids'] ?? [])->get(),
            'custom' => $this->applyCustomFilters($query)->get(),
            default => collect(),
        };
    }

    protected function applyCustomFilters($query)
    {
        $filters = $this->filter_config ?? [];

        if (isset($filters['countries']) && ! empty($filters['countries'])) {
            $query->whereIn('country', $filters['countries']);
        }

        if (isset($filters['segments']) && ! empty($filters['segments'])) {
            $query->whereIn('segment', $filters['segments']);
        }

        if (isset($filters['labels']) && ! empty($filters['labels'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['labels'] as $label) {
                    $q->orWhereJsonContains('labels', $label);
                }
            });
        }

        if (isset($filters['channels']) && ! empty($filters['channels'])) {
            $query->whereIn('channel', $filters['channels']);
        }

        if (isset($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        if (isset($filters['created_before'])) {
            $query->where('created_at', '<=', $filters['created_before']);
        }

        return $query;
    }
}
