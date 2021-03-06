<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Menus\UserMenuSeeder;
use Database\Seeders\Menus\MasterDataSeeder;
use Database\Seeders\Menus\DashboardSeeder;
use Database\Seeders\Menus\FinanceMenuSeeder;
use Database\Seeders\Menus\ReportMenuSeeder;
use Database\Seeders\Menus\ResiMenuSeeder;
use Database\Seeders\Menus\OrderMenuSeeder;
use Database\Seeders\Menus\RoutingMenuSeeder;
use Database\Seeders\Menus\AppContentSeeder;
use DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(
        //     [
        //         DashboardSeeder::class,
        //         MasterDataSeeder::class,
        //         UserMenuSeeder::class,
        //         ShippingMenuSeeder::class,
        //         ResiMenuSeeder::class,
        //         ReportMenuSeeder::class,
        //         FinanceMenuSeeder::class
        //     ]
        // );
        DB::beginTransaction();
        DB::table('submenus')->truncate();
        DB::table('menus')->truncate();
        $this->call(DashboardSeeder::class);
        // $this->call(MasterDataSeeder::class);
        // $this->call(UserMenuSeeder::class);
        // $this->call(OrderMenuSeeder::class);
        // $this->call(RoutingMenuSeeder::class);
        // $this->call(ResiMenuSeeder::class);
        // $this->call(ReportMenuSeeder::class);
        // $this->call(FinanceMenuSeeder::class);
        $this->call(AppContentSeeder::class);
        DB::commit();
    }
}
