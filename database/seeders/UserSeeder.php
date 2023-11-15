<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'Admin123',
            'email' => 'admin@mail.com',
            'password' => bcrypt('12345678'),
        ]);

        $admin->assignRole('admin');

        $visitor = User::create([
            'name' => 'Visitor',
            'username' => 'Visitor123',
            'email' => 'visitor@mail.com',
            'password' => bcrypt('12345678'),
        ]);

        $visitor->assignRole('visitor');

        for ($i = 1; $i <= 13; $i++) {
            $user = User::create([
                'name' => 'User' . $i,
                'username' => 'User' . $i,
                'email' => 'user' . $i . '@mail.com',
                'password' => bcrypt('12345678'),
            ]);

            $user->assignRole('member');
        }
    }
}
