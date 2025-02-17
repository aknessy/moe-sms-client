<?php

namespace Database\Seeders;

use App\Models\Leave;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeaveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Leave::factory()->count(30)->create();
    }
}
