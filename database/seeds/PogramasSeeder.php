<?php

use Illuminate\Database\Seeder;

class PogramasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('modulos')->insert([
            'nome' => 'Sistema',
            'icon' => 'sistema-icon.png',
            'url'  => 'sistema'
        ]);
        DB::table('modulos')->insert([
            'nome' => 'Financeiro',
            'icon' => 'financeiro-icon.png',
            'url'  => 'financeiro'
        ]);
        DB::table('modulos')->insert([
            'nome' => 'Produção',
            'icon' => 'producao-icon.png',
            'url'  => 'producao'
        ]);
        DB::table('modulos')->insert([
            'nome' => 'Comercial',
            'icon' => 'comercial-icon.png',
            'url'  => 'comercial'
        ]);
        DB::table('modulos')->insert([
            'nome' => 'Estoque',
            'icon' => 'estoque-icon.png',
            'url'  => 'estoque'
        ]);
    }
}
