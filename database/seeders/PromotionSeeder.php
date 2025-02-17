<?php

namespace Database\Seeders;

use App\Models\BasicInformation;
use App\Models\Promotion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Promotion::factory()->count(30)->create();
    }
}
