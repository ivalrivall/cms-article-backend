<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();
        DB::table('branches')->truncate();
        DB::table('branches')->insert([
            [
                'name' => 'Cabang Jakarta',
                'slug' => 'jakarta',
                'province' => 'DKI JAKARTA',
                'city' => 'KOTA JAKARTA PUSAT',
                'district' => 'GAMBIR',
                'village' => 'PETOJO SELATAN',
                'postal_code' => '12345',
                'street' => 'jl. bunga cempaka putih No.12b, RT.008/RW.09',
            ]
        ]);
        DB::commit();
    }
}
