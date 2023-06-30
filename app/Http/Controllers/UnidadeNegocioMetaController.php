<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\User;
use App\UnidadeNegocio;
use App\UnidadeNegocioMeta;
use App\UnidadeNegocioMetaXUser;
use App\Movimentacao;

use App\Http\Requests\UnidadeNegocioMetaRequest;
use App\Http\Requests\UnidadeNegocioMetaAdicionarMembroRequest;

use App\Http\Controllers\MapaVendaController;

class UnidadeNegocioMetaController extends Controller
{
    private $unidade_metro = ['m', 'M', 'M...','METRO', 'METROS', 'MT', 'MTS', 'MTS.'];
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\UnidadeNegocioMetas") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\UnidadeNegocioMetas');

        return view('programs.unidade_negocio.metas.index');
    }

    public function modalAdicionar() {
        return view('programs.unidade_negocio.metas.modal.adicionar');
    }

    public function adicionarRepresentante(UnidadeNegocioMetaAdicionarMembroRequest $request){
        $fields = $request->only('unidade_negocio', 'usuario', 'meta', 'usuarios_adicionado', 'usuarios_unidade_negocio');

        if(!empty($fields['usuarios_adicionado'])){
            $usuarios_adicionado = decrypt($fields['usuarios_adicionado']);
        }else{
            $usuarios_adicionado = []; 
        }

        if(!empty($fields['usuarios_unidade_negocio'])){
            $usuarios_unidade_negocio = decrypt($fields['usuarios_unidade_negocio']);
        }else{
            $usuarios_unidade_negocio = []; 
        }

        if(!empty($fields['usuario'])){
            $usuario = User::select()
                ->whereRaw("TRIM(CONCAT(TRIM(codigo_representante), ' - ', name)) ilike '".trim($fields['usuario'])."'")
                ->first();

            $usuarios_adicionado[$usuario->id] = [
                'id_usuario' => encrypt($usuario->id),
                'nome' => $usuario->codigo_representante." - ".$usuario->name,
                'meta' => $fields['meta'],
            ];
        }

        $usuarios = [];
        foreach($usuarios_unidade_negocio as $usuario){
            $usuarios[decrypt($usuario['id_usuario'])] = [
                'id_usuario' => $usuario['id_usuario'],
                'nome' => $usuario['nome'],
                'meta' => $usuario['meta'],
            ];
        }

        foreach($usuarios_adicionado as $usuario){
            $usuarios[decrypt($usuario['id_usuario'])] = [
                'id_usuario' => $usuario['id_usuario'],
                'nome' => $usuario['nome'],
                'meta' => $usuario['meta'],
            ];
        }

        $total_meta = 0;
        foreach($usuarios as $key => $usuario){
            $representantes[$key]['meta'] = $usuario['meta'];
            $total_meta += parserNumber($usuario['meta']);
        }
        
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'tabela' => $usuarios,
                'usuarios' => empty($usuarios)? '' : encrypt($usuarios),
                'usuarios_adicionado' => empty($usuarios_adicionado)? '' : encrypt($usuarios_adicionado),
                'total_meta' => empty($total_meta)? '' : parserValor($total_meta)
            ],
        ]);
    }

    public function deletarRepresentante(Request $request){
        $fields = $request->only('id', 'usuarios_unidade_negocio', 'usuarios_adicionado');

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

        $usuarios = [];

        if(!empty($fields['usuarios_adicionado'])){
            $usuarios_adicionado = decrypt($fields['usuarios_adicionado']);
            if(!empty($usuarios_adicionado[$id])){
                unset($usuarios_adicionado[$id]);
            }
        }else{
            $usuarios_adicionado = []; 
        }

        if(!empty($fields['usuarios_unidade_negocio'])){
            $usuarios_unidade_negocio = decrypt($fields['usuarios_unidade_negocio']);
            if(!empty($usuarios_unidade_negocio[$id])){
                unset($usuarios_unidade_negocio[$id]);
            }
        }else{
            $usuarios_unidade_negocio = []; 
        }
        
        foreach($usuarios_unidade_negocio as $usuario){
            $usuarios[decrypt($usuario['id_usuario'])] = [
                'id_usuario' => $usuario['id_usuario'],
                'nome' => $usuario['nome'],
                'meta' => $usuario['meta'],
            ];
        }
        
        foreach($usuarios_adicionado as $usuario){
            $usuarios[decrypt($usuario['id_usuario'])] = [
                'id_usuario' => $usuario['id_usuario'],
                'nome' => $usuario['nome'],
                'meta' => $usuario['meta'],
            ];
        }

        $total_meta = 0;
        foreach($usuarios as $key => $usuario){
            $representantes[$key]['meta'] = $usuario['meta'];
            $total_meta += parserNumber($usuario['meta']);
        }
        
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'tabela' => $usuarios,
                'usuarios' => empty($usuarios)? '' : encrypt($usuarios),
                'usuarios_adicionado' => empty($usuarios_adicionado)? '' : encrypt($usuarios_adicionado),
                'usuarios_unidade_negocio' => empty($usuarios_unidade_negocio)? '' : encrypt($usuarios_unidade_negocio),
                'total_meta' => empty($total_meta)? '' : parserValor($total_meta)
            ],
        ]);
    }

    public function adicionar(UnidadeNegocioMetaRequest $request){
        $fields = $request->only('usuarios', 'mes_ano', 'unidade_negocio');

        $usuarios = decrypt($fields['usuarios']);

        $data = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);

        $unidade_negocio = UnidadeNegocio::select()->where('unidade', 'ilike', $fields['unidade_negocio'])->first();

        $unidadeNegocioMetaObj = new UnidadeNegocioMeta;
        $unidadeNegocioMetaObj->unidades_negocios_id = $unidade_negocio->id;
        $unidadeNegocioMetaObj->data = $data; 
        $unidadeNegocioMetaObj->created_by = Auth::id();
        $unidadeNegocioMetaObj->save();

        $total_meta = 0;
        foreach($usuarios as $usuario){
            $unidadeNegocioMetaXUserObj = new UnidadeNegocioMetaXUser;
            $unidadeNegocioMetaXUserObj->unidade_negocio_metas_id = $unidadeNegocioMetaObj->id; 
            $unidadeNegocioMetaXUserObj->users_id = decrypt($usuario["id_usuario"]);
            $unidadeNegocioMetaXUserObj->metas = parserNumber($usuario["meta"]); 
            $unidadeNegocioMetaXUserObj->created_by = Auth::id();
            $unidadeNegocioMetaXUserObj->save();

            $total_meta += parserNumber($usuario['meta']);
        }

        $unidadeNegocioMetaObj->valor = $total_meta; 
        $unidadeNegocioMetaObj->save();

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [],
        ]);
    }

    public function getRepresentantesPorUnidadeNegocioMeta(Request $request){
        $fields = $request->only('unidade_negocio', 'valor', 'usuarios', 'usuarios_unidade_negocio', 'usuarios_adicionado');

        if(!empty($fields['unidade_negocio'])){
            $unidade_negocio = UnidadeNegocio::select()->where('unidade', 'ilike', $fields['unidade_negocio'])->first();
        }else{
            $unidade_negocio = "";
        }

        $usuarios_unidade_negocio = []; 

        if(!empty($fields['usuarios_adicionado'])){
            $usuarios = decrypt($fields['usuarios_adicionado']);
        }else{
            $usuarios = []; 
        }

        $total_meta = 0;

        if(!empty($unidade_negocio)){
            $query_data_maxima = UnidadeNegocioMeta::selectRaw('max(data) as data');
            $query_data_maxima->where('unidades_negocios_id', $unidade_negocio->id);
            $result_data_maxima = $query_data_maxima->first();

            if(!empty($result_data_maxima)){
                $query_unidade_negocio_metas = UnidadeNegocioMeta::select();
                $query_unidade_negocio_metas->where('unidades_negocios_id', $unidade_negocio->id);
                $query_unidade_negocio_metas->where('data', $result_data_maxima->data);
                $result_unidade_negocio_metas = $query_unidade_negocio_metas->first();

                if(!empty($result_unidade_negocio_metas)){
                    $query_unidade_negocio_metas_x_users = UnidadeNegocioMetaXUser::select();
                    $query_unidade_negocio_metas_x_users->where('unidade_negocio_metas_id', $result_unidade_negocio_metas->id);
                    $result_unidade_negocio_metas_x_users = $query_unidade_negocio_metas_x_users->get();

                    foreach($result_unidade_negocio_metas_x_users as $usuario){
                        $usuarios_unidade_negocio[$usuario->detalhesUsuario->id] = [
                            'id_usuario' => encrypt($usuario->detalhesUsuario->id),
                            'nome' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                            'meta' => parserValor($usuario->metas),
                        ];
                    }

                    foreach($usuarios_unidade_negocio as $usuario){
                        $usuarios[decrypt($usuario['id_usuario'])] = [
                            'id_usuario' => $usuario['id_usuario'],
                            'nome' => $usuario['nome'],
                            'meta' => $usuario['meta'],
                        ];
                    }
                }
            }
        }

        $total_meta = 0;
        foreach($usuarios as $key => $usuario){
            $representantes[$key]['meta'] = $usuario['meta'];
            $total_meta += parserNumber($usuario['meta']);
        }
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'tabela' => $usuarios,
                'usuarios' => empty($usuarios)? '' : encrypt($usuarios),
                'usuarios_unidade_negocio' => empty($usuarios_unidade_negocio)? '' : encrypt($usuarios_unidade_negocio),
                'total_meta' => parserValor($total_meta)
            ],
        ]);
    }

    public function filterMetas(Request $request){
        $fields = $request->only('mes_ano', 'unidade_negocio');
        $query = UnidadeNegocioMeta::select();
        $query->with(['detalhesUnidadeNegocio']);
        if(!empty($fields['mes_ano'])){
            $data = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
            $query->where('data', $data);
        }
        if(!empty($fields['unidade_negocio'])){
            $query->whereHas('detalhesUnidadeNegocio', function($query) use($fields){
                $query->where('unidade', 'ilike', '%'.$fields['unidade_negocio'].'%');
            });
        }
        
        $gerente_unidade = $this->verificacaoGerente();

        if(!empty($gerente_unidade)){
            $query->whereIn('unidades_negocios_id', $gerente_unidade);
        }

        $result = $query->get();

        $metas = [];

        $total_meta = 0;
        foreach($result as $meta){
            $data_atual = Carbon::now();
            $data_meta = Carbon::createFromFormat('Y-m-d', $meta->data)->setTime(0,0,0)->addMonths(1);

            $metas[] = [
                'id' => encrypt($meta->id),
                'mes_ano' => substr(parserData($meta->data), -7),
                'unidade_negocio' => $meta->detalhesUnidadeNegocio->unidade,
                'meta' => parserValor($meta->valor),
            ];

            $total_meta += $meta->valor;
        }

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'metas' => $metas,
                'total_meta' => empty($total_meta)? '' : parserValor($total_meta),
            ],
        ]);
    }

    public function modalEditar(Request $request) {
        $id = $request->only('id')['id'];

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

        $unidade_negocio_meta = UnidadeNegocioMeta::find($id);

        $query_unidade_negocio_metas_x_users = UnidadeNegocioMetaXUser::select();
        $query_unidade_negocio_metas_x_users->where('unidade_negocio_metas_id', $unidade_negocio_meta->id);
        $result_unidade_negocio_metas_x_users = $query_unidade_negocio_metas_x_users->get();

        $usuarios = [];
        foreach($result_unidade_negocio_metas_x_users as $usuario){
            $usuarios[$usuario->detalhesUsuario->id] = [
                'id_usuario' => encrypt($usuario->detalhesUsuario->id),
                'nome' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                'meta' => parserValor($usuario->metas),
            ];
        }

        $total_meta = 0;
        foreach($usuarios as $key => $usuario){
            $total_meta += parserNumber($usuario['meta']);
        }

        $dados = [
            'id' => encrypt($unidade_negocio_meta->id),
            'mes_ano' => substr(parserData($unidade_negocio_meta->data), -7),
            'unidade_negocio' => $unidade_negocio_meta->detalhesUnidadeNegocio->unidade,
            'meta' => parserValor($unidade_negocio_meta->valor),
            'usuarios' => $usuarios,
            'usuarios_dados' => encrypt($usuarios),
            'total_meta' => parserValor($total_meta),
        ];

        return view('programs.unidade_negocio.metas.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(UnidadeNegocioMetaRequest $request){
        $fields = $request->only('id', 'usuarios', 'mes_ano', 'unidade_negocio');

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

        $usuarios = decrypt($fields['usuarios']);

        $data = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);

        $unidade_negocio = UnidadeNegocio::select()->where('unidade', 'ilike', $fields['unidade_negocio'])->first();

        $unidadeNegocioMetaObj = UnidadeNegocioMeta::find($id);
        $unidadeNegocioMetaObj->unidades_negocios_id = $unidade_negocio->id;
        $unidadeNegocioMetaObj->data = $data;  
        $unidadeNegocioMetaObj->updated_by = Auth::id();
        $unidadeNegocioMetaObj->save();

        $query_unidade_negocio_meta_users = UnidadeNegocioMetaXUser::select();
        $query_unidade_negocio_meta_users->where('unidade_negocio_metas_id', $id);
        $result_unidade_negocio_meta_users = $query_unidade_negocio_meta_users->get();

        $id_usuarios_anteriores = [];
        foreach($result_unidade_negocio_meta_users as $unidade_negocio_meta_representante){
            $id_usuarios_anteriores[] = $unidade_negocio_meta_representante->users_id;
        }

        $representantes_confirmados = [];
        $total_meta = 0;
        foreach($usuarios as $usuario){
            $id_usuario = decrypt($usuario["id_usuario"]);

            if(!in_array($id_usuario, $id_usuarios_anteriores)){
                $unidadeNegocioMetaXUserObj = new UnidadeNegocioMetaXUser;
                $unidadeNegocioMetaXUserObj->unidade_negocio_metas_id = $id; 
                $unidadeNegocioMetaXUserObj->users_id = $id_usuario;
                $unidadeNegocioMetaXUserObj->metas = parserNumber($usuario['meta']);
                $unidadeNegocioMetaXUserObj->created_by = Auth::id();
                $unidadeNegocioMetaXUserObj->save();
            }else{
                $unidadeNegocioMetaXUserObj = UnidadeNegocioMetaXUser::select();
                $unidadeNegocioMetaXUserObj->where('unidade_negocio_metas_id', $id);
                $unidadeNegocioMetaXUserObj->where('users_id', $id_usuario);
                $unidadeNegocioMetaXUserObj = $unidadeNegocioMetaXUserObj->first();
                $unidadeNegocioMetaXUserObj->metas = parserNumber($usuario['meta']);
                $unidadeNegocioMetaXUserObj->updated_by = Auth::id();
                $unidadeNegocioMetaXUserObj->save(); 
            }

            $representantes_confirmados[] = $id_usuario;

            $total_meta += parserNumber($usuario['meta']);
        }

        $query_unidade_negocio_meta_users = UnidadeNegocioMetaXUser::select();
        $query_unidade_negocio_meta_users->where('unidade_negocio_metas_id', $id);
        $query_unidade_negocio_meta_users->whereNotIn('users_id', $representantes_confirmados);
        $result_unidade_negocio_meta_users = $query_unidade_negocio_meta_users->get();

        foreach($result_unidade_negocio_meta_users as $unidade_negocio_meta_representante){
            $unidadeNegocioMetaXUserObj = UnidadeNegocioMetaXUser::find($unidade_negocio_meta_representante->id);
            $unidadeNegocioMetaXUserObj->deleted_by = Auth::id();
            $unidadeNegocioMetaXUserObj->save();
            $unidadeNegocioMetaXUserObj->delete();
        }

        $unidadeNegocioMetaObj->valor = $total_meta;  
        $unidadeNegocioMetaObj->save();

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [],
        ]);
    }

    public function modalDeletar(Request $request) {
        $id = $request->only('id')['id'];

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

        $unidade_negocio_meta = UnidadeNegocioMeta::find($id);

        $dados = [
            'id' => encrypt($unidade_negocio_meta->id),
            'mes_ano' => substr(parserData($unidade_negocio_meta->data), -7),
            'unidade_negocio' => $unidade_negocio_meta->detalhesUnidadeNegocio->unidade,
            'meta' => parserValor($unidade_negocio_meta->valor),
        ];

        return view('programs.unidade_negocio.metas.modal.deletar')->with(['dados' => $dados]);
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
        
        $unidadeNegocioMetaObj = UnidadeNegocioMeta::find($id);
        $unidadeNegocioMetaObj->deleted_by = Auth::id();
        $unidadeNegocioMetaObj->save();
        $unidadeNegocioMetaObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    function isInteger($val){
        if (!is_scalar($val) || is_bool($val)) {
            return false;
        }
        if (is_float($val + 0) && ($val + 0) > PHP_INT_MAX) {
            return false;
        }
        return is_float($val) ? false : preg_match('~^((?:\+|-)?[0-9]+)$~', $val);
    }

    public function getVendedorMetaFaturamento($mes_ano, $array_vendedores = []){
        $mapaVendaControllerObj = new MapaVendaController;

        $data_escolhida = '01/'.$mes_ano;

        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_verificacao_hospitalar_outfits = Carbon::createFromFormat('d/m/Y', '01/09/2020')->setTime(0,0,0);
        $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();        

        $excecoes = $mapaVendaControllerObj->excecoes($data_escolhida_inicial, $data_escolhida_final);

        $usuarios_workwear = $mapaVendaControllerObj->getUserWorkwear($data_escolhida_inicial);
        $usuarios_denim = $mapaVendaControllerObj->getUserDenim($data_escolhida_inicial);
        $usuarios_hospitalar = $mapaVendaControllerObj->getUserHospitalar($data_escolhida_inicial);
        $clientes_bionexo = $mapaVendaControllerObj->clientesBionexo();

        $query = UnidadeNegocio::select();

        $query->with(['metas' => function($query) use($data_escolhida_inicial, $array_vendedores){
            $query->where('data', $data_escolhida_inicial);
            $query->with(['usuarios' => function ($query) use($array_vendedores){
                $query->whereHas('detalhesUsuario', function ($query) use($array_vendedores){
                    $query->whereIn('codigo_representante', $array_vendedores);
                });
                $query->with(['detalhesUsuario']);
            }]);
        }]);
        
        $query->whereHas('metas', function($query) use($data_escolhida_inicial, $array_vendedores){
            $query->where('data', $data_escolhida_inicial);
            if(!empty($array_vendedores)){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($array_vendedores){
                    $query->whereIn('codigo_representante', $array_vendedores);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        });
        $result = $query->get();

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, unidade, documento, cliente_codigo, estabelecimento, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $mapaVendaControllerObj->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $mapaVendaControllerObj->cfop_devolucao);
        if(!empty($array_vendedores)){
            $query_movimentacao->whereIn('vendedor', $array_vendedores);
        }
        $query_movimentacao->groupBy('vendedor', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'estabelecimento', 'cliente_codigo');
        $result_movimentacao = $query_movimentacao->get();

        $movimentacoes_devolucao = [];
        foreach($result_movimentacao as $movimentacao){
            $bionexo = false;
            if(!empty($movimentacao->cliente)){
                if(in_array($movimentacao->cliente->cpf_cnpj, $clientes_bionexo)){
                    $bionexo = true;
                }
            }
            $movimentacoes_devolucao[$movimentacao->vendedor][] = [
                'vendedor' => $movimentacao->vendedor,
                'marca' => $movimentacao->marca,
                'linha' => $movimentacao->linha,
                'grupo' => $movimentacao->grupo,
                'preco' => $movimentacao->preco_total,
                'unidade' => $movimentacao->unidade,
                'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
                'bionexo' => $bionexo,
            ];
        }

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, unidade, documento, cliente_codigo, estabelecimento, sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end ) as preco_total');
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->with(['detalhesVendedor']);
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $mapaVendaControllerObj->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $mapaVendaControllerObj->cfop_venda);
        if(!empty($array_vendedores)){
            $query_movimentacao->whereIn('vendedor', $array_vendedores);
        }
        $query_movimentacao->groupBy('vendedor', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'estabelecimento', 'cliente_codigo');
        $result_movimentacao = $query_movimentacao->get();
        
        $movimentacoes = [];
        foreach($result_movimentacao as $movimentacao){
            $bionexo = false;
            if(!empty($movimentacao->cliente)){
                if(in_array($movimentacao->cliente->cpf_cnpj, $clientes_bionexo)){
                    $bionexo = true;
                }
            }
            $movimentacoes[$movimentacao->vendedor][] = [
                'vendedor' => $movimentacao->vendedor,
                'nome' => empty($movimentacao->detalhesVendedor)? '' : $movimentacao->detalhesVendedor->name,
                'marca' => $movimentacao->marca,
                'linha' => $movimentacao->linha,
                'grupo' => $movimentacao->grupo,
                'preco' => $movimentacao->preco_total,
                'unidade' => $movimentacao->unidade,
                'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
                'bionexo' => $bionexo,
            ];
        }

        $unidades = [];

        $total_meta = 0;
        $total_valor = 0;
        $vendedores = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        $unidade_negocio_id = 0;
        
        if($data_escolhida_inicial->gte($data_verificacao)){
            $usuarios_fashion_3 = $mapaVendaControllerObj->getUserFashion3($data_escolhida_inicial);
            $usuarios_fashion_2 = $mapaVendaControllerObj->getUserFashion2($data_escolhida_inicial);
            if($data_escolhida_inicial->gte($data_verificacao_hospitalar_outfits)){
                $usuarios_outfites_confeccionados = $mapaVendaControllerObj->getUserOutfitsConfeccionados($data_escolhida_inicial); 
            }else{
                $usuarios_magazine = $mapaVendaControllerObj->getUserMagazine($data_escolhida_inicial);
            }
            $usuarios_magazine = $mapaVendaControllerObj->getUserMagazine($data_escolhida_inicial);
            foreach($result as $unidade_negocio){
                $valor = 0;
                $meta = 0;
                if($unidade_negocio->metas->count() > 0){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        if(!empty($usuario->detalhesUsuario)){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            $meta += $usuario->metas;
                            $unidade_negocio_id = $unidade_negocio->id;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    $unidade_negocio_id = $unidade_negocio->id;
                                    if(!empty($excecoes[$movimentacao['documento']])){
                                        $unidade_negocio_id = $excecoes[$movimentacao['documento']];
                                    }else if($data_escolhida_inicial->gte($data_verificacao_hospitalar_outfits)){
                                        if(in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos']) && $movimentacao['bionexo']){
                                            $unidade_negocio_id = 16;//OutfitsConfeccionados
                                        }else if(in_array($codigo_vendedor, $usuarios_fashion_2['codigos']) && in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos'])){
                                            if(in_array($movimentacao['unidade'], $this->unidade_metro)){
                                                $unidade_negocio_id = 9;//Fashion 2
                                            }else{
                                                $unidade_negocio_id = 16;//OutfitsConfeccionados
                                            }
                                        }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos'])){
                                            if(in_array($movimentacao['unidade'], $this->unidade_metro)){
                                                $unidade_negocio_id = 15;//Fashion 3
                                            }else{
                                                $unidade_negocio_id = 16;//OutfitsConfeccionados
                                            }
                                        }
                                    }else{
                                        if(in_array($codigo_vendedor, $usuarios_magazine['codigos']) && $movimentacao['bionexo']){
                                            $unidade_negocio_id = 10;//FashionMagazine
                                        }else if(in_array($codigo_vendedor, $usuarios_fashion_2['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                            if(in_array($movimentacao['unidade'], $this->unidade_metro)){
                                                $unidade_negocio_id = 9;//Fashion 2
                                            }else{
                                                $unidade_negocio_id = 7;//Hospitalar
                                            }
                                        }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                            if(in_array($movimentacao['unidade'], $this->unidade_metro)){
                                                $unidade_negocio_id = 15;//Fashion 3
                                            }else{
                                                $unidade_negocio_id = 7;//Hospitalar
                                            }
                                        }
                                    }
    
                                    $valor += $movimentacao['preco'];
    
                                    if(empty($vendedores[$codigo_vendedor][$unidade_negocio_id])){
                                        $vendedores[$codigo_vendedor][$unidade_negocio_id] = [
                                            'vendedor_codigo' => $codigo_vendedor,
                                            'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                            'meta' => $usuario->metas,
                                            'valor' => $movimentacao['preco'],
                                        ];
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        $vendedores[$codigo_vendedor][$unidade_negocio_id]['valor'] += $movimentacao['preco'];
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }
    
                                    unset($movimentacoes[$codigo_vendedor][$key]);
    
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                    $unidade_negocio_id = $unidade_negocio->id;
                                    if(!empty($excecoes[$movimentacao['documento']])){
                                        $unidade_negocio_id = $excecoes[$movimentacao['documento']];
                                    }else if($data_escolhida_inicial->gte($data_verificacao_hospitalar_outfits)){
                                        if(in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos']) && $movimentacao_devolucao['bionexo']){
                                            $unidade_negocio_id = 16;//OutfitsConfeccionados
                                        }else if(in_array($codigo_vendedor, $usuarios_fashion_2['codigos']) && in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos'])){
                                            if(in_array($movimentacao_devolucao['unidade'], $this->unidade_metro)){
                                                $unidade_negocio_id = 9;//Fashion 2
                                            }else{
                                                $unidade_negocio_id = 16;//OutfitsConfeccionados
                                            }
                                        }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos'])){
                                            if(in_array($movimentacao_devolucao['unidade'], $this->unidade_metro)){
                                                $unidade_negocio_id = 15;//Fashion 3
                                            }else{
                                                $unidade_negocio_id = 16;//OutfitsConfeccionados
                                            }
                                        }
                                    }else{
                                        if(in_array($codigo_vendedor, $usuarios_magazine['codigos']) && $movimentacao_devolucao['bionexo']){
                                            $unidade_negocio_id = 10;//FashionMagazine
                                        }else if(in_array($codigo_vendedor, $usuarios_fashion_2['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                            if(in_array($movimentacao_devolucao['unidade'], $this->unidade_metro)){
                                                $unidade_negocio_id = 9;//Fashion 2
                                            }else{
                                                $unidade_negocio_id = 7;//Hospitalar
                                            }
                                        }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                            if(in_array($movimentacao_devolucao['unidade'], $this->unidade_metro)){
                                                $unidade_negocio_id = 15;//Fashion 3
                                            }else{
                                                $unidade_negocio_id = 7;//Hospitalar
                                            }
                                        }
                                    }
        
                                    $valor -= $movimentacao_devolucao['preco'];
    
                                    if(empty($vendedores[$codigo_vendedor][$unidade_negocio_id])){
                                        $vendedores[$codigo_vendedor][$unidade_negocio_id] = [
                                            'vendedor_codigo' => $codigo_vendedor,
                                            'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                            'meta' => $usuario->metas,
                                            'valor' => 0,
                                        ];
                                    }
                                    $vendedores[$codigo_vendedor][$unidade_negocio_id]['valor'] -= $movimentacao_devolucao['preco'];
    
                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                }
                            }
                            if(empty($vendedores[$codigo_vendedor][$unidade_negocio_id])){
                                $vendedores[$codigo_vendedor][$unidade_negocio_id] = [
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'valor' => 0,
                                ];
                            }
                        }
                    }
                }
                
                $total_meta += $meta;
                $total_valor += $valor;
            }
        }else{
            foreach($result as $unidade_negocio){
                $valor = 0;
                $meta = 0;
                if($unidade_negocio->metas->count() > 0){
                    if($unidade_negocio->unidade !== 'DENIM' && $unidade_negocio->unidade !== 'WORKWEAR' && $unidade_negocio->unidade !== 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    $liberado = false;
                                    if(!empty($excecoes[$movimentacao['documento']])){
                                        if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_workwear['codigos']) && in_array($codigo_vendedor, $usuarios_denim['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(($movimentacao['marca'] === 'IMPORTADOS' || $movimentacao['marca'] === "TEXTIL MN") && (($movimentacao['linha'] !== 'DENIM IMPORTADO' && ($movimentacao['linha'] !== 'INDIGO E SARJAS' || $movimentacao['marca'] !== 'IMPORTADOS')) || ($movimentacao['grupo'] === 'DENIM SANIBEL PLUS' || $movimentacao['grupo'] === 'DENIM SANIBEL')) && substr_count($movimentacao['linha'], 'HOSPITALAR') === 0){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_workwear['codigos']) && in_array($codigo_vendedor, $usuarios_denim['codigos'])){                     
                                        if(($movimentacao['marca'] === 'IMPORTADOS' || $movimentacao['marca'] === "TEXTIL MN") && (($movimentacao['linha'] !== 'DENIM IMPORTADO' && ($movimentacao['linha'] !== 'INDIGO E SARJAS' || $movimentacao['marca'] !== 'IMPORTADOS')) || ($movimentacao['grupo'] === 'DENIM SANIBEL PLUS' || $movimentacao['grupo'] === 'DENIM SANIBEL'))){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_workwear['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(($movimentacao['marca'] === 'IMPORTADOS' || $movimentacao['marca'] === "TEXTIL MN") && substr_count($movimentacao['linha'], 'HOSPITALAR') === 0){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_denim['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if((($movimentacao['linha'] !== 'DENIM IMPORTADO' && ($movimentacao['linha'] !== 'INDIGO E SARJAS' || $movimentacao['marca'] !== 'IMPORTADOS')) || ($movimentacao['grupo'] === 'DENIM SANIBEL PLUS' || $movimentacao['grupo'] === 'DENIM SANIBEL')) && substr_count($movimentacao['linha'], 'HOSPITALAR') === 0){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_workwear['codigos'])){
                                        if(($movimentacao['marca'] === 'IMPORTADOS' || $movimentacao['marca'] === "TEXTIL MN")){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_denim['codigos'])){
                                        if(($movimentacao['linha'] !== 'DENIM IMPORTADO' && ($movimentacao['linha'] !== 'INDIGO E SARJAS' || $movimentacao['marca'] !== 'IMPORTADOS')) || ($movimentacao['grupo'] === 'DENIM SANIBEL PLUS' || $movimentacao['grupo'] === 'DENIM SANIBEL')){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(substr_count($movimentacao['linha'], 'HOSPITALAR') === 0){
                                            $liberado = true;
                                        }
                                    }else{
                                        $liberado = true;
                                    }
        
                                    if($liberado){
                                        $valor += $movimentacao['preco'];
    
                                        if(empty($vendedores[$codigo_vendedor][$unidade_negocio->id])){
                                            $vendedores[$codigo_vendedor][$unidade_negocio->id] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$codigo_vendedor][$unidade_negocio->id]['valor'] += $movimentacao['preco'];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
        
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                    $liberado = false;
                                    if(!empty($excecoes[$movimentacao['documento']])){
                                        if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_workwear['codigos']) && in_array($codigo_vendedor, $usuarios_denim['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(($movimentacao_devolucao['marca'] === 'IMPORTADOS' || $movimentacao_devolucao['marca'] === "TEXTIL MN") && (($movimentacao_devolucao['linha'] !== 'DENIM IMPORTADO' && ($movimentacao_devolucao['linha'] !== 'INDIGO E SARJAS' || $movimentacao_devolucao['marca'] !== 'IMPORTADOS')) || ($movimentacao_devolucao['grupo'] === 'DENIM SANIBEL PLUS' || $movimentacao_devolucao['grupo'] === 'DENIM SANIBEL')) && substr_count($movimentacao_devolucao['linha'], 'HOSPITALAR') === 0){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_workwear['codigos']) && in_array($codigo_vendedor, $usuarios_denim['codigos'])){
                                        if(($movimentacao_devolucao['marca'] === 'IMPORTADOS' || $movimentacao_devolucao['marca'] === "TEXTIL MN") && (($movimentacao_devolucao['linha'] !== 'DENIM IMPORTADO' && ($movimentacao_devolucao['linha'] !== 'INDIGO E SARJAS' || $movimentacao_devolucao['marca'] !== 'IMPORTADOS')) || ($movimentacao_devolucao['grupo'] === 'DENIM SANIBEL PLUS' || $movimentacao_devolucao['grupo'] === 'DENIM SANIBEL'))){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_workwear['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(($movimentacao_devolucao['marca'] === 'IMPORTADOS' || $movimentacao_devolucao['marca'] === "TEXTIL MN") && substr_count($movimentacao_devolucao['linha'], 'HOSPITALAR') === 0){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_denim['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if((($movimentacao_devolucao['linha'] !== 'DENIM IMPORTADO' && ($movimentacao_devolucao['linha'] !== 'INDIGO E SARJAS' || $movimentacao_devolucao['marca'] !== 'IMPORTADOS')) || ($movimentacao_devolucao['grupo'] === 'DENIM SANIBEL PLUS' || $movimentacao_devolucao['grupo'] === 'DENIM SANIBEL')) && substr_count($movimentacao_devolucao['linha'], 'HOSPITALAR') === 0){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_workwear['codigos'])){
                                        if(($movimentacao_devolucao['marca'] === 'IMPORTADOS' || $movimentacao_devolucao['marca'] === "TEXTIL MN")){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_denim['codigos'])){
                                        if(($movimentacao_devolucao['linha'] !== 'DENIM IMPORTADO' && ($movimentacao_devolucao['linha'] !== 'INDIGO E SARJAS' || $movimentacao_devolucao['marca'] !== 'IMPORTADOS')) || ($movimentacao_devolucao['grupo'] === 'DENIM SANIBEL PLUS' || $movimentacao_devolucao['grupo'] === 'DENIM SANIBEL')){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(substr_count($movimentacao_devolucao['linha'], 'HOSPITALAR') === 0){
                                            $liberado = true;
                                        }
                                    }else{
                                        $liberado = true;
                                    }
                                    if($liberado){
                                        $valor -= $movimentacao_devolucao['preco'];
    
                                        if(empty($vendedores[$codigo_vendedor][$unidade_negocio->id])){
                                            $vendedores[$codigo_vendedor][$unidade_negocio->id] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'valor' => 0,
                                            ];
                                        }
                                        $vendedores[$codigo_vendedor][$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];
    
                                        unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'DENIM'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    $liberado = false;
                                    if(!empty($excecoes[$movimentacao['documento']])){
                                        if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                            $liberado = true;
                                        }
                                    }else if(($movimentacao['linha'] === 'DENIM IMPORTADO' || ($movimentacao['linha'] === 'INDIGO E SARJAS' && $movimentacao['marca'] === 'IMPORTADOS')) && ($movimentacao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao['grupo'] !== 'DENIM SANIBEL')){
                                        $liberado = true;
                                    }
                                    if($liberado){
                                            $valor += $movimentacao['preco'];
    
                                            $id_denim = $unidade_negocio->id;
                                            
                                            if(empty($vendedores[$codigo_vendedor][$unidade_negocio->id])){
                                                $vendedores[$codigo_vendedor][$unidade_negocio->id] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                    'meta' => $usuario->metas,
                                                    'valor' => $movimentacao['preco']
                                                ];
            
                                                unset($movimentacoes[$codigo_vendedor][$key]);
                                            }else{
                                                $vendedores[$codigo_vendedor][$unidade_negocio->id]['valor'] += $movimentacao['preco'];
                                                unset($movimentacoes[$codigo_vendedor][$key]);
                                            }
            
                                            if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                                    if(($movimentacao_devolucao['linha'] === 'DENIM IMPORTADO' || ($movimentacao_devolucao['linha'] === 'INDIGO E SARJAS' && $movimentacao_devolucao['marca'] === 'IMPORTADOS')) && ($movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL')){
                                                        $valor -= $movimentacao_devolucao['preco'];
    
                                                        $vendedores[$codigo_vendedor][$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];
            
                                                        unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                    }
                                                }
                                            }
                
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        if(empty($vendedores[$codigo_vendedor][$unidade_negocio->id])){
                                            $vendedores[$codigo_vendedor][$unidade_negocio->id] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'valor' => 0,
                                            ];
                                        }     
                                    }
                                }
                            }else{
                                $vendedores[$codigo_vendedor][$unidade_negocio->id] = [
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'valor' => 0,
                                ];
                            }
                        }
                    }else if($unidade_negocio->unidade === 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    $liberado = false;
                                    if(!empty($excecoes[$movimentacao['documento']])){
                                        if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                            $liberado = true;
                                        }
                                    }else if(substr_count($movimentacao['linha'], "HOSPITALAR") > 0 && !in_array($movimentacao['unidade'], $mapaVendaControllerObj->unidade_metro)){
                                        $liberado = true;
                                    }
                                    if($liberado){
                                        $valor += $movimentacao['preco'];
    
                                        $id_hospitalar = $unidade_negocio->id;
        
                                        if(empty($vendedores[$codigo_vendedor][$unidade_negocio->id])){
                                            $vendedores[$codigo_vendedor][$unidade_negocio->id] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$codigo_vendedor][$unidade_negocio->id]['valor'] += $movimentacao['preco'];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
        
                                        if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                                if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") > 0){
                                                    $valor -= $movimentacao_devolucao['preco'];
    
                                                    $vendedores[$codigo_vendedor][$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];
        
                                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                }
                                            }
                                        }
            
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        if(empty($vendedores[$codigo_vendedor][$unidade_negocio->id])){
                                            $vendedores[$codigo_vendedor][$unidade_negocio->id] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'valor' => 0,
                                            ];
                                        }     
                                    }
                                }
                            }else{
                                $vendedores[$codigo_vendedor][$unidade_negocio->id] = [
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'valor' => 0,
                                ];
                            }
                        }
                    }else{
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    $liberado = false;
                                    if(!empty($excecoes[$movimentacao['documento']])){
                                        if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                         if((substr_count($movimentacao['linha'], "HOSPITALAR") === 0 || in_array($movimentacao['unidade'], $mapaVendaControllerObj->unidade_metro)) && ($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN")){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_denim['codigos'])){
                                        if(($movimentacao['linha'] !== "DENIM IMPORTADO" && ($movimentacao['linha'] !== 'INDIGO E SARJAS' && $movimentacao['marca'] !== 'IMPORTADOS')) && ($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN")){
                                            $liberado = true;
                                        }
                                    }else if($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN"){
                                        $liberado = true;
                                    }
                                    if($liberado){
                                        $valor += $movimentacao['preco'];
    
                                        $id_workwear = $unidade_negocio->id;
                                        if(empty($vendedores[$codigo_vendedor][$unidade_negocio->id])){
                                            $vendedores[$codigo_vendedor][$unidade_negocio->id] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$codigo_vendedor][$unidade_negocio->id]['valor'] += $movimentacao['preco'];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
        
                                        if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                                $liberado = false;
                                                if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                                    if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") === 0 && ($movimentacao_devolucao['marca'] !== 'IMPORTADOS' && $movimentacao_devolucao['marca'] !== "TEXTIL MN")){
                                                        $liberado = true;
                                                    }
                                                }else if($movimentacao_devolucao['marca'] !== 'IMPORTADOS' && $movimentacao_devolucao['marca'] !== "TEXTIL MN"){
                                                    $liberado = true;
                                                }
                                                if($liberado){
                                                    $valor -= $movimentacao_devolucao['preco'];
    
                                                    $vendedores[$codigo_vendedor][$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];
        
                                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                }
                                            }
                                        }
        
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        if(empty($vendedores[$codigo_vendedor][$unidade_negocio->id])){
                                            $vendedores[$codigo_vendedor][$unidade_negocio->id] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'valor' => 0,
                                            ];
                                        }     
                                    }
                                }
                            }else{
                                $vendedores[$codigo_vendedor][$unidade_negocio->id] = [
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'valor' => 0,
                                ];
                            }
                        }
                    }
                }
                
                $total_meta += $meta;
                $total_valor += $valor;
            }
        }

        return $vendedores;
    }

    private function verificacaoGerente(){
        $query = UnidadeNegocio::select();
        $query->where('users_id', Auth::id());
        $result = $query->get();

        $unidades = [];

        foreach($result as $unidade){
            $unidades[] = $unidade->id;
        }

        return $unidades;
    }
}
