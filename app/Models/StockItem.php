<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    protected $fillable = [
        'title','supplier','delivery_date','delivery_time','status','delivered_at','issue_note',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'delivered_at'  => 'datetime',
    ];

    public function deviations()
    {
        return $this->hasMany(\App\Models\Deviation::class, 'stock_item_id');
    }

}
