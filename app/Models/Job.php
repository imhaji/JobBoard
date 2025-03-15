<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $fillable = [
        'title', 'description', 'company_name', 'salary_min', 'salary_max',
        'is_remote', 'job_type', 'status', 'published_at',
    ];

    protected $casts = [
        'is_remote' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function languages()
    {
        return $this->belongsToMany(Language::class, 'job_language');
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'job_location');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'job_category');
    }

    public function attributeValues()
    {
        return $this->hasMany(JobAttributeValue::class);
    }
}
