<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['city', 'state', 'country'];

    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'job_location');
    }
}
