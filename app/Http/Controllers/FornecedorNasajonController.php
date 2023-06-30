<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\FornecedorNasajon;
use App\CepPais;
use Auth;

use App\Http\Requests\FornecedorNasajonBuscaRequest;

class FornecedorNasajonController extends Controller
{

    public function filterBuscar(FornecedorNasajonBuscaRequest $request){
        $fields = $request->only('codigo_cad','razao','fantasia','cnpj_cpf');

        $query = FornecedorNasajon::select('codigo','nome','nomefantasia','cnpj_cpf','municipio','uf','pais');
        if(!empty($fields['codigo_cad'])){
            $query->where('codigo', 'ilike', '%'.$fields['codigo_cad'].'%');
        }
        if(!empty($fields['razao'])){
            $query->where('nome', 'ilike', '%'.$fields['razao'].'%');
        }
        if(!empty($fields['fantasia'])){
            $query->where('nomefantasia', 'ilike', '%'.$fields['fantasia'].'%');
        }
        if(!empty($fields['cnpj_cpf'])){
            $query->where('cnpj_cpf', 'ilike', '%'.$fields['cnpj_cpf'].'%');
        }
        $query->distinct('nome');
        $result = $query->get();
        
        $fornecedores = [];
        foreach($result as $value){
            
            $pais = CepPais::find($value->pais??1058);

            $fornecedores [] = [
                'codigo' => $value->codigo,
                'nome' => $value->nome,
                'nomefantasia' => $value->nomefantasia,
                'cnpj_cpf' => empty($value->cnpj_cpf)?'':$value->cnpj_cpf,
                'municipio' => $value->municipio,
                'estado' => $value->uf,
                'pais' => $pais->nome_pt
            ];
        }
        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $fornecedores
        ];

        return response()->json($response);
    }

    public function indexBusca(Request $request){
        return view('programs.fornecedor.busca.index');
    }

    public function autoComplete(Request $request){
        $descricao = $request->only('term');
        $return = [];
        $query = FornecedorNasajon::select('codigo','nome', 'cnpj_cpf')
            ->limit("15")
            ->distinct()
            ->orderBy('nome', "ASC")
            ->whereRaw('TRIM(CONCAT(nome, \' - \', cnpj_cpf)) ILIKE \'%'.trim($descricao['term']).'%\'')
            ->get()
            ->toArray();
        foreach ($query as $value){
            $value = (array) $value;
            $return[] = [
                'label' => trim($value['nome']). ' - '. $value['cnpj_cpf'],
                'cpf_cnpj' => $value['cnpj_cpf'],
                'nome' => trim($value['nome']),
                'value' => $value['codigo']
            ];
        }

        return response()->json($return);
    }
}
