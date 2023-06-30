<?php

use Illuminate\Database\Seeder;
use App\AliquotaPreco;

class FormatacaoDePrecosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$insert = array();

    	foreach (returnEmpresasPrologusView() as $key => $value) {
    		$insert[] = [
    			'estabelecimento' => (string) $key,
    			'prazo_vista' => 0,
    			'prazo_15' => 1.25,
    			'prazo_30' => 2.52,
    			'prazo_45' => 3.80,
    			'prazo_60' => 5.09,
    			'preco_a' => 0,
    			'preco_b' => 2.04,
    			'preco_c' => 4.44,
    			'comissao_a' => 3,
    			'comissao_b' => 4,
    			'comissao_c' => 5,
    			'created_by' => 1,
    			'created_at' => date("Y-m-d H:i:s")
    		];
    	}

        DB::table('margem_prazos')->insert($insert);
    }
}
