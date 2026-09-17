<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds a verified admin with one election and three candidates for local development.
     */
    public function run(): void
    {
        $admin = Admin::factory()->create([
            'email' => 'admin@school.edu',
            'password' => 'password',
        ]);

        $election = Election::factory()->for($admin)->create([
            'title' => 'Student Council Election',
            'description' => 'Annual student council election',
            'allowed_domains' => ['school.edu'],
        ]);

        Candidate::factory()->count(3)->for($election)->create();
    }
}
