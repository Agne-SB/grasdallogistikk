<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'external_id','title','customer_name','address',
        'vendor_status','vendor_updated_at','vendor_closed_at','payload',
        'bucket','goods_note','delivery_date',
    ];

    protected $casts = [
        'payload'           => 'array',
        'vendor_updated_at' => 'datetime',
        'vendor_closed_at'  => 'datetime',
        'delivery_date'     => 'date',
    ];
}




