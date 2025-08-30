<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deviation extends Model
{
    protected $fillable = [
        'project_id','source','type','note',
        'qty_expected','qty_received','status',
        'opened_at','resolved_at',
    ];
    protected $casts = [
        'opened_at'   => 'datetime',
        'resolved_at' => 'datetime',
    ];
    public function project(){ return $this->belongsTo(Project::class); }
}
