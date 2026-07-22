<?php

namespace Database\Seeders;

use App\Providers\ModuleServiceProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The application seeds nothing of its own: every table belongs to a
     * module, so this hands off to each module's `ModuleSeeder`, over the same
     * list of modules that `ModuleServiceProvider` registers providers from.
     *
     * A seeder is optional where a provider is not, so a module without one is
     * skipped rather than raised: plenty of modules have nothing to seed.
     */
    public function run(): void
    {
        foreach (ModuleServiceProvider::names() as $module) {
            /** @var class-string<Seeder> $seeder */
            $seeder = 'App\\Modules\\'.$module.'\\Database\\Seeders\\ModuleSeeder';

            if (class_exists($seeder)) {
                $this->call($seeder);
            }
        }
    }
}
