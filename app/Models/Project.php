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
        'pickup_collected_at', 'requires_appointment',
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
    ];

    public function deviations()
    {
        return $this->hasMany(\App\Models\Deviation::class);
    }
    public function openDeviations()
    {
        return $this->hasMany(\App\Models\Deviation::class)->where('status','open');
    }

}
