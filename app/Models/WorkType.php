<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkType extends Model
{
    protected $fillable = ['name','code','default_weight','active'];
    protected $casts = ['default_weight'=>'float','active'=>'boolean'];
}
