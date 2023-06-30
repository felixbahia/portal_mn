<?php

use Illuminate\Database\Seeder;
use App\CepEstado;
use App\AliquotaPreco;

class AliquotasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$estados = CepEstado::select('uf')->get()->toArray();
   		$query_insert = [];

    	foreach ($estados as $value) {

    		$query_insert[] = [
    			'origem' => 'SP',
    			'estado' => $value['uf'],
    			'aliquota' => 7,
                'internacional' => 0,
    			'created_by' => 1
    		];

    		$query_insert[] = [
    			'origem' => 'TO',
    			'estado' => $value['uf'],
    			'aliquota' => 7,
                'internacional' => 0,
    			'created_by' => 1
    		];

    		$query_insert[] = [
    			'origem' => 'RO',
    			'estado' => $value['uf'],
    			'aliquota' => 4,
                'internacional' => 1,
    			'created_by' => 1
    		];

            $query_insert[] = [
                'origem' => 'TO',
                'estado' => $value['uf'],
                'aliquota' => 4,
                'internacional' => 1,
                'created_by' => 1
            ];
    	}

        AliquotaPreco::insert($query_insert);

        AliquotaPreco::where("origem", "SP")
            ->whereIn('estado', ['MG', 'PR', 'RS', 'RJ', 'SC'])
            ->where('internacional', '=', 0)
            ->update(["aliquota" => 12]);

        AliquotaPreco::where("origem", "SP")
            ->where('estado', "=", 'SP')
            ->where('internacional', '=', 0)
            ->update(["aliquota" => 18]);

        AliquotaPreco::whereIn('origem', ['TO', 'RO'])
            ->where("estado", '=', 'RJ')
            ->where('internacional', '=', 1)
            ->update(["aliquota" => 19]);
    }
}
