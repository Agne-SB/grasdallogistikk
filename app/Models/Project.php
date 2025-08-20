<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'external_id','title','customer_name','address','status','updated_at_from_api'
    ];
}

