<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->upsert(
            [
                [
                    'name'      => '管理者',
                    'code'      => '0000',
                    'email'     => 'muratekku@example.com',
                    'password'  => Hash::make('muratekku1'),
                    'role'      => 1,
                    'status'    => 2,
                    'available' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name'      => 'SuperAdmin',
                    'code'      => '0001',
                    'email'     => 'admin1120@example.com',
                    'password'  => Hash::make('0001'),
                    'role'      => 1,
                    'status'    => 2,
                    'available' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name'       => 'test',
                    'code'       => 'test',
                    'email'      => 'test@gmail.com',
                    'password'   => Hash::make('test'),
                    'role'       => 2,
                    'status'     => 2,
                    'available'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name'        => 'User2',
                    'code'        => '0003',
                    'email'       => 'user3@gmail.com',
                    'password'    => Hash::make('user12345678'),
                    'role'        => 2,
                    'status'      => 2,
                    'available'   => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name'     => 'User4',
                    'code'     => '0004',
                    'email'    => 'user4@gmail.com',
                    'password' => Hash::make('user12345678'),
                    'role'     => 2,
                    'status'   => 2,
                    'available'   => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name'     => 'User5',
                    'code'     => '0005',
                    'email'    => 'user5@gmail.com',
                    'password' => Hash::make('user12345678'),
                    'role'     => 2,
                    'status'   => 2,
                    'available'   => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]
            ,
            ['code', 'email']
        );
    }
}
