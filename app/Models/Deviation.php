<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deviation extends Model
{
    protected $fillable = [
        'project_id','stock_item_id','source','type','note',
        'qty_expected','qty_received','status',
        'opened_at','resolved_at', 'resolution_note','resolved_by',
    ];
    protected $casts = [
        'opened_at'   => 'datetime',
        'resolved_at' => 'datetime',
    ];
    public function project(){ return $this->belongsTo(Project::class); }

    public function stockItem()
    {
        return $this->belongsTo(\App\Models\StockItem::class, 'stock_item_id');
    }

    // Unified title for tables/views
    public function getSubjectTitleAttribute(): string
    {
        return $this->project->title
            ?? $this->stockItem->title
            ?? '—';
    }

}
