<?php

namespace Database\Seeders\Menus;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\Submenu;
use Carbon\Carbon;

class AppContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menu = new Menu;
        $menu->name = 'App Content';
        $menu->slug = 'app-content';
        $menu->icon = 'app-content';
        $menu->save();
        $submenu = [
            [
                'menu_id' => $menu->id,
                'name' => 'Artikel',
                'slug' => 'article',
            ],
        ];
        Submenu::insert($submenu);
    }
}
