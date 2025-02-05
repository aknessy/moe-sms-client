<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Resource;
class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $avatars_resource = [
            [
                'type' => 'image',
                'name' => 'avatar-1',
                'url' => 'resources/images/avatars/avatar-1.png'
            ],
            [
                'type' => 'image',
                'name' => 'avatar-2',
                'url' => 'resources/images/avatars/avatar-2.png'
            ],
            [
                'type' => 'image',
                'name' => 'avatar-3',
                'url' => 'resources/images/avatars/avatar-3.png'
            ],
            [
                'type' => 'image',
                'name' => 'avatar-4',
                'url' => 'resources/images/avatars/avatar-4.png'
            ],
            [
                'type' => 'image',
                'name' => 'avatar-5',
                'url' => 'resources/images/avatars/avatar-5.png'
            ],
            [
                'type' => 'image',
                'name' => 'avatar-6',
                'url' => 'resources/images/avatars/avatar-6.png'
            ],
            [
                'type' => 'image',
                'name' => 'avatar-7',
                'url' => 'resources/images/avatars/avatar-7.png'
            ]
        ];

        foreach ($avatars_resource as $res){
            Resource::factory()->create([
                'type' => $res['type'],
                'name' => $res['name'],
                'url' => $res['url']
            ]);
        }
    }
}
