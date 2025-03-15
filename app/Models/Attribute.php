<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = ['name', 'type', 'options'];

    protected $casts = [
        'options' => 'array',
    ];

    public function jobAttributeValues()
    {
        return $this->hasMany(JobAttributeValue::class);
    }
}
