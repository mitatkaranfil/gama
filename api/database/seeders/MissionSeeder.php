<?php

namespace Database\Seeders;

use App\Models\Mission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $missions = [
            ['name' => 'Lily Pad Leap', 'mission_type_id' => 1, 'image' => '/images/missions/1.png'],
            ['name' => 'Fly Catch Frenzy', 'mission_type_id' => 1, 'image' => '/images/missions/2.png'],
            ['name' => 'Swamp Sprint', 'mission_type_id' => 1, 'image' => '/images/missions/3.png'],
            ['name' => 'Croak Quest', 'mission_type_id' => 1, 'image' => '/images/missions/4.png'],
        ];

        foreach ($missions as $key => $mission) {
            \App\Models\Mission::updateOrCreate(['id' => $key + 1], ['id' => $key + 1, ...$mission]);
        }

        Mission::all()->each(function ($mission) {
            $mission->levels()->createMany([
                ['level' => 1, 'cost' => 100, 'production_per_hour' => 10],
                ['level' => 2, 'cost' => 200, 'production_per_hour' => 20],
                ['level' => 3, 'cost' => 300, 'production_per_hour' => 30],
                ['level' => 4, 'cost' => 400, 'production_per_hour' => 40],
                ['level' => 5, 'cost' => 500, 'production_per_hour' => 50],
            ]);
        });
    }
}
