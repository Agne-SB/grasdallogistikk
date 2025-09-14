<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        // identifiers & meta
        'external_id', 'external_number', 'title', 'supervisor_name',
        'customer_name', 'address', 'bucket', 'payload',

        // vendor snapshot
        'vendor_status', 'vendor_updated_at', 'vendor_closed_at',

        // local edits / planning
        'goods_note', 'delivery_date', 'supplier_eta',
        'delivered_at', 'staged_location', 'ready_at', 'notified_at',
        'pickup_time_from', 'pickup_time_to', 'appointment_notes',
        'pickup_collected_at', 'requires_appointment', 'mount_started_at', 'mount_completed_at',
        'geo_lat','geo_lng', 'geocoded_at','geocode_provider', 'geocode_attempts','geocode_failed_at',
        'geocode_last_error','closed_at',
    ];

    protected $casts = [
        'payload'              => 'array',
        'vendor_updated_at'    => 'datetime',
        'vendor_closed_at'     => 'datetime',

        'delivery_date'        => 'date',
        'supplier_eta'         => 'date',

        'delivered_at'         => 'datetime',
        'ready_at'             => 'datetime',
        'notified_at'          => 'datetime',
        'pickup_time_from'     => 'datetime',
        'pickup_time_to'       => 'datetime',
        'pickup_collected_at'  => 'datetime',

        'requires_appointment' => 'boolean',

        'mount_started_at'   => 'datetime',
        'mount_completed_at' => 'datetime',

        'geo_lat'=>'float',
        'geo_lng'=>'float',
        'geocoded_at'=>'datetime',
        'geocode_failed_at' => 'datetime',
        
        'closed_at' => 'datetime',
    ];

    public function deviations()
    {
        return $this->hasMany(\App\Models\Deviation::class);
    }
    public function openDeviations()
    {
        return $this->hasMany(\App\Models\Deviation::class)->where('status','open');
    }

    //  “mann” code
    public function getMannAttribute(): ?int
    {
        $title = (string) $this->title;
        if ($title === '') return null;

        if (preg_match('/^[^\d]*\b(\d{1,3})\b/u', $title, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    public function setVendorStatusAttribute($value)
    {
        $this->attributes['vendor_status'] = strtolower($value);
    }

}
