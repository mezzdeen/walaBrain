<?php

namespace App\Modules\Flows\Database\Seeders;

use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Seed everything the module owns.
     */
    public function run(): void
    {
        $this->call(PilotSeeder::class);
    }
}
