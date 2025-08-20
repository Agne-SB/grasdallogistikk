<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('projects')->insert([
            [
                'title' => 'Test Prosjekt A',
                'customer_name' => 'Kunde AS',
                'address' => 'Storgata 1, Oslo',
                'status' => 'Planlagt',
                'updated_at_from_api' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'title' => 'Test Prosjekt B',
                'customer_name' => 'Firma AB',
                'address' => 'Parkveien 2, Bergen',
                'status' => 'Pågår',
                'updated_at_from_api' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }
}

