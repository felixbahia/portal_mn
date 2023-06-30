<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'mn tecidos',
            'username' => 'mntecidos',
            'email' => 'mntecidos@mntecidos.com.br',
            'password' => Hash::make('mntecidos'),
            'setor' => 'administrator'
        ]);
    }
}
