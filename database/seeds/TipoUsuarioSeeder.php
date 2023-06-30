<?php

use Illuminate\Database\Seeder;

class TipoUsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tipo_usuarios')->insert([
            'nome' => 'Interno'
        ]);
        DB::table('tipo_usuarios')->insert([
            'nome' => 'Representante'
        ]);
        DB::table('tipo_usuarios')->insert([
            'nome' => 'Supervisor'
        ]);
        DB::table('tipo_usuarios')->insert([
            'nome' => 'Gerente'
        ]);
        DB::table('tipo_usuarios')->insert([
            'nome' => 'Diretor'
        ]);
    }
}
