<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();
        DB::table('categories')->truncate();
        DB::table('categories')->insert(
        [
            [
                'name' => 'Technology',
                'slug' => 'tech',
            ],
            [
                'name' => 'Health',
                'slug' => 'health',
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
            ]
        ]);
        DB::commit();
    }
}
