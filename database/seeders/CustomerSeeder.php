<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $arr = [
            [
                'name' => '丸西組',
                'person' => '木葉',
            ],
            [
                'name' => '前田建設',
            ],
            [
                'name' => '北浜建設',
            ],
            [
                'name' => '大林組',
            ],
            [
                'name' => '宮前',
            ],
            [
                'name' => '島岡',
                'person' => '中瀬',
            ],
            [
                'name' => '栗山組',
            ],
            [
                'name' => '竹中工務店',
            ],
        ];
        foreach($arr as $val) {
            Customer::create([
                'customer_name' => $val['name'] ?? null,
                'customer_person' => $val['person'] ?? null,
            ]);
        }
    }
}
