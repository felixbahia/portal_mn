<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\NotasNasajon;
use App\ClienteNasajon;
use App\ConfirmacaoNotaSaida;
use App\ContasReceberBaixadoNasajon;

use Illuminate\Support\Facades\Storage;

use App\Http\Requests\ConfirmacaoNotasSaidaFiltroRequest;
use App\Http\Requests\ConfirmacaoNotaSaidaAdicionarRequest;
use App\Http\Requests\ConfirmacaoNotaSaidaEditarRequest;

class ConfirmacaoNotaSaidaController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ConfirmacaoSaidaNota") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ConfirmacaoSaidaNota');

        $estabelecimentos = $this->dadosEstabelecimentos();

        return view('programs.confirmacao_nota_saida.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function modalAdicionar(){
        $estabelecimentos = $this->dadosEstabelecimentos();

        return view('programs.confirmacao_nota_saida.modal.adicionar')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function adicionar(ConfirmacaoNotaSaidaAdicionarRequest $request){
        $fields = $request->only('estabelecimento', 'nota_saida', 'codigo_cliente', 'data_saida', 'peso', 'data_emissao','tipo_nota');
        $data_saida = Carbon::createFromFormat('d/m/Y', $fields['data_saida']);
        $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);
        $nota_saida = str_pad($fields["nota_saida"], 9, '0', STR_PAD_LEFT);
        
        if(isset($fields['peso'])){
            $peso = parserNumber($fields['peso']);
        }
        else{
            $peso = 0;
        }
        
        if($fields['tipo_nota'] === 'nfe'){
            $query = NotasNasajon::select();
            $query->where('estabelecimento_codigo', $estabelecimento);
            $query->where('numero', $nota_saida);
            $result = $query->first();
            if(empty($result->cliente_documento)){
                $cliente_documento = '000000000000000000';
            }else{
                $cliente_documento = $result->cliente_documento;
            }
        }else if($fields['tipo_nota']  === 'nfce'){
            $query = ContasReceberBaixadoNasajon::select();
            $query->where('codigo', $estabelecimento);
            $query->where('numero','ilike', '%'.$nota_saida.'%');
            $result = $query->first();
            if(empty($result->cod_cliente)){
                $cliente_documento = '000000000000000000';
            }else{
                $cliente_documento = $result->cod_cliente;
            }
        }

        $confirmacaoNotaSaidaObj = new ConfirmacaoNotaSaida;
        $confirmacaoNotaSaidaObj->estabelecimento = $estabelecimento;
        $confirmacaoNotaSaidaObj->nota = $result->numero ;
        $confirmacaoNotaSaidaObj->cod_cliente = $cliente_documento;
        $confirmacaoNotaSaidaObj->data_saida = $data_saida;

        if($fields['tipo_nota'] === 'nfe'){
            $confirmacaoNotaSaidaObj->peso = $peso;
        }else{
            $confirmacaoNotaSaidaObj->peso = 0;
        }

        $confirmacaoNotaSaidaObj->created_by = Auth::id();

        if($fields['tipo_nota'] === 'nfce'){
            $confirmacaoNotaSaidaObj->nfce = true;
        }

        $confirmacaoNotaSaidaObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);

    }

    public function modalEditar(Request $request) {
        $estabelecimentos = $this->dadosEstabelecimentos();
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
        if(!is_array($id)){

            $query = ConfirmacaoNotaSaida::select();
            $query->with(['cliente_cnpj', 'cliente_codigo', 'nota_detalhe','nfce_detalhe']);
            $query->where('id', $id);
            $result = $query->first();
            $nome = '';

            if(!is_null($result->cliente_cnpj)){
                $nome = $result->cliente_cnpj->nome;
            }
            else if(!is_null($result->cliente_codigo)){
                $nome = $result->cliente_codigo->nome;
            }

            if(!empty($result->peso)){
                $peso_confirmado = parserQtd3CasaDecimais($result->peso);
            }
            else{
                $peso_confirmado = '';
            }

            $dados = [
                'estabelecimento' => intval($result->estabelecimento),
                'nota_saida' => $result->nota,
                'cliente' => $nome,
                'codigo_cliente' => $result->cod_cliente,
                'data_emissao'  => (!empty($result->nota_detalhe->emissao)) ? parserData($result->nota_detalhe->emissao) : parserData($result->nfce_detalhe->emissao),
                'data_saida' => parserData($result->data_saida),
                'peso' => $peso_confirmado,
                'id' => encrypt($id),
                'tipo_nota' => $result->nfce === true ? 'verdadeiro' : null,
            ];
        }else{

            $query = NotasNasajon::select();
            $query->whereNotIn('operacao_codigo', ['REMARMAZMOVTO']);
            $query->where('estabelecimento_codigo', $id['estabelecimento_codigo']);
            $query->where('numero', $id['numero_nota']);
            $result = $query->first();

            $dados = [
                'estabelecimento' => intval($result->estabelecimento_codigo),
                'nota_saida' => $result->numero,
                'cliente' => $result->cliente_nome,
                'codigo_cliente' => $result->cliente_documento,
                'data_emissao'  => parserData($result->emissao),
                'data_saida' => '',
                'peso' => '',
                'id' => encrypt($id),
                'tipo_nota' => null,
            ];
        }
        return view('programs.confirmacao_nota_saida.modal.editar')->with(['estabelecimentos' => $estabelecimentos, 'dados' => $dados]);
    }

    public function editar(ConfirmacaoNotaSaidaEditarRequest $request){
        $fields = $request->only('id','estabelecimento', 'nota_saida', 'peso', 'codigo_cliente', 'data_saida', 'data_emissao','tipo_nota');
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
        if(!is_array($id)){

            $data_saida = Carbon::createFromFormat('d/m/Y', $fields['data_saida']);
            $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);
            $nota_saida = str_pad($fields["nota_saida"], 9, '0', STR_PAD_LEFT);

            if(isset($fields['peso']) && empty($fields['tipo_nota'])){
                $peso = parserNumber($fields['peso']);
            }
            else{
                $peso = 0;
            }
            if(!empty($fields['tipo_nota'])){
                $query = ContasReceberBaixadoNasajon::select();
                $query->where('codigo', $estabelecimento);
                $query->where('numero','ilike', '%'.$nota_saida.'%');
                $result = $query->first();
            }

            $confirmacaoNotaSaidaObj = ConfirmacaoNotaSaida::find($id);
            $confirmacaoNotaSaidaObj->estabelecimento = $estabelecimento;
            $confirmacaoNotaSaidaObj->nota = (!empty($fields['tipo_nota'])) ? $result->numero :$nota_saida;
            $confirmacaoNotaSaidaObj->cod_cliente = $fields['codigo_cliente'];
            $confirmacaoNotaSaidaObj->peso = $peso;
            $confirmacaoNotaSaidaObj->data_saida = $data_saida;
            $confirmacaoNotaSaidaObj->nfce = (empty($fields['tipo_nota'])) ? null : true;
            $confirmacaoNotaSaidaObj->updated_by = Auth::id();
            $confirmacaoNotaSaidaObj->save();

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => []
            ];
            return response()->json($response);
        }else{
            $data_saida = Carbon::createFromFormat('d/m/Y', $fields['data_saida']);
            $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);
            $nota_saida = str_pad($fields["nota_saida"], 9, '0', STR_PAD_LEFT);

            if(empty($fields['tipo_nota'])){
                $query = NotasNasajon::select();
                $query->whereNotIn('operacao_codigo', ['REMARMAZMOVTO']);
                $query->where('estabelecimento_codigo', $estabelecimento);
                $query->where('numero', $nota_saida);
            }else{
                $query = ContasReceberBaixadoNasajon::select();
                $query->where('codigo', $estabelecimento);
                $query->where('numero','ilike', '%'.$nota_saida.'%');
            }
            $result = $query->first();

            if(empty($fields['tipo_nota'])){
                if(empty($result->cliente_documento)){
                    $cliente_documento = '000000000000000000';
                }else{
                    $cliente_documento = $result->cliente_documento;
                }
            }else{
                if(empty($result->cod_cliente)){
                    $cliente_documento = '000000000000000000';
                }else{
                    $cliente_documento = $result->cod_cliente;
                }
            }

            $confirmacaoNotaSaidaObj = new ConfirmacaoNotaSaida;
            $confirmacaoNotaSaidaObj->estabelecimento = $estabelecimento;
            $confirmacaoNotaSaidaObj->nota = $nota_saida;
            $confirmacaoNotaSaidaObj->cod_cliente = $cliente_documento;
            $confirmacaoNotaSaidaObj->data_saida = $data_saida;
            $confirmacaoNotaSaidaObj->created_by = Auth::id();
            $confirmacaoNotaSaidaObj->save();

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => []
            ];
            return response()->json($response);
        }
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

        $empresa = returnEmpresasNasajonView();

        $query = ConfirmacaoNotaSaida::select();
        $query->with(['cliente', 'nota_detalhe']);
        $query->where('id', $id);
        $result = $query->first();

        $dados = [
            'estabelecimento' =>  $empresa[intval($result->estabelecimento)],
            'nota_saida' => $result->nota,
            'cliente' => $result->cliente->nome,
            'cnpj' => $result->nota_detalhe->cliente_documento,
            'data_emissao'  => parserData($result->nota_detalhe->emissao),
            'data_saida' => parserData($result->data_saida),
            'id' => encrypt($id)
        ];

        return view('programs.confirmacao_nota_saida.modal.deletar')->with(['dados' => $dados]);
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
        
        $confirmacaoNotaSaidaObj = ConfirmacaoNotaSaida::find($id);
        $confirmacaoNotaSaidaObj->deleted_by = Auth::id();
        $confirmacaoNotaSaidaObj->save();
        $confirmacaoNotaSaidaObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(ConfirmacaoNotasSaidaFiltroRequest $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('estabelecimento', 'nota_saida', 'codigo_cliente', 'cliente', 'data_inicio', 'data_fim', 'notas_nao_lancadas','notas_sem_canhoto');

        $empresa = returnEmpresasNasajonView();

        $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);

        $query_nfce = ContasReceberBaixadoNasajon::select();

        $query = NotasNasajon::select();
        if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->setTime(0,0,0);
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->setTime(23,59,59);
            $query->whereBetween('emissao', [$data_inicio,$data_fim]);
            $query_nfce->whereBetween('emissao', [$data_inicio,$data_fim]);
        }

        $query_nfce->with(['confirmacaoNotaSaida' => function($query) use ($fields, $estabelecimento){
            if(!empty($fields['estabelecimento'])){
                $query->where('estabelecimento',$estabelecimento);
            }
            if(!empty($fields['nota_saida'])){
                $query->where('nota',$fields['nota_saida']);
            }
            if(!empty($fields['codigo_cliente'])){
                $query->where('cod_cliente',$fields['codigo_cliente']);
            }
            if(!empty($fields['notas_sem_canhoto'])){
                $query->whereNull('foto_canhoto');
            }
        }]);

        $query->with(['confirmacaoNotaSaida' => function($query) use ($fields, $estabelecimento){
            if(!empty($fields['estabelecimento'])){
                $query->where('estabelecimento',$estabelecimento);
            }
            if(!empty($fields['nota_saida'])){
                $query->where('nota',$fields['nota_saida']);
            }
            if(!empty($fields['codigo_cliente'])){
                $query->where('cod_cliente',$fields['codigo_cliente']);
            }
            if(!empty($fields['notas_sem_canhoto'])){
                $query->whereNull('foto_canhoto');
            }
        }]);

        $query_nfce->orderBy('codigo');
        $query->orderBy('estabelecimento_codigo');
        $result_nfce = $query_nfce->get();

        $result_nfce = $result_nfce->filter(function($query){
            return !empty($query->confirmacaoNotaSaida);
        });

        $result = $query->get();

        $cliente = 'CLIENTE NÃO ENCONTRADO';
        $retorno = [];
        $notas_adicionadas = [];

        foreach($result_nfce as $nfce_saida){
            $cliente = 'CLIENTE NÃO ENCONTRADO';
            if(!empty($nfce_saida->confirmacaoNotaSaida)){
                if(!empty($nfce_saida->cliente)){
                    $cliente = $nfce_saida->cliente->nome;
                }else if(!empty($nfce_saida->confirmacaoNotaSaida->cliente_cnpj)){
                    $cliente = $nfce_saida->confirmacaoNotaSaida->cliente_cnpj->nome;
                }else if (!empty($nfce_saida->cliente_documento)){
                    $nfce_saida->confirmacaoNotaSaida->cod_cliente = $nfce_saida->cliente_documento;
                    $nfce_saida->confirmacaoNotaSaida->save();
                    $cliente = $nfce_saida->cliente_nome;
                }
                $canhoto = '';
                if(Storage::exists($nfce_saida->confirmacaoNotaSaida->foto_canhoto)){
                    $canhoto = Storage::url($nfce_saida->confirmacaoNotaSaida->foto_canhoto);
                }
                $notas_adicionadas[] = $nfce_saida->numero;
                if(!isset($fields['notas_nao_lancadas'])){
                    $retorno [] = [
                        'id' => encrypt($nfce_saida->confirmacaoNotaSaida->id),
                        'estabelecimento' => $empresa[intval($nfce_saida->estabelecimento_codigo)],
                        'nota' => $nfce_saida->numero,
                        'cliente' => $cliente,
                        'canhoto' => $canhoto,
                        'data_saida' => parserData($nfce_saida->confirmacaoNotaSaida->data_saida),
                        'data_emissao' => parserData($nfce_saida->emissao),
                        'nfce' => (!empty($nfce_saida->confirmacaoNotaSaida->nfce && $nfce_saida->confirmacaoNotaSaida->nfce === true)) ? '<center><i class="fa fa-check check-icon" aria-hidden="true"></i></cener>' : '',
                        'peso' => parserQtd3CasaDecimais($nfce_saida->confirmacaoNotaSaida->peso),
                        'valor' => parserValor($nfce_saida->valor_titulo)
                    ];
                }
            }
        }

        foreach($result as $nota_saida){
            $cliente = 'CLIENTE NÃO ENCONTRADO';

            if(!empty($nota_saida->confirmacaoNotaSaida)){
                if(!empty($nota_saida->cliente)){
                    $cliente = $nota_saida->cliente->nome;
                }else if(!empty($nota_saida->confirmacaoNotaSaida->cliente_cnpj)){
                    $cliente = $nota_saida->confirmacaoNotaSaida->cliente_cnpj->nome;
                }else if (!empty($nota_saida->cliente_documento)){
                    $nota_saida->confirmacaoNotaSaida->cod_cliente = $nota_saida->cliente_documento;
                    $nota_saida->confirmacaoNotaSaida->save();
                    $cliente = $nota_saida->cliente_nome;
                }
                $canhoto = '';
                if(Storage::exists($nota_saida->confirmacaoNotaSaida->foto_canhoto)){
                    $canhoto = Storage::url($nota_saida->confirmacaoNotaSaida->foto_canhoto);
                }

                $notas_adicionadas[] = $nota_saida->numero;

                if(!isset($fields['notas_nao_lancadas'])){
                    $retorno[] = [
                        'id' => encrypt($nota_saida->confirmacaoNotaSaida->id),
                        'estabelecimento' => $empresa[intval($nota_saida->estabelecimento_codigo)],
                        'nota' => $nota_saida->numero,
                        'cliente' => $cliente,
                        'canhoto' => $canhoto,
                        'data_saida' => parserData($nota_saida->confirmacaoNotaSaida->data_saida),
                        'data_emissao' => parserData($nota_saida->emissao),
                        'nfce' => '',
                        'peso' => parserQtd3CasaDecimais($nota_saida->confirmacaoNotaSaida->peso),
                        'valor' => parserValor($nota_saida->valor)
                    ];
                }
            }
        }

        if(isset($fields['notas_nao_lancadas']) && $fields['notas_nao_lancadas'] == 'true'){
            
            $query = NotasNasajon::query();
            $query->whereNotIn('operacao_codigo', ['REMARMAZMOVTO']);
            $query->whereNotIn('numero', $notas_adicionadas);
            if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
                $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
                $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
                $query->whereBetween('emissao', [$data_inicio, $data_fim]);
            }
            if(!empty($fields['estabelecimento'])){
                $query->where('estabelecimento_codigo', $estabelecimento);
            }
            if(!empty($fields['nota_saida'])){
                $query->where('numero', $fields['nota_saida']);
            }
    
            $query->orderBy('estabelecimento_codigo');
            $result = $query->get();
            foreach($result as $nota_saida){
                $cliente = $nota_saida->cliente_nome;
                $retorno [] = [
                    'id' => encrypt(['numero_nota' => $nota_saida->numero, 'estabelecimento_codigo' => $nota_saida->estabelecimento_codigo]),
                    'estabelecimento' => $empresa[intval($nota_saida->estabelecimento_codigo)],
                    'nota' => $nota_saida->numero,
                    'cliente' => $cliente,
                    'data_saida' => '',
                    'data_emissao' => parserData($nota_saida->emissao),
                    'nfce' => '',
                    'peso' => ''
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

    private function dadosEstabelecimentos(){
        $retorno[''] = 'ESTABELECIMENTO';
        $estabelecimentos = returnEmpresasNasajonView();
        foreach($estabelecimentos as $key => $estabelecimento){
            $retorno[$key] = $estabelecimento;
        }
        return $retorno; 
    }

    public function getClienteDataEmissao(Request $request){
        $fields = $request->only('estabelecimento', 'nota_saida','tipo_nota');
        
        $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);
        $nota_saida = str_pad($fields["nota_saida"], 9, '0', STR_PAD_LEFT);

        if($fields['tipo_nota'] === 'nfe'){
            $query = NotasNasajon::select();
            $query->where('estabelecimento_codigo', $estabelecimento);
            $query->where('numero', $nota_saida);

            $result = $query->first();

            if(empty($result)){
                return response()->json([
                    'status' => 'error',
                    'message' => '',
                    'error' => ['nota_saida' => 'Nota não encontrada.'],
                    'response' => ''
                ],422);
            }else if(empty($result->cliente_documento)){
                $retorno = [
                    'cliente' => 'Cliente Não Encontrado',
                    'data_emissao' => parserData($result->emissao),
                    'codigo_cliente' => '000000000000000000'
                ];
    
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => $retorno
                ]);
            }else{
                $cliente = ClienteNasajon::where('cpf_cnpj', $result->cliente_documento)->first();
                $retorno = [
                    'cliente' => $result->cliente_nome,
                    'data_emissao' => parserData($result->emissao),
                    'codigo_cliente' => $cliente->codigo
                ];
    
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => $retorno
                ]);
            }
        }else if($fields['tipo_nota'] === 'nfce'){
            $query = ContasReceberBaixadoNasajon::select();
            $query->where('codigo', $estabelecimento);
            $query->with('cliente');
            $query->where('numero','ilike', '%'.$nota_saida.'%');

            $result = $query->first();
            
            if(empty($result)){
                return response()->json([
                    'status' => 'error',
                    'message' => '',
                    'error' => ['nota_saida' => 'Nota não encontrada.'],
                    'response' => ''
                ],422);
            }else if(empty($result->documento_id)){
                $retorno = [
                    'cliente' => 'Cliente Não Encontrado',
                    'data_emissao' => parserData($result->emissao),
                    'codigo_cliente' => '000000000000000000'
                ];
    
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => $retorno
                ]);
            }else{
                $retorno = [
                    'cliente' => $result->cliente->nome,
                    'data_emissao' => parserData($result->emissao),
                    'codigo_cliente' => $result->cliente->codigo
                ];
    
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => $retorno
                ]);
            }
        }
    }
}
