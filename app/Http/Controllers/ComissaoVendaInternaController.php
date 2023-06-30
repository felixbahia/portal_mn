<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\UnidadeNegocio;
use App\UnidadeNegocioMeta;
use App\User;
use App\Movimentacao;
use App\ClienteNasajon;
use App\ClienteBionexo;
use App\TitulosPagosNasajon;

use App\Http\Controllers\UnidadeNegocioMetaController;
use App\Http\Controllers\UserController;

use Illuminate\Support\Facades\DB;

class ComissaoVendaInternaController extends Controller
{
    private $cfop_devolucao = ['1201', '1202', '2201', '2202'];

    // private $cfop_venda = ['5922', '5949', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101'];
    private $cfop_venda = ['5922', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101'], '5104', '6104';

    private $unidade_metro = ['m', 'M', 'M...','METRO', 'METROS', 'MT', 'MTS', 'MTS.'];
    
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ComissaoVendaInterna") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ComissaoVendaInterna');

        $data = Carbon::now();
        $data = $data->format('m/Y');

        $unidades_negocios = $this->getUnidadeNegocio();
        $representantes = $this->getRepresentante();

        return view('programs.comissao_venda_interna.index')->with(['data' => $data, 'unidades_negocios' => $unidades_negocios, 'representantes' => $representantes]);
    }

    private function getUnidadeNegocio(){
        $query = UnidadeNegocio::select();
        $query->orderBy('unidade');
        $result = $query->get();

        $unidades_negocios = [];
        foreach($result as $value){
            $unidades_negocios[$value->id] = $value->unidade;
        }

        return $unidades_negocios;
    }

    private function getRepresentante(){
        $representantes_busca = User::select('codigo_representante', 'name')->where('codigo_representante', '!=', '')->where('tipo_usuario_id', 16)->orderBy('codigo_representante')->get()->toArray();
		$representantes = [];
		
		foreach ($representantes_busca as $key => $value) {
			$representantes[$value['codigo_representante']] = $value['codigo_representante']." - ".strtoupper($value['name']);
        }
        
        return $representantes;
    }

    private function getUltimoDiaMes($mes, $ano){
        $ultimo_dia = date("t", mktime(0,0,0,$mes,'01',$ano));

        return $ultimo_dia;
    }

    private function getUserWorkwear($data_escolhida_inicial){
        $query = UnidadeNegocio::select();
        $query->where('unidade', 'ilike', 'workwear');
        $query->with(['metas' => function($query) use($data_escolhida_inicial){
            $query->where('data', $data_escolhida_inicial);
        }]);
        $result = $query->first();

        $detalhes_vendedores = [];
        $codigos = [];

        $metas = [];
        if(!empty($result)){
            if($result->metas->count() > 0){
                foreach($result->metas[0]->usuarios as $usuario){
                    $metas[$usuario->users_id] = $usuario->metas;
                }
        
                $query_usuarios = User::select();
                $query_usuarios->whereIn('id', $result->metas[0]->usuarios->pluck('users_id'));
                $result_usuarios = $query_usuarios->get();        
        
                foreach($result_usuarios as $usuario){
                    if(!empty($usuario->codigo_representante)){
                        $detalhes_vendedores[] = [
                            'codigo' => $usuario->codigo_representante,
                            'nome' => $usuario->name,
                            'meta' => $metas[$usuario->id]
                        ];
    
                        $codigos[] = $usuario->codigo_representante;
                    }
                }
            }
        }

        $codigos_vendedores = [
            'detalhes' => $detalhes_vendedores,
            'codigos' => $codigos,
        ];
        
        return $codigos_vendedores;
    }

    private function getUserDenim($data_escolhida_inicial){
        $query = UnidadeNegocio::select();
        $query->where('unidade', 'ilike', 'denim');
        $query->with(['metas' => function($query) use($data_escolhida_inicial){
            $query->where('data', $data_escolhida_inicial);
        }]);
        $result = $query->first();

        $detalhes_vendedores = [];
        $codigos = [];

        $metas = [];
        if(!empty($result)){
            if($result->metas->count() > 0){
                foreach($result->metas[0]->usuarios as $usuario){
                    $metas[$usuario->users_id] = $usuario->metas;
                }
        
                $query_usuarios = User::select();
                $query_usuarios->whereIn('id', $result->metas[0]->usuarios->pluck('users_id'));
                $result_usuarios = $query_usuarios->get();        
        
                foreach($result_usuarios as $usuario){
                    if(!empty($usuario->codigo_representante)){
                        $detalhes_vendedores[] = [
                            'codigo' => $usuario->codigo_representante,
                            'nome' => $usuario->name,
                            'meta' => $metas[$usuario->id]
                        ];

                        $codigos[] = $usuario->codigo_representante;
                    }
                }
            }
        }

        $codigos_vendedores = [
            'detalhes' => $detalhes_vendedores,
            'codigos' => $codigos,
        ];

        return $codigos_vendedores;
    }

    private function getUserHospitalar($data_escolhida_inicial){
        $query = UnidadeNegocio::select();
        $query->where('unidade', 'ilike', 'hospitalar');
        $query->with(['metas' => function($query) use($data_escolhida_inicial){
            $query->where('data', $data_escolhida_inicial);
        }]);
        $result = $query->first();

        $detalhes_vendedores = [];
        $codigos = [];

        $metas = [];
        if(!empty($result)){
            if($result->metas->count() > 0){
                foreach($result->metas[0]->usuarios as $usuario){
                    $metas[$usuario->users_id] = $usuario->metas;
                }
        
                $query_usuarios = User::select();
                $query_usuarios->whereIn('id', $result->metas[0]->usuarios->pluck('users_id'));
                $result_usuarios = $query_usuarios->get();        
        
                foreach($result_usuarios as $usuario){
                    if(!empty($usuario->codigo_representante)){
                        $detalhes_vendedores[] = [
                            'codigo' => $usuario->codigo_representante,
                            'nome' => $usuario->name,
                            'meta' => $metas[$usuario->id]
                        ];

                        $codigos[] = $usuario->codigo_representante;
                    }
                }
            }
        }

        $codigos_vendedores = [
            'detalhes' => $detalhes_vendedores,
            'codigos' => $codigos,
        ];

        return $codigos_vendedores;
    }

    private function getUserFashion3($data_escolhida_inicial){
        $query = UnidadeNegocio::select();
        $query->where('unidade', 'ilike', 'FASHION 3');
        $query->with(['metas' => function($query) use($data_escolhida_inicial){
            $query->where('data', $data_escolhida_inicial);
        }]);
        $result = $query->first();

        $detalhes_vendedores = [];
        $codigos = [];

        $metas = [];
        if(!empty($result)){
            if($result->metas->count() > 0){
                foreach($result->metas[0]->usuarios as $usuario){
                    $metas[$usuario->users_id] = $usuario->metas;
                }
        
                $query_usuarios = User::select();
                $query_usuarios->whereIn('id', $result->metas[0]->usuarios->pluck('users_id'));
                $result_usuarios = $query_usuarios->get();        
        
                foreach($result_usuarios as $usuario){
                    if(!empty($usuario->codigo_representante)){
                        $detalhes_vendedores[] = [
                            'codigo' => $usuario->codigo_representante,
                            'nome' => $usuario->name,
                            'meta' => $metas[$usuario->id]
                        ];
    
                        $codigos[] = $usuario->codigo_representante;
                    }
                }
            }
        }

        $codigos_vendedores = [
            'detalhes' => $detalhes_vendedores,
            'codigos' => $codigos,
        ];
        
        return $codigos_vendedores;
    }

