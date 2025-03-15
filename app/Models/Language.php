<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = ['name'];

    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'job_language');
    }
}
