<?php

namespace App\Http\Controllers;

use Auth;

use App\Faccao;
use App\FornecedorNasajon;
use App\FaccaoTipoDeServico;
use App\CepPais;

use Illuminate\Http\Request;

use App\Http\Requests\FaccaoAdicionarRequest;
use App\Http\Requests\FaccaoEditarRequest;

class FaccaoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Faccao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Faccao');

        return view('programs.faccao.index');
    }

    public function modalAdicionar() {
        return view('programs.faccao.modal.adicionar');
    }

    public function adicionar(FaccaoAdicionarRequest $request) {
        $fields = $request->only('fornecedor');

        $query_fornecedor = FornecedorNasajon::select();
        $query_fornecedor->orderBy('nome', "ASC");
        $query_fornecedor->whereRaw('nome || \' - \' || cnpj_cpf ILIKE \'%'.$fields['fornecedor'].'%\'');
        $result_fornecedor = $query_fornecedor->first();

        $faccaoObj = new Faccao;
        $faccaoObj->cod_fornecedor = $result_fornecedor->cnpj_cpf;
        $faccaoObj->created_by = Auth::id();
        $faccaoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function modalEditar(Request $request){
        $fields = $request->only(['id']);

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query = Faccao::select();
        $query->where('id', '=', $id);
        $query->with(['fornecedor']);

        $result = $query->get();

        foreach ($result as $faccao) {            
            $dados = [
                'id' => encrypt($faccao->id),
                'faccao' => $faccao->fornecedor->nome.' - '.$faccao->fornecedor->cnpj_cpf
            ];
        }
        return view('programs.faccao.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(FaccaoEditarRequest $request){
        $fields = $request->only('id','fornecedor');
 
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query_fornecedor = FornecedorNasajon::select();
        $query_fornecedor->orderBy('nome', "ASC");
        $query_fornecedor->whereRaw('nome || \' - \' || cnpj_cpf ILIKE \'%'.$fields['fornecedor'].'%\'');
        $result_fornecedor = $query_fornecedor->first();

        $faccaoObj = Faccao::find($id);
        $faccaoObj->cod_fornecedor = $result_fornecedor->cnpj_cpf;
        $faccaoObj->updated_by = Auth::id();
        $faccaoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function modalDeletar(Request $request){
        $id = $request->only(['id'])['id'];
        
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $query = Faccao::select();
        $query->where('id', '=', $id);
        $query->with(['fornecedor']);
        if(is_null($query)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        $result = $query->get();
        foreach ($result as $faccao) {            
            $dados = [
                'id' => encrypt($faccao->id),
                'faccao' => $faccao->fornecedor->nome.' - '.$faccao->fornecedor->cnpj_cpf
            ];
        }

        return view('programs.faccao.modal.deletar')->with(['dados' => $dados]);
    }

    public function deletar(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        
        
        $faccaoObj = Faccao::find($id);
        $faccaoObj->deleted_by = Auth::id();
        $faccaoObj->save();
        $faccaoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(Request $request){
        $fields = $request->only('faccao');

        $retorno = [];

        $query_faccao = Faccao::select();
        $query_faccao->with(['fornecedor' => function($query) use ($fields){
            if(!empty($fields['faccao'])){
                $query->whereRaw('nome || \' - \' || cnpj_cpf ILIKE \'%'.$fields['faccao'].'%\'');
            }
        }]);
        $query_faccao->distinct('cod_fornecedor');
        $result_faccao = $query_faccao->get();

        foreach ($result_faccao as $faccao) {
            if(!empty($faccao->fornecedor)){
                $retorno [] =[
                    'id' => encrypt($faccao->id),
                    'cnpj' => $faccao->fornecedor->cnpj_cpf,
                    'descricao' => $faccao->fornecedor->nome
                ];
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function autoComplete(Request $request){
        $descricao = $request->only('term');
        $return = [];

        $query_faccao = Faccao::select();
        $query_faccao->with(['fornecedor'=>function($query) use($descricao){
            if(!empty($descricao['term'])){
                $query->whereRaw('nome || \' - \' || cnpj_cpf ILIKE \'%'.$descricao['term'].'%\'');
            }
        }]);

        $result_faccao = $query_faccao->get();

        foreach ($result_faccao as $value){
            if($value->fornecedor){
                $return[] = [
                    'label' => trim($value->fornecedor->nome). ' - '. $value->fornecedor->cnpj_cpf,
                    'cpf_cnpj' => $value->fornecedor->cnpj_cpf,
                    'nome' => trim($value->fornecedor->nome),
                    'value' => $value->id
                ];
            }
        }

        return response()->json($return);
    }

    public function autoCompleteServico(Request $request){
        $descricao = $request->only('term', 'codigo_servico');

        $return = [];
        $query = FaccaoTipoDeServico::select();
        $query->with(['faccao' => function($query) use($descricao){
            $query->with(['fornecedor'=>function($query) use($descricao){
                if(!empty($descricao['term'])){
                    $query->whereRaw('nome || \' - \' || cnpj_cpf ILIKE \'%'.$descricao['term'].'%\'');
                }
            }]);
        }]);
        $query->where('codigo_tipo_de_servico', $descricao['codigo_servico']);
        $result = $query->get();
        foreach ($result as $value){
            if(!empty($value->faccao->fornecedor)){
                $return[] = [
                    'label' => trim($value->faccao->fornecedor->nome). ' - '. $value->faccao->fornecedor->cnpj_cpf,
                    'cpf_cnpj' => $value->faccao->fornecedor->cnpj_cpf,
                    'nome' => trim($value->faccao->fornecedor->nome),
                    'value' => $value->faccao->id
                ];
            }
        }

        return response()->json($return);
    }

    public function modalBuscar(Request $request){
        $tipo_de_servico = $request->only('tipo_de_servico');
        if(empty($tipo_de_servico)){
            $tipo_de_servico = '';
        }else{
            $tipo_de_servico = $tipo_de_servico['tipo_de_servico'];
        }

        return view('programs.faccao.modal.buscar')->with(['tipo_de_servico' => $tipo_de_servico]);
    }

    public function filterBuscar(Request $request){
        $fields = $request->only('codigo_cad','razao','fantasia','cnpj_cpf', 'tipo_de_servico');

        $faccaos = [];
        
        if(empty($fields['tipo_de_servico'])){
            $query_faccao = Faccao::select();
            $query_faccao->with(['fornecedor'=> function($query) use ($fields){
                $query->select();
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
            }]);
            $result_faccao = $query_faccao->get();
    
            foreach($result_faccao as $value){
                if($value->fornecedor){
                    $pais = CepPais::find($value->fornecedor->pais??1058);
    
                    $faccaos [] = [
                        'codigo' => $value->id,
                        'nome' => $value->fornecedor->nome,
                        'nomefantasia' => $value->fornecedor->nomefantasia,
                        'cnpj_cpf' => empty($value->fornecedor->cnpj_cpf)?'':$value->fornecedor->cnpj_cpf,
                        'municipio' => $value->fornecedor->municipio,
                        'estado' => $value->fornecedor->uf,
                        'pais' => $pais->nome_pt
                    ];
                }
            }
        }else{
            $query = FaccaoTipoDeServico::query()
            ->with(['tipo_de_servico'])
            ->with(['faccao' => function($query) use($fields){
                $query->with(['fornecedor'=> function($query) use ($fields){
                    $query->select();
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
                }]);
            }])
            ->where('codigo_tipo_de_servico', $fields['tipo_de_servico']);
            $result = $query->get();

            foreach($result as $value){
                if($value->faccao->fornecedor){
                    $pais = CepPais::find($value->fornecedor->pais??1058);
    
                    $faccaos [] = [
                        'codigo' => $value->faccao->id,
                        'nome' => $value->faccao->fornecedor->nome,
                        'nomefantasia' => $value->faccao->fornecedor->nomefantasia,
                        'cnpj_cpf' => empty($value->faccao->fornecedor->cnpj_cpf)?'':$value->faccao->fornecedor->cnpj_cpf,
                        'municipio' => $value->faccao->fornecedor->municipio,
                        'estado' => $value->faccao->fornecedor->uf,
                        'pais' => $pais->nome_pt
                    ];
                }
            }
        }
        

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $faccaos
        ];

        return response()->json($response);
    }
}