    private function getUserMagazine($data_escolhida_inicial){
        $query = UnidadeNegocio::select();
        $query->where('unidade', 'ilike', 'MAGAZINE');
        $query->with(['metas' => function($query) use($data_escolhida_inicial){
            $query->where('data', $data_escolhida_inicial);
        }]);
        $result = $query->first();

        $detalhes_vendedores = [];
        $codigos = [];

        $metas = [];
        if(!empty($result)){
            if($result->metas->count() > 0){
                foreach($result->metas[0]->usuarios as $usuario){
                    $metas[$usuario->users_id] = $usuario->metas;
                }
        
                $query_usuarios = User::select();
                $query_usuarios->whereIn('id', $result->metas[0]->usuarios->pluck('users_id'));
                $result_usuarios = $query_usuarios->get();        
        
                foreach($result_usuarios as $usuario){
                    if(!empty($usuario->codigo_representante)){
                        $detalhes_vendedores[] = [
                            'codigo' => $usuario->codigo_representante,
                            'nome' => $usuario->name,
                            'meta' => $metas[$usuario->id]
                        ];
    
                        $codigos[] = $usuario->codigo_representante;
                    }
                }
            }
        }

        $codigos_vendedores = [
            'detalhes' => $detalhes_vendedores,
            'codigos' => $codigos,
        ];
        
        return $codigos_vendedores;
    }

    private function clientesExcluido(){
        $clientes_exluir = ClienteNasajon::select('codigo')
            ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
            ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
            ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        return $clientes_exluir;
    }

    private function clientesBionexo(){
        $clientes_bionexo = ClienteBionexo::select()->get()->pluck('cpf_cnpj')->toArray();

        return $clientes_bionexo;
    }

    private function ajusteArrayParaValores($array){
        if(is_array($array)){
            foreach($array as $key => $value){
                if(is_array($value)){
                    $array[$key] = $this->ajusteArrayParaValores($value);
                }else{
                    if(is_numeric($value)){
                        if(substr_count($key, "codigo") === 0){
                            if(substr_count($key, "porcetagem") === 0){
                                $array[$key] = empty($value)? '': parserValor($value);
                            }else{
                                $array[$key] = empty($value)? '': parserValor($value).'%';
                            }                            
                        }
                    }else{
                        $array[$key] = $value;
                    }
                }
            }
        }
        return $array;
    }

    public function filtro(Request $request, $retorno_array = false){
        $fields = $request->only('mes_ano', 'unidade_negocio', 'vendedor');

        $query = UnidadeNegocio::select();
        
        $data_escolhida = '01/'.$fields['mes_ano'];
        $separado_data = explode('/', $data_escolhida);
        $ultimo_dia = $this->getUltimoDiaMes($separado_data[1], $separado_data[2]);

        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$fields['mes_ano'])->setTime(0,0,0);

        $usuarios_workwear = $this->getUserWorkwear($data_escolhida_inicial);
        $usuarios_denim = $this->getUserDenim($data_escolhida_inicial);
        $usuarios_hospitalar = $this->getUserHospitalar($data_escolhida_inicial);
        $clientes_bionexo = $this->clientesBionexo();

