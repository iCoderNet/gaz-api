<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'tg_id'    => '123456789',
            'username' => 'admin',
            'phone'    => '331815253',
            'password' => bcrypt('admin123'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);
    }
}
