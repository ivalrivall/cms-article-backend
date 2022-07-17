<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\BranchSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(
            [
                RoleSeeder::class,
                UserSeeder::class,
                MenuSeeder::class,
                BranchSeeder::class,
                CategorySeeder::class,
                ArticleSeeder::class
            ]
        );
    }
}