        $query->with(['detalhesUsuarioResponsavel']);
        $query->with(['metas' => function($query) use($data_escolhida_inicial, $fields){
            $query->where('data', $data_escolhida_inicial);
            if(!empty($fields['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($fields){
                    $query->where('codigo_representante', $fields['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);


        if(!empty($fields['unidade_negocio'])){
            $query->where('id', $fields['unidade_negocio']);
        }

        $result = $query->get();
        
        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, documento, unidade, cliente_codigo, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->groupBy('vendedor', 'marca', 'linha', 'grupo', 'documento', 'unidade', 'cliente_codigo');
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
                'documento' =>$movimentacao->documento,
                'unidade' => $movimentacao->unidade,
                'bionexo' => $bionexo,
            ];
        }

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, documento, unidade, cliente_codigo, sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end ) as preco_total');
        $query_movimentacao->with(['detalhesVendedor']);
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);

        $query_movimentacao->groupBy('vendedor', 'marca', 'linha', 'grupo', 'documento', 'unidade', 'cliente_codigo');
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
                'documento' => $movimentacao->documento,
                'unidade' => $movimentacao->unidade,
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
        $valores_totais = []; 
        $gerentes = [];

        if($data_escolhida_inicial->gte($data_verificacao)){
            $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
            $usuarios_magazine = $this->getUserMagazine($data_escolhida_inicial);
            foreach($result as $unidade_negocio){
                $valor = 0;
                $meta = 0;
                if($unidade_negocio->metas->count() > 0){
                    $gerentes[$unidade_negocio->id] = [
                        'id' => encrypt($unidade_negocio->id),
                        'unidade' => $unidade_negocio->unidade,
                        'tipo' => 'Gerente',
                        'id_usuario' => $unidade_negocio->detalhesUsuarioResponsavel->id,
                        'vendedor' => $unidade_negocio->detalhesUsuarioResponsavel->name,
                        'folha_matricula_codigo' => $unidade_negocio->detalhesUsuarioResponsavel->folha_matricula,
                        'folha_lotacao_codigo' => $unidade_negocio->detalhesUsuarioResponsavel->folha_lotacao,
                        'meta' => $unidade_negocio->metas[0]->valor,
                        'faturado' => 0,
                        'atingimento' => 0,
                        'comissao_porcetagem' => 0,
                        'valor_individual' => 0,
                        'valor_equipe' => 0,
                        'valor_total' => 0,
                    ];
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        
                        if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            $meta += $usuario->metas;
                            foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                $unidade_negocio_id = $unidade_negocio->id;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    $unidade_negocio_id = $excecoes[$movimentacao['documento']];
                                }else if(in_array($codigo_vendedor, $usuarios_magazine['codigos']) && $movimentacao['bionexo']){
                                    $unidade_negocio_id = 10;//FashionMagazine
                                }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                    if(in_array($movimentacao['unidade'], $this->unidade_metro)){
                                        $unidade_negocio_id = 15;//Fashion 3
                                    }else{
                                        $unidade_negocio_id = 7;//Hospitalar
                                    }
                                }
                                if(empty($valores_totais[$unidade_negocio_id])){
                                    $valores_totais[$unidade_negocio_id] = [
                                        'unidade' => $unidade_negocio->unidade,
                                        'valor' => 0,
                                        'quantidade_membro' => 1,
                                    ];
                                }else{
                                    $valores_totais[$unidade_negocio_id]['quantidade_membro']++;
                                }
                                if(!empty($fields['unidade_negocio'])){       
                                    if(intval($fields['unidade_negocio']) === $unidade_negocio_id){
                                        $valor += $movimentacao['preco'];
                                        $valores_totais[$unidade_negocio_id]['valor'] += $movimentacao['preco'];

                                        if(empty($vendedores[$unidade_negocio_id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio_id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                                'valor_codigo' => $movimentacao['preco'],
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                            $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
        
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }
                                }else{
                                    $valor += $movimentacao['preco'];
                                    $valores_totais[$unidade_negocio_id]['valor'] += $movimentacao['preco'];

                                    if(empty($vendedores[$unidade_negocio_id][$codigo_vendedor])){
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor] = [
                                            'unidade' => $unidade_negocio->unidade,
                                            'vendedor_codigo' => $codigo_vendedor,
                                            'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                            'meta' => $usuario->metas,
                                            'meta_codigo' => $usuario->metas,
                                            'valor' => $movimentacao['preco'],
                                            'valor_codigo' => $movimentacao['preco'],
                                            'atingimento_metal_porcetagem' => 0,
                                            'diferenca_meta' => 0,
                                        ];
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }
    
                                    unset($movimentacoes[$codigo_vendedor][$key]);
                                }
                            }
                        }
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                $unidade_negocio_id = $unidade_negocio->id;
                                if(!empty($excecoes[$movimentacao_devolucao['documento']])){
                                    $unidade_negocio_id = $excecoes[$movimentacao_devolucao['documento']];
                                }else if(in_array($codigo_vendedor, $usuarios_magazine['codigos']) && $movimentacao_devolucao['bionexo']){
                                    $unidade_negocio_id = 10;//FashionMagazine
                                }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                    if(in_array($movimentacao_devolucao['unidade'], $this->unidade_metro)){
                                        $unidade_negocio_id = 15;//Fashion 3
                                    }else{
                                        $unidade_negocio_id = 7;//Hospitalar
                                    }
                                }
                                if(empty($valores_totais[$unidade_negocio_id])){
                                    $valores_totais[$unidade_negocio_id] = [
                                        'unidade' => $unidade_negocio->unidade,
                                        'valor' => 0,
                                        'quantidade_membro' => 1,
                                    ];
                                }else{
                                    $valores_totais[$unidade_negocio_id]['quantidade_membro']++;
                                }
                                if(!empty($fields['unidade_negocio'])){
                                    if($fields['unidade_negocio'] === $unidade_negocio_id){
                                        $valores_totais[$unidade_negocio_id]['valor'] -= $movimentacao_devolucao['preco'];
                                        $valor -= $movimentacao_devolucao['preco'];

                                        if(empty($vendedores[$unidade_negocio_id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio_id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => 0,
                                                'valor_codigo' => 0,
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                        }
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                    }
                                }else{
                                    $valores_totais[$unidade_negocio_id]['valor'] -= $movimentacao_devolucao['preco'];
                                    $valor -= $movimentacao_devolucao['preco'];

                                    if(empty($vendedores[$unidade_negocio_id][$codigo_vendedor])){
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor] = [
                                            'unidade' => $unidade_negocio->unidade,
                                            'vendedor_codigo' => $codigo_vendedor,
                                            'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                            'meta' => $usuario->metas,
                                            'meta_codigo' => $usuario->metas,
                                            'valor' => 0,
                                            'valor_codigo' => 0,
                                            'atingimento_metal_porcetagem' => 0,
                                            'diferenca_meta' => 0,
                                        ];
                                    }
                                    $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                    $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];

                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                }
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
                    $gerentes[$unidade_negocio->id] = [
                        'id' => encrypt($unidade_negocio->id),
                        'unidade' => $unidade_negocio->unidade,
                        'tipo' => 'Gerente',
                        'id_usuario' => $unidade_negocio->detalhesUsuarioResponsavel->id,
                        'vendedor' => $unidade_negocio->detalhesUsuarioResponsavel->name,
                        'meta' => $unidade_negocio->metas[0]->valor,
                        'faturado' => 0,
                        'atingimento' => 0,
                        'comissao_porcetagem' => 0,
                        'valor_individual' => 0,
                        'valor_equipe' => 0,
                        'valor_total' => 0,
                    ];
                    if($unidade_negocio->unidade !== 'DENIM' && $unidade_negocio->unidade !== 'WORKWEAR' && $unidade_negocio->unidade !== 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(empty($valores_totais[$unidade_negocio->id])){
                                $valores_totais[$unidade_negocio->id] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'valor' => 0,
                                    'quantidade_membro' => 1,
                                ];
                            }else{
                                $valores_totais[$unidade_negocio->id]['quantidade_membro']++;
                            }
                            
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    $liberado = false;
                                    if(in_array($codigo_vendedor, $usuarios_workwear['codigos']) && in_array($codigo_vendedor, $usuarios_denim['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
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
                                        $valores_totais[$unidade_negocio->id]['valor'] += $movimentacao['preco'];

                                        if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                                $liberado = false;
                                                if(in_array($codigo_vendedor, $usuarios_workwear['codigos']) && in_array($codigo_vendedor, $usuarios_denim['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
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
                                                    $valores_totais[$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];
                                                    $valor -= $movimentacao_devolucao['preco'];

                                                    if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                                        $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                            'unidade' => $unidade_negocio->unidade,
                                                            'vendedor_codigo' => $codigo_vendedor,
                                                            'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                            'meta' => $usuario->metas,
                                                            'meta_codigo' => $usuario->metas,
                                                            'valor' => 0,
                                                            'valor_codigo' => 0,
                                                            'atingimento_metal_porcetagem' => 0,
                                                            'diferenca_meta' => 0,
                                                        ];
                                                    }
                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];
        
                                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                }
                                            }
                                        }

                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                                'valor_codigo' => $movimentacao['preco'],
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
        
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'DENIM'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(empty($valores_totais[$unidade_negocio->id])){
                                $valores_totais[$unidade_negocio->id] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'valor' => 0,
                                    'quantidade_membro' => 1,
                                ];
                            }else{
                                $valores_totais[$unidade_negocio->id]['quantidade_membro']++;
                            }
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    if(($movimentacao['linha'] === 'DENIM IMPORTADO' || ($movimentacao['linha'] === 'INDIGO E SARJAS' && $movimentacao['marca'] === 'IMPORTADOS')) && ($movimentacao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao['grupo'] !== 'DENIM SANIBEL')){
                                        $valor += $movimentacao['preco'];
                                        $valores_totais[$unidade_negocio->id]['valor'] += $movimentacao['preco'];
                                        $id_denim = $unidade_negocio->id;
                                        
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                                'valor_codigo' => $movimentacao['preco'],
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
        
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
        
                                        if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                                if(($movimentacao_devolucao['linha'] === 'DENIM IMPORTADO' || ($movimentacao_devolucao['linha'] === 'INDIGO E SARJAS' && $movimentacao_devolucao['marca'] === 'IMPORTADOS')) && ($movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL')){
                                                    $valor -= $movimentacao_devolucao['preco'];
                                                    $valores_totais[$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];

                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];
        
                                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                }
                                            }
                                        }
            
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => 0,
                                                'valor_codigo' => 0,
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                        }     
                                    }
                                }
                            }else{
                                $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'meta_codigo' => $usuario->metas,
                                    'valor' => 0,
                                    'valor_codigo' => 0,
                                    'atingimento_metal_porcetagem' => 0,
                                    'diferenca_meta' => 0,
                                ];
                            }
                        }
                    }else if($unidade_negocio->unidade === 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            if(empty($valores_totais[$unidade_negocio->id])){
                                $valores_totais[$unidade_negocio->id] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'valor' => 0,
                                    'quantidade_membro' => 1,
                                ];
                            }else{
                                $valores_totais[$unidade_negocio->id]['quantidade_membro']++;
                            }
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    if(substr_count($movimentacao['linha'], "HOSPITALAR") > 0){
                                        $valor += $movimentacao['preco'];
                                        $valores_totais[$unidade_negocio->id]['valor'] += $movimentacao['preco'];

                                        $id_hospitalar = $unidade_negocio->id;
        
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                                'valor_codigo' => $movimentacao['preco'],
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
        
                                        if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                                if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") > 0){
                                                    $valor -= $movimentacao_devolucao['preco'];
                                                    $valores_totais[$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];

                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];
        
                                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                }
                                            }
                                        }
            
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => 0,
                                                'valor_codigo' => 0,
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                        }     
                                    }
                                }
                            }else{
                                $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'meta_codigo' => $usuario->metas,
                                    'valor' => 0,
                                    'valor_codigo' => 0,
                                    'atingimento_metal_porcetagem' => 0,
                                    'diferenca_meta' => 0,
                                ];
                            }
                        }
                    }else{
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(empty($valores_totais[$unidade_negocio->id])){
                                $valores_totais[$unidade_negocio->id] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'valor' => 0,
                                    'quantidade_membro' => 1,
                                ];
                            }else{
                                $valores_totais[$unidade_negocio->id]['quantidade_membro']++;
                            }
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    $liberado = false;
                                    if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(substr_count($movimentacao['linha'], "HOSPITALAR") === 0 && ($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN")){
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
                                        $valores_totais[$unidade_negocio->id]['valor'] += $movimentacao['preco'];

                                        $id_workwear = $unidade_negocio->id;
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                                'valor_codigo' => $movimentacao['preco'],
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
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
                                                    $valores_totais[$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];

                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];
        
                                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                }
                                            }
                                        }
        
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => 0,
                                                'valor_codigo' => 0,
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                        }     
                                    }
                                }
                            }else{
                                $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'meta_codigo' => $usuario->metas,
                                    'valor' => 0,
                                    'valor_codigo' => 0,
                                    'atingimento_metal_porcetagem' => 0,
                                    'diferenca_meta' => 0,
                                ];
                            }
                        }
                    }
                }
                
                $total_meta += $meta;
                $total_valor += $valor;
            }
        }

        $valores_totais = [];
        foreach($vendedores as $key_unidade_negocio => $unidade_negocio){
            foreach($unidade_negocio as $codigo_vendedor => $vendedor){
                if(empty($valores_totais[$key_unidade_negocio]['valor'] )){
                    $valores_totais[$key_unidade_negocio]['valor']  = 0;
                }
                $valores_totais[$key_unidade_negocio]['valor'] += $vendedor['valor'];
            }
        }

        $comissao_equipe = [];

        foreach($gerentes as $unidade_negocio_id => $gerente){
            $valor_equipe_porcetagem = empty($valores_totais[$unidade_negocio_id]['valor'] )? 0 : ($valores_totais[$unidade_negocio_id]['valor'] / $gerente['meta']) * 100;
            if(in_array($unidade_negocio_id, [2, 3, 4])){
                if($valor_equipe_porcetagem >= 99.0){
                    $gerentes[$unidade_negocio_id]['comissao_porcetagem'] = 0.12;
                    $comissao_equipe[$unidade_negocio_id] = 20;
                }else if($valor_equipe_porcetagem >= 89.9 && $valor_equipe_porcetagem <= 98.9){
                    $gerentes[$unidade_negocio_id]['comissao_porcetagem'] = 0.11;
                    $comissao_equipe[$unidade_negocio_id] = 10;
                }else{
                    $gerentes[$unidade_negocio_id]['comissao_porcetagem'] = 0.10;
                    $comissao_equipe[$unidade_negocio_id] = 0;
                }
                if($gerente['id_usuario'] === 73){
                    $gerentes[$unidade_negocio_id]['comissao_porcetagem'] = 0.20;
                }
            }else{
                $comissao_equipe[$unidade_negocio_id] = 0;
            }

            if($valor_equipe_porcetagem >= 99.0){
                $gerentes[$unidade_negocio_id]['comissao_porcetagem'] = 0.12;
            }else if($valor_equipe_porcetagem >= 89.9 && $valor_equipe_porcetagem <= 98.9){
                $gerentes[$unidade_negocio_id]['comissao_porcetagem'] = 0.11;
            }else{
                $gerentes[$unidade_negocio_id]['comissao_porcetagem'] = 0.10;
            }
            if($gerente['id_usuario'] === 73){
                $gerentes[$unidade_negocio_id]['comissao_porcetagem'] = 0.20;
            }
            
            $gerentes[$unidade_negocio_id]['faturamento_equipe'] = empty($valores_totais[$unidade_negocio_id])? 0 : $valores_totais[$unidade_negocio_id]['valor'];
            $gerentes[$unidade_negocio_id]['atingimento_equipe_porcetagem'] = empty($valores_totais[$unidade_negocio_id])? 0 : $valores_totais[$unidade_negocio_id]['valor']/  $gerentes[$unidade_negocio_id]['meta'] * 100;
            $gerentes[$unidade_negocio_id]['valor_equipe'] = empty($valores_totais[$unidade_negocio_id])? 0 : $valores_totais[$unidade_negocio_id]['valor'] * ($gerentes[$unidade_negocio_id]['comissao_porcetagem']/100);
            $gerentes[$unidade_negocio_id]['valor_total'] = empty($valores_totais[$unidade_negocio_id])? 0 : $valores_totais[$unidade_negocio_id]['valor'] * ($gerentes[$unidade_negocio_id]['comissao_porcetagem']/100);
        }

        $vendedores_busca = [];
        if(!empty($fields['vendedor'])){
            $vendedores_busca[] = $fields['vendedor'];
        }else{
            $busca = User::select('codigo_representante', 'name')->where('codigo_representante', '!=', '')->where('tipo_usuario_id', 16)->orderBy('codigo_representante')->get();
            foreach($busca as $vendedor_busca){
                $vendedores_busca[] = $vendedor_busca->codigo_representante;
            }
        }

        $unidades = [];
        $total_meta = 0;
        $total_faturamento = 0;
        $total_comissao = 0;
        foreach($gerentes as $unidade_negocio_id => $gerente){
            $unidades[] = [
                'id' => encrypt($unidade_negocio_id),
                'unidade' => $gerente['unidade'],
                'meta' => $gerente['meta'],
                'faturamento' => $gerente['faturamento_equipe'],
                'atingimento_porcetagem' => $gerente['atingimento_equipe_porcetagem'],
                'comissao_porcetagem' => $comissao_equipe[$unidade_negocio_id],
                'valor' => ($gerente['faturamento_equipe'] * ($comissao_equipe[$unidade_negocio_id]/100)),
            ];

            $total_meta += $gerente['meta'];
            $total_faturamento += $gerente['faturamento_equipe'];
            $total_comissao += ($gerente['faturamento_equipe'] * ($comissao_equipe[$unidade_negocio_id]/100));
        }

        $total_valor_individual = 0;
        $total_valor_equipe = 0;
        $total_valor_total = 0;
        $vazio_vendedor_interno = true;
        $contador_vendedores_interno = [];
        foreach($vendedores as $key_unidade_negocio => $unidade_negocio){
            foreach($unidade_negocio as $codigo_vendedor => $vendedor){
                $id = empty($fields['unidade_negocio'])? $key_unidade_negocio : $fields['unidade_negocio'];
                if(empty($contador_vendedores_interno[$key_unidade_negocio])){
                    $contador_vendedores_interno[$key_unidade_negocio] = 0;
                }
                if($id === $key_unidade_negocio){
                    if(in_array($codigo_vendedor, $vendedores_busca)){
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['atingimento_metal_porcetagem'] = !empty($vendedor['valor']) && !empty($vendedor['meta'])? (($vendedor['valor'] / $vendedor['meta']) * 100) : 0;
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['diferenca_meta'] = !empty($vendedor['valor']) && !empty($vendedor['meta'])? $vendedor['valor'] - $vendedor['meta'] : 0;
                        if($vendedores[$key_unidade_negocio][$codigo_vendedor]['atingimento_metal_porcetagem'] >= 99.0){
                            if($codigo_vendedor === 415){
                                $vendedores[$key_unidade_negocio][$codigo_vendedor]['comissao_porcetagem'] = 1.0;
                            }else{
                                $vendedores[$key_unidade_negocio][$codigo_vendedor]['comissao_porcetagem'] = 0.3;
                            }
                        }else if($vendedores[$key_unidade_negocio][$codigo_vendedor]['atingimento_metal_porcetagem'] >= 89.9 && $vendedores[$key_unidade_negocio][$codigo_vendedor]['atingimento_metal_porcetagem'] <= 98.9){
                            if($codigo_vendedor === 415){
                                $vendedores[$key_unidade_negocio][$codigo_vendedor]['comissao_porcetagem'] = 0.7;
                            }else{
                                $vendedores[$key_unidade_negocio][$codigo_vendedor]['comissao_porcetagem'] = 0.26;
                            }
                        }else{
                            if($codigo_vendedor === 415){
                                $vendedores[$key_unidade_negocio][$codigo_vendedor]['comissao_porcetagem'] = 0.5;
                            }else{
                                $vendedores[$key_unidade_negocio][$codigo_vendedor]['comissao_porcetagem'] = 0.22;
                            }
                        }

                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['id'] = encrypt($key_unidade_negocio);
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['comissao_equipe_porcetagem'] = $comissao_equipe[$key_unidade_negocio];
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_individual'] = $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor'] * ($vendedores[$key_unidade_negocio][$codigo_vendedor]['comissao_porcetagem']/100);
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['tipo'] = 'Vendedor Interno';
                        $vazio_vendedor_interno = false;

                        $total_valor_individual += $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_individual'];
                        $contador_vendedores_interno[$key_unidade_negocio]++;
                    }else{
                        unset($vendedores[$key_unidade_negocio][$codigo_vendedor]);
                    }
                }else{
                    unset($vendedores[$key_unidade_negocio]);
                }
            }
        }

        foreach($vendedores as $key_unidade_negocio => $unidade_negocio){
            foreach($unidade_negocio as $codigo_vendedor => $vendedor){
                $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_equipe'] = empty($contador_vendedores_interno[$key_unidade_negocio])? 0 : $valores_totais[$unidade_negocio_id]['valor'] * ($comissao_equipe[$unidade_negocio_id] / 100) / $contador_vendedores_interno[$key_unidade_negocio];
                $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_total'] = $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_individual'] + $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_equipe'];
            }
        }

        $vendedores = $this->ajusteArrayParaValores($vendedores);
        
        $vendedores = $this->ajusteArrayParaValores($vendedores);
        $gerentes = $this->ajusteArrayParaValores($gerentes);
        $unidades = $this->ajusteArrayParaValores($unidades);

        $total_atingimento = empty($total_meta) || empty($total_faturamento)? '' : parserValor($total_faturamento/$total_meta*100).'%';
        $total_meta = parserValor($total_meta);
        $total_faturamento = parserValor($total_faturamento);
        $total_comissao = parserValor($total_comissao);

        $retorno = [
            'vendedores' => $vendedores,
            'gerentes' => $gerentes,
            'unidades' => $unidades,
            'filtro' => encrypt($fields),
            'data_escolhida' => parserNameMonth($data_escolhida_inicial->format('m')).'/'.$data_escolhida_inicial->format('Y'),
            'mes_ano' => $fields['mes_ano'],
            'total_meta' => $total_meta,
            'total_faturamento' => $total_faturamento,
            'total_atingimento' => $total_atingimento,
            'total_comissao' => $total_comissao
        ];
        
        if($retorno_array){
            return $retorno;
        }else{
            return response()->json([
                'status' => 'sucess',
                'message' => '',
                'error' => '',
                'response' => $retorno,
            ]);
        }
    }

    public function modalMembros(Request $request){
        $fields = $request->only('mes_ano', 'id');

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

        $query = UnidadeNegocio::select();
        
        $data_escolhida = '01/'.$fields['mes_ano'];
        $separado_data = explode('/', $data_escolhida);
        $ultimo_dia = $this->getUltimoDiaMes($separado_data[1], $separado_data[2]);

        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$fields['mes_ano'])->setTime(0,0,0);

        $usuarios_workwear = $this->getUserWorkwear($data_escolhida_inicial);
        $usuarios_denim = $this->getUserDenim($data_escolhida_inicial);
        $usuarios_hospitalar = $this->getUserHospitalar($data_escolhida_inicial);
        $clientes_bionexo = $this->clientesBionexo();

        $query->with(['detalhesUsuarioResponsavel']);
        $query->with(['metas' => function($query) use($data_escolhida_inicial, $fields){
            $query->where('data', $data_escolhida_inicial);
            if(!empty($fields['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($fields){
                    $query->where('codigo_representante', $fields['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);

        $query->where('id', $id);

        $result = $query->get();

        $codigos_vendedores = [];
        foreach($result[0]->metas[0]->usuarios as $usuario){
            $codigos_vendedores[] = $usuario->detalhesUsuario->codigo_representante;
        }

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, documento, unidade, cliente_codigo, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->groupBy('vendedor', 'marca', 'linha', 'grupo', 'documento', 'unidade', 'cliente_codigo');
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
                'documento' =>$movimentacao->documento,
                'unidade' => $movimentacao->unidade,
                'bionexo' => $bionexo,
            ];
        }

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, documento, unidade, cliente_codigo, sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end ) as preco_total');
        $query_movimentacao->with(['detalhesVendedor']);
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);

        $query_movimentacao->groupBy('vendedor', 'marca', 'linha', 'grupo', 'documento', 'unidade', 'cliente_codigo');
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
                'documento' => $movimentacao->documento,
                'unidade' => $movimentacao->unidade,
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
        $valores_totais = []; 
        $gerentes = [];

        if($data_escolhida_inicial->gte($data_verificacao)){
            $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
            $usuarios_magazine = $this->getUserMagazine($data_escolhida_inicial);
            foreach($result as $unidade_negocio){
                $valor = 0;
                $meta = 0;
                if($unidade_negocio->metas->count() > 0){
                    $gerentes[$unidade_negocio->id] = [
                        'id' => encrypt($unidade_negocio->id),
                        'unidade' => $unidade_negocio->unidade,
                        'tipo' => 'Gerente',
                        'id_usuario' => $unidade_negocio->detalhesUsuarioResponsavel->id,
                        'vendedor' => $unidade_negocio->detalhesUsuarioResponsavel->name,
                        'meta' => $unidade_negocio->metas[0]->valor,
                        'faturado' => 0,
                        'atingimento' => 0,
                        'comissao_porcetagem' => 0,
                        'valor_individual' => 0,
                        'valor_equipe' => 0,
                        'valor_total' => 0,
                    ];
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(empty($valores_totais[$unidade_negocio->id])){
                            $valores_totais[$unidade_negocio->id] = [
                                'unidade' => $unidade_negocio->unidade,
                                'valor' => 0,
                                'quantidade_membro' => 1,
                            ];
                        }else{
                            $valores_totais[$unidade_negocio->id]['quantidade_membro']++;
                        }

                        if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            $meta += $usuario->metas;
                            foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                $unidade_negocio_id = $unidade_negocio->id;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    $unidade_negocio_id = $excecoes[$movimentacao['documento']];
                                }else if(in_array($codigo_vendedor, $usuarios_magazine['codigos']) && $movimentacao['bionexo']){
                                    $unidade_negocio_id = 10;//FashionMagazine
                                }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                    if(in_array($movimentacao['unidade'], $this->unidade_metro)){
                                        $unidade_negocio_id = 15;//Fashion 3
                                    }else{
                                        $unidade_negocio_id = 7;//Hospitalar
                                    }
                                }
                                if(empty($valores_totais[$unidade_negocio_id])){
                                    $valores_totais[$unidade_negocio_id] = [
                                        'unidade' => $unidade_negocio->unidade,
                                        'valor' => 0,
                                        'quantidade_membro' => 1,
                                    ];
                                }
                                if(!empty($id)){
                                    if($id === $unidade_negocio_id){
                                        $valor += $movimentacao['preco'];
                                        $valores_totais[$unidade_negocio_id]['valor'] += $movimentacao['preco'];

                                        if(empty($vendedores[$unidade_negocio_id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio_id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                                'valor_codigo' => $movimentacao['preco'],
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];

                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                            $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
        
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }
                                }else{
                                    $valor += $movimentacao['preco'];
                                    $valores_totais[$unidade_negocio_id]['valor'] += $movimentacao['preco'];

                                    if(empty($vendedores[$unidade_negocio_id][$codigo_vendedor])){
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor] = [
                                            'unidade' => $unidade_negocio->unidade,
                                            'vendedor_codigo' => $codigo_vendedor,
                                            'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                            'meta' => $usuario->metas,
                                            'meta_codigo' => $usuario->metas,
                                            'valor' => $movimentacao['preco'],
                                            'valor_codigo' => $movimentacao['preco'],
                                            'atingimento_metal_porcetagem' => 0,
                                            'diferenca_meta' => 0,
                                        ];
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }
    
                                    unset($movimentacoes[$codigo_vendedor][$key]);
                                }
                            }
                        }else{
                            if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'meta_codigo' => $usuario->metas,
                                    'valor' => 0,
                                    'valor_codigo' => 0,
                                    'atingimento_metal_porcetagem' => 0,
                                    'diferenca_meta' => 0,
                                ];
                            }
                        }
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                $unidade_negocio_id = $unidade_negocio->id;
                                if(!empty($excecoes[$movimentacao_devolucao['documento']])){
                                    $unidade_negocio_id = $excecoes[$movimentacao_devolucao['documento']];
                                }else if(in_array($codigo_vendedor, $usuarios_magazine['codigos']) && $movimentacao_devolucao['bionexo']){
                                    $unidade_negocio_id = 10;//FashionMagazine
                                }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                    if(in_array($movimentacao_devolucao['unidade'], $this->unidade_metro)){
                                        $unidade_negocio_id = 15;//Fashion 3
                                    }else{
                                        $unidade_negocio_id = 7;//Hospitalar
                                    }
                                }
                                if(empty($valores_totais[$unidade_negocio_id])){
                                    $valores_totais[$unidade_negocio_id] = [
                                        'unidade' => $unidade_negocio->unidade,
                                        'valor' => 0,
                                        'quantidade_membro' => 1,
                                    ];
                                }
                                if(!empty($id)){
                                    if($id === $unidade_negocio_id){
                                        $valores_totais[$unidade_negocio_id]['valor'] -= $movimentacao_devolucao['preco'];
                                        $valor -= $movimentacao_devolucao['preco'];

                                        if(empty($vendedores[$unidade_negocio_id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio_id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => 0,
                                                'valor_codigo' => 0,
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                        }
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                    }
                                }else{
                                    $valores_totais[$unidade_negocio_id]['valor'] -= $movimentacao_devolucao['preco'];
                                    $valor -= $movimentacao_devolucao['preco'];

                                    if(empty($vendedores[$unidade_negocio_id][$codigo_vendedor])){
                                        $vendedores[$unidade_negocio_id][$codigo_vendedor] = [
                                            'unidade' => $unidade_negocio->unidade,
                                            'vendedor_codigo' => $codigo_vendedor,
                                            'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                            'meta' => $usuario->metas,
                                            'meta_codigo' => $usuario->metas,
                                            'valor' => 0,
                                            'valor_codigo' => 0,
                                            'atingimento_metal_porcetagem' => 0,
                                            'diferenca_meta' => 0,
                                        ];
                                    }
                                    $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                    $vendedores[$unidade_negocio_id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];

                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                }
                            }
                        }else{
                            if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'meta_codigo' => $usuario->metas,
                                    'valor' => 0,
                                    'valor_codigo' => 0,
                                    'atingimento_metal_porcetagem' => 0,
                                    'diferenca_meta' => 0,
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
                    $gerentes[$unidade_negocio->id] = [
                        'id' => encrypt($unidade_negocio->id),
                        'unidade' => $unidade_negocio->unidade,
                        'tipo' => 'Gerente',
                        'id_usuario' => $unidade_negocio->detalhesUsuarioResponsavel->id,
                        'vendedor' => $unidade_negocio->detalhesUsuarioResponsavel->name,
                        'meta' => $unidade_negocio->metas[0]->valor,
                        'faturado' => 0,
                        'atingimento' => 0,
                        'comissao_porcetagem' => 0,
                        'valor_individual' => 0,
                        'valor_equipe' => 0,
                        'valor_total' => 0,
                    ];
                    if($unidade_negocio->unidade !== 'DENIM' && $unidade_negocio->unidade !== 'WORKWEAR' && $unidade_negocio->unidade !== 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(empty($valores_totais[$unidade_negocio->id])){
                                $valores_totais[$unidade_negocio->id] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'valor' => 0,
                                    'quantidade_membro' => 1,
                                ];
                            }else{
                                $valores_totais[$unidade_negocio->id]['quantidade_membro']++;
                            }
                            
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    $liberado = false;
                                    if(in_array($codigo_vendedor, $usuarios_workwear['codigos']) && in_array($codigo_vendedor, $usuarios_denim['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
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
                                        $valores_totais[$unidade_negocio->id]['valor'] += $movimentacao['preco'];

                                        if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                                $liberado = false;
                                                if(in_array($codigo_vendedor, $usuarios_workwear['codigos']) && in_array($codigo_vendedor, $usuarios_denim['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
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
                                                    $valores_totais[$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];
                                                    $valor -= $movimentacao_devolucao['preco'];

                                                    if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                                        $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                            'unidade' => $unidade_negocio->unidade,
                                                            'vendedor_codigo' => $codigo_vendedor,
                                                            'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                            'meta' => $usuario->metas,
                                                            'meta_codigo' => $usuario->metas,
                                                            'valor' => 0,
                                                            'valor_codigo' => 0,
                                                            'atingimento_metal_porcetagem' => 0,
                                                            'diferenca_meta' => 0,
                                                        ];
                                                    }
                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];
        
                                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                }
                                            }
                                        }

                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                                'valor_codigo' => $movimentacao['preco'],
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
        
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'DENIM'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(empty($valores_totais[$unidade_negocio->id])){
                                $valores_totais[$unidade_negocio->id] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'valor' => 0,
                                    'quantidade_membro' => 1,
                                ];
                            }else{
                                $valores_totais[$unidade_negocio->id]['quantidade_membro']++;
                            }
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    if(($movimentacao['linha'] === 'DENIM IMPORTADO' || ($movimentacao['linha'] === 'INDIGO E SARJAS' && $movimentacao['marca'] === 'IMPORTADOS')) && ($movimentacao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao['grupo'] !== 'DENIM SANIBEL')){
                                        $valor += $movimentacao['preco'];
                                        $valores_totais[$unidade_negocio->id]['valor'] += $movimentacao['preco'];
                                        $id_denim = $unidade_negocio->id;
                                        
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                                'valor_codigo' => $movimentacao['preco'],
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
        
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
        
                                        if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                                if(($movimentacao_devolucao['linha'] === 'DENIM IMPORTADO' || ($movimentacao_devolucao['linha'] === 'INDIGO E SARJAS' && $movimentacao_devolucao['marca'] === 'IMPORTADOS')) && ($movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL')){
                                                    $valor -= $movimentacao_devolucao['preco'];
                                                    $valores_totais[$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];

                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];
        
                                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                }
                                            }
                                        }
            
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => 0,
                                                'valor_codigo' => 0,
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                        }     
                                    }
                                }
                            }else{
                                $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'meta_codigo' => $usuario->metas,
                                    'valor' => 0,
                                    'valor_codigo' => 0,
                                    'atingimento_metal_porcetagem' => 0,
                                    'diferenca_meta' => 0,
                                ];
                            }
                        }
                    }else if($unidade_negocio->unidade === 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            if(empty($valores_totais[$unidade_negocio->id])){
                                $valores_totais[$unidade_negocio->id] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'valor' => 0,
                                    'quantidade_membro' => 1,
                                ];
                            }else{
                                $valores_totais[$unidade_negocio->id]['quantidade_membro']++;
                            }
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    if(substr_count($movimentacao['linha'], "HOSPITALAR") > 0){
                                        $valor += $movimentacao['preco'];
                                        $valores_totais[$unidade_negocio->id]['valor'] += $movimentacao['preco'];

                                        $id_hospitalar = $unidade_negocio->id;
        
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                                'valor_codigo' => $movimentacao['preco'],
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
        
                                        if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                                if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") > 0){
                                                    $valor -= $movimentacao_devolucao['preco'];
                                                    $valores_totais[$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];

                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];
        
                                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                }
                                            }
                                        }
            
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => 0,
                                                'valor_codigo' => 0,
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                        }     
                                    }
                                }
                            }else{
                                $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'meta_codigo' => $usuario->metas,
                                    'valor' => 0,
                                    'valor_codigo' => 0,
                                    'atingimento_metal_porcetagem' => 0,
                                    'diferenca_meta' => 0,
                                ];
                            }
                        }
                    }else{
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(empty($valores_totais[$unidade_negocio->id])){
                                $valores_totais[$unidade_negocio->id] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'valor' => 0,
                                    'quantidade_membro' => 1,
                                ];
                            }else{
                                $valores_totais[$unidade_negocio->id]['quantidade_membro']++;
                            }
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    $liberado = false;
                                    if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(substr_count($movimentacao['linha'], "HOSPITALAR") === 0 && ($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN")){
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
                                        $valores_totais[$unidade_negocio->id]['valor'] += $movimentacao['preco'];

                                        $id_workwear = $unidade_negocio->id;
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => $movimentacao['preco'],
                                                'valor_codigo' => $movimentacao['preco'],
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }else{
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] += $movimentacao['preco'];
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
                                                    $valores_totais[$unidade_negocio->id]['valor'] -= $movimentacao_devolucao['preco'];

                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                                    $vendedores[$unidade_negocio->id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];
        
                                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                }
                                            }
                                        }
        
                                        unset($movimentacoes[$codigo_vendedor][$key]);
                                    }else{
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                                'unidade' => $unidade_negocio->unidade,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                                'meta' => $usuario->metas,
                                                'meta_codigo' => $usuario->metas,
                                                'valor' => 0,
                                                'valor_codigo' => 0,
                                                'atingimento_metal_porcetagem' => 0,
                                                'diferenca_meta' => 0,
                                            ];
                                        }     
                                    }
                                }
                            }else{
                                $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                    'unidade' => $unidade_negocio->unidade,
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'vendedor_nome' => $usuario->detalhesUsuario->name,
                                    'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                    'meta' => $usuario->metas,
                                    'meta_codigo' => $usuario->metas,
                                    'valor' => 0,
                                    'valor_codigo' => 0,
                                    'atingimento_metal_porcetagem' => 0,
                                    'diferenca_meta' => 0,
                                ];
                            }
                        }
                    }
                }
                
                $total_meta += $meta;
                $total_valor += $valor;
            }
        }

        $comissao_equipe = [];
        $id_gerente = []; 
        foreach($gerentes as $unidade_negocio_id => $gerente){
            $id_gerente[] = $gerente['id_usuario'];
            $valor_equipe_porcetagem = ($valores_totais[$unidade_negocio_id]['valor'] / $gerente['meta']) * 100;
            if($valor_equipe_porcetagem >= 99.0){
                $gerentes[$unidade_negocio_id]['comissao_porcetagem'] = 0.12;
                $comissao_equipe[$unidade_negocio_id] = 20;
            }else if($valor_equipe_porcetagem >= 89.9 && $valor_equipe_porcetagem <= 98.9){
                $gerentes[$unidade_negocio_id]['comissao_porcetagem'] = 0.11;
                $comissao_equipe[$unidade_negocio_id] = 10;
            }else{
                $gerentes[$unidade_negocio_id]['comissao_porcetagem'] = 0.10;
                $comissao_equipe[$unidade_negocio_id] = 0;
            }
            if(!in_array($unidade_negocio_id, [2, 3, 4])){
                $comissao_equipe[$unidade_negocio_id] = 0;
            }
            $gerentes[$unidade_negocio_id]['faturamento_equipe'] = $valores_totais[$unidade_negocio_id]['valor'];
            $gerentes[$unidade_negocio_id]['atingimento_equipe_porcetagem'] = $valores_totais[$unidade_negocio_id]['valor']/  $gerentes[$unidade_negocio_id]['meta'] * 100;
            $valores_totais[$unidade_negocio_id]['valor_equipe'] = $valores_totais[$unidade_negocio_id]['valor'] * ($comissao_equipe[$unidade_negocio_id] / 100) / $valores_totais[$unidade_negocio_id]['quantidade_membro'];
            $gerentes[$unidade_negocio_id]['valor_equipe'] = $valores_totais[$unidade_negocio_id]['valor'] * ($gerentes[$unidade_negocio_id]['comissao_porcetagem']/100);
            $gerentes[$unidade_negocio_id]['valor_total'] = $valores_totais[$unidade_negocio_id]['valor'] * ($gerentes[$unidade_negocio_id]['comissao_porcetagem']/100);
        }

        $vendedores_busca = [];
        if(!empty($fields['vendedor'])){
            $vendedores_busca[] = $fields['vendedor'];
        }else{
            $busca = User::select('codigo_representante', 'name')->where('codigo_representante', '!=', '')->where('tipo_usuario_id', 16)->orderBy('codigo_representante')->get();
            foreach($busca as $vendedor_busca){
                $vendedores_busca[] = $vendedor_busca->codigo_representante;
            }
        }

        $total_valor_individual = 0;
        $total_valor_equipe = 0;
        $total_valor_total = 0;
        $vazio_vendedor_interno = true;
        $contador_vendedores_interno = 0;
        foreach($vendedores as $key_unidade_negocio => $unidade_negocio){
            foreach($unidade_negocio as $codigo_vendedor => $vendedor){
                $vendedores[$key_unidade_negocio][$codigo_vendedor]['comissao_porcetagem'] = 0;
                $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_titulo_pago'] = 0;
                if($id === $key_unidade_negocio){
                    if(in_array($codigo_vendedor, $vendedores_busca)){
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['atingimento_metal_porcetagem'] = 0;
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['diferenca_meta'] = !empty($vendedor['valor']) && !empty($vendedor['meta'])? $vendedor['valor'] - $vendedor['meta'] : 0;

                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['id'] = encrypt($key_unidade_negocio);
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['comissao_equipe_porcetagem'] = $comissao_equipe[$key_unidade_negocio];
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_individual'] = 0;
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['tipo'] = 'Vendedor Interno';
                        $vazio_vendedor_interno = false;

                        $total_valor_individual += $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_individual'];
                        $contador_vendedores_interno++;
                    }else{
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['diferenca_meta'] = !empty($vendedor['valor']) && !empty($vendedor['meta'])? $vendedor['valor'] - $vendedor['meta'] : 0;
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['id'] = encrypt($key_unidade_negocio);
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['comissao_equipe_porcetagem'] = 0;
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_individual'] = 0;
                        $vendedores[$key_unidade_negocio][$codigo_vendedor]['tipo'] = 'Vendedor Externo';
                    }
                }else{
                    unset($vendedores[$key_unidade_negocio]);
                }
            }
        }

        $total_meta = 0;
        $total_valor = 0;

        foreach($vendedores as $key_unidade_negocio => $unidade_negocio){
            foreach($unidade_negocio as $codigo_vendedor => $vendedor){
                $total_meta += $vendedor['meta'];
                $total_valor += $vendedor['valor'];
                $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_titulo_pago'] = 0;
                $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_equipe'] = 0;
                $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_total'] = 0;
            }
        }

        $query_titulos = TitulosPagosNasajon::select('vendedor_codigo', 'documento_id', 'documento_numero', DB::raw('sum(valor) as valor'));
        $query_titulos->whereIn('vendedor_codigo', $codigos_vendedores);
        $query_titulos->whereBetween('data_pagamento', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_titulos->with(['notaDetalhes.itens_nota']);
        $query_titulos->groupBy('vendedor_codigo', 'documento_id', 'documento_numero');
        $result_titulos = $query_titulos->get();

        $total_titulo_pago = 0; 
        $total_valor_equipe = 0;
        $total_valor_total = 0;
        $total_valor_individual = 0;
        foreach($result_titulos as $titulo){
            if($titulo->vendedor_codigo === "415"){
                $vendedores[$id][$titulo->vendedor_codigo]['comissao_porcetagem'] = 0.75;   
            }else{
                $vendedores[$id][$titulo->vendedor_codigo]['comissao_porcetagem'] = 0.25;
            }

            if(in_array($titulo->vendedor_codigo, $usuarios_fashion_3['codigos']) && in_array($titulo->vendedor_codigo, $usuarios_hospitalar['codigos'])){
                if(!empty($titulo->notaDetalhes->itens_nota)){
                    if(in_array($titulo->notaDetalhes->itens_nota[0]->unidade, $this->unidade_metro) && $id == 15){
                        $vendedores[$id][$titulo->vendedor_codigo]['valor_individual'] += ($vendedores[$id][$titulo->vendedor_codigo]['comissao_porcetagem']/100) * $titulo->valor;
                        $vendedores[$id][$titulo->vendedor_codigo]['valor_equipe'] = empty($contador_vendedores_interno)? 0 : $valores_totais[$id]['valor'] * ($comissao_equipe[$id] / 100) / $contador_vendedores_interno;
                        $vendedores[$id][$titulo->vendedor_codigo]['valor_total'] = $vendedores[$id][$titulo->vendedor_codigo]['valor_individual'] + $vendedores[$id][$titulo->vendedor_codigo]['valor_equipe'];
                        $vendedores[$id][$titulo->vendedor_codigo]['valor_titulo_pago'] += $titulo->valor;
                        $vendedores[$id][$titulo->vendedor_codigo]['atingimento_metal_porcetagem'] = $vendedores[$id][$titulo->vendedor_codigo]['valor_titulo_pago'] / $vendedores[$id][$titulo->vendedor_codigo]['meta'] * 100;
            
                        $total_titulo_pago += $titulo->valor;
                    }else if(!in_array($titulo->notaDetalhes->itens_nota[0]->unidade, $this->unidade_metro) && $id == 7){
                        $vendedores[$id][$titulo->vendedor_codigo]['valor_individual'] += ($vendedores[$id][$titulo->vendedor_codigo]['comissao_porcetagem']/100) * $titulo->valor;
                        $vendedores[$id][$titulo->vendedor_codigo]['valor_equipe'] = empty($contador_vendedores_interno)? 0 : $valores_totais[$id]['valor'] * ($comissao_equipe[$id] / 100) / $contador_vendedores_interno;
                        $vendedores[$id][$titulo->vendedor_codigo]['valor_total'] = $vendedores[$id][$titulo->vendedor_codigo]['valor_individual'] + $vendedores[$id][$titulo->vendedor_codigo]['valor_equipe'];
                        $vendedores[$id][$titulo->vendedor_codigo]['valor_titulo_pago'] += $titulo->valor;
                        $vendedores[$id][$titulo->vendedor_codigo]['atingimento_metal_porcetagem'] = $vendedores[$id][$titulo->vendedor_codigo]['valor_titulo_pago'] / $vendedores[$id][$titulo->vendedor_codigo]['meta'] * 100;
            
                        $total_titulo_pago += $titulo->valor;
                    }
                }
            }else{
                $vendedores[$id][$titulo->vendedor_codigo]['valor_individual'] += ($vendedores[$id][$titulo->vendedor_codigo]['comissao_porcetagem']/100) * $titulo->valor;
                $vendedores[$id][$titulo->vendedor_codigo]['valor_equipe'] = empty($contador_vendedores_interno)? 0 : $valores_totais[$id]['valor'] * ($comissao_equipe[$id] / 100) / $contador_vendedores_interno;
                $vendedores[$id][$titulo->vendedor_codigo]['valor_total'] = $vendedores[$id][$titulo->vendedor_codigo]['valor_individual'] + $vendedores[$id][$titulo->vendedor_codigo]['valor_equipe'];
                $vendedores[$id][$titulo->vendedor_codigo]['valor_titulo_pago'] += $titulo->valor;
                $vendedores[$id][$titulo->vendedor_codigo]['atingimento_metal_porcetagem'] = $vendedores[$id][$titulo->vendedor_codigo]['valor_titulo_pago'] / $vendedores[$id][$titulo->vendedor_codigo]['meta'] * 100;
    
                $total_titulo_pago += $titulo->valor;
            }
            
        }

        foreach($vendedores as $key_unidade_negocio => $unidade_negocio){
            foreach($unidade_negocio as $codigo_vendedor => $vendedor){
                if($vendedores[$key_unidade_negocio][$codigo_vendedor]['tipo'] === 'Vendedor Interno'){
                    $total_valor_individual += $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_individual'];
                    $total_valor_equipe += $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_equipe'];
                    $total_valor_total += $vendedores[$key_unidade_negocio][$codigo_vendedor]['valor_total'];
                }
            }
        }

        $total_atingimento = empty($total_meta) || empty($total_titulo_pago)? '' : ($total_titulo_pago/$total_meta)*100; 
        
        if($total_atingimento >= 99.0){
            $comissao_porcetagem = 0.12;
        }else if($total_atingimento >= 89.9 && $total_atingimento <= 98.9){
            $comissao_porcetagem = 0.11;
        }else{
            $comissao_porcetagem = 0.10;
        }

        $gerentes[$id]['comissao_porcetagem'] = $comissao_porcetagem;
        $gerentes[$id]['valor_total'] = $comissao_porcetagem * $total_titulo_pago / 100;

        $vendedores = $this->ajusteArrayParaValores($vendedores);
        $gerentes = $this->ajusteArrayParaValores($gerentes);

        $total_atingimento = empty($total_atingimento)? '' : parserValor($total_atingimento).'%'; 
        $total_meta =  empty($total_meta)? '' : parserValor($total_meta);
        $total_faturado =  empty($total_valor)? '' : parserValor($total_valor);
        $total_valor_individual =  empty($total_valor_individual)? '' : parserValor($total_valor_individual);
        $total_valor_equipe = empty($total_valor_equipe)? '' : parserValor($total_valor_equipe);
        $total_valor_total =  empty($total_valor_total)? '' : parserValor($total_valor_total);
        $total_titulo_pago =  empty($total_titulo_pago)? '' : parserValor($total_titulo_pago);

        return view('programs.comissao_venda_interna.modal.membro')->
        with(['vendedores' => $vendedores, 
        'gerentes' => $gerentes, 
        'id_gerente' => $id_gerente, 
        'vazio_vendedor_interno' => $vazio_vendedor_interno,
        'total_meta' => $total_meta,
        'total_faturado' => $total_faturado,
        'total_valor_individual' => $total_valor_individual,
        'total_valor_equipe' => $total_valor_equipe,
        'total_valor_total' => $total_valor_total,
        'total_atingimento' => $total_atingimento,
        'total_titulo_pago' => $total_titulo_pago]);
    }
}
