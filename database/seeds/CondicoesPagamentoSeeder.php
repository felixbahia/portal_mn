<?php

use Illuminate\Database\Seeder;
use App\Vencimentos;
use App\CondicoesPagamentoWeb;

class CondicoesPagamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $vencimentos = Vencimentos::get();

        foreach ($vencimentos as $value) {

            if(strlen($value->PRAZO) > 0){

    			CondicoesPagamentoWeb::create([
                    "descricao" => utf8_encode($value->DESCRICAO),
                    "media" => utf8_encode($value->PRAZO),
                    "id_web" => utf8_encode($value->CODVCT),
                    "created_by" => '1',
                    'liberado_representante' => ($value->LIBERADO_WEB == 'S')?true:false
            	]);
                
            }


        }

    }

}