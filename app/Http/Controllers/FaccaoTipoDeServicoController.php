<?php

namespace App\Http\Controllers;

use Auth;

use App\Faccao;
use App\FaccaoTipoDeServico;
use App\UnidadeMedidaNasajon;
use App\TipoDeServico;

use Illuminate\Http\Request;

use App\Http\Controllers\FaccaoController;

use App\Http\Requests\FaccaoTipoDeServicoAdicionarRequest;
use App\Http\Requests\FaccaoTipoDeServicoEditarRequest;
use App\Http\Requests\GetTipoDeServicoRequest;

class FaccaoTipoDeServicoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\FaccaoTipoDeServico") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\FaccaoTipoDeServico');

        $tipo_de_servicos = $this->tipo_de_servicos_todos();
        $unidades = $this->unidades();

        return view('programs.faccao_x_tipo_de_servico.index')->with(['unidades' => $unidades,'tipo_de_servicos' => $tipo_de_servicos]);
    }

    public function modalAdicionar() {
        $unidades = $this->unidades();
        $tipo_de_servicos = $this->tipo_de_servicos();

        return view('programs.faccao_x_tipo_de_servico.modal.adicionar')->with(['unidades' => $unidades, 'tipo_de_servicos' => $tipo_de_servicos]);
    }

    public function adicionar(FaccaoTipoDeServicoAdicionarRequest $request) {
        $fields = $request->only('codigo_faccao', 'tipo_de_servico', 'preco', 'unidade');

        $preco = floatval(str_replace(",", ".", str_replace(".","", $fields['preco'])));

        $faccao_x_tipo_de_servicoObj = new FaccaoTipoDeServico;
        $faccao_x_tipo_de_servicoObj->codigo_faccao = $fields['codigo_faccao'];
        $faccao_x_tipo_de_servicoObj->codigo_tipo_de_servico = $fields['tipo_de_servico'];
        $faccao_x_tipo_de_servicoObj->preco = $preco;
        $faccao_x_tipo_de_servicoObj->codigo_unidade = $fields['unidade'];
        $faccao_x_tipo_de_servicoObj->created_by = Auth::id();
        $faccao_x_tipo_de_servicoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function modalEditar(Request $request){
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

        $query = FaccaoTipoDeServico::select();
        $query->with(['faccao' => function($query){
            $query->with(['fornecedor']);
        }]);
        $query->where('id', $id);
        $result = $query->first();

        $dados = [
            'id' => encrypt($result->id),
            'codigo_faccao' => $result->faccao->cod_fornecedor,
            'faccao' => $result->faccao->fornecedor->nome.' - '.$result->faccao->fornecedor->cnpj_cpf,
            'tipo_de_servico' => $result->codigo_tipo_de_servico,
            'unidade' => $result->codigo_unidade,
            'preco' => parserValor($result->preco)
        ];

        $unidades = $this->unidades();
        $tipo_de_servicos = $this->tipo_de_servicos();

        return view('programs.faccao_x_tipo_de_servico.modal.editar')->with(['dados' => $dados, 'unidades' => $unidades, 'tipo_de_servicos' => $tipo_de_servicos]);
    }

    public function editar(FaccaoTipoDeServicoEditarRequest $request){
        $fields = $request->only('id','codigo_faccao', 'tipo_de_servico', 'preco', 'unidade');

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

        $preco = floatval(str_replace(",", ".", str_replace(".","", $fields['preco'])));

        $query = Faccao::select();
        $query->where('cod_fornecedor', $fields['codigo_faccao']);
        $result = $query->first();

        $faccao_x_tipo_de_servicoObj = FaccaoTipoDeServico::find($id);
        $faccao_x_tipo_de_servicoObj->codigo_faccao = $result->id;
        $faccao_x_tipo_de_servicoObj->codigo_tipo_de_servico = $fields['tipo_de_servico'];
        $faccao_x_tipo_de_servicoObj->preco = $preco;
        $faccao_x_tipo_de_servicoObj->codigo_unidade = $fields['unidade'];
        $faccao_x_tipo_de_servicoObj->updated_by = Auth::id();
        $faccao_x_tipo_de_servicoObj->save();

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

        $query = FaccaoTipoDeServico::select();
        $query->with(['faccao' => function($query){
            $query->with(['fornecedor']);
        },
        'tipo_de_servico', 'unidade']);
        $query->where('id', $id);
        $result = $query->first();

        $dados = [
            'id' => encrypt($result->id),
            'faccao' => strtoupper($result->faccao->fornecedor->nome),
            'cnpj' => strtoupper($result->faccao->fornecedor->cnpj_cpf),
            'tipo_de_servico' => strtoupper($result->tipo_de_servico->descricao),
            'unidade' => strtoupper($result->unidade->descricao),
            'preco' => parserValor($result->preco)
        ];
        return view('programs.faccao_x_tipo_de_servico.modal.deletar')->with(['dados' => $dados]);
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
        
        
        $faccao_x_tipo_de_servicoObj = FaccaoTipoDeServico::find($id);
        $faccao_x_tipo_de_servicoObj->deleted_by = Auth::id();
        $faccao_x_tipo_de_servicoObj->save();
        $faccao_x_tipo_de_servicoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(Request $request, $array = false){
        $fields = $request->only('faccao', 'tipo_de_servico', 'unidade');
        $retorno = [];

        $query = FaccaoTipoDeServico::select();
        $query->with(['faccao' => function($query) use($fields){
            $query->with(['fornecedor'=>function($query) use($fields){
                if(!empty($fields['faccao'])){
                    $query->whereRaw('nome || \' - \' || cnpj_cpf ILIKE \'%'.$fields['faccao'].'%\'');
                }
            }]);
        }, 
        'tipo_de_servico' => function($query) use($fields){
            if(!empty($fields['tipo_de_servico'])){
                $query->where('id', $fields['tipo_de_servico']);
            }
        },
        'unidade' => function($query) use($fields){
            if(!empty($fields['unidade'])){
                $query->where('codigo', $fields['unidade']);
            }
        }]);
        $result = $query->get();

        foreach($result as $faccao_x_tipo_de_servico){
            if(!empty($faccao_x_tipo_de_servico->faccao->fornecedor) && !empty($faccao_x_tipo_de_servico->tipo_de_servico)){
                $retorno [] = [
                    'id' => encrypt($faccao_x_tipo_de_servico->id),
                    'faccao' => $faccao_x_tipo_de_servico->faccao->fornecedor->nome,
                    'cnpj_cpf'=> $faccao_x_tipo_de_servico->faccao->fornecedor->cnpj_cpf,
                    'tipo_de_servico' => $faccao_x_tipo_de_servico->tipo_de_servico->descricao,
                    'unidade' => '',
                    'preco' => parserValor($faccao_x_tipo_de_servico->preco)
                ];
            }
        }
        if($array){
            return $retorno;
        }else{
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => $retorno
            ];
            return response()->json($response);
        }
    }

    public function tipo_de_servicos(){
        $tipo_de_servicos[''] = 'Selecione o Serviço';

        $query_tipo_de_servico = TipoDeServico::select();
        $result_tipo_de_servico = $query_tipo_de_servico->get();

        foreach($result_tipo_de_servico as $key => $tipo_de_servico){
            $tipo_de_servicos [$tipo_de_servico->id] = $tipo_de_servico->descricao;
        }

        return $tipo_de_servicos;
    }

    public function tipo_de_servicos_todos(){
        $tipo_de_servicos[''] = 'Todos os Serviços';

        $query_tipo_de_servico = TipoDeServico::select();
        $result_tipo_de_servico = $query_tipo_de_servico->get();

        foreach($result_tipo_de_servico as $key => $tipo_de_servico){
            $tipo_de_servicos [$tipo_de_servico->id] = $tipo_de_servico->descricao;
        }

        return $tipo_de_servicos;
    }

    public function unidades(){
        $query_unidades = UnidadeMedidaNasajon::select();
        $query_unidades->where('descricao', '<>', '');

        $query_unidades->orderBy('descricao');
        $result_unidades = $query_unidades->get();

        foreach($result_unidades as $key => $unidade){
            $unidades [$unidade->codigo] = $unidade->descricao;
        }

        return $unidades;
    }

    public function getTipoDeServico(Request $request){
        $codigo_faccao = $request->only('codigo_faccao');

        $query = FaccaoTipoDeServico::query()
            ->with(['tipo_de_servico'])
            ->whereHas('faccao', function($query) use($codigo_faccao){
                $query->where('id', $codigo_faccao);
            });
        $result = $query->get();

        $tipo_de_servicos[0] = 'Selecione o Serviço';

        foreach($result as $faccao_tipo_de_servico){
            $tipo_de_servicos [$faccao_tipo_de_servico->codigo_tipo_de_servico] = $faccao_tipo_de_servico->tipo_de_servico->descricao;
        }
        

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $tipo_de_servicos
        ];

        return response()->json($response);
    }

    public function getPreco(Request $request){
        $fields = $request->only('faccao','tipo_de_servico', 'quantidade');

        $query = FaccaoTipoDeServico::query()
            ->where('codigo_tipo_de_servico', $fields['tipo_de_servico']);
        if(!empty($fields['faccao'])){
            $query->where('codigo_faccao', $fields['faccao']);
        }
        $result = $query->max('preco');

        if(empty($fields['quantidade'])){
            $retorno = [
                'preco' => parserValor($result->preco),
                'quantidade' => '0,00',
                'custo_total' => '0,00'
            ];

            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => ['faccao_quantidade_exibicao' => 'Não há quantidade no Produto'],
                'response' => $retorno
            ],422);
        }else{
            $preco = empty($result)? 0.0:$result;
            $quantidade = $this->formtFloat($fields['quantidade']);
            $custo_total = $quantidade * $preco;

            $retorno = [
                'preco' => parserValor($preco),
                'custo_total' => parserValor($custo_total)
            ];

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => $retorno
            ];
    
            return response()->json($response);
        }
    }

    function formtFloat($value){
        $value = str_replace(",", ".", str_replace(".", "", $value));
        $value = floatval($value);
        
        return $value;
    }
}