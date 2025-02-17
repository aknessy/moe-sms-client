<?php

namespace Database\Seeders;

use App\Models\EduProgress;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EduProgressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EduProgress::factory()->count(30)->create();
    }
}
