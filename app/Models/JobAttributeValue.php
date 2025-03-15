<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobAttributeValue extends Model
{
    protected $fillable = ['job_id', 'attribute_id', 'value'];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
