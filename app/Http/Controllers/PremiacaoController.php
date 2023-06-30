<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\UnidadeNegocio;
use App\User;
use App\ClienteBionexo;
use App\Movimentacao;
use App\ClienteNasajon;
use App\Premiacao;
use App\MapaVendaExcecao;
use App\UnidadeNegocioMeta;
use App\ComissaoDataFechamento;
use App\ComunicadoComissoe;
use App\DevolucaoNota;
use App\NotaVendaItemNasajon;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\ComissaoDuplicatasFiltroRequest;
use App\Http\Requests\ComissaoDialogRequest;

use App\Http\Controllers\ComissaoDuplicatasController;
use App\LancamentoDebCredVendedor;

class PremiacaoController extends Controller
{
    private $cfop_devolucao = ['1201', '1202', '2201', '2202'];

    private $cfop_venda = ['5922', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '5104', '6104'];

    private $unidade_metro = ['m', 'M', 'M...','METRO', 'METROS', 'MT', 'MTS', 'MTS.'];

    private $habilitado_comissao_equipe = [2,3,4];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Premiacao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Premiacao');

        $data = Carbon::now();
        $data = $data->format('m/Y');

        $unidades_negocios = $this->getUnidadeNegocio();
        $representantes = $this->getRepresentante();

        return view('programs.premiacao.index')->with(['data' => $data, 'unidades_negocios' => $unidades_negocios, 'representantes' => $representantes]);
    }

    private function getUnidadeNegocio(){
        $query = UnidadeNegocio::select();

        $tipo_usuario_id = empty(Auth::user())? 1 : Auth::user()->tipo_usuario_id;
        $usuario_id = empty(Auth::id())? 1 : Auth::id();

        if(in_array($usuario_id, $this->getGerentesId())){
            $query->where('users_id', $usuario_id);
        }else if(in_array($tipo_usuario_id , [12, 16])){
            $query->whereHas('metas', function($query){
                $query->whereHas('usuarios.detalhesUsuario', function ($query){
                    $query->where('codigo_representante', Auth::user()->codigo_representante);
                });
            });
        }
        
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

    private function getGerentesCodRepresentante(){
        $query = UnidadeNegocio::select();
        $query->with(['detalhesUsuarioResponsavel']);
        $result = $query->get();

        $codigos_vendedores_gerentes = [];
        foreach($result as $unidade_negocio){
            if(!empty($unidade_negocio->detalhesUsuarioResponsavel->codigo_representante)){
                $codigos_vendedores_gerentes[] = $unidade_negocio->detalhesUsuarioResponsavel->codigo_representante;
            }
        }

        return $codigos_vendedores_gerentes;
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

    public function getUserFashion2($data_escolhida_inicial){
        $query = UnidadeNegocio::select();
        $query->where('unidade', 'ilike', 'FASHION 2');
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

    public function getUserOutfitsConfeccionados($data_escolhida_inicial){
        $query = UnidadeNegocio::select();
        $query->where('unidade', 'ilike', 'OUTFITS CONFECCIONADOS');
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

    public function filtro(Request $request){
        $fields = $request->only('mes_ano', 'unidade_negocio', 'vendedor');

        $tipo_usuario_id = empty(Auth::user())? 1 : Auth::user()->tipo_usuario_id;
        $usuario_id = empty(Auth::id())? 1 : Auth::id();
        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
        $codigos_vendedores_gerentes = $this->getGerentesCodRepresentante();
        
        $query = UnidadeNegocio::select();

        if(in_array($usuario_id, $this->getGerentesId())){
            $query->where('users_id', $usuario_id);
        }        
        
        $data_escolhida = '01/'.$fields['mes_ano'];
        $separado_data = explode('/', $data_escolhida);
        $ultimo_dia = $this->getUltimoDiaMes($separado_data[1], $separado_data[2]);

        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
        $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$fields['mes_ano'])->setTime(0,0,0);

        $excecoes = $this->excecoes($data_escolhida_inicial, $data_escolhida_final);
        
        $clientes_bionexo = $this->clientesBionexo();

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

        $query_movimentacao = Movimentacao::select('vendedor', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'cliente_codigo', 'estabelecimento', DB::raw('sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total'));
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        if(!empty($fields['vendedor'])){
            $query_movimentacao->where('vendedor', $fields['vendedor']);
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

        $query_movimentacao = Movimentacao::select('vendedor', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'cliente_codigo', 'estabelecimento', DB::raw('sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total'));
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);
        if(!empty($fields['vendedor'])){
            $query_movimentacao->where('vendedor', $fields['vendedor']);
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
        $codigos_vendedores = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        
        $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
        $usuarios_fashion_2 = $this->getUserFashion2($data_escolhida_inicial);
        $usuarios_outfites_confeccionados = $this->getUserOutfitsConfeccionados($data_escolhida_inicial); 
        
        $valor = [];
        $meta = [];
        $total = [
            "meta_valor" => 0,
            "faturado_valor" => 0,
            "atingimento_meta_porcetagem" => 0, 
            "comissao_valor" => 0,
            "premio_valor" => 0, 
            "premio_a_pagar" => 0,
            "devolucao" => 0,
        ];
        foreach($result as $unidade_negocio){
            if($unidade_negocio->metas->count() > 0){
                $metas[$unidade_negocio->id]['total'] = $unidade_negocio->metas->count() === 0? '' : $unidade_negocio->metas[0]->valor;
                $total['meta_valor'] += $unidade_negocio->metas->count() === 0? 0 : $unidade_negocio->metas[0]->valor;
                foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                    $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                    $metas[$unidade_negocio->id][$codigo_vendedor] = $usuario->metas;
                    if(empty($meta[$unidade_negocio->id])){
                        $meta[$unidade_negocio->id] = $usuario->metas;
                    }else{
                        $meta[$unidade_negocio->id] += $usuario->metas;
                    }
                    if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                        foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                            $unidade_negocio_id = $unidade_negocio->id;
                            if(!empty($excecoes[$movimentacao['documento']])){
                                $unidade_negocio_id = $excecoes[$movimentacao['documento']];
                            }else if(in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos']) && $movimentacao['bionexo']){
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

                            if(!empty($fields['unidade_negocio'])){
                                if(intval($fields['unidade_negocio']) === $unidade_negocio_id){
                                    if(empty($valor[$unidade_negocio_id])){
                                        $valor[$unidade_negocio_id]['total'] = $movimentacao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id]['total'] += $movimentacao['preco'];
                                    }
                                    if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                        $valor[$unidade_negocio_id][$codigo_vendedor] = $movimentacao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id][$codigo_vendedor] += $movimentacao['preco'];
                                    }

                                    $total['faturado_valor'] += $movimentacao['preco'];
                                    
                                    unset($movimentacoes[$codigo_vendedor][$key]);
                                }
                            }else{
                                if(empty($valor[$unidade_negocio_id])){
                                    $valor[$unidade_negocio_id]['total'] = $movimentacao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id]['total'] += $movimentacao['preco'];
                                }
                                if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                    $valor[$unidade_negocio_id][$codigo_vendedor] = $movimentacao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id][$codigo_vendedor] += $movimentacao['preco'];
                                }

                                $total['faturado_valor'] += $movimentacao['preco'];
    
                                unset($movimentacoes[$codigo_vendedor][$key]);
                            }
                            
                        }
                    }
                    if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                        foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                            $unidade_negocio_id = $unidade_negocio->id;
                            if(!empty($excecoes[$movimentacao_devolucao['documento']])){
                                $unidade_negocio_id = $excecoes[$movimentacao_devolucao['documento']];
                            }else if(in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos']) && $movimentacao_devolucao['bionexo']){
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

                            if(!empty($fields['unidade_negocio'])){
                                if($fields['unidade_negocio'] === $unidade_negocio_id){
                                    if(empty($valor[$unidade_negocio_id])){
                                        $valor[$unidade_negocio_id]['total'] = 0 - $movimentacao_devolucao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id]['total'] -= $movimentacao_devolucao['preco'];
                                    }
                                    if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                        $valor[$unidade_negocio_id][$codigo_vendedor] = 0 - $movimentacao_devolucao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id][$codigo_vendedor] -= $movimentacao_devolucao['preco'];
                                    }

                                    $total['faturado_valor'] -= $movimentacao_devolucao['preco'];

                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                }
                            }else{
                                if(empty($valor[$unidade_negocio_id])){
                                    $valor[$unidade_negocio_id]['total'] = 0 - $movimentacao_devolucao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id]['total'] -= $movimentacao_devolucao['preco'];
                                }
                                if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                    $valor[$unidade_negocio_id][$codigo_vendedor] = 0 - $movimentacao_devolucao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id][$codigo_vendedor] -= $movimentacao_devolucao['preco'];
                                }

                                $total['faturado_valor'] -= $movimentacao_devolucao['preco'];

                                unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                            }
                        }
                    }
                }
            }    
        }

        $query = Premiacao::select();
        $query->where('data_meta', $data_escolhida_inicial);
        $query->with(['detalhesUnidadeNegocio' => function ($query) use($usuario_id, $tipo_usuario_id){
            if(in_array($usuario_id, $this->getGerentesId())){
                $query->where('users_id', $usuario_id);
            }
            if(in_array($tipo_usuario_id , [13])){
                $query->where('users_id', Auth::user()->responsavel);
            }
        },
         'detalhesUsuario',
        'lancamentoDebCredVendedor' => function ($query) use($data_escolhida_inicial, $data_escolhida_final){
            $query->where('codigo_motivo', 24)
            ->whereBetween('data_lancamento', [$data_escolhida_inicial, $data_escolhida_final])
            ->whereHas('devolucaoNota', function ($query){
                $query->whereHas('motivo_devolucao', function ($query){
                    $query->where('afeta_premiacao', 'sim');
                });
            });
        },
        'detalhesUsuario.confirmacaoComissao']);

        if(in_array($usuario_id, $this->getGerentesId())){
            $query->whereHas('detalhesUnidadeNegocio', function ($query) use($usuario_id){
                $query->where('users_id', $usuario_id);
            });
        }

        if(in_array($tipo_usuario_id, [13])){
            $query->whereHas('detalhesUnidadeNegocio', function ($query){
                $query->where('users_id', Auth::user()->responsavel);
            });
        }

        $query->whereHas('detalhesUnidadeNegocio');

        if(!empty($fields['unidade_negocio'])){
            $query->where('unidades_negocios_id', $fields['unidade_negocio']);
        }
        if(!empty($fields['vendedor'])){
            $query->where('codigo_vendedor', $fields['vendedor']);
        }
        $result = $query->get();

        $premiacaos = [];
        $total['meta_valor'] = 0;
        foreach($result as $premiacao){
            $atingimento_meta_porcetagem = !empty($metas[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor]) && !empty($valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor])? $valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor]/$metas[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor]*100 : 0;
            if($premiacao->detalhesUsuario->tipo_usuario_id === 16){
                if($atingimento_meta_porcetagem >= 99){
                    $premio_valor = $premiacao->comissao * 0.20;
                }else if($atingimento_meta_porcetagem >= 90 && $atingimento_meta_porcetagem < 99){
                    $premio_valor = $premiacao->comissao * 0.10;
                }else{
                    $premio_valor = 0;
                }
            }else{
                if($atingimento_meta_porcetagem >= 99){
                    $premio_valor = $premiacao->comissao * 0.05;
                }else{
                    $premio_valor = 0;
                }
            }

            if($data_escolhida_inicial->gte('2021-11-01')){
                $notas_devolvidas = $premiacao->lancamentoDebCredVendedor->count('id');
                if($notas_devolvidas == 0){
                    $premio_a_pagar = $premio_valor + ($premio_valor * 0.15);
                }
                elseif($notas_devolvidas >= 3){
                    $premio_a_pagar = 0;
                }else{
                    $premio_a_pagar = $premio_valor - ($premio_valor * 0.10);
                }
            }else{
                $premio_a_pagar = $premio_valor;
                $notas_devolvidas = 0;
            } 
           
            if(empty($premiacaos[$premiacao->unidades_negocios_id])){
                $meta = 0;
                if(!empty($fields['vendedor'])){
                    $meta = $metas[$premiacao->unidades_negocios_id][$fields['vendedor']];
                }else if(isset($metas[$premiacao->unidades_negocios_id]['total'])){
                    $meta = $metas[$premiacao->unidades_negocios_id]['total'];
                }
                $faturado_valor = 0;
                if(isset($valor[$premiacao->unidades_negocios_id]['total'])){
                    $faturado_valor = $valor[$premiacao->unidades_negocios_id]['total'];
                }

                $premiacaos[$premiacao->unidades_negocios_id] = [
                    "unidade_id" => $premiacao->unidades_negocios_id,
                    "unidade" => $premiacao->detalhesUnidadeNegocio->unidade,
                    "meta_valor" => $meta,
                    "faturado_valor" => $faturado_valor,
                    "atingimento_meta_porcetagem" => 0,
                    "comissao_valor" => $premiacao->comissao,
                    "premio_valor" => in_array($premiacao->detalhesUsuario->codigo_representante, $codigos_vendedores_gerentes)? 0 : $premio_valor,
                    "premio_a_pagar" => in_array($premiacao->detalhesUsuario->codigo_representante, $codigos_vendedores_gerentes)? 0 : $premio_a_pagar,
                    "devolucao" => in_array($premiacao->detalhesUsuario->codigo_representante, $codigos_vendedores_gerentes)? 0 : $notas_devolvidas,
                    "vendedores" => [0 => $premiacao->users_id]
                ];    
                $total_meta_valor = 0;

                if(!empty($fields['vendedor']) && isset($metas[$premiacao->unidades_negocios_id][$fields['vendedor']])){
                    $total_meta_valor = $metas[$premiacao->unidades_negocios_id][$fields['vendedor']];
                }else if(isset($metas[$premiacao->unidades_negocios_id]['total'])){
                    $total_meta_valor = $metas[$premiacao->unidades_negocios_id]['total'];
                }
               
                $total['meta_valor'] += $total_meta_valor;
            }else{
                $premiacaos[$premiacao->unidades_negocios_id]["vendedores"][] = $premiacao->users_id; 
                $premiacaos[$premiacao->unidades_negocios_id]['comissao_valor'] += $premiacao->comissao;
                $premiacaos[$premiacao->unidades_negocios_id]['premio_valor'] += in_array($premiacao->detalhesUsuario->codigo_representante, $codigos_vendedores_gerentes)? 0 : $premio_valor;
                $premiacaos[$premiacao->unidades_negocios_id]['premio_a_pagar'] += in_array($premiacao->detalhesUsuario->codigo_representante, $codigos_vendedores_gerentes)? 0 : $premio_a_pagar;
                $premiacaos[$premiacao->unidades_negocios_id]['devolucao'] += in_array($premiacao->detalhesUsuario->codigo_representante, $codigos_vendedores_gerentes)? 0 : $notas_devolvidas;
            }


            $total['comissao_valor'] += $premiacao->comissao;
            $total['premio_valor'] += in_array($premiacao->detalhesUsuario->codigo_representante, $codigos_vendedores_gerentes)? 0 : $premio_valor;
            $total['premio_a_pagar'] += in_array($premiacao->detalhesUsuario->codigo_representante, $codigos_vendedores_gerentes)? 0 : $premio_a_pagar;
            $total['devolucao'] += in_array($premiacao->detalhesUsuario->codigo_representante, $codigos_vendedores_gerentes)? 0 : $notas_devolvidas;
        }

        foreach($premiacaos as $key => $premiacao){
            $premiacaos[$key]['atingimento_meta_porcetagem'] = ($premiacao['meta_valor'] > 0) ? $premiacao['faturado_valor']/$premiacao['meta_valor']*100 : 0;
            $premiacaos[$key]['hash'] = encrypt([
                'representantes' => $premiacaos[$key]['vendedores'],
                'data_inicio_updated' => $data_escolhida_inicial,
                'data_fim_updated' => $data_escolhida_final,
                'motivos' => [8, 10, 16],
            ]);
            if(in_array($premiacao["unidade_id"], $this->habilitado_comissao_equipe)){
                if($premiacaos[$key]['atingimento_meta_porcetagem'] > 99){
                    $premiacaos[$key]['premio_valor'] = $premiacao['premio_valor'] + $premiacao['faturado_valor'] * (0.10 / 100);
                    $diferenca_premiacao = $premiacaos[$key]['premio_valor'] - $premiacao['premio_valor'];
                    $total['premio_valor'] += $diferenca_premiacao;
                    $premiacaos[$key]['premio_a_pagar'] = $premiacao['premio_a_pagar'] + $premiacao['faturado_valor'] * (0.10 / 100);
                    $diferenca_premiacao_a_pagar = $premiacaos[$key]['premio_a_pagar'] - $premiacao['premio_a_pagar'];
                    $total['premio_a_pagar'] += $diferenca_premiacao_a_pagar;
                }
            }
        }

        $total['atingimento_meta_porcetagem'] = empty($total['meta_valor'])? 0 : $total['faturado_valor']/$total['meta_valor']*100;
        $premiacaos = $this->ajusteArrayParaValores($premiacaos);
        $total = $this->ajusteArrayParaValores($total);
        
        $retorno = [
            'premiacaos' => $premiacaos,
            'mes_ano' => $fields['mes_ano'],
            'total' => $total,
            'vendedor' =>  empty($fields['vendedor'])? '' :  $fields['vendedor'],
        ];

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $retorno,
        ]);
    }

    public function atualizarPremiacao($inicio,$fim){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $query_unidade_negocio = UnidadeNegocioMeta::select();
        
        if(!empty($inicio) && !empty($fim)){
            $inicio_data = Carbon::createFromFormat('Y-m-d',$inicio)->setTime(0,0,0)->format('Y-m-d');
            $fim_data = Carbon::createFromFormat('Y-m-d',$fim)->setTime(23,59,59)->format('Y-m-d');;
            
            $query_unidade_negocio->whereBetween('data', [$inicio_data,$fim_data]);
        }else{
            $primeiro_dia_do_mes = Carbon::now()->startOfMonth();
            $query_unidade_negocio->where('data', $primeiro_dia_do_mes);
        }
        
        $query_unidade_negocio->with(['usuarios.detalhesUsuario']);
        $result_unidade_negocio = $query_unidade_negocio->get();
        
        foreach($result_unidade_negocio as $unidade_negocio){
            $primeiro_dia_do_mes = Carbon::parse($unidade_negocio->data);

            foreach($unidade_negocio->usuarios as $usuario){
                $query_premiacao = Premiacao::select();
                $query_premiacao->where('data_meta', $primeiro_dia_do_mes);
                $query_premiacao->where('unidades_negocios_id', $unidade_negocio->unidades_negocios_id);
                $query_premiacao->where('users_id', $usuario->users_id);
                $result_premiacao = $query_premiacao->first();

                if(empty($result_premiacao)){
                    $premiacaoObj = new Premiacao;
                    $premiacaoObj->data_meta = $primeiro_dia_do_mes;
                    $premiacaoObj->codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                    $premiacaoObj->users_id = $usuario->users_id;
                    $premiacaoObj->unidades_negocios_id =  $unidade_negocio->unidades_negocios_id;
                    $premiacaoObj->meta_valor = $usuario->metas;
                    $premiacaoObj->movimentacao_valor = 0;
                    $premiacaoObj->titulo_valor = 0;
                    $premiacaoObj->comissao = 0;
                    $premiacaoObj->save(); 
                }
            }
        }
        
        $arr['data'] = $primeiro_dia_do_mes->format('m/Y');
        $arr['estabelecimento'] = "";
        $arr['representantes'] = "";
        $arr['tipo'] = "";

        $ComissaoDuplicatasFiltroRequest = new ComissaoDuplicatasFiltroRequest($arr);

        $ComissaoDuplicatasController = new ComissaoDuplicatasController();
        $comissoes = $ComissaoDuplicatasController->filter($ComissaoDuplicatasFiltroRequest, true);
 
        foreach($comissoes['titulos'] as $titulo){
            $query = Premiacao::select();
            $query->where('codigo_vendedor', $titulo['cod_representante']);
            $query->where('data_meta', $primeiro_dia_do_mes);
            $result = $query->get();

            if($result->count() == 0){
                $unidadeNegocio = UnidadeNegocio::select()->where('users_id', $titulo['representante_not_parse'])->first();
                $premiacaoObj = new Premiacao;
                $premiacaoObj->data_meta = $primeiro_dia_do_mes;
                $premiacaoObj->codigo_vendedor = $titulo['cod_representante'];
                $premiacaoObj->users_id = $titulo['representante_not_parse'];
                $premiacaoObj->unidades_negocios_id =  empty($unidadeNegocio)? 1 : $unidadeNegocio->id;
                $premiacaoObj->meta_valor = 0;
                $premiacaoObj->movimentacao_valor = 0;
                $premiacaoObj->titulo_valor = parserNumber($titulo['base_comissao']);
                $premiacaoObj->comissao = parserNumber($titulo['valor_comissao']);
                $premiacaoObj->save(); 
            }else{
                foreach($result as $premiacao){
                    $premiacao->titulo_valor = parserNumber($titulo['base_comissao']);
                    $premiacao->comissao = parserNumber($titulo['valor_comissao']);
                    $premiacao->save();
                }
            } 
        }

        $query_premiacao_duplo = Premiacao::select('codigo_vendedor', DB::raw('count(*) as quantidade'));
        $query_premiacao_duplo->whereIn('unidades_negocios_id', [15,16]);
        $query_premiacao_duplo->where('data_meta', $primeiro_dia_do_mes);
        $query_premiacao_duplo->groupBy('codigo_vendedor');
        $query_premiacao_duplo->having(DB::raw('count(*)'), '>', 1);
        $result_premiacao_duplo = $query_premiacao_duplo->get();

        foreach($result_premiacao_duplo as $vendedor){
            $arr = [];

            $primeiro_dia_do_mes_anterior = Carbon::now()->startOfMonth();

            $comissao_data_fechamento = ComissaoDataFechamento::select()->where('periodo', $primeiro_dia_do_mes_anterior->format('m/Y'))->first();
        
            if(!empty($comissao_data_fechamento)){
                $primeiro_dia_do_mes_anterior = Carbon::parse($comissao_data_fechamento->data_inicio);
                $ultimo_dia_do_mes_anterior = Carbon::parse($comissao_data_fechamento->data_fim);
            }else{
                $primeiro_dia_do_mes_anterior = Carbon::now()->startOfMonth();
                $ultimo_dia_do_mes_anterior = Carbon::now()->startOfMonth();
                $primeiro_dia_do_mes_anterior = $primeiro_dia_do_mes_anterior->subMonth()->addDays(25);      
                $ultimo_dia_do_mes_anterior = $ultimo_dia_do_mes_anterior->addDays(24);
            }

            $arr['representante'] = $vendedor->codigo_vendedor;
            $arr['data_inicio'] = $primeiro_dia_do_mes_anterior->format('d/m/Y');
            $arr['data_fim'] = $ultimo_dia_do_mes_anterior->format('d/m/Y');

            $ComissaoDialogRequest = new ComissaoDialogRequest($arr);
            $ComissaoDuplicatasController = new ComissaoDuplicatasController();
            $comissoes = $ComissaoDuplicatasController->dialog($ComissaoDialogRequest, true);
            $total_15 = [
                'comissao' => 0,
                'total' => 0,
            ];
            $total_16 = [
                'comissao' => 0,
                'total' => 0,
            ];
            foreach($comissoes['titulos'] as $titulo){
                $NotaVendaItemNasajon = NotaVendaItemNasajon::select();
                $NotaVendaItemNasajon->where('id_nota', $titulo['nota_id']);
                $NotaVendaItemNasajon = $NotaVendaItemNasajon->first();
    
                if(!empty($NotaVendaItemNasajon)){
                    if(in_array($NotaVendaItemNasajon->unidade, $this->unidade_metro)){
                        $total_15['comissao'] += parserNumber($titulo['comissao']);
                        $total_15['total'] += parserNumber($titulo['valor']);
                    }else{
                        $total_16['comissao'] += parserNumber($titulo['comissao']);
                        $total_16['total'] += parserNumber($titulo['valor']);
                    }
                }else{
                    $total_16['comissao'] += parserNumber($titulo['comissao']);
                    $total_16['total'] += parserNumber($titulo['valor']);
                }
                
            }
    
            if(!empty($total_15['total'])){
                $query = Premiacao::select();
                $query->where('codigo_vendedor', $arr['representante']);
                $query->where('data_meta', $primeiro_dia_do_mes);
                $query->where('unidades_negocios_id', 15);
                $result_15 = $query->first();
        
                $result_15->titulo_valor = $total_15['total'];
                $result_15->comissao = $total_15['comissao'];
                $result_15->save(); 
            }
            
            if(!empty($total_16['total'])){
                $query_16 = Premiacao::select();
                $query_16->where('codigo_vendedor', $arr['representante']);
                $query_16->where('data_meta', $primeiro_dia_do_mes);
                $query_16->where('unidades_negocios_id', 16);
                $result_16 = $query_16->first();
                
                $result_16->titulo_valor = $total_16['total'];
                $result_16->comissao = $total_16['comissao'];
                $result_16->save();
            }
        }
    }

    private function ajusteArrayParaValores($array){
        if(is_array($array)){
            foreach($array as $key => $value){
                if(is_array($value)){
                    if(substr_count($key, "vendedores") !== 0){
                        return $array;
                    }else{
                        $array[$key] = $this->ajusteArrayParaValores($value);
                    }
                }else{
                    if(is_numeric($value)){
                        if(substr_count($key, "codigo") === 0 && substr_count($key, "id") === 0){
                            if(substr_count($key, "porcetagem") === 0){
                                if($key === 'devolucao'){
                                    $array[$key] = $value > 0 ? $value : '';
                                }else{
                                    $array[$key] = empty($value)? '': parserValor($value);
                                }
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

    public function modalMembros(Request $request){
        $fields = $request->only('mes_ano', 'unidade_id', 'vendedor');

        $tipo_usuario_id = empty(Auth::user())? 1 : Auth::user()->tipo_usuario_id;
        $usuario_id = empty(Auth::id())? 1 : Auth::id();
        
        $data_escolhida = '01/'.$fields['mes_ano'];
        $separado_data = explode('/', $data_escolhida);
        $ultimo_dia = $this->getUltimoDiaMes($separado_data[1], $separado_data[2]);

        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
        $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$fields['mes_ano'])->setTime(0,0,0);

        if($data_escolhida_final->lte(Carbon::now()->format('Y-m'))){
            $comissao_fechamento = true;
        }else{
            $comissao_fechamento = false;
        }

        $excecoes = $this->excecoes($data_escolhida_inicial, $data_escolhida_final);

        $clientes_bionexo = $this->clientesBionexo();

        $query = UnidadeNegocio::select();

        if(in_array($usuario_id, $this->getGerentesId())){
            $query->where('users_id', $usuario_id);
        }
        
        $query->with(['metas' => function($query) use($data_escolhida_inicial){
            $query->where('data', $data_escolhida_inicial);
            if(!empty($fields['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($fields){
                    $query->where('codigo_representante', $fields['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);
        $query->where('id', $fields['unidade_id']);

        $result = $query->get();

        $query_movimentacao = Movimentacao::select('vendedor', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'cliente_codigo', 'estabelecimento', DB::raw('sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total'));
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        if(!empty($fields['vendedor'])){
            $query_movimentacao->where('vendedor', $fields['vendedor']);
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

        $query_movimentacao = Movimentacao::select('vendedor', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'cliente_codigo', 'estabelecimento', DB::raw('sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total'));
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);
        if(!empty($fields['vendedor'])){
            $query_movimentacao->where('vendedor', $fields['vendedor']);
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
        $codigos_vendedores = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        
        $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
        $usuarios_fashion_2 = $this->getUserFashion2($data_escolhida_inicial);
        $usuarios_outfites_confeccionados = $this->getUserOutfitsConfeccionados($data_escolhida_inicial); 

        $valor = [];
        $meta = [];
        $total = [
            "meta_valor" => 0,
            "faturado_valor" => 0,
            "atingimento_meta_porcetagem" => 0, 
            "comissao_valor" => 0,
            "premio_valor" => 0, 
            "premio_a_pagar" => 0,
        ];

        foreach($result as $unidade_negocio){
            if($unidade_negocio->metas->count() > 0){
                $metas[$unidade_negocio->id]['total'] = $unidade_negocio->metas->count() === 0? '' : $unidade_negocio->metas[0]->valor;
                $total['meta_valor'] += $unidade_negocio->metas->count() === 0? 0 : $unidade_negocio->metas[0]->valor;
                foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                    $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                    $metas[$unidade_negocio->id][$codigo_vendedor] = $usuario->metas;
                    if(empty($meta[$unidade_negocio->id])){
                        $meta[$unidade_negocio->id] = $usuario->metas;
                    }else{
                        $meta[$unidade_negocio->id] += $usuario->metas;
                    }
                    if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                        foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                            $unidade_negocio_id = $unidade_negocio->id;
                            if(!empty($excecoes[$movimentacao['documento']])){
                                $unidade_negocio_id = $excecoes[$movimentacao['documento']];
                            }else if(in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos']) && $movimentacao['bionexo']){
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

                            if(!empty($fields['unidade_negocio'])){
                                if(intval($fields['unidade_negocio']) === $unidade_negocio_id){
                                    if(empty($valor[$unidade_negocio_id])){
                                        $valor[$unidade_negocio_id]['total'] = $movimentacao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id]['total'] += $movimentacao['preco'];
                                    }
                                    if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                        $valor[$unidade_negocio_id][$codigo_vendedor] = $movimentacao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id][$codigo_vendedor] += $movimentacao['preco'];
                                    }

                                    $total['faturado_valor'] += $movimentacao['preco'];
                                    
                                    unset($movimentacoes[$codigo_vendedor][$key]);
                                }
                            }else{
                                if(empty($valor[$unidade_negocio_id])){
                                    $valor[$unidade_negocio_id]['total'] = $movimentacao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id]['total'] += $movimentacao['preco'];
                                }
                                if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                    $valor[$unidade_negocio_id][$codigo_vendedor] = $movimentacao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id][$codigo_vendedor] += $movimentacao['preco'];
                                }

                                $total['faturado_valor'] += $movimentacao['preco'];
    
                                unset($movimentacoes[$codigo_vendedor][$key]);
                            }
                            
                        }
                    }
                    if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                        foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                            $unidade_negocio_id = $unidade_negocio->id;
                            if(!empty($excecoes[$movimentacao_devolucao['documento']])){
                                $unidade_negocio_id = $excecoes[$movimentacao_devolucao['documento']];
                            }else if(in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos']) && $movimentacao_devolucao['bionexo']){
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

                            if(!empty($fields['unidade_negocio'])){
                                if($fields['unidade_negocio'] === $unidade_negocio_id){
                                    if(empty($valor[$unidade_negocio_id])){
                                        $valor[$unidade_negocio_id]['total'] = 0 - $movimentacao_devolucao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id]['total'] -= $movimentacao_devolucao['preco'];
                                    }
                                    if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                        $valor[$unidade_negocio_id][$codigo_vendedor] = 0 - $movimentacao_devolucao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id][$codigo_vendedor] -= $movimentacao_devolucao['preco'];
                                    }

                                    $total['faturado_valor'] -= $movimentacao_devolucao['preco'];

                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                }
                            }else{
                                if(empty($valor[$unidade_negocio_id])){
                                    $valor[$unidade_negocio_id]['total'] = 0 - $movimentacao_devolucao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id]['total'] -= $movimentacao_devolucao['preco'];
                                }
                                if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                    $valor[$unidade_negocio_id][$codigo_vendedor] = 0 - $movimentacao_devolucao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id][$codigo_vendedor] -= $movimentacao_devolucao['preco'];
                                }

                                $total['faturado_valor'] -= $movimentacao_devolucao['preco'];

                                unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                            }
                        }
                    }
                }
            }    
        }

        $data_escolhida_inicial_lacamento = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
        $codigos_vendedores_gerentes = $this->getGerentesCodRepresentante();

        $query = Premiacao::select();
        $query->where('data_meta', $data_escolhida_inicial);
        $query->with(['detalhesUnidadeNegocio', 'detalhesUsuario' => function($query) use($fields){
            if(!empty($fields['vendedor'])){
                $query->where('codigo_representante', $fields['vendedor']);
            }
        },'detalhesUsuario.detalhesRepresentanteCliente', 'detalhesUsuario.detalhesRepresentanteFornecedor',
        'detalhesUsuario.confirmacaoComissao' => function($query) use ($data_escolhida_inicial){
            $data_inicio = $data_escolhida_inicial->addMonth();
            $data_fim = $data_escolhida_inicial->format('Y-m-t 23:59:59');
            
            $query->whereBetween('created_at',[$data_inicio,$data_fim])
            ->where('tipo','premio')
            ->orWhere('tipo','premio');
        },
        'lancamentoDebCredVendedor' => function ($query) use($data_escolhida_inicial_lacamento, $data_escolhida_final){
            $query->where('codigo_motivo', 24)
            ->whereBetween('data_lancamento', [$data_escolhida_inicial_lacamento, $data_escolhida_final])
            ->whereHas('devolucaoNota', function ($query){
                $query->whereHas('motivo_devolucao', function ($query){
                    $query->where('afeta_premiacao', 'sim');
                });
            });
        }]);
        if(!empty($fields['vendedor'])){
            $query->whereHas('detalhesUsuario', function($query) use($fields){
                    $query->where('codigo_representante', $fields['vendedor']);
            });
        }
        $query->where('unidades_negocios_id', $fields['unidade_id']);

        $representantes = $query->pluck('codigo_vendedor');
        $result = $query->get();

        $premiacaos = [];
        $vendedores = [];
        $gerente = [];
        $quantidade_vendedores = 0;
        $codigo_vendedores = []; 
        $total = [
            "meta_valor" => 0,
            "faturado_valor" => 0,
            "atingimento_meta_porcetagem" => 0, 
            "comissao_valor" => 0,
            "premio_valor" => 0, 
            "premio_a_pagar" => 0,
            "devolucao" => 0,
            'premio_devolucao_valor' => 0
        ];

        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);

        foreach($result as $premiacao){
            $atingimento_meta_porcetagem = !empty($metas[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor]) && !empty($valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor])? $valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor]/$metas[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor]*100 : 0;
            if($premiacao->detalhesUsuario->tipo_usuario_id === 16 || $premiacao->detalhesUsuario->tipo_usuario_id === 13){
                if($atingimento_meta_porcetagem >= 99){
                    $premiacao_porcetagem = 20;
                }else if($atingimento_meta_porcetagem >= 90 && $atingimento_meta_porcetagem < 99){
                    $premiacao_porcetagem = 10;
                }else{
                    $premiacao_porcetagem = 0;
                }
            }else{
                if($atingimento_meta_porcetagem >= 99){
                    $premiacao_porcetagem = 5;
                }else{
                    $premiacao_porcetagem = 0;
                }
            }

            $premio_valor = $premiacao->comissao * ($premiacao_porcetagem/100);

            if($data_escolhida_inicial->gte('2021-11-01')){
                $notas_devolvidas = $premiacao->lancamentoDebCredVendedor->count('id');
                if($notas_devolvidas == 0){
                    if(!in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes) && $premiacao->detalhesUsuario->tipo_usuario_id !== 12){
                        $premio_devolucao_percentual = 15;
                        $premio_devolucao = $premio_valor * ($premio_devolucao_percentual/100);
                        $premio_a_pagar = $premio_valor + $premio_devolucao;
                    }else{
                        $premio_devolucao_percentual = 0;
                        $premio_devolucao = 0;
                        $premio_a_pagar = $premio_valor + $premio_devolucao;
                    }
                }
                elseif($notas_devolvidas >= 3){
                    $premio_devolucao = 0;
                    $premio_devolucao_percentual = 0;
                    $premio_a_pagar = 0;
                }else{
                    $premio_devolucao_percentual = 10;
                    $premio_devolucao = $premio_valor * ($premio_devolucao_percentual/100);
                    $premio_a_pagar = $premio_valor + $premio_devolucao;
                }
            }else{
                $premio_devolucao_percentual = 0;
                $premio_a_pagar = $premio_valor;
                $notas_devolvidas = 0;
                $premio_devolucao = 0;
            }

            $cnpj_do_representante = '';
            if(!empty($premiacao->detalhesUsuario->detalhesRepresentanteCliente)){
                $cnpj_do_representante = empty($premiacao->detalhesUsuario->detalhesRepresentanteCliente->cpf_cnpj)? '' : $premiacao->detalhesUsuario->detalhesRepresentanteCliente->cpf_cnpj;
            }else if(!empty($premiacao->detalhesUsuario->detalhesRepresentanteFornecedor)){
                $cnpj_do_representante = empty($premiacao->detalhesUsuario->detalhesRepresentanteFornecedor->cpf_cnpj)? '' : $premiacao->detalhesUsuario->detalhesRepresentanteFornecedor->cpf_cnpj;
            }

            $verifica_confirmacao_comissao = 'Não';
            $link = md5($premiacao->detalhesUsuario->id.$fields['mes_ano'].$premiacao->detalhesUsuario->name.'premio');

            if(count($premiacao->detalhesUsuario->confirmacaoComissao) > 0){
                if($premiacao->detalhesUsuario->confirmacaoComissao->where('link', $link)->pluck('confirmacao')->implode('') == true){
                    $verifica_confirmacao_comissao = 'Sim';
                }else if($premiacao->detalhesUsuario->confirmacaoComissao->where('link', $link)->pluck('confirmacao')->implode('') == false){
                    $verifica_confirmacao_comissao = 'Não';
                }
            }

            $data_hoje = Carbon::now();

            $comissao_fechamento_data = ComissaoDataFechamento::where(DB::raw('data_fim +1'),$data_hoje)
            ->orWhere(DB::raw('data_fim +2'),$data_hoje)
            ->orWhere(DB::raw('data_fim +3'),$data_hoje)
            ->orWhere(DB::raw('data_fim +4'),$data_hoje)
            ->first();

            $verificar_fechamento = (!empty($comissao_fechamento_data )) ? true : false;

            $vendedores[$premiacao->codigo_vendedor] = [
                "id_user" => $premiacao->users_id,
                "vendedor" => empty($cnpj_do_representante)? $premiacao->codigo_vendedor." - ".$premiacao->detalhesUsuario->name : $premiacao->codigo_vendedor." - ".$premiacao->detalhesUsuario->name." - ".$cnpj_do_representante,
                "meta_valor" => empty($metas[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor])? 0 : $metas[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor],
                "faturado_valor" => !empty($valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor])? $valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor] : 0,
                "atingimento_meta_porcetagem" => $atingimento_meta_porcetagem,
                "comissao_valor" => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premiacao->comissao,
                "premio_valor" => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premio_valor,
                "premio_a_pagar" => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premio_a_pagar,
                "premiacao_porcetagem" => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premiacao_porcetagem,
                "confirmacao" => $verifica_confirmacao_comissao,
                "devolucao" => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 :$notas_devolvidas,
                'premio_devolucao_porcetagem' => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premio_devolucao_percentual,
                'premio_devolucao_valor' => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premio_devolucao,
                "comissao_fechamento" => $comissao_fechamento,
                "periodo" => $fields['mes_ano'],
                "verifica_fechamento" => $verificar_fechamento
            ];
            
            $total['meta_valor'] += empty($metas[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor])? 0 : $metas[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor];
            $total['faturado_valor'] += !empty($valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor])? $valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor] : 0;
            $total['comissao_valor'] += $premiacao->comissao;
            $total['premio_valor'] += in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premio_valor;
            $total['premio_a_pagar'] += in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premio_a_pagar;
            $total['devolucao'] += in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 :$notas_devolvidas;
            $total['premio_devolucao_valor'] += in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premio_devolucao;
            
            if(!in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes) && $premiacao->detalhesUsuario->tipo_usuario_id !== 12){
                $quantidade_vendedores++;
                $codigo_vendedores[] = $premiacao->codigo_vendedor;
            }
            
        }

        $total['atingimento_meta_porcetagem'] = empty($total['meta_valor'])? 0 : $total['faturado_valor']/$total['meta_valor']*100;

        $unidadeNegocioObj = UnidadeNegocio::with(['detalhesUsuarioResponsavel.confirmacaoComissao' => function($query) use ($data_escolhida_inicial){
            $data_inicio = $data_escolhida_inicial->addMonth();
            $data_fim = $data_escolhida_inicial->format('Y-m-t 23:59:59');
            
            $query->whereBetween('created_at',[$data_inicio,$data_fim])
            ->where('tipo','premio')
            ->orWhere('tipo', 'premio');
        }])->find($fields['unidade_id']);

        if($fields['mes_ano'] == '03/2021'){
            $premiacao_porcetagem = 20;
        }else if($total['atingimento_meta_porcetagem'] >= 99){
            $premiacao_porcetagem = 20;
        }else if($total['atingimento_meta_porcetagem'] >= 90 && $total['atingimento_meta_porcetagem'] < 99){
            $premiacao_porcetagem = 10;
        }else{
            $premiacao_porcetagem = 0;
        }

        $primeiro_dia_do_mes_anterior = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
        $ultimo_dia_do_mes_anterior = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
 
        // $arr['representante'] = $unidadeNegocioObj->detalhesUsuarioResponsavel->id;
        // $arr['data_inicio'] = $primeiro_dia_do_mes_anterior->format('d/m/Y');
        // $arr['data_fim'] = $ultimo_dia_do_mes_anterior->format('d/m/Y');

        // $ComissaoDialogRequest = new ComissaoDialogRequest($arr);
        // $ComissaoDuplicatasController = new ComissaoDuplicatasController();
        // $comissao = $ComissaoDuplicatasController->dialog($ComissaoDialogRequest, true);

        $comissao_gerente_premiacao = Premiacao::where('users_id', $unidadeNegocioObj->detalhesUsuarioResponsavel->id)
        ->where('data_meta', $primeiro_dia_do_mes_anterior)
        ->first();

        $subordinados = User::find($unidadeNegocioObj->detalhesUsuarioResponsavel->id);

        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
        $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$fields['mes_ano'])->setTime(0,0,0);

        $notas_devolvidas = LancamentoDebCredVendedor::where('codigo_motivo', 24)
        ->whereBetween('data_lancamento', [$data_escolhida_inicial, $data_escolhida_final])
        ->whereIn('codigo_vendedor', [$unidadeNegocioObj->detalhesUsuarioResponsavel->id])
        ->whereHas('devolucaoNota', function ($query){
            $query->whereHas('motivo_devolucao', function ($query){
                $query->where('afeta_premiacao', 'sim');
            });
        })
        ->count();

        $premio_valor_gerente = (isset($comissao_gerente_premiacao->comissao)) ? $comissao_gerente_premiacao->comissao * ($premiacao_porcetagem / 100) : null;
    
        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);

        $tipo_vendedor = $subordinados->subordinados->where('tipo_usuario_id', 12)->count();

        if(isset($comissao_gerente_premiacao->codigo_vendedor)){
            if($data_escolhida_inicial->gte('2021-11-01')){
                if($data_escolhida_inicial->gte('2023-03-01')){
                    if(!in_array($subordinados->id, [38, 49])){
                        if($notas_devolvidas == 0){
                            $premio_devolucao_percentual = 15;
                            $premio_devolucao = $premio_valor_gerente * ($premio_devolucao_percentual/100);
                            $premioAPagarGerente = $premio_valor_gerente + $premio_devolucao;
                        }
                        elseif($notas_devolvidas >= 3){
                            $premioAPagarGerente = 0;
                            $premio_devolucao_percentual = 0;
                            $premio_devolucao = 0;
                        }else{
                            $premio_devolucao_percentual = 10;
                            $premio_devolucao = $premio_valor_gerente * ($premio_devolucao_percentual/100);
                            $premioAPagarGerente = $premio_valor_gerente - $premio_devolucao;
                        }
                    }else{
                        $premio_devolucao_percentual = 0;
                        $premio_devolucao = 0;
                        $premioAPagarGerente = $premio_valor_gerente;
                    }   
                }else{
                    if($notas_devolvidas == 0){
                        $premio_devolucao_percentual = 15;
                        $premio_devolucao = $premio_valor_gerente * ($premio_devolucao_percentual/100);
                        $premioAPagarGerente = $premio_valor_gerente + $premio_devolucao;
                    }
                    elseif($notas_devolvidas >= 3){
                        $premioAPagarGerente = 0;
                        $premio_devolucao_percentual = 0;
                        $premio_devolucao = 0;
                    }else{
                        $premio_devolucao_percentual = 10;
                        $premio_devolucao = $premio_valor_gerente * ($premio_devolucao_percentual/100);
                        $premioAPagarGerente = $premio_valor_gerente - $premio_devolucao;
                    }
                }                             
            }else{
                $premioAPagarGerente = $premio_valor_gerente;
                $notas_devolvidas = 0;
                $premio_devolucao_percentual = 0;
                $premio_devolucao = 0;
            }
        }else{
            $notas_devolvidas = 0;
            $premioAPagarGerente = 0;
            $premio_devolucao_percentual = 0;
            $premio_devolucao = 0;
        }

        $verificaConfirmacaoComissaoGerente = 'Não';
        $link = md5($unidadeNegocioObj->detalhesUsuarioResponsavel->id.$fields['mes_ano'].$unidadeNegocioObj->detalhesUsuarioResponsavel->name.'premio');

        if(count($unidadeNegocioObj->detalhesUsuarioResponsavel->confirmacaoComissao)){
            if($unidadeNegocioObj->detalhesUsuarioResponsavel->confirmacaoComissao->where('link', $link)->pluck('confirmacao')->implode('') == true){
                $verificaConfirmacaoComissaoGerente = 'Sim';
            }else if($unidadeNegocioObj->detalhesUsuarioResponsavel->confirmacaoComissao->where('link', $link)->pluck('confirmacao')->implode('') == false){
                $verificaConfirmacaoComissaoGerente = 'Não';
            }
        }
        
        $gerente = [
            'nome' => $unidadeNegocioObj->detalhesUsuarioResponsavel->name,
            'id_user' => $unidadeNegocioObj->users_id,
            'comissao' => (isset($comissao_gerente_premiacao->comissao)) ? $comissao_gerente_premiacao->comissao : null,
            'premio_porcetagem' => $premiacao_porcetagem,
            'premio_valor' => $premio_valor_gerente,
            'premio_a_pagar' => isset($premioAPagarGerente) ? $premioAPagarGerente : '',
            'devolucao' => isset($notas_devolvidas) ? $notas_devolvidas : '',
            'confirmacao' => $verificaConfirmacaoComissaoGerente,
            'premio_devolucao_porcentagem' => isset($premio_devolucao_percentual) ? $premio_devolucao_percentual : '',
            'premio_devolucao_valor' => isset($premio_devolucao) ? $premio_devolucao : '',
            'comissao_fechamento' => $comissao_fechamento,
            'periodo' => $fields['mes_ano']
        ];
        
        if(in_array($fields['unidade_id'], $this->habilitado_comissao_equipe)){
            $total['premio_equipe'] = 0;
            $total['premio_total'] = 0;

            $premio_equipe_vendedor = 0;
            $premio_equipe_porcetagem = 0;

            if($total['atingimento_meta_porcetagem'] >= 99){
                $premio_equipe_porcetagem = 0.10;
                
                $total['premio_equipe'] = $total['faturado_valor'] * $premio_equipe_porcetagem / 100;
                $total['premio_total'] = $total['premio_equipe'] + $total['premio_a_pagar'];
    
                $premio_equipe_vendedor = $total['premio_equipe'] / $quantidade_vendedores;
            }

            foreach($vendedores as $key => $vendedor){
                $vendedores[$key]['premio_equipe'] = 0;
                $vendedores[$key]['premio_total'] = 0;
                $vendedores[$key]['premio_porcetagem_equipe'] = 0;
                if(in_array($key, $codigo_vendedores)){
                    $vendedores[$key]['premio_porcetagem_equipe'] = $premio_equipe_porcetagem;
                    $vendedores[$key]['premio_equipe'] = $premio_equipe_vendedor;
                    $vendedores[$key]['premio_total'] = $premio_equipe_vendedor + $vendedores[$key]['premio_a_pagar'];
                }
            }
        }

        $vendedores = $this->ajusteArrayParaValores($vendedores);
        $gerente = $this->ajusteArrayParaValores($gerente);
        $total = $this->ajusteArrayParaValores($total);

        return view('programs.premiacao.modal.membros')->with(['vendedores' => $vendedores, 'gerente' => $gerente, 'total' => $total, 'unidade_id' => $fields['unidade_id'], 'habilitado_comissao_equipe' => $this->habilitado_comissao_equipe]);
    }

    public function getUltimoDiaMes($mes, $ano){
        $ultimo_dia = date("t", mktime(0,0,0,$mes,'01',$ano));

        return $ultimo_dia;
    }

    public function excecoes($data_inicial, $data_final){
        $query = MapaVendaExcecao::select();
        $query->whereBetween('data_emissao', [$data_inicial, $data_final]);
        $result = $query->get();

        $excecoes = [];

        foreach($result as $excecao){
            $excecoes[$excecao->numero_nota.$excecao->estabelecimento_codigo] = $excecao->unidades_negocios_id;
        }

        return $excecoes;
    }

    private function getGerentesId(){
        $query = UnidadeNegocio::select();
        $result = $query->get();

        $gerentes = [];
        foreach($result as $unidade_negocio){
            $gerentes[] = $unidade_negocio->users_id;
        }

        return $gerentes;
    }

    public function filtroRepresentante(Request $request){
        $fields = $request->only('mes_ano', 'unidade_id', 'vendedor');
        
        $tipo_usuario_id = empty(Auth::user())? 1 : Auth::user()->tipo_usuario_id;
        $usuario_id = empty(Auth::id())? 1 : Auth::id();
        
        $data_escolhida = '01/'.$fields['mes_ano'];
        $separado_data = explode('/', $data_escolhida);
        $ultimo_dia = $this->getUltimoDiaMes($separado_data[1], $separado_data[2]);

        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
        $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$fields['mes_ano'])->setTime(0,0,0);
        $data_hoje = Carbon::now();

        $comissao_fechamento_query = ComissaoDataFechamento::where(DB::raw('data_fim +1'),$data_hoje)
        ->orWhere(DB::raw('data_fim +2'),$data_hoje)
        ->orWhere(DB::raw('data_fim +3'),$data_hoje)
        ->orWhere(DB::raw('data_fim +4'),$data_hoje)
        ->first();

        $verifica_mes = $data_hoje;
        $verifica_mes = $verifica_mes->subMonth()->format('m/Y');
        $verifica_mes_consulta = $data_escolhida_inicial;
        $verifica_mes_consulta = $verifica_mes_consulta->format('m/Y');
        
        if(!empty($comissao_fechamento_query) && $verifica_mes == $verifica_mes_consulta){
            $comissao_fechamento = true;
        }else{
            $comissao_fechamento = false;
        }
        
        $excecoes = $this->excecoes($data_escolhida_inicial, $data_escolhida_final);

        $clientes_bionexo = $this->clientesBionexo();

        $query = UnidadeNegocio::select();

        if(in_array($usuario_id, $this->getGerentesId())){
            $query->where('users_id', $usuario_id);
        }
        
        $query->with(['metas' => function($query) use($data_escolhida_inicial){
            $query->where('data', $data_escolhida_inicial);
            if(!empty($fields['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($fields){
                    $query->where('codigo_representante', $fields['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);
        if(!empty($fields['unidade_id'])){
            $query->where('id', $fields['unidade_id']);
        }
        
        $result = $query->get();

        $query_movimentacao = Movimentacao::select('vendedor', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'cliente_codigo', 'estabelecimento', DB::raw('sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total'));
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
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

        $query_movimentacao = Movimentacao::select('vendedor', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'cliente_codigo', 'estabelecimento', DB::raw('sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total'));
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);
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
        $codigos_vendedores = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        
        $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
        $usuarios_fashion_2 = $this->getUserFashion2($data_escolhida_inicial);
        $usuarios_outfites_confeccionados = $this->getUserOutfitsConfeccionados($data_escolhida_inicial); 

        $valor = [];
        $meta = [];
        $total = [
            "meta_valor" => 0,
            "faturado_valor" => 0,
            "atingimento_meta_porcetagem" => 0, 
            "comissao_valor" => 0,
            "premio_a_pagar" => 0,
            "premio_valor" => 0, 
            "devolucao" => 0
        ];

        foreach($result as $unidade_negocio){
            if($unidade_negocio->metas->count() > 0){
                $metas[$unidade_negocio->id]['total'] = $unidade_negocio->metas->count() === 0? '' : $unidade_negocio->metas[0]->valor;
                $total['meta_valor'] += $unidade_negocio->metas->count() === 0? 0 : $unidade_negocio->metas[0]->valor;
                foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                    if(empty($usuario->detalhesUsuario->codigo_representante)){
                        continue;
                    }
                    $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                    $metas[$unidade_negocio->id][$codigo_vendedor] = $usuario->metas;
                    if(empty($meta[$unidade_negocio->id])){
                        $meta[$unidade_negocio->id] = $usuario->metas;
                    }else{
                        $meta[$unidade_negocio->id] += $usuario->metas;
                    }
                    if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                        foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                            $unidade_negocio_id = $unidade_negocio->id;
                            if(!empty($excecoes[$movimentacao['documento']])){
                                $unidade_negocio_id = $excecoes[$movimentacao['documento']];
                            }else if(in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos']) && $movimentacao['bionexo']){
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

                            if(!empty($fields['unidade_negocio'])){
                                if(intval($fields['unidade_negocio']) === $unidade_negocio_id){
                                    if(empty($valor[$unidade_negocio_id])){
                                        $valor[$unidade_negocio_id]['total'] = $movimentacao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id]['total'] += $movimentacao['preco'];
                                    }
                                    if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                        $valor[$unidade_negocio_id][$codigo_vendedor] = $movimentacao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id][$codigo_vendedor] += $movimentacao['preco'];
                                    }

                                    $total['faturado_valor'] += $movimentacao['preco'];
                                    
                                    unset($movimentacoes[$codigo_vendedor][$key]);
                                }
                            }else{
                                if(empty($valor[$unidade_negocio_id])){
                                    $valor[$unidade_negocio_id]['total'] = $movimentacao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id]['total'] += $movimentacao['preco'];
                                }
                                if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                    $valor[$unidade_negocio_id][$codigo_vendedor] = $movimentacao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id][$codigo_vendedor] += $movimentacao['preco'];
                                }

                                $total['faturado_valor'] += $movimentacao['preco'];
    
                                unset($movimentacoes[$codigo_vendedor][$key]);
                            }
                            
                        }
                    }
                    if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                        foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                            $unidade_negocio_id = $unidade_negocio->id;
                            if(!empty($excecoes[$movimentacao_devolucao['documento']])){
                                $unidade_negocio_id = $excecoes[$movimentacao_devolucao['documento']];
                            }else if(in_array($codigo_vendedor, $usuarios_outfites_confeccionados['codigos']) && $movimentacao_devolucao['bionexo']){
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

                            if(!empty($fields['unidade_negocio'])){
                                if($fields['unidade_negocio'] === $unidade_negocio_id){
                                    if(empty($valor[$unidade_negocio_id])){
                                        $valor[$unidade_negocio_id]['total'] = 0 - $movimentacao_devolucao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id]['total'] -= $movimentacao_devolucao['preco'];
                                    }
                                    if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                        $valor[$unidade_negocio_id][$codigo_vendedor] = 0 - $movimentacao_devolucao['preco'];
                                    }else{
                                        $valor[$unidade_negocio_id][$codigo_vendedor] -= $movimentacao_devolucao['preco'];
                                    }

                                    $total['faturado_valor'] -= $movimentacao_devolucao['preco'];

                                    unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                }
                            }else{
                                if(empty($valor[$unidade_negocio_id])){
                                    $valor[$unidade_negocio_id]['total'] = 0 - $movimentacao_devolucao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id]['total'] -= $movimentacao_devolucao['preco'];
                                }
                                if(empty($valor[$unidade_negocio_id][$codigo_vendedor])){
                                    $valor[$unidade_negocio_id][$codigo_vendedor] = 0 - $movimentacao_devolucao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id][$codigo_vendedor] -= $movimentacao_devolucao['preco'];
                                }

                                $total['faturado_valor'] -= $movimentacao_devolucao['preco'];

                                unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                            }
                        }
                    }
                }
            }    
        }

        $quantidade_vendedores = [];
        $codigos_vendedores_gerentes = $this->getGerentesCodRepresentante();
        foreach($result as $unidade_negocio){
            if($unidade_negocio->metas->count() > 0){
                foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                    if(empty($usuario->detalhesUsuario->codigo_representante)){
                        continue;
                    }
                    if(!in_array($usuario->detalhesUsuario->codigo_representante, $codigos_vendedores_gerentes) && $usuario->detalhesUsuario->tipo_usuario_id !== 12){
                        if(empty($quantidade_vendedores[$unidade_negocio->id])){
                            $quantidade_vendedores[$unidade_negocio->id] = 1;
                        }else{
                            $quantidade_vendedores[$unidade_negocio->id]++;
                        }
                    }
                }
            }
        }

        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
        $codigos_vendedores_gerentes = $this->getGerentesCodRepresentante();

        $query = Premiacao::select();
        $query->where('data_meta', $data_escolhida_inicial);
        $query->where('meta_valor', '>',0);
        $query->with(['detalhesUnidadeNegocio', 'detalhesUsuario' => function($query) use($fields){
            if(!empty($fields['vendedor'])){
                $query->where('codigo_representante', $fields['vendedor']);
            }
        },
        'lancamentoDebCredVendedor' => function ($query) use($data_escolhida_inicial, $data_escolhida_final){
            $query->where('codigo_motivo', 24)
            ->whereBetween('data_lancamento', [$data_escolhida_inicial, $data_escolhida_final])
            ->whereHas('devolucaoNota', function ($query){
                $query->whereHas('motivo_devolucao', function ($query){
                    $query->where('afeta_premiacao', 'sim');
                });
            });
        },
        'detalhesUsuario.confirmacaoComissao']);
        if(!empty($fields['vendedor'])){
            $query->whereHas('detalhesUsuario', function($query) use($fields){
                    $query->where('codigo_representante', $fields['vendedor']);
            });
        }
        if(!empty($fields['unidade_id'])){
            $query->where('unidades_negocios_id', $fields['unidade_id']);
        }

        $result = $query->get();

        $representantes = $result->pluck('codigo_vendedor');

        $$data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
        $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$fields['mes_ano'])->setTime(0,0,0);

        $premiacaos = [];
        $vendedores = [];
        $gerente = [];
        $codigo_vendedores = []; 
        $devolucaoVendedor = [];
        $total_representante = [
            "meta_valor" => 0,
            "faturado_valor" => 0,
            "atingimento_meta_porcetagem" => 0, 
            "comissao_valor" => 0,
            "premio_valor" => 0, 
            "premio_a_pagar" => 0,
            "devolucao" => 0,
            'premio_equipe' => 0,
        ];

        foreach($result as $premiacao){
            $atingimento_meta_porcetagem = !empty($metas[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor]) && !empty($valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor])? $valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor]/$metas[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor]*100 : 0;
            if($premiacao->detalhesUsuario->tipo_usuario_id === 16){
                if($atingimento_meta_porcetagem >= 99){
                    $premiacao_porcetagem = 20;
                }else if($atingimento_meta_porcetagem >= 90 && $atingimento_meta_porcetagem < 99){
                    $premiacao_porcetagem = 10;
                }else{
                    $premiacao_porcetagem = 0;
                }
            }else{
                if($atingimento_meta_porcetagem >= 99){
                    $premiacao_porcetagem = 5;
                }else{
                    $premiacao_porcetagem = 0;
                }
            }

            $premio_valor = $premiacao->comissao * ($premiacao_porcetagem/100);
            if($data_escolhida_inicial->gte('2021-11-01') && Auth::user()->tipo_usuario_id != 12){
                $notas_devolvidas = $premiacao->lancamentoDebCredVendedor->count('id');
                if($notas_devolvidas == 0){
                    $premio_a_pagar = $premio_valor + ($premio_valor * 0.15);
                }
                elseif($notas_devolvidas >= 3){
                    $premio_a_pagar = 0;
                }else{
                    $premio_a_pagar = $premio_valor - ($premio_valor * 0.10);
                }
            }else{
                $premio_a_pagar = $premio_valor;
                $notas_devolvidas = 0;
            }

            $verifica_confirmacao_comissao = 'Não';
            $link = md5($premiacao->detalhesUsuario->id.$fields['mes_ano'].$premiacao->detalhesUsuario->name.'premio');

            if(count($premiacao->detalhesUsuario->confirmacaoComissao) > 0){
                if($premiacao->detalhesUsuario->confirmacaoComissao->where('link', $link)->pluck('confirmacao')->implode('') == true){
                    $verifica_confirmacao_comissao = 'Sim';
                }else if($premiacao->detalhesUsuario->confirmacaoComissao->where('link', $link)->pluck('confirmacao')->implode('') == false){
                    $verifica_confirmacao_comissao = 'Não';
                }
            }
            
            $vendedores[$premiacao->codigo_vendedor] = [
                'unidade_id'=> $premiacao->detalhesUnidadeNegocio->id,
                'unidade'=> $premiacao->detalhesUnidadeNegocio->unidade,
                "vendedor" => $premiacao->codigo_vendedor." - ".$premiacao->detalhesUsuario->name,
                "meta_valor" => $metas[$premiacao->unidades_negocios_id][$fields['vendedor']],
                "faturado_valor" => !empty($valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor])? $valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor] : 0,
                "atingimento_meta_porcetagem" => $atingimento_meta_porcetagem,
                "comissao_valor" => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premiacao->comissao,
                "premio_valor" => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premio_valor,
                "premio_a_pagar" => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premio_a_pagar,
                "premiacao_porcetagem" => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premiacao_porcetagem,
                "devolucao" => in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $notas_devolvidas,
                'premio_equipe' => 0,
                "periodo" => $fields['mes_ano'],
                "confirmacao" => $verifica_confirmacao_comissao,
                "comissao_fechamento" => $comissao_fechamento
            ];
            
            $total_representante['meta_valor'] += !empty($premiacao->meta_valor)? $premiacao->meta_valor : 0;
            $total_representante['faturado_valor'] += !empty($valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor])? $valor[$premiacao->unidades_negocios_id][$premiacao->codigo_vendedor] : 0;
            $total['comissao_valor'] += $premiacao->comissao;
            $total['premio_valor'] += in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premio_valor;
            $total['premio_a_pagar'] += in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $premio_a_pagar;
            $total['devolucao'] += in_array($premiacao->codigo_vendedor, $codigos_vendedores_gerentes)? 0 : $notas_devolvidas;
            
        }

        $total['atingimento_meta_porcetagem'] = empty($total['meta_valor'])? 0 : $total['faturado_valor']/$total['meta_valor']*100;

        if($total['atingimento_meta_porcetagem'] >= 99){
            $premiacao_porcetagem = 20;
        }else if($total['atingimento_meta_porcetagem'] >= 90 && $total['atingimento_meta_porcetagem'] < 99){
            $premiacao_porcetagem = 10;
        }else{
            $premiacao_porcetagem = 0;
        }

        $comissao_data_fechamento = ComissaoDataFechamento::select()->where('periodo', $fields['mes_ano'])->first();

        if(!empty($comissao_data_fechamento)){
            $primeiro_dia_do_mes_anterior = Carbon::parse($comissao_data_fechamento->data_inicio);
            $ultimo_dia_do_mes_anterior = Carbon::parse($comissao_data_fechamento->data_fim);
        }else{
            $primeiro_dia_do_mes_anterior = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
            $ultimo_dia_do_mes_anterior = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
            $primeiro_dia_do_mes_anterior = $primeiro_dia_do_mes_anterior->subMonth()->addDays(25);      
            $ultimo_dia_do_mes_anterior = $ultimo_dia_do_mes_anterior->addDays(24);
        }

        $arr['data_inicio'] = $primeiro_dia_do_mes_anterior->format('d/m/Y');
        $arr['data_fim'] = $ultimo_dia_do_mes_anterior->format('d/m/Y');

        $total['premio_equipe'] = 0;
        $total['premio_total'] = 0;

        $total['meta_valor'] = 0;
        $total['faturado_valor'] = 0;
        $total['atingimento_meta_porcetagem'] = 0;
        $total['comissao_valor'] = 0;
        $total['premio_valor'] = 0;
        $total['premio_a_pagar'] = 0;

        foreach($vendedores as $key => $vendedor){
            $premio_equipe_vendedor = 0;
            if(in_array($vendedor['unidade_id'], $this->habilitado_comissao_equipe)){
                if(!isset($valor[$vendedor['unidade_id']]) || !isset($meta[$vendedor['unidade_id']])){
                    continue;
                }
                $atingimento_meta_porcetagem = $valor[$vendedor['unidade_id']]['total']/$meta[$vendedor['unidade_id']]*100;
                if($atingimento_meta_porcetagem >= 99){
                    $premio_equipe_porcetagem = 0.10;
                
                    $premio_equipe = $valor[$vendedor['unidade_id']]['total'] * $premio_equipe_porcetagem / 100;

                    if(!empty($quantidade_vendedores[$vendedor['unidade_id']])){
                        $premio_equipe_vendedor = $premio_equipe / $quantidade_vendedores[$vendedor['unidade_id']];

                        $vendedores[$key]['premio_a_pagar'] += $premio_equipe_vendedor;
                    }
                }
            }
            $vendedores[$key]['premio_equipe'] = $premio_equipe_vendedor;

            $total['meta_valor'] += $vendedor['meta_valor'];
            $total['faturado_valor'] += $vendedor['faturado_valor'];
            $total['comissao_valor'] += $vendedor['comissao_valor'];
            $total['premio_valor'] += $vendedor['premio_valor'];
            $total['premio_a_pagar'] += $vendedor['premio_a_pagar'] + $premio_equipe_vendedor;
            $total['premio_equipe'] += $premio_equipe_vendedor;
        }

        $total['atingimento_meta_porcetagem'] = ($total['meta_valor'] > 0) ? $total['faturado_valor'] / $total['meta_valor'] * 100 : 0;

        $retorno = [
            'premiacaos' => $this->ajusteArrayParaValores($vendedores),
            'total' => $this->ajusteArrayParaValores($total),
        ];

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $retorno,
        ]);
    }

    public function aprovarPremio(Request $request){
        $campo = $request->only('premio_valor', 'meta_valor','periodo');
        
        $vendedor = User::find(Auth::id());

        if(!in_array($vendedor->tipo_usuario_id, [13,12,16,19,14])){
            return response()->json([
                'status' => 'error', 
                'message' => 'Usuário sem permissão para aprovar!', 
                'error' => 'Usuário sem permissão para aprovar!',
                'response' => []
            ], 422);   
        }
        $mes = $campo['periodo'];

        $link = md5($vendedor->id.$mes.$vendedor->name.'premio');
        $verificar_comunicado = ComunicadoComissoe::where('link',$link)->with('vendedor')->first();

        if(empty($verificar_comunicado->confirmacao)){
            $comunicado_comissoes = new ComunicadoComissoe;
            $comunicado_comissoes->tipo = 'premio';
            $comunicado_comissoes->vendedor_codigo = $vendedor->codigo_representante;
            $comunicado_comissoes->valor_meta = parserNumber($campo['meta_valor']);
            $comunicado_comissoes->valor_comissao = parserNumber($campo['premio_valor']);
            $comunicado_comissoes->created_by = Auth::id();
            $comunicado_comissoes->link = $link;
            $comunicado_comissoes->confirmacao = false;
            $comunicado_comissoes->save();
        }
        elseif($verificar_comunicado->confirmacao == true){
            $response = [
                "status" => 'error',
                "message" => 'A confirmação já foi realizada!',
                "error" => 'A confirmação já foi realizada!',
                "response" => []
            ];
            return response()->json($response, 422);  
        }

        $verificar_comunicado = ComunicadoComissoe::where('link',$link)->with('vendedor')->first();
        $verificar_comunicado->updated_by = Auth::id();
        $verificar_comunicado->confirmacao = true;
        $verificar_comunicado->data_confirmacao = Carbon::now();
        $verificar_comunicado->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);

    }
}
