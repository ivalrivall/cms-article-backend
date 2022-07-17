<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create();
        DB::beginTransaction();
        DB::table('articles')->truncate();
        DB::table('articles')->insert(
        [
            [
                'title' => $faker->name(),
                'description' => $faker->paragraphs(3, true),
                'image' => $faker->imageUrl,
                'url' => $faker->url,
                'category_id' => $faker->numberBetween(1, 3),
            ],
            [
                'title' => $faker->name(),
                'description' => $faker->paragraphs(3, true),
                'image' => $faker->imageUrl,
                'url' => $faker->url,
                'category_id' => $faker->numberBetween(1, 3),
            ],
            [
                'title' => $faker->name(),
                'description' => $faker->paragraphs(3, true),
                'image' => $faker->imageUrl,
                'url' => $faker->url,
                'category_id' => $faker->numberBetween(1, 3),
            ],
            [
                'title' => $faker->name(),
                'description' => $faker->paragraphs(3, true),
                'image' => $faker->imageUrl,
                'url' => $faker->url,
                'category_id' => $faker->numberBetween(1, 3),
            ],
        ]);
        DB::commit();
    }
}
