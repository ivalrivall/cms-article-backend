<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();
        DB::table('roles')->truncate();
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'ranking' => 1,
                'privilleges' => json_encode(["menu_1","menu_2","menu_3","menu_4","menu_5","menu_6","menu_7","menu_8","submenu_1","submenu_2","submenu_3","submenu_4","submenu_5","submenu_6","submenu_7","submenu_8","submenu_9","submenu_10","submenu_11","submenu_12","submenu_13","submenu_14","submenu_15","submenu_16","submenu_17","submenu_18","submenu_19","submenu_20"])
            ]
        ];
        Role::insert($roles);
        DB::commit();
    }
}
