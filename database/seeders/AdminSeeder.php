<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        Admin::truncate();

        Schema::enableForeignKeyConstraints();

        Admin::create([
            'role_id' => 1,
            'name' => 'Admin One',
            'first_name' => 'Admin',
            'last_name' => 'One',
            'mobile' => '9999999999',
            'email' => 'vkdeveloper900@gmail.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
    }
}
