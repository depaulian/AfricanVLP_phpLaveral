<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BlogCategoriesSeeder::class,
            OpportunityCategoriesSeeder::class,
            SuperAdminSeeder::class,
            ContentTagsSeeder::class,
            PageSeeder::class,
            // CakePHPDataSeeder::class, // Uncomment to seed CakePHP data
        ]);
    }
}
