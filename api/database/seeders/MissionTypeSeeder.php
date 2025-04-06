<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MissionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['id' => 1, 'name' => 'Missions'],
        ];

        foreach ($types as $type) {
            \App\Models\MissionType::updateOrCreate(['id' => $type['id']], $type);
        }
    }
}
