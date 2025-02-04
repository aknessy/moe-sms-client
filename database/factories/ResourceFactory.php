<?php

namespace Database\Factories;

use App\Model\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resources>
 */
class ResourceFactory extends Factory
{
    protected $resource = Resource::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
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
            Resource\::factory()->create([
                'type' => $res['type'],
                'name' => $res['name'],
                'url' => $res['url']
            ]);
        }
    }
}
