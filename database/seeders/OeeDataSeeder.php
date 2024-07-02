<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class OeeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('oee_data')->insert([
            'id' => 1,
            'line' => 1,
            'nama_line' => 'Line 1',
            'tgl' => '2024-07-02',
            'shift' => 1,
            'item' => 'Item A',
            'seq' => 1,
            'timestamp' => now(),
        ]);
    }
}