<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobAttributeValue;
use App\Models\Language;
use App\Models\Location;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Languages
        $php = Language::create(['name' => 'PHP']);
        $js = Language::create(['name' => 'JavaScript']);

        // Locations
        $ny = Location::create(['city' => 'New York', 'state' => 'NY', 'country' => 'USA']);
        $remote = Location::create(['city' => 'Remote', 'country' => 'Global']);

        // Categories
        $dev = Category::create(['name' => 'Development']);

        // Attributes
        $exp = Attribute::create(['name' => 'years_experience', 'type' => 'number']);

        // Job
        $job = Job::create([
            'title' => 'Senior PHP Developer',
            'description' => 'A senior role requiring PHP expertise.',
            'company_name' => 'Tech Corp',
            'salary_min' => 80000,
            'salary_max' => 100000,
            'is_remote' => true,
            'job_type' => 'full-time',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Attach relationships
        $job->languages()->attach([$php->id, $js->id]);
        $job->locations()->attach([$ny->id, $remote->id]);
        $job->categories()->attach($dev->id);

        // Add EAV attribute
        JobAttributeValue::create([
            'job_id' => $job->id,
            'attribute_id' => $exp->id,
            'value' => '5',
        ]);
    }
}
