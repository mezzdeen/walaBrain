<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The application seeds nothing of its own: every table belongs to a
     * module, so this hands off to each module's `ModuleSeeder`, found the same
     * way `bootstrap/providers.php` finds each module's service provider. A
     * module without one is simply skipped.
     */
    public function run(): void
    {
        foreach ($this->moduleSeeders() as $seeder) {
            $this->call($seeder);
        }
    }

    /**
     * A file named `ModuleSeeder.php` is not proof of a seeder, so each
     * candidate is checked before it is called: a module whose file does not
     * hold the class it advertises is skipped rather than failing the run.
     *
     * @return list<class-string<Seeder>>
     */
    protected function moduleSeeders(): array
    {
        $seeders = [];

        foreach (glob(app_path('Modules/*/Database/Seeders/ModuleSeeder.php')) ?: [] as $path) {
            $seeder = 'App\\Modules\\'.basename(dirname($path, 3)).'\\Database\\Seeders\\ModuleSeeder';

            if (is_subclass_of($seeder, Seeder::class)) {
                $seeders[] = $seeder;
            }
        }

        return $seeders;
    }
}
