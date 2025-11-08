<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'customers';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'created_at',
        'email',
        'phone',
        'country',
        'state',
        'city',
        'region',
        'birthday',
        'gender',
        'segment',
        'labels',
        'channel',
        'meta',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'birthday' => 'datetime',
        'labels' => 'array',
        'meta' => 'array',
    ];

    public function getRouteKeyName()
    {
        return 'id';
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }

    // public function rfmMetric(): HasOne
    // {
    //     return $this->hasOne(CustomerRfmMetric::class, 'customer_id', 'id');
    // }

    public function getRecencyAttribute(): ?int
    {
        $last = $this->orders()->max('created_at');
        if (!$last) {
            return null;
        }
        return now()->diffInDays($last);
    }

    public function getFrequencyAttribute(): int
    {
        return (int) $this->orders()->count();
    }

    public function getMonetaryAttribute(): float
    {
        return (float) $this->orders()->sum('total_amount');
    }
}