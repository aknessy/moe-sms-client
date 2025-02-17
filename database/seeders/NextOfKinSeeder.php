<?php

namespace Database\Seeders;

use App\Models\NextOfKin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NextOfKinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NextOfKin::factory()->count(10)->create();
    }
}
