<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\UnidadeNegocio;
use App\UnidadeNegocioMeta;
use App\Movimentacao;
use App\ClienteNasajon;
use App\User;
use App\TipoUsuario;
use App\CepEstado;
use App\MapaVendaExcecao;
use App\ClienteBionexo;
use App\AtualizacaoCron;

use App\Http\Controllers\FeriadoController;

class MapaVendaController extends Controller
{
    public $cfop_devolucao = ['1201', '1202', '2201', '2202', '2504'];

    public $cfop_venda = ['5922', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '6502', '5104', '6104'];

    public $unidade_metro = ['m', 'M', 'M...','METRO', 'METROS', 'MT', 'MTS', 'MTS.'];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\MapaDeVenda") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\MapaDeVenda');

        $data = Carbon::now();
        $data = $data->format('m/Y');

        $unidades_negocios = $this->getUnidadeNegocio();
        $representantes = $this->getRepresentante();

        $atualizacao_movimentacao = AtualizacaoCron::select()->where('token', 'movimentacao')->max('atualizacao');
        $atualizacao_movimentacao_vendedor = AtualizacaoCron::select()->where('token', 'movimentacao_vendedor')->max('atualizacao');
        
        $atualizacao_movimentacao_vendedor = Carbon::createFromFormat('Y-m-d H:i:s', $atualizacao_movimentacao_vendedor);
        $atualizacao_movimentacao = Carbon::createFromFormat('Y-m-d H:i:s', $atualizacao_movimentacao);

        if($atualizacao_movimentacao->gt($atualizacao_movimentacao_vendedor)){
            $horario = "<h2><div class='falha-validacao'>Base de dados esta sendo atualizada, tente novamente em 5 minutos.</div></h2>";
        }else{
            $horario = "Última Atualização: ".$atualizacao_movimentacao_vendedor->format('d/m H:i');
        }

        $tipo_datas = [
            'mensal' => 'Mensal',
            'quinzenal' => 'Quinzenal',
            'semanal' => 'Semanal',
            'diario' => 'Diário',
        ];

        $quinzenas = [
            1 => '1ª Quinzena',
            2 => '2ª Quinzena'
        ];

        $data_dia = Carbon::now()->format('d/m/Y');

        return view('programs.mapa_venda.index')->with(['data' => $data, 'unidades_negocios' => $unidades_negocios, 'representantes' => $representantes, 'horario' => $horario, 'tipo_datas' => $tipo_datas, 'data_dia' => $data_dia, 'quinzenas' => $quinzenas]);
    }

    public function filtro(Request $request){
        $fields = $request->only('mes_ano', 'unidade_negocio', 'vendedor', 'tipo_data', 'data_dia', 'quinzena', 'semanas');
        
        $atualizacao_movimentacao = AtualizacaoCron::select()->where('token', 'movimentacao')->max('atualizacao');
        $atualizacao_movimentacao_vendedor = AtualizacaoCron::select()->where('token', 'movimentacao_vendedor')->max('atualizacao');
        
        $atualizacao_movimentacao_vendedor = Carbon::createFromFormat('Y-m-d H:i:s', $atualizacao_movimentacao_vendedor);
        $atualizacao_movimentacao = Carbon::createFromFormat('Y-m-d H:i:s', $atualizacao_movimentacao);

        if($atualizacao_movimentacao->gt($atualizacao_movimentacao_vendedor)){
            $horario = "<h2><div class='falha-validacao'>Base de dados esta sendo atualizada, tente novamente em 5 minutos.</div></h2>";
            return response()->json([
                'status' => 'error',
                'message' => 'Atualização os dados',
                'error' => ['horario' => $horario],
                'response' => []
            ], 422);
        }else{
            $horario = "Última Atualização: ".$atualizacao_movimentacao_vendedor->format('d/m H:i');
        }

        $query = UnidadeNegocio::select();
        
        $data_escolhida = '01/'.$fields['mes_ano'];
        $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
        $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();
        
        $feriadoControllerObj = new FeriadoController;
        $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));

        $mes_data_escolhida = Carbon::createFromFormat('d/m/Y', $data_escolhida)->format('m');
        $mes_atual = Carbon::now()->format('m');
        $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);
        $divisao_meta_diaria = $mes_data_escolhida == $mes_atual ? quantidadeDiasUteisNoPeriodo(Carbon::now()->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'), $feriados) : $divisao_meta;

        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_verificacao_hospitalar_outfits = Carbon::createFromFormat('d/m/Y', '01/09/2020')->setTime(0,0,0);

        if($fields['tipo_data'] == 'diario'){
            $data_escolhida = $fields['data_dia'];
            $ultimo_dia = $fields['data_dia'];

            $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();
            
            $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));

            $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);
            
            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $fields['data_dia'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $fields['data_dia'])->setTime(0,0,0);

            $divisao_meta_diaria = $mes_data_escolhida == $mes_atual ? quantidadeDiasUteisNoPeriodo(Carbon::now()->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'), $feriados) : $divisao_meta;

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
            $dias_restantes = $mes_data_escolhida == $mes_atual ? quantidadeDiasUteisNoPeriodo(Carbon::now()->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados) : quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($fields['tipo_data'] == 'quinzenal'){
            if($fields['quinzena'] == 1){
                $data_escolhida = '01/'.$fields['mes_ano'];
                $ultimo_dia = 15;
            }else{
                $data_escolhida = '16/'.$fields['mes_ano'];
                $ultimo_dia = '';
            }

            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0);
            $data_escolhida_final = empty($ultimo_dia)? $ultimo_dia_do_mes : Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$fields['mes_ano'])->setTime(0,0,0);

            $divisao_meta_diaria = $mes_data_escolhida == $mes_atual ? quantidadeDiasUteisNoPeriodo(Carbon::now()->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'), $feriados) : $divisao_meta;

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
            $dias_restantes = $mes_data_escolhida == $mes_atual ? quantidadeDiasUteisNoPeriodo(Carbon::now()->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados) : quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($fields['tipo_data'] == 'semanal'){
            $data_escolhida = '01/'.$fields['mes_ano'];

            $arraySemanas = semanaDiaInicialFinal($primeiro_dia_do_mes->format('Y-m-d'));

            $data_escolhida_inicial = Carbon::parse($arraySemanas[(int)$fields['semanas']][0]['day_week'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('Y-m-d', $arraySemanas[(int)$fields['semanas']][count($arraySemanas[(int)$fields['semanas']])-1]['day_week'])->setTime(0,0,0);

            $divisao_meta_diaria = $mes_data_escolhida == $mes_atual ? quantidadeDiasUteisNoPeriodo(Carbon::now()->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'), $feriados) : $divisao_meta;
            
            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
            $dias_restantes = $mes_data_escolhida == $mes_atual ? quantidadeDiasUteisNoPeriodo(Carbon::now()->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados) : quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else{
            $dias_uteis = $divisao_meta;
            $dias_restantes = $mes_data_escolhida == $mes_atual ? $divisao_meta_diaria : $divisao_meta;

            $data_escolhida_inicial = $primeiro_dia_do_mes;
            $data_escolhida_final = $ultimo_dia_do_mes;
        }
        
        $excecoes = $this->excecoes($data_escolhida_inicial, $data_escolhida_final);

        $usuarios_workwear = $this->getUserWorkwear($primeiro_dia_do_mes);
        $usuarios_denim = $this->getUserDenim($primeiro_dia_do_mes);
        $usuarios_hospitalar = $this->getUserHospitalar($primeiro_dia_do_mes);
        $clientes_bionexo = $this->clientesBionexo();

        $query->with(['metas' => function($query) use($primeiro_dia_do_mes, $fields){
            $query->where('data', $primeiro_dia_do_mes);
            if(!empty($fields['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($fields){
                    $query->where('codigo_representante', $fields['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);

        $result = $query->get();

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, unidade, documento, cliente_codigo, estabelecimento, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
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

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, unidade, documento, cliente_codigo, estabelecimento, sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total');
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
        $total_meta_diaria = 0;
        $total_valor = 0;
        $codigos_vendedores = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        
        if($data_escolhida_inicial->gte($data_verificacao)){
            $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
            $usuarios_fashion_2 = $this->getUserFashion2($data_escolhida_inicial);
            if($data_escolhida_inicial->gte($data_verificacao_hospitalar_outfits)){
                $usuarios_outfites_confeccionados = $this->getUserOutfitsConfeccionados($data_escolhida_inicial); 
            }else{
                $usuarios_magazine = $this->getUserMagazine($data_escolhida_inicial);
            }
            $valor = [];
            $meta = [];
            foreach($result as $unidade_negocio){
                if($unidade_negocio->metas->count() > 0){
                    $unidades[$unidade_negocio->id] = [
                        'id' => encrypt($unidade_negocio->id),
                        'mes_ano' => $unidade_negocio->metas->count() === 0? '' : substr(parserData($unidade_negocio->metas[0]->data), -7),
                        'unidade_negocio' => $unidade_negocio->unidade,
                        'meta' => 0,
                        'meta_diaria' => 0,
                        'valor' => 0,
                        'meta_codigo' => $unidade_negocio->metas->count() === 0? '' : $unidade_negocio->metas[0]->valor/$divisao_meta*$dias_uteis,
                        'valor_codigo' => 0,
                        'atingimento_metal_porcetagem' => 0,
                        'diferenca_meta' => 0,
                    ];
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(empty($meta[$unidade_negocio->id])){
                            $meta[$unidade_negocio->id] = $usuario->metas/$divisao_meta*$dias_uteis;
                        }else{
                            $meta[$unidade_negocio->id] += $usuario->metas/$divisao_meta*$dias_uteis;
                        }

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

                                if(empty($valor[$unidade_negocio_id])){
                                    $valor[$unidade_negocio_id] = $movimentacao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id] += $movimentacao['preco'];
                                }
    
                                unset($movimentacoes[$codigo_vendedor][$key]);
                            }
                        }
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                $unidade_negocio_id = $unidade_negocio->id;
                                if(!empty($excecoes[$movimentacao_devolucao['documento']])){
                                    $unidade_negocio_id = $excecoes[$movimentacao_devolucao['documento']];
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


                                if(empty($valor[$unidade_negocio_id])){
                                    $valor[$unidade_negocio_id] = 0 - $movimentacao_devolucao['preco'];
                                }else{
                                    $valor[$unidade_negocio_id] -= $movimentacao_devolucao['preco'];
                                }

                                unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);

                            }
                        }
                    }
                }    
            }

            foreach($unidades as $key => $unidade){
                if(empty($divisao_meta_diaria)){
                    $divisao_meta_diaria = 1;
                }
                $unidades[$key]['valor'] = empty($valor[$key])? 0 : $valor[$key];
                $unidades[$key]['valor_codigo'] = empty($valor[$key])? 0 : $valor[$key];
                $unidades[$key]['meta'] = empty($meta[$key])? 0 : $meta[$key];
                $unidades[$key]['atingimento_metal_porcetagem'] = !empty($valor[$key]) && !empty($meta[$key])? (($valor[$key] / $meta[$key])* 100) : '';
                $unidades[$key]['diferenca_meta'] = empty($valor[$key])? 0 : $valor[$key] - $meta[$key];
                $unidades[$key]['meta_diaria'] = $mes_data_escolhida == $mes_atual ? ($unidades[$key]['meta'] - $unidades[$key]['valor'])/$divisao_meta_diaria : $unidades[$key]['meta']/$divisao_meta;
                $total_meta_diaria += $unidades[$key]['meta_diaria'];
                
                
                $total_meta += empty($meta[$key])? 0 : $meta[$key];
                $total_valor += empty($valor[$key])? 0 : $valor[$key];
            }
        }else{
            foreach($result as $unidade_negocio){
                $valor = 0;
                $meta = 0;
                if($unidade_negocio->metas->count() > 0){
                    if($unidade_negocio->unidade !== 'DENIM' && $unidade_negocio->unidade !== 'WORKWEAR' && $unidade_negocio->unidade !== 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            $meta += $usuario->metas;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
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
                
                                        unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'DENIM'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            $meta += $usuario->metas;
                            foreach($movimentacoes as $vendedores){
                                if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
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
            
                                            if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                                    if(($movimentacao_devolucao['linha'] === 'DENIM IMPORTADO' || ($movimentacao_devolucao['linha'] === 'INDIGO E SARJAS' && $movimentacao_devolucao['marca'] === 'IMPORTADOS'))&& ($movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL')){
                                                        $valor -= $movimentacao_devolucao['preco'];

                                                        unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                    }
                                                }
                                            }
                
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            $meta += $usuario->metas;
                            foreach($movimentacoes as $vendedores){
                                if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                    foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(substr_count($movimentacao['linha'], "HOSPITALAR") > 0 && !in_array($movimentacao['unidade'], $this->unidade_metro)){
                                            $liberado = true;
                                        }
                                        if($liberado){
                                            $valor += $movimentacao['preco'];

                                            $id_hospitalar = $unidade_negocio->id;
            
                                            if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                                    if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") > 0 && !in_array($movimentacao['unidade'], $this->unidade_metro)){
                                                        $valor -= $movimentacao_devolucao['preco'];
            
                                                        unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                    }
                                                }
                                            }
                
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            $meta += $usuario->metas;
                            foreach($movimentacoes as $vendedores){
                                if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                    foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                            if((substr_count($movimentacao['linha'], "HOSPITALAR") === 0 || in_array($movimentacao['unidade'], $this->unidade_metro)) && ($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN")){
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

                                                        unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                                                    }
                                                }
                                            }
            
                                            unset($movimentacoes[$codigo_vendedor][$key]);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $unidades[$unidade_negocio->id] = [
                        'id' => encrypt($unidade_negocio->id),
                        'mes_ano' => $unidade_negocio->metas->count() === 0? '' : substr(parserData($unidade_negocio->metas[0]->data), -7),
                        'unidade_negocio' => $unidade_negocio->unidade,
                        'meta' => $meta,
                        'valor' => $valor,
                        'meta_codigo' => $unidade_negocio->metas->count() === 0? '' : $unidade_negocio->metas[0]->valor,
                        'valor_codigo' => $valor,
                        'atingimento_metal_porcetagem' => empty($valor) || $unidade_negocio->metas->count() === 0? '' : (($valor / $unidade_negocio->metas[0]->valor)* 100),
                        'diferenca_meta' => empty($valor) || $unidade_negocio->metas->count() === 0? '' : $valor - $unidade_negocio->metas[0]->valor,
                    ];
                }

                $total_meta += $meta;
                $total_valor += $valor;
            }

            if(!empty($id_hospitalar)){
                foreach($usuarios_hospitalar['codigos'] as $usuario_hospitalar){
                    if(!empty($movimentacoes[$usuario_hospitalar])){
                        $valor = 0;
                        foreach($movimentacoes[$usuario_hospitalar] as $key => $movimentacao){
                            $liberado = true;
                            if(!empty($excecoes[$movimentacao['documento']])){
                                if($excecoes[$movimentacao['documento']] !== $id_hospitalar){
                                    $liberado = false;
                                }
                            }

                            if($liberado){  
                                $valor += $movimentacao['preco'];
            
                                unset($movimentacoes[$usuario_hospitalar][$key]);
                            }
                        }
                        $unidades[$id_hospitalar]['valor'] += $valor;
                        $unidades[$id_hospitalar]['valor_codigo'] += $valor;
                        $total_valor += $valor;
                    }
                }
            }  

            if(!empty($id_workwear)){
                foreach($usuarios_workwear['codigos'] as $usuario_workwear){
                    if(!empty($movimentacoes[$usuario_workwear])){
                        $valor = 0;
                        foreach($movimentacoes[$usuario_workwear] as $key => $movimentacao){
                            $liberado = true;
                            if(!empty($excecoes[$movimentacao['documento']])){
                                if($excecoes[$movimentacao['documento']] !== $id_workwear){
                                    $liberado = false;
                                }
                            }

                            if($liberado){  
                                $valor += $movimentacao['preco'];
            
                                unset($movimentacoes[$usuario_workwear][$key]);
                            }
                        }
                        $unidades[$id_workwear]['valor'] += $valor;
                        $unidades[$id_workwear]['valor_codigo'] += $valor;
                        $total_valor += $valor;
                    }
                }
            }
            
            if(!empty($id_denim)){
                foreach($usuarios_denim['codigos'] as $usuario_denim){
                    if(!empty($movimentacoes[$usuario_denim])){
                        $valor = 0;
                        foreach($movimentacoes[$usuario_denim] as $key => $movimentacao){
                            $liberado = true;
                            if(!empty($excecoes[$movimentacao['documento']])){
                                if($excecoes[$movimentacao['documento']] !== $id_denim){
                                    $liberado = false;
                                }
                            }

                            if($liberado){  
                                $valor += $movimentacao['preco'];
            
                                unset($movimentacoes[$usuario_denim][$key]);
                            }
                        }
                        $unidades[$id_denim]['valor'] += $valor;
                        $unidades[$id_denim]['valor_codigo'] += $valor;
                        $total_valor += $valor;
                    }
                }
            }
        }

        if(empty($fields['unidade_negocio'])){
            if(!empty($movimentacoes[""])){
                if(count($movimentacoes[""]) > 0){
                    $valor = 0;
                    foreach($movimentacoes[""] as $movimentacao){
                        $valor += $movimentacao['preco'];
                    }

                    if(!empty($movimentacoes_devolucao[""])){
                        foreach($movimentacoes_devolucao[""] as $movimentacao_devolucao){
                            $valor -= $movimentacao_devolucao['preco'];
                        }
                        unset($movimentacoes_devolucao[""]);
                    }

                    $unidades[] = [
                        'id' => encrypt("nulo"),
                        'mes_ano' => '',
                        'unidade_negocio' => "Sem Código Vendedor",
                        'meta' => 0,
                        'valor' => $valor,
                        'atingimento_metal_porcetagem' => '',
                        'diferenca_meta' => '',
                        'meta_diaria' => '',
                    ];

                    $total_valor += $valor;

                    unset($movimentacoes[""]);
                }
            }

            if(count($movimentacoes) > 0){
                $valor = 0;
                foreach($movimentacoes as $codigo_vendedor => $vendedor){
                    foreach($vendedor as $movimentacao ){
                        $valor += $movimentacao['preco'];

                        if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                $valor -= $movimentacao_devolucao['preco'];
    
                                unset($movimentacoes_devolucao[$codigo_vendedor][$key_devolucao]);
                            }
                        }
                        
                    }
                    unset($movimentacoes[$codigo_vendedor]);
                }
                if($valor > 0){
                    $unidades[] = [
                        'id' => encrypt("outros"),
                        'mes_ano' => '',
                        'unidade_negocio' => "Outros",
                        'meta' => '',
                        'valor' => $valor,
                        'atingimento_metal_porcetagem' => '',
                        'diferenca_meta' => '',
                        'meta_diaria' => '',
                    ];

                    $total_valor += $valor;
                }
            }
        }

        if(!empty($fields['unidade_negocio'])){
            foreach($unidades as $index => $unidade){
                if($fields['unidade_negocio'] != $index){
                    unset($unidades[$index]);
                }
            }

        }

        $unidades = $this->ajusteArrayParaValores($unidades);

        $atingimento_metal_porcetagem = empty($total_meta) || empty($total_valor)? '' : parserValor(($total_valor / $total_meta) * 100).'%';
        $diferenca_meta = empty($total_meta) || empty($total_valor)? '' : parserValor($total_valor - $total_meta);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'unidades' => $unidades,
                'total_meta' => empty($total_meta)? '' : parserValor($total_meta),
                'total_meta_diaria' => $total_meta_diaria < 0 ? '' : parserValor($total_meta_diaria),
                'total_valor' => empty($total_valor)? '' : parserValor($total_valor),
                'filtro' => encrypt($fields),
                'atingimento_metal_porcetagem' => $atingimento_metal_porcetagem,
                'atingimento_metal_porcetagem_codigo' => empty($atingimento_metal_porcetagem)? '' : parserNumber(str_replace('%', '', $atingimento_metal_porcetagem)),
                'diferenca_meta' => $diferenca_meta == 0 ? '' : $diferenca_meta,
                'data_escolhida' => parserNameMonth($data_escolhida_inicial->format('m')).'/'.$data_escolhida_inicial->format('Y'),
                'horario' => $horario,
                'dias_uteis' => $dias_restantes,
                'divisao_meta' => $divisao_meta,
            ],
        ]);
    }

    public function modalEquipeIndividual(Request $request){
        $fields = $request->only('filtro', 'id_unidade_negocio', 'title');
        $filtro = decrypt($fields['filtro']);
        $id_unidade_negocio = decrypt($fields['id_unidade_negocio']);

        $data_escolhida = '01/'.$filtro['mes_ano'];
        $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
        $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

        $feriadoControllerObj = new FeriadoController;
        $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));

        $mes_data_escolhida = Carbon::createFromFormat('d/m/Y', $data_escolhida)->format('m');
        $mes_atual = Carbon::now()->format('m');
        $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);
        $divisao_meta_diaria = $mes_data_escolhida == $mes_atual ? quantidadeDiasUteisNoPeriodo(Carbon::now()->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'), $feriados) : $divisao_meta;

        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_verificacao_hospitalar_outfits = Carbon::createFromFormat('d/m/Y', '01/09/2020')->setTime(0,0,0);

        if($filtro['tipo_data'] == 'diario'){
            $data_escolhida = $filtro['data_dia'];
            $ultimo_dia = $filtro['data_dia'];

            $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

            $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
            $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);
            
            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);

            $divisao_meta_diaria = $mes_data_escolhida == $mes_atual ? quantidadeDiasUteisNoPeriodo(Carbon::now()->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'), $feriados) : $divisao_meta;
            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'quinzenal'){
            if($filtro['quinzena'] == 1){
                $data_escolhida = '01/'.$filtro['mes_ano'];
                $ultimo_dia = 15;
            }else{
                $data_escolhida = '16/'.$filtro['mes_ano'];
                $ultimo_dia = '';
            }

            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0);
            $data_escolhida_final = empty($ultimo_dia)? $ultimo_dia_do_mes : Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$filtro['mes_ano'])->setTime(0,0,0);
           
            $divisao_meta_diaria = $mes_data_escolhida == $mes_atual ? quantidadeDiasUteisNoPeriodo(Carbon::now()->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'), $feriados) : $divisao_meta;
            
            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'semanal'){
            $data_escolhida = '01/'.$filtro['mes_ano'];

            $arraySemanas = semanaDiaInicialFinal($primeiro_dia_do_mes->format('Y-m-d'));

            $data_escolhida_inicial = Carbon::parse($arraySemanas[(int)$filtro['semanas']][0]['day_week'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('Y-m-d', $arraySemanas[(int)$filtro['semanas']][count($arraySemanas[(int)$filtro['semanas']])-1]['day_week'])->setTime(0,0,0);
            
            $divisao_meta_diaria = $mes_data_escolhida == $mes_atual ? quantidadeDiasUteisNoPeriodo(Carbon::now()->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'), $feriados) : $divisao_meta;
            
            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else{
            $dias_uteis = $divisao_meta;

            $data_escolhida_inicial = $primeiro_dia_do_mes;
            $data_escolhida_final = $ultimo_dia_do_mes;
        }

        $excecoes = $this->excecoes($data_escolhida_inicial, $data_escolhida_final);

        $usuarios_workwear = $this->getUserWorkwear($primeiro_dia_do_mes);
        $usuarios_denim = $this->getUserDenim($primeiro_dia_do_mes);
        $usuarios_hospitalar = $this->getUserHospitalar($primeiro_dia_do_mes);
        $clientes_bionexo = $this->clientesBionexo();

        $query = UnidadeNegocio::select();
        
        $query->with(['metas' => function($query) use($primeiro_dia_do_mes, $filtro){
            $query->where('data', $primeiro_dia_do_mes);
            if(!empty($filtro['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($filtro){
                    $query->where('codigo_representante', $filtro['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);

        if(($id_unidade_negocio !== "outros" && $id_unidade_negocio !== "" && $id_unidade_negocio !== "nulo") && (!in_array($id_unidade_negocio, [5,6,7]) || $data_escolhida_inicial->gte($data_verificacao))){
            $query->where('id', $id_unidade_negocio);
        }

        $result = $query->get();

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, unidade, documento, cliente_codigo, estabelecimento, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        if(!empty($filtro['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro['vendedor']);
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
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);
        if(!empty($filtro['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro['vendedor']);
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
        $total_meta_diaria = 0;
        $total_valor = 0;
        $vendedores = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        $teste = [];
        if($data_escolhida_inicial->gte($data_verificacao)){
            $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
            $usuarios_fashion_2 = $this->getUserFashion2($data_escolhida_inicial);
            if($data_escolhida_inicial->gte($data_verificacao_hospitalar_outfits)){
                $usuarios_outfites_confeccionados = $this->getUserOutfitsConfeccionados($data_escolhida_inicial); 
            }else{
                $usuarios_magazine = $this->getUserMagazine($data_escolhida_inicial);
            }
            foreach($result as $unidade_negocio){
                $valor = 0;
                $meta = 0;
                if($unidade_negocio->metas->count() > 0){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        $meta += $usuario->metas/$divisao_meta*$dias_uteis;
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

                                if($id_unidade_negocio === $unidade_negocio_id || $id_unidade_negocio === "outros"){
                                    $valor += $movimentacao['preco'];
                                    if($codigo_vendedor === "492"){
                                        $teste[] = $movimentacao;
                                    }
                                    if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                        $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                            'vendedor_codigo' => $codigo_vendedor,
                                            'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                            'meta' => $usuario->metas/$divisao_meta*$dias_uteis,
                                            'meta_codigo' => $usuario->metas/$divisao_meta*$dias_uteis,
                                            'meta_diaria' => 0,
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
    
                                if($id_unidade_negocio === $unidade_negocio_id || $id_unidade_negocio === "outros"){
                                    $valor -= $movimentacao_devolucao['preco'];

                                    if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                        $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
                                            'vendedor_codigo' => $codigo_vendedor,
                                            'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                            'meta' => $usuario->metas/$divisao_meta*$dias_uteis,
                                            'meta_codigo' => $usuario->metas/$divisao_meta*$dias_uteis,
                                            'meta_diaria' => 0,
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
                                'vendedor_codigo' => $codigo_vendedor,
                                'vendedor' => $usuario->detalhesUsuario->codigo_representante." - ".$usuario->detalhesUsuario->name,
                                'meta' => $usuario->metas/$divisao_meta*$dias_uteis,
                                'meta_codigo' => $usuario->metas/$divisao_meta*$dias_uteis,
                                'meta_diaria' => 0,
                                'valor' => 0,
                                'valor_codigo' => 0,
                                'atingimento_metal_porcetagem' => 0,
                                'diferenca_meta' => 0,
                            ];
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
    
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
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
    
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
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
                                            
                                            if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                                $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
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
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    $liberado = false;
                                    if(!empty($excecoes[$movimentacao['documento']])){
                                        if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                            $liberado = true;
                                        }
                                    }else if(substr_count($movimentacao['linha'], "HOSPITALAR") > 0 && !in_array($movimentacao['unidade'], $this->unidade_metro)){
                                        $liberado = true;
                                    }
                                    if($liberado){
                                        $valor += $movimentacao['preco'];
    
                                        $id_hospitalar = $unidade_negocio->id;
        
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
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
                            if(!empty($excecoes[$movimentacao['documento']])){
                                if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                    $liberado = true;
                                }
                            }else if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                $meta += $usuario->metas;
                                foreach($movimentacoes[$codigo_vendedor] as $key => $movimentacao){
                                    $liberado = false;
                                    if(!empty($excecoes[$movimentacao['documento']])){
                                        if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                            $liberado = true;
                                        }
                                    }else if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                         if((substr_count($movimentacao['linha'], "HOSPITALAR") === 0 || in_array($movimentacao['unidade'], $this->unidade_metro)) && ($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN")){
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
                                        if(empty($vendedores[$unidade_negocio->id][$codigo_vendedor])){
                                            $vendedores[$unidade_negocio->id][$codigo_vendedor] = [
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
    
            if(!empty($id_hospitalar)){
                if($id_unidade_negocio === $id_hospitalar){
                    foreach($vendedores as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_hospitalar){
                            unset($vendedores[$key_unidade_negocio]);
                        }
                    }
                }
    
                foreach($usuarios_hospitalar['detalhes'] as $usuario_hospitalar){
                    if(!empty($movimentacoes[$usuario_hospitalar['codigo']])){
                        foreach($movimentacoes[$usuario_hospitalar['codigo']] as $key => $movimentacao){
                            $liberado = true;
                            if(!empty($excecoes[$movimentacao['documento']])){
                                if($excecoes[$movimentacao['documento']] !== $id_hospitalar){
                                    $liberado = false;
                                }
                            }
    
                            if($liberado){  
                                if(empty($vendedores[$id_hospitalar][$usuario_hospitalar['codigo']])){
                                    $vendedores[$id_hospitalar][$usuario_hospitalar['codigo']] = [
                                        'vendedor_codigo' => $usuario_hospitalar['codigo'],
                                        'vendedor' => $usuario_hospitalar['codigo']." - ".$usuario_hospitalar['nome'],
                                        'meta' => $usuario_hospitalar['meta'],
                                        'meta_codigo' => $usuario_hospitalar['meta'],
                                        'valor' => $movimentacao['preco'],
                                        'valor_codigo' => $movimentacao['preco'],
                                        'atingimento_metal_porcetagem' => 0,
                                        'diferenca_meta' => 0,
                                    ];
                                    unset($movimentacoes[$id_hospitalar][$usuario_hospitalar['codigo']][$key]);
                                }else{
                                    $vendedores[$id_hospitalar][$usuario_hospitalar['codigo']]['valor_codigo'] += $movimentacao['preco'];
                                    $vendedores[$id_hospitalar][$usuario_hospitalar['codigo']]['valor'] += $movimentacao['preco'];
                                    unset($movimentacoes[$usuario_hospitalar['codigo']][$key]);
                                }
                            }
                        }
                    }
                }
    
                $total_meta = 0;
                $total_valor = 0;
                foreach($vendedores as $unidade_negocio){
                    foreach($unidade_negocio as $vendedor){
                        $total_valor += $vendedor['valor'];
                        $total_meta += $vendedor['meta']; 
                    }
                }
            }
    
            if(!empty($id_workwear) && ($id_unidade_negocio === $id_workwear || $id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_workwear){
                    foreach($vendedores as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_workwear){
                            unset($vendedores[$key_unidade_negocio]);
                        }
                    }
                }
    
                foreach($usuarios_workwear['detalhes'] as $usuario_workwear){
                    if(!empty($movimentacoes[$usuario_workwear['codigo']])){
                        foreach($movimentacoes[$usuario_workwear['codigo']] as $key => $movimentacao){
                            $liberado = true;
                            if(!empty($excecoes[$movimentacao['documento']])){
                                if($excecoes[$movimentacao['documento']] !== $id_workwear){
                                    $liberado = false;
                                }
                            }
    
                            if($liberado){  
                                if(empty($vendedores[$id_workwear][$usuario_workwear['codigo']])){
                                    $vendedores[$id_workwear][$usuario_workwear['codigo']] = [
                                        'vendedor_codigo' => $usuario_workwear['codigo'],
                                        'vendedor' => $usuario_workwear['codigo']." - ".$usuario_workwear['nome'],
                                        'meta' => 0,
                                        'meta_codigo' => 0,
                                        'valor' => $movimentacao['preco'],
                                        'valor_codigo' => $movimentacao['preco'],
                                        'atingimento_metal_porcetagem' => 0,
                                        'diferenca_meta' => 0,
                                    ];
                                    unset($movimentacoes[$usuario_workwear['codigo']][$key]);
                                }else{
                                    $vendedores[$id_workwear][$usuario_workwear['codigo']]['valor_codigo'] += $movimentacao['preco'];
                                    $vendedores[$id_workwear][$usuario_workwear['codigo']]['valor'] += $movimentacao['preco'];
                                    unset($movimentacoes[$usuario_workwear['codigo']][$key]);
                                }
                            }
                        }
                    }
                }
    
                $total_meta = 0;
                $total_valor = 0;
                foreach($vendedores as $unidade_negocio){
                    foreach($unidade_negocio as $vendedor){
                        $total_valor += $vendedor['valor'];
                        $total_meta += $vendedor['meta']; 
                    }
                }
            }
    
            if(!empty($id_denim) && ($id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_denim){
                    foreach($vendedores as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_denim){
                            unset($vendedores[$key_unidade_negocio]);
                        }
                    }
                }
                foreach($usuarios_denim['detalhes'] as $usuario_denim){
                    if(!empty($movimentacoes[$usuario_denim['codigo']])){
                        foreach($movimentacoes[$usuario_denim['codigo']] as $key => $movimentacao){
                            $liberado = true;
                            if(!empty($excecoes[$movimentacao['documento']])){
                                if($excecoes[$movimentacao['documento']] !== $id_denim){
                                    $liberado = false;
                                }
                            }
    
                            if($liberado){          
                                if(empty($vendedores[$id_denim][$usuario_denim['codigo']])){
                                    $vendedores[$usuario_denim['codigo']] = [
                                        'vendedor_codigo' => $usuario_denim['codigo'],
                                        'vendedor' => $usuario_denim['codigo']." - ".$usuario_denim['nome'],
                                        'meta' => 0,
                                        'meta_codigo' => 0,
                                        'valor' => $movimentacao['preco'],
                                        'valor_codigo' => $movimentacao['preco'],
                                        'atingimento_metal_porcetagem' => 0,
                                        'diferenca_meta' => 0,
                                    ];
                                    unset($movimentacoes[$usuario_denim['codigo']][$key]);
                                }else{
                                    $vendedores[$id_denim][$usuario_denim['codigo']]['valor_codigo'] += $movimentacao['preco'];
                                    $vendedores[$id_denim][$usuario_denim['codigo']]['valor'] += $movimentacao['preco'];
                                    unset($movimentacoes[$usuario_denim['codigo']][$key]);
                                }
                            }
                        }
                    }
                }
    
                $total_meta = 0;
                $total_valor = 0;
                foreach($vendedores as $unidade_negocio){
                    foreach($unidade_negocio as $vendedor){
                        $total_valor += $vendedor['valor'];
                        $total_meta += $vendedor['meta']; 
                    }
                }
            }
        }

        if($id_unidade_negocio === "" || $id_unidade_negocio === "nulo"){
            $total_meta = 0;
            $total_valor = 0;
            if(!empty($movimentacoes[""])){
                if(count($movimentacoes[""]) > 0){
                    $valor = 0;
                    foreach($movimentacoes[""] as $movimentacao){
                        $valor += $movimentacao['preco'];
                    }

                    if(!empty($movimentacoes_devolucao[""])){
                        foreach($movimentacoes_devolucao[""] as $movimentacao_devolucao){
                            $valor -= $movimentacao_devolucao['preco'];
                        }
                        unset($movimentacoes_devolucao[""]);
                    }
                    $vendedores = [];
                    $vendedores[""]["nulo"] = [
                        'vendedor_codigo' => "",
                        'vendedor' => "",
                        'meta' => "",
                        'meta_codigo' => "",
                        'meta_diaria' => "",
                        'valor' => $valor,
                        'valor_codigo' => $valor,
                        'atingimento_metal_porcetagem' => 0,
                        'diferenca_meta' => 0,
                    ];

                    $total_valor += $valor;

                    unset($movimentacoes[""]);
                }
            }
        }
        
        if($id_unidade_negocio === "outros"){
            $vendedores = [];
            $total_valor = 0;
            $total_meta = 0;
            if(count($movimentacoes) > 0){
                $valor = 0;

                foreach($movimentacoes as $codigo_vendedor => $vendedor){
                    foreach($vendedor as $movimentacao ){
                        if(!empty($codigo_vendedor)){
                            if(empty($vendedores[$id_unidade_negocio][$codigo_vendedor])){
                                $vendedores[$id_unidade_negocio][$codigo_vendedor] = [
                                    'vendedor_codigo' => $movimentacao['vendedor'],
                                    'vendedor' => $movimentacao['vendedor']." - ".$movimentacao['nome'],
                                    'meta' => 0,
                                    'meta_codigo' => 0,
                                    'meta_diaria' => "",
                                    'valor' => $movimentacao['preco'],
                                    'valor_codigo' => $movimentacao['preco'],
                                    'atingimento_metal_porcetagem' => 0,
                                    'diferenca_meta' => 0,
                                ];

                                if(!empty($movimentacoes_devolucao[$codigo_vendedor])){
                                    foreach($movimentacoes_devolucao[$codigo_vendedor] as $key_devolucao => $movimentacao_devolucao){
                                        $valor -= $movimentacao_devolucao['preco'];

                                        $vendedores[$id_unidade_negocio][$codigo_vendedor]['valor_codigo'] -= $movimentacao_devolucao['preco'];
                                        $vendedores[$id_unidade_negocio][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];
                                    }
                                }
                            }else{
                                $vendedores[$id_unidade_negocio][$codigo_vendedor]['valor'] += $movimentacao['preco'];
                                $vendedores[$id_unidade_negocio][$codigo_vendedor]['valor_codigo'] += $movimentacao['preco'];
                            }
                        }
                        
                        $valor += $movimentacao['preco'];
                    }
                    unset($movimentacoes[$codigo_vendedor]);
                }
                $total_valor += $valor;
            }
        }

        foreach($vendedores as $key_unidade_negocio => $unidade_negocio){
            foreach($unidade_negocio as $codigo_vendedor => $vendedor){
                $vendedores[$key_unidade_negocio][$codigo_vendedor]['atingimento_metal_porcetagem'] = (!empty($vendedor['valor'])) && !empty($vendedor['meta'])? (($vendedor['valor'] / $vendedor['meta']) * 100) : 0;
                $vendedores[$key_unidade_negocio][$codigo_vendedor]['diferenca_meta'] = !empty($vendedor['valor']) && !empty($vendedor['meta'])? $vendedor['valor'] - $vendedor['meta'] : 0;

                if($mes_data_escolhida == $mes_atual){
                    $vendedores[$key_unidade_negocio][$codigo_vendedor]['meta_diaria'] = (!empty($vendedor['valor']) && !empty($vendedor['meta']) && !empty($divisao_meta_diaria))? ($vendedor['meta'] - $vendedor['valor'])/$divisao_meta_diaria : 0; 
                    $total_meta_diaria += $vendedores[$key_unidade_negocio][$codigo_vendedor]['meta_diaria'];
                }else{
                    $vendedores[$key_unidade_negocio][$codigo_vendedor]['meta_diaria'] = !empty($vendedor['valor']) && !empty($vendedor['meta'])&& !empty($divisao_meta)? $vendedor['meta']/$divisao_meta : 0;
                    $total_meta_diaria += $vendedores[$key_unidade_negocio][$codigo_vendedor]['meta_diaria'];
                }
            }
        }

        $vendedores = $this->ajusteArrayParaValoresVendedor($vendedores);

        $atingimento_metal_porcetagem = empty($total_meta) || empty($total_valor)? '' : parserValor(($total_valor / $total_meta) * 100).'%';
        $diferenca_meta = empty($total_meta) || empty($total_valor)? '' : parserValor($total_valor - $total_meta);

        $atingimento_metal_porcetagem_codigo = empty($atingimento_metal_porcetagem)? '' : parserNumber(str_replace('%', '', $atingimento_metal_porcetagem));
        
        return view('programs.mapa_venda.modal.equipe_individual')->with(['vendedores' => $vendedores,'total_meta' => empty($total_meta)? '' : parserValor($total_meta), 'total_valor' => empty($total_valor)? '' : parserValor($total_valor), 'filtro' => $fields['filtro'], 'atingimento_metal_porcetagem' => $atingimento_metal_porcetagem, 'diferenca_meta' => $diferenca_meta, 'atingimento_metal_porcetagem_codigo' => $atingimento_metal_porcetagem_codigo, 'id_unidade_negocio' => empty($id_unidade_negocio)? encrypt('nulo') : encrypt($id_unidade_negocio), 'title' => $fields['title'], 'total_meta_diaria' => empty($total_meta_diaria) || $total_meta_diaria < 0 ? '' :  parserValor($total_meta_diaria)]);
    }

    public function modalGrupo(Request $request){
        $fields = $request->only('filtro', 'id_unidade_negocio', 'codigo_cliente', 'title', 'filtro_cliente', 'codigo_vendedor');
        
        if(isset($fields['filtro_cliente'])){
            $filtro_clientes = decrypt($fields['filtro_cliente']);

            $filtro_cliente = [
                "tipo" => $filtro_clientes['tipo'],
                "vendedor" => isset($fields['codigo_vendedor'])? $fields['codigo_vendedor'] : $filtro_clientes['vendedor'],
                "regiao" => $filtro_clientes['regiao'],
                "marca_nacional_importado" => $filtro_clientes['marca_nacional_importado'],
            ];
        }else{
            $filtro_cliente = [
                "tipo" => "",
                "vendedor" => isset($fields['codigo_vendedor'])? $fields['codigo_vendedor'] : "",
                "regiao" => "",
                "marca_nacional_importado" => "",
            ];
        }

        if(!isset($fields['codigo_vendedor'])){
            $fields['codigo_vendedor'] = "";
        }     

        $filtro = decrypt($fields['filtro']);

        if(!empty($fields['id_unidade_negocio'])){
            $id_unidade_negocio = decrypt($fields['id_unidade_negocio']);
        }else{
            $id_unidade_negocio = "";
        }
        
        $data_escolhida = '01/'.$filtro['mes_ano'];
        $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
        $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

        $feriadoControllerObj = new FeriadoController;
        $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
        $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);

        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_verificacao_hospitalar_outfits = Carbon::createFromFormat('d/m/Y', '01/09/2020')->setTime(0,0,0);

        if($filtro['tipo_data'] == 'diario'){
            $data_escolhida = $filtro['data_dia'];
            $ultimo_dia = $filtro['data_dia'];

            $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

            $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
            $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);
            
            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'quinzenal'){
            if($filtro['quinzena'] == 1){
                $data_escolhida = '01/'.$filtro['mes_ano'];
                $ultimo_dia = 15;
            }else{
                $data_escolhida = '16/'.$filtro['mes_ano'];
                $ultimo_dia = '';
            }

            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0);
            $data_escolhida_final = empty($ultimo_dia)? $ultimo_dia_do_mes : Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$filtro['mes_ano'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'semanal'){
            $data_escolhida = '01/'.$filtro['mes_ano'];

            $arraySemanas = semanaDiaInicialFinal($primeiro_dia_do_mes->format('Y-m-d'));

            $data_escolhida_inicial = Carbon::parse($arraySemanas[(int)$filtro['semanas']][0]['day_week'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('Y-m-d', $arraySemanas[(int)$filtro['semanas']][count($arraySemanas[(int)$filtro['semanas']])-1]['day_week'])->setTime(0,0,0);
            
            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else{
            $dias_uteis = $divisao_meta;

            $data_escolhida_inicial = $primeiro_dia_do_mes;
            $data_escolhida_final = $ultimo_dia_do_mes;
        }

        $excecoes = $this->excecoes($data_escolhida_inicial, $data_escolhida_final);

        $usuarios_workwear = $this->getUserWorkwear($primeiro_dia_do_mes);
        $usuarios_denim = $this->getUserDenim($primeiro_dia_do_mes);
        $usuarios_hospitalar = $this->getUserHospitalar($primeiro_dia_do_mes);
        $clientes_bionexo = $this->clientesBionexo();

        $query = UnidadeNegocio::select();
        
        $query->with(['metas' => function($query) use($primeiro_dia_do_mes, $filtro){
            $query->where('data', $primeiro_dia_do_mes);
            if(!empty($filtro['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($filtro){
                    $query->where('codigo_representante', $filtro['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);

        if(($id_unidade_negocio !== "outros" && $id_unidade_negocio !== "" && $id_unidade_negocio !== "nulo") && !in_array($id_unidade_negocio, [5,6,7])){
            $query->where('id', $id_unidade_negocio);
        }
        if(!empty($filtro_cliente['equipe'])){
            $query->where('id', $filtro_cliente['equipe']);
        }

        $result = $query->get();

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, unidade, documento, cliente_codigo, estabelecimento, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        if(!empty($fields['codigo_cliente'])){
            $query_movimentacao->where('cliente_codigo', 'ilike', $fields['codigo_cliente']);
        }
        $query_movimentacao->with(['cliente' => function($query)use($fields){
            if(!empty($filtro_cliente['regiao'])){
                $query->whereIn('uf', $this->getEstadoPorRegiao($filtro_cliente['regiao']));
            }
        }]);
        if(!empty($filtro_cliente['tipo'])){
            $query_user = User::select();
            $query_user->where('tipo_usuario_id', $filtro_cliente['tipo']);
            $result_user = $query_user->get();

            $query_movimentacao->whereIn('vendedor', $result_user->pluck('codigo_representante'));
        }
        if(!empty($filtro_cliente['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro_cliente['vendedor']);
        }
        if(!empty($filtro_cliente['marca_nacional_importado'])){
            $query_movimentacao->whereHas('produto', function($query) use($filtro_cliente){
                if($filtro_cliente['marca_nacional_importado'] !== 'importado'){
                    $query->whereIn('procedencia', [0,3,4,5]);
                }else{
                    $query->whereIn('procedencia', [1,2,6,7]);
                }
            });
        }

        if(!empty($filtro['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro['vendedor']);
        }
        if($fields['codigo_vendedor'] !== "nulo"){
            $query_movimentacao->where('vendedor', $fields['codigo_vendedor']);
        }
        if($id_unidade_negocio === "nulo" || $fields['codigo_vendedor'] === "nulo"){
            $query_movimentacao->whereNull('vendedor');
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
            $movimentacoes_devolucao[$movimentacao->vendedor][$movimentacao->grupo.$movimentacao->linha.$movimentacao->marca][] = [
                'vendedor' => $movimentacao->vendedor,
                'marca' => $movimentacao->marca,
                'linha' => $movimentacao->linha,
                'grupo' => $movimentacao->grupo,
                'quantidade' => $movimentacao->quantidade_total,
                'preco' => $movimentacao->preco_total,
                'unidade' => $movimentacao->unidade,
                'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
                'bionexo' => $bionexo,
            ];
        }

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, unidade, documento, cliente_codigo, estabelecimento, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end ) as preco_total, sum(preco_pcmn * quantidade) as custo');
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);
        if(!empty($fields['codigo_cliente'])){
            $query_movimentacao->where('cliente_codigo', 'ilike', $fields['codigo_cliente']);
        }
        $query_movimentacao->with(['cliente' => function($query)use($fields){
            if(!empty($filtro_cliente['regiao'])){
                $query->whereIn('uf', $this->getEstadoPorRegiao($filtro_cliente['regiao']));
            }
        }]);
        if(!empty($filtro_cliente['tipo'])){
            $query_user = User::select();
            $query_user->where('tipo_usuario_id', $filtro_cliente['tipo']);
            $result_user = $query_user->get();

            $query_movimentacao->whereIn('vendedor', $result_user->pluck('codigo_representante'));
        }
        if(!empty($filtro_cliente['vendedor'])){
            if($filtro_cliente['vendedor'] === 'nulo'){
                $query_movimentacao->whereNull('vendedor');
            }else{
                $query_movimentacao->where('vendedor', $filtro_cliente['vendedor']);
            }
        }
        if(!empty($filtro_cliente['marca_nacional_importado'])){
            $query_movimentacao->whereHas('produto', function($query) use($filtro_cliente){
                if($filtro_cliente['marca_nacional_importado'] !== 'importado'){
                    $query->whereIn('procedencia', [0,3,4,5]);
                }else{
                    $query->whereIn('procedencia', [1,2,6,7]);
                }
            });
        }
        if(!empty($filtro['vendedor'])){
            if($filtro['vendedor'] === 'nulo'){
                $query_movimentacao->whereNull('vendedor');
            }else{
                $query_movimentacao->where('vendedor', $filtro['vendedor']);
            }
        }
        if($id_unidade_negocio === "nulo" || $fields['codigo_vendedor'] === "nulo"){
            $query_movimentacao->whereNull('vendedor');
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
            $movimentacoes[$movimentacao->vendedor][$movimentacao->grupo.$movimentacao->linha.$movimentacao->marca][] = [
                'vendedor' => $movimentacao->vendedor,
                'marca' => $movimentacao->marca,
                'linha' => $movimentacao->linha,
                'grupo' => $movimentacao->grupo,
                'quantidade' => $movimentacao->quantidade_total,
                'preco' => $movimentacao->preco_total,
                'custo' => $movimentacao->custo,
                'unidade' => $movimentacao->unidade,
                'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
                'bionexo' => $bionexo,
            ];
        }
        
        $unidades = [];

        $total_quantidade = 0;
        $total_valor = 0;
        $produtos = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        
        if($data_escolhida_inicial->gte($data_verificacao)){
            $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
            $usuarios_fashion_2 = $this->getUserFashion2($data_escolhida_inicial);
            if($data_escolhida_inicial->gte($data_verificacao_hospitalar_outfits)){
                $usuarios_outfites_confeccionados = $this->getUserOutfitsConfeccionados($data_escolhida_inicial); 
            }else{
                $usuarios_magazine = $this->getUserMagazine($data_escolhida_inicial);
            }
            foreach($result as $unidade_negocio){
                $valor = 0;
                $quantidade = 0;
                if($unidade_negocio->metas->count() > 0){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key => $movimentacao){
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
        
                                    if($id_unidade_negocio === $unidade_negocio_id || $id_unidade_negocio === "outros"){
                                        $valor += $movimentacao['preco'];
                                        $quantidade += $movimentacao['quantidade'];

                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'linha' => $movimentacao['linha'],
                                                'grupo' => $movimentacao['grupo'],
                                                'marca' => $movimentacao['marca'],
                                                'quantidade' => $movimentacao['quantidade'],
                                                'valor' => $movimentacao['preco'],
                                                'custo' => $movimentacao['custo'],
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                        }else{
                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                            unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                        }
                                    }
                                }
                            }
                        }
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                    $unidade_negocio_id = $unidade_negocio->id;
                                if(!empty($excecoes[$movimentacao_devolucao['documento']])){
                                    $unidade_negocio_id = $excecoes[$movimentacao_devolucao['documento']];
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

                                if($id_unidade_negocio === $unidade_negocio_id || $id_unidade_negocio === "outros" || $id_unidade_negocio === ""){
                                    
                                        $valor -= $movimentacao_devolucao['preco'];
                                        $quantidade -= $movimentacao_devolucao['quantidade'];

                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'linha' => $movimentacao_devolucao['linha'],
                                                'grupo' => $movimentacao_devolucao['grupo'],
                                                'marca' => $movimentacao_devolucao['marca'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                                'custo' => 0,
                                            ];
                                        }
                                        $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                        $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }
                
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
        }else{
            foreach($result as $unidade_negocio){
                $valor = 0;
                $quantidade = 0;
                if($unidade_negocio->metas->count() > 0){
                    if($unidade_negocio->unidade !== 'DENIM' && $unidade_negocio->unidade !== 'WORKWEAR' && $unidade_negocio->unidade !== 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
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
                                            $quantidade += $movimentacao['quantidade'];
    
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
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
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'DENIM'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
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
                                            $quantidade += $movimentacao['quantidade'];
        
                                            $id_denim = $unidade_negocio->id;
                                            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
            
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                        if(($movimentacao_devolucao['linha'] === 'DENIM IMPORTADO' || ($movimentacao_devolucao['linha'] === 'INDIGO E SARJAS' && $movimentacao_devolucao['marca'] === 'IMPORTADOS')) && ($movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL')){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(substr_count($movimentacao['linha'], "HOSPITALAR") > 0 && !in_array($movimentacao['unidade'], $this->unidade_metro)){
                                            $liberado = true;
                                        }
                                        if($liberado){
                                            $valor += $movimentacao['preco'];
                                            $quantidade += $movimentacao['quantidade'];
        
                                            $id_hospitalar = $unidade_negocio->id;
            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                        if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") > 0){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            $id_hospitalar = $unidade_negocio->id;
            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                             if((substr_count($movimentacao['linha'], "HOSPITALAR") === 0 || in_array($movimentacao['unidade'], $this->unidade_metro)) && ($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN")){
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
                                            $quantidade += $movimentacao['quantidade'];
    
                                            $id_workwear = $unidade_negocio->id;
        
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
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
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
                                            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
    
            if(!empty($id_hospitalar)){
                if($id_unidade_negocio === $id_hospitalar){
                    foreach($produtos as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_hospitalar){
                            unset($produtos[$key_unidade_negocio]);
                        }
                    }
                }
    
                foreach($usuarios_hospitalar['detalhes'] as $usuario_hospitalar){
                    if(!empty($movimentacoes[$usuario_hospitalar['codigo']])){
                        foreach($movimentacoes[$usuario_hospitalar['codigo']] as $codigo_produto => $produto){
                            foreach($produto as $key => $movimentacao){
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_hospitalar){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){
                                    if(empty($produtos[$id_hospitalar][$codigo_produto])){
                                        $produtos[$id_hospitalar][$codigo_produto] = [
                                            'vendedor_codigo' => $usuario_hospitalar['codigo'],
                                            'linha' => $movimentacao['linha'],
                                            'grupo' => $movimentacao['grupo'],
                                            'marca' => $movimentacao['marca'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_hospitalar['codigo']][$codigo_produto][$key]);
                                    }else{
                                        $produtos[$id_hospitalar][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                        $produtos[$id_hospitalar][$codigo_produto]['valor'] += $movimentacao['preco'];
                                        $produtos[$id_hospitalar][$codigo_produto]['custo'] += $movimentacao['custo'];
                                    }
                                    unset($movimentacoes[$usuario_hospitalar['codigo']][$codigo_produto][$key]);
                                }
                            }                        
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($produtos as $unidade_negocio){
                    foreach($unidade_negocio as $produto){
                        $total_valor += $produto['valor'];
                        $total_quantidade += $produto['quantidade']; 
                    }
                }
            }
    
            if(!empty($id_workwear) && ($id_unidade_negocio === $id_workwear || $id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_workwear){
                    foreach($produtos as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_workwear){
                            unset($produtos[$key_unidade_negocio]);
                        }
                    }
                }
                foreach($usuarios_workwear['detalhes'] as $usuario_workwear){
                    if(!empty($movimentacoes[$usuario_workwear['codigo']])){
                        foreach($movimentacoes[$usuario_workwear['codigo']] as $codigo_produto => $produto){
                            foreach($produto as $key => $movimentacao){
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_workwear){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){
                                    if(empty($produtos[$id_workwear][$codigo_produto])){
                                        $produtos[$id_workwear][$codigo_produto] = [
                                            'vendedor_codigo' => $usuario_workwear['codigo'],
                                            'linha' => $movimentacao['linha'],
                                            'grupo' => $movimentacao['grupo'],
                                            'marca' => $movimentacao['marca'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_workwear['codigo']][$codigo_produto][$key]);
                                    }else{
                                        $produtos[$id_workwear][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                        $produtos[$id_workwear][$codigo_produto]['valor'] += $movimentacao['preco'];
                                        $produtos[$id_workwear][$codigo_produto]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_workwear['codigo']][$codigo_produto][$key]);
                                    }
                                }
                            }
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($produtos as $unidade_negocio){
                    foreach($unidade_negocio as $produto){
                        $total_valor += $produto['valor'];
                        $total_quantidade += $produto['quantidade']; 
                    }
                }
            }
    
            if(!empty($id_denim) && ($id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_denim){
                    foreach($produtos as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_denim){
                            unset($produtos[$key_unidade_negocio]);
                        }
                    }
                }
                foreach($usuarios_denim['detalhes'] as $usuario_denim){
                    if(!empty($movimentacoes[$usuario_denim['codigo']])){
                        foreach($movimentacoes[$usuario_denim['codigo']] as $codigo_produto => $produto){
                            foreach( $produto as $key => $movimentacao){     
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_denim){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){   
                                    if(empty($produtos[$id_denim][$codigo_produto])){
                                        $produtos[$id_denim][$codigo_produto] = [
                                            'vendedor_codigo' => $usuario_denim['codigo'],
                                            'linha' => $movimentacao['linha'],
                                            'grupo' => $movimentacao['grupo'],
                                            'marca' => $movimentacao['marca'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_denim['codigo']][$codigo_produto][$key]);
                                    }else{
                                        $clientes[$id_denim][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                        $clientes[$id_denim][$codigo_produto]['valor'] += $movimentacao['preco'];
                                        $clientes[$id_denim][$codigo_produto]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_denim['codigo']][$codigo_produto][$key]);
                                    }
                                }
                            }
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($produtos as $unidade_negocio){
                    foreach($unidade_negocio as $produto){
                        $total_valor += $produto['valor'];
                        $total_quantidade += $produto['quantidade']; 
                    }
                }
            }
        }

        if($id_unidade_negocio === "" || $id_unidade_negocio == "nulo"){
            if(!empty($movimentacoes[""])){
                if(count($movimentacoes[""]) > 0){
                    $valor = 0;
                    $produtos = [];
                    foreach($movimentacoes[""] as $codigo_produto => $produto){
                        foreach($produto as $movimentacao){
                            $valor += empty($movimentacao['preco'])? 0 : $movimentacao['preco'];

                            if(empty($produtos['nulo'][$codigo_produto])){
                                $produtos['nulo'][$codigo_produto] = [
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'linha' => $movimentacao['linha'],
                                    'grupo' => $movimentacao['grupo'],
                                    'marca' => $movimentacao['marca'],
                                    'quantidade' => $movimentacao['quantidade'],
                                    'valor' => empty($movimentacao['preco'])? 0 : $movimentacao['preco'],
                                    'custo' => empty($movimentacao['custo'])? 0 : $movimentacao['custo'],
                                ];
                            }else{
                                $produtos['nulo'][$codigo_produto]['valor'] += $movimentacao['preco'];
                                $produtos['nulo'][$codigo_produto]['custo'] += $movimentacao['custo'];
                            }
                        }
                    }

                    if(!empty($movimentacoes_devolucao[""])){
                        foreach($movimentacoes_devolucao[""] as $movimentacao_devolucao_dados){
                            $valor -= $movimentacao_devolucao_dados['preco'];
                            
                            if(empty($produtos['nulo'][$codigo_produto])){
                                $produtos['nulo'][$codigo_produto] = [
                                    'vendedor_codigo' => $codigo_vendedor,
                                    'linha' => $movimentacao['linha'],
                                    'grupo' => $movimentacao['grupo'],
                                    'marca' => $movimentacao['marca'],
                                    'quantidade' => $movimentacao['quantidade'],
                                    'valor' => empty($movimentacao['preco'])? 0 : $movimentacao['preco'],
                                    'custo' => empty($movimentacao['custo'])? 0 : $movimentacao['custo'],
                                ];
                            }else{
                                $produtos['nulo'][$codigo_produto]['valor'] += $movimentacao['preco'];
                                $produtos['nulo'][$codigo_produto]['custo'] += $movimentacao['custo'];
                            }
                        }
                        unset($movimentacoes_devolucao[""]);
                    }

                    $total_valor += $valor;

                    unset($movimentacoes[""]);
                }
            }
        }

        if($id_unidade_negocio === "outros" || $id_unidade_negocio === ""){
            $produtos = [];
            if($id_unidade_negocio !== ""){
                $total_valor = 0;
                $total_quantidade = 0;
            }
            if(count($movimentacoes) > 0){
                $valor = 0;
                $quantidade = 0;
                foreach($movimentacoes as $codigo_vendedor => $vendedor){
                    foreach($vendedor as $codigo_produto => $produto ){
                        if(!empty($codigo_vendedor)){
                            foreach($produto as $movimentacao){
                                if(empty($produtos[$id_unidade_negocio][$codigo_produto])){
                                    $produtos[$id_unidade_negocio][$codigo_produto] = [
                                        'vendedor_codigo' => $movimentacao['vendedor'],
                                        'linha' => $movimentacao['linha'],
                                        'grupo' => $movimentacao['grupo'],
                                        'marca' => $movimentacao['marca'],
                                        'quantidade' => $movimentacao['quantidade'],
                                        'valor' => $movimentacao['preco'],
                                        'custo' => $movimentacao['custo'],
                                    ];
    
                                    if(!empty($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto])){
                                        foreach($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto] as $key_devolucao => $movimentacao_devolucao){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            $produtos[$id_unidade_negocio][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$id_unidade_negocio][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
                                        }
                                    }
                                }else{
                                    $produtos[$id_unidade_negocio][$codigo_produto]['valor'] += $movimentacao['preco'];
                                    $produtos[$id_unidade_negocio][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                    $produtos[$id_unidade_negocio][$codigo_produto]['custo'] += $movimentacao['custo'];
                                }

                                $valor += $movimentacao['preco'];
                            }
                        }
                    }
                    unset($movimentacoes[$codigo_vendedor]);
                }
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
        }

        $array_produtos = $produtos;
        $produtos = [];
        $total_custo = 0;
        foreach($array_produtos as $unidade_negocio){
            foreach($unidade_negocio as $produto){
                $produtos [] =[
                    'valor' => $produto['valor'],
                    'rank_codigo' => 0,
                    'grupo' => $produto['grupo'],
                    'linha' => $produto['linha'],
                    'marca' => $produto['marca'],
                    'quantidade' => $produto['quantidade'],
                    'porcetagem' => ($produto['valor'] / $total_valor) * 100,
                    'porcetagem_acumulativa' => '',
                    'preco_unitario' => empty($produto['valor'])  || empty($produto['quantidade'])? 0 : ($produto['valor'] / $produto['quantidade']),
                    'custo_gerencial' => empty($produto['custo'])  || empty($produto['quantidade'])? 0 : ($produto['custo'] / $produto['quantidade']),
                    'retabilidade_porcetagem' => $produto['valor'] > 0 && $produto['custo'] > 0? (($produto['valor'] / $produto['custo']) - 1) * 100: '',
                ];

                $total_custo += $produto['custo'];
            }
        }

        arsort($produtos);
        $rank = 0;
        $total_porcetagem = 0;
        $total_preco_unitario = 0;
        $total_custo_gerencial = 0;
        foreach($produtos as $key => $produto){
            $rank++;
            $total_porcetagem += $produtos[$key]['porcetagem'];
            $produtos[$key]['rank_codigo'] = $rank;
            $produtos[$key]['porcetagem_acumulativa'] = $total_porcetagem;
            $total_preco_unitario += $produtos[$key]['preco_unitario'];
            $total_custo_gerencial += $produtos[$key]['custo_gerencial'];
        }
        
        $total_porcetagem = empty($total_porcetagem)? '' : parserValor($total_porcetagem).'%';
        $total_retabilidade = $total_valor > 0 && $total_custo > 0? parserValor((($total_valor / $total_custo) - 1) * 100)."%": '';
        $quantidade_total_data_escolhida = empty($total_quantidade)? '' : parserValor($total_quantidade);
        $valor_total_data_escolhida = empty($total_valor)? '' : parserValor($total_valor);

        $produtos = $this->ajusteArrayParaValores($produtos);

        $tipo_usuarios = $this->getTipoVendedores();
        $unidades_negocios = $this->getUnidadeNegocio();
        $representantes = $this->getRepresentante();
        $regioes = $this->getRegiao();
        $nome_excel = $fields['title'];

        return view('programs.mapa_venda.modal.grupo')->with(['produtos' => $produtos, 'quantidade_total_data_escolhida' => $quantidade_total_data_escolhida, 'valor_total_data_escolhida' => $valor_total_data_escolhida, 'data_escolhida_inicial' => $data_escolhida_inicial, 'data_escolhida_final' => $data_escolhida_final, 'total_retabilidade' => $total_retabilidade, "filtro" => encrypt($filtro), "id_unidade_negocio" => encrypt($id_unidade_negocio), 'codigo_cliente' => $fields['codigo_cliente'], 'total_porcetagem' => $total_porcetagem, 'total_preco_unitario' => $total_preco_unitario, 'total_custo_gerencial' => $total_custo_gerencial, 'title' => $fields['title'], 'tipo_usuarios' => $tipo_usuarios, 'unidades_negocios' => $unidades_negocios, 'representantes' => $representantes, 'regioes' => $regioes, 'filtro_cliente' => $filtro_cliente,'codigo_vendedor' => $fields['codigo_vendedor'], 'nome_excel' => $nome_excel]);
    }

    public function filtroGrupo(Request $request){
        $fields = $request->only('filtro', 'id_unidade_negocio', 'codigo_cliente', 'tipo', 'equipe', 'vendedor', 'regiao', 'marca_nacional_importado','codigo_produto', 'descricao_produto', 'marca_produto', 'linha_produto', 'grupo_produto', 'subgrupo_produto', 'filtro_produto_vendedor');
        $filtro = decrypt($fields['filtro']);

        if(!empty($fields['id_unidade_negocio'])){
            $id_unidade_negocio = decrypt($fields['id_unidade_negocio']);
        }else{
            $id_unidade_negocio = "";
        }
        
        $unidades_negocios = $this->getUnidadeNegocio();
        $representantes = $this->getRepresentante();

        $data_escolhida = '01/'.$filtro['mes_ano'];
        $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
        $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

        $feriadoControllerObj = new FeriadoController;
        $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
        $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);

        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_verificacao_hospitalar_outfits = Carbon::createFromFormat('d/m/Y', '01/09/2020')->setTime(0,0,0);

        if($filtro['tipo_data'] == 'diario'){
            $data_escolhida = $filtro['data_dia'];
            $ultimo_dia = $filtro['data_dia'];

            $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

            $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
            $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);
            
            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'quinzenal'){
            if($filtro['quinzena'] == 1){
                $data_escolhida = '01/'.$filtro['mes_ano'];
                $ultimo_dia = 15;
            }else{
                $data_escolhida = '16/'.$filtro['mes_ano'];
                $ultimo_dia = '';
            }

            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0);
            $data_escolhida_final = empty($ultimo_dia)? $ultimo_dia_do_mes : Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$filtro['mes_ano'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'semanal'){
            $data_escolhida = '01/'.$filtro['mes_ano'];

            $arraySemanas = semanaDiaInicialFinal($primeiro_dia_do_mes->format('Y-m-d'));

            $data_escolhida_inicial = Carbon::parse($arraySemanas[(int)$filtro['semanas']][0]['day_week'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('Y-m-d', $arraySemanas[(int)$filtro['semanas']][count($arraySemanas[(int)$filtro['semanas']])-1]['day_week'])->setTime(0,0,0);
            
            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else{
            $dias_uteis = $divisao_meta;

            $data_escolhida_inicial = $primeiro_dia_do_mes;
            $data_escolhida_final = $ultimo_dia_do_mes;
        }

        $excecoes = $this->excecoes($data_escolhida_inicial, $data_escolhida_final);

        $usuarios_workwear = $this->getUserWorkwear($primeiro_dia_do_mes);
        $usuarios_denim = $this->getUserDenim($primeiro_dia_do_mes);
        $usuarios_hospitalar = $this->getUserHospitalar($primeiro_dia_do_mes);
        $clientes_bionexo = $this->clientesBionexo();

        $query = UnidadeNegocio::select();
        
        $query->with(['metas' => function($query) use($primeiro_dia_do_mes, $filtro){
            $query->where('data', $primeiro_dia_do_mes);
            if(!empty($filtro['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($filtro){
                    $query->where('codigo_representante', $filtro['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);

        if(($id_unidade_negocio !== "outros" && $id_unidade_negocio !== "") && !in_array($id_unidade_negocio, [5,6,7])){
            $query->where('id', $id_unidade_negocio);
        }

        if(!empty($fields['equipe'])){
            $query->where('id', $fields['equipe']);
        }

        $result = $query->get();

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, unidade, documento, estabelecimento, cliente_codigo, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        if(!empty($fields['codigo_cliente'])){
            $query_movimentacao->where('cliente_codigo', 'ilike', $fields['codigo_cliente']);
        }
        $query_movimentacao->with(['cliente' => function($query)use($fields){
            if(!empty($fields['regiao'])){
                $query->whereIn('uf', $this->getEstadoPorRegiao($fields['regiao']));
            }
        }]);
        if(!empty($fields['tipo'])){
            $query_user = User::select();
            $query_user->where('tipo_usuario_id', $fields['tipo']);
            $result_user = $query_user->get();

            $query_movimentacao->whereIn('vendedor', $result_user->pluck('codigo_representante'));
        }
        if(!empty($fields['vendedor'])){
            $query_movimentacao->where('vendedor', $fields['vendedor']);
        }
        if(!empty($fields['marca_nacional_importado'])){
            $query_movimentacao->whereHas('produto', function($query) use($fields){
                if($fields['marca_nacional_importado'] !== 'importado'){
                    $query->whereIn('procedencia', [0,3,4,5]);
                }else{
                    $query->whereIn('procedencia', [1,2,6,7]);
                }
            });
        }
        if(!empty($fields['codigo_produto'])){
            $query_movimentacao->where('produto_codigo', 'ilike', '%'.$fields['codigo_produto'].'%');
        }
        if(!empty($fields['descricao_produto'])){
            $query_movimentacao->where('descricao', 'ilike', '%'.$fields['descricao_produto'].'%');
        }
        if(!empty($fields['marca_produto'])){
            $query_movimentacao->where('marca', 'ilike', '%'.$fields['marca_produto'].'%');
        }
        if(!empty($fields['linha_produto'])){
            $query_movimentacao->where('linha', 'ilike', '%'.$fields['linha_produto'].'%');
        }
        if(!empty($fields['grupo_produto'])){
            $query_movimentacao->where('grupo', 'ilike', '%'.$fields['grupo_produto'].'%');
        }
        if(!empty($fields['subgrupo_produto'])){
            $query_movimentacao->where('subgrupo', 'ilike', '%'.$fields['subgrupo_produto'].'%');
        }
        if(!empty($filtro['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro['vendedor']);
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
            $movimentacoes_devolucao[$movimentacao->vendedor][$movimentacao->grupo.$movimentacao->linha.$movimentacao->marca][] = [
                'vendedor' => $movimentacao->vendedor,
                'vendedor_nome' => empty($representantes[$movimentacao->vendedor])? '' : $representantes[$movimentacao->vendedor],
                'marca' => $movimentacao->marca,
                'linha' => $movimentacao->linha,
                'grupo' => $movimentacao->grupo,
                'quantidade' => $movimentacao->quantidade_total,
                'preco' => $movimentacao->preco_total,
                'unidade' => $movimentacao->unidade,
                'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
                'bionexo' => $bionexo,
            ];
        }

        $query_movimentacao = Movimentacao::selectRaw('vendedor, marca, linha, grupo, unidade, documento, cliente_codigo, estabelecimento, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end ) as preco_total, sum(preco_pcmn * quantidade) as custo');
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);
        if(!empty($fields['codigo_cliente'])){
            $query_movimentacao->where('cliente_codigo', 'ilike', $fields['codigo_cliente']);
        }
        $query_movimentacao->with(['cliente' => function($query)use($fields){
            if(!empty($fields['regiao'])){
                $query->whereIn('uf', $this->getEstadoPorRegiao($fields['regiao']));
            }
        }]);

        if(!empty($fields['tipo'])){
            $query_user = User::select();
            $query_user->where('tipo_usuario_id', $fields['tipo']);
            $result_user = $query_user->get();

            $query_movimentacao->whereIn('vendedor', $result_user->pluck('codigo_representante'));
        }
        if(!empty($fields['vendedor'])){
            $query_movimentacao->where('vendedor', $fields['vendedor']);
        }
        if(!empty($fields['marca_nacional_importado'])){
            $query_movimentacao->whereHas('produto', function($query) use($fields){
                if($fields['marca_nacional_importado'] !== 'importado'){
                    $query->whereIn('procedencia', [0,3,4,5]);
                }else{
                    $query->whereIn('procedencia', [1,2,6,7]);
                }
            });
        }
        if(!empty($fields['codigo_produto'])){
            $query_movimentacao->where('produto_codigo', 'ilike', '%'.$fields['codigo_produto'].'%');
        }
        if(!empty($fields['descricao_produto'])){
            $query_movimentacao->where('descricao', 'ilike', '%'.$fields['descricao_produto'].'%');
        }
        if(!empty($fields['marca_produto'])){
            $query_movimentacao->where('marca', 'ilike', '%'.$fields['marca_produto'].'%');
        }
        if(!empty($fields['linha_produto'])){
            $query_movimentacao->where('linha', 'ilike', '%'.$fields['linha_produto'].'%');
        }
        if(!empty($fields['grupo_produto'])){
            $query_movimentacao->where('grupo', 'ilike', '%'.$fields['grupo_produto'].'%');
        }
        if(!empty($fields['subgrupo_produto'])){
            $query_movimentacao->where('subgrupo', 'ilike', '%'.$fields['subgrupo_produto'].'%');
        }
        if(!empty($filtro['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro['vendedor']);
        }
        $query_movimentacao->groupBy('vendedor', 'cliente_codigo', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'estabelecimento', 'cliente_codigo');
        $result_movimentacao = $query_movimentacao->get();

        $movimentacoes = [];
        foreach($result_movimentacao as $movimentacao){
            $bionexo = false;
            if(!empty($movimentacao->cliente)){
                if(in_array($movimentacao->cliente->cpf_cnpj, $clientes_bionexo)){
                    $bionexo = true;
                }
            }
            $movimentacoes[$movimentacao->vendedor][$movimentacao->grupo.$movimentacao->linha.$movimentacao->marca][] = [
                'vendedor' => $movimentacao->vendedor,
                'vendedor_nome' => empty($representantes[$movimentacao->vendedor])? '' : $representantes[$movimentacao->vendedor],
                'marca' => $movimentacao->marca,
                'linha' => $movimentacao->linha,
                'grupo' => $movimentacao->grupo,
                'quantidade' => $movimentacao->quantidade_total,
                'preco' => $movimentacao->preco_total,
                'custo' => $movimentacao->custo,
                'unidade' => $movimentacao->unidade,
                'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
                'bionexo' => $bionexo,
            ];
        }

        $unidades = [];

        $total_quantidade = 0;
        $total_valor = 0;
        $produtos = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        
        if($data_escolhida_inicial->gte($data_verificacao)){
            $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
            $usuarios_fashion_2 = $this->getUserFashion2($data_escolhida_inicial);
            if($data_escolhida_inicial->gte($data_verificacao_hospitalar_outfits)){
                $usuarios_outfites_confeccionados = $this->getUserOutfitsConfeccionados($data_escolhida_inicial); 
            }else{
                $usuarios_magazine = $this->getUserMagazine($data_escolhida_inicial);
            }
            foreach($result as $unidade_negocio){
                $valor = 0;
                $quantidade = 0;
                if($unidade_negocio->metas->count() > 0){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key => $movimentacao){
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
        
                                    if($id_unidade_negocio === $unidade_negocio_id || $id_unidade_negocio === "outros"){
                                        $valor += $movimentacao['preco'];
                                        $quantidade += $movimentacao['quantidade'];

                                        if($fields['filtro_produto_vendedor'] == 'vendedor'){
                                            if(empty($produtos[$unidade_negocio_id][$codigo_vendedor][$codigo_produto])){
                                                $produtos[$unidade_negocio_id][$codigo_vendedor][$codigo_produto] = [
                                                    'unidade_nome' => $unidades_negocios[$unidade_negocio_id],
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'vendedor_nome' => $movimentacao['vendedor_nome'],
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio_id][$codigo_vendedor][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio_id][$codigo_vendedor][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio_id][$codigo_vendedor][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }else{
                                            if(empty($produtos[$unidade_negocio_id][$codigo_produto])){
                                                $produtos[$unidade_negocio_id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio_id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio_id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio_id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                    $unidade_negocio_id = $unidade_negocio->id;
                                if(!empty($excecoes[$movimentacao_devolucao['documento']])){
                                    $unidade_negocio_id = $excecoes[$movimentacao_devolucao['documento']];
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

                                if($id_unidade_negocio === $unidade_negocio_id || $id_unidade_negocio === "outros" || $id_unidade_negocio === ""){
                                    
                                        $valor -= $movimentacao_devolucao['preco'];
                                        $quantidade -= $movimentacao_devolucao['quantidade'];

                                        if($fields['filtro_produto_vendedor'] == 'vendedor'){
                                            if(empty($produtos[$unidade_negocio_id][$codigo_vendedor][$codigo_produto])){
                                                $produtos[$unidade_negocio_id][$codigo_vendedor][$codigo_produto] = [
                                                    'unidade_nome' => $unidades_negocios[$unidade_negocio_id],
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'vendedor_nome' => $movimentacao['vendedor_nome'],
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }

                                            $produtos[$unidade_negocio_id][$codigo_vendedor][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio_id][$codigo_vendedor][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
                                        
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }else{
                                            if(empty($produtos[$unidade_negocio_id][$codigo_produto])){
                                                $produtos[$unidade_negocio_id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
                                            $produtos[$unidade_negocio_id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio_id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
        }else{
            foreach($result as $unidade_negocio){
                $valor = 0;
                $quantidade = 0;
                if($unidade_negocio->metas->count() > 0){
                    if($unidade_negocio->unidade !== 'DENIM' && $unidade_negocio->unidade !== 'WORKWEAR' && $unidade_negocio->unidade !== 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(!empty($excecoes[$movimentacao['documento']])){
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
                                            $quantidade += $movimentacao['quantidade'];
    
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
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
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'DENIM'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
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
                                            $quantidade += $movimentacao['quantidade'];
        
                                            $id_denim = $unidade_negocio->id;
                                            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
            
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                        if(($movimentacao_devolucao['linha'] === 'DENIM IMPORTADO' || ($movimentacao_devolucao['linha'] === 'INDIGO E SARJAS' && $movimentacao_devolucao['marca'] === 'IMPORTADOS')) && ($movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL')){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(substr_count($movimentacao['linha'], "HOSPITALAR") > 0 && !in_array($movimentacao['unidade'], $this->unidade_metro)){
                                            $liberado = true;
                                        }
                                        if($liberado){
                                            $valor += $movimentacao['preco'];
                                            $quantidade += $movimentacao['quantidade'];
        
                                            $id_hospitalar = $unidade_negocio->id;
            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                        if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") > 0){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            $id_hospitalar = $unidade_negocio->id;
            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                             if((substr_count($movimentacao['linha'], "HOSPITALAR") === 0 || in_array($movimentacao['unidade'], $this->unidade_metro)) && ($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN")){
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
                                            $quantidade += $movimentacao['quantidade'];
    
                                            $id_workwear = $unidade_negocio->id;
        
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
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
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
                                            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
    
            if(!empty($id_hospitalar)){
                if($id_unidade_negocio === $id_hospitalar){
                    foreach($produtos as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_hospitalar){
                            unset($produtos[$key_unidade_negocio]);
                        }
                    }
                }
    
                foreach($usuarios_hospitalar['detalhes'] as $usuario_hospitalar){
                    if(!empty($movimentacoes[$usuario_hospitalar['codigo']])){
                        foreach($movimentacoes[$usuario_hospitalar['codigo']] as $codigo_produto => $produto){
                            foreach($produto as $key => $movimentacao){
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_hospitalar){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){  
                                    if(empty($produtos[$id_hospitalar][$codigo_produto])){
                                        $produtos[$id_hospitalar][$codigo_produto] = [
                                            'vendedor_codigo' => $usuario_hospitalar['codigo'],
                                            'linha' => $movimentacao['linha'],
                                            'grupo' => $movimentacao['grupo'],
                                            'marca' => $movimentacao['marca'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_hospitalar['codigo']][$codigo_produto][$key]);
                                    }else{
                                        $produtos[$id_hospitalar][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                        $produtos[$id_hospitalar][$codigo_produto]['valor'] += $movimentacao['preco'];
                                        $produtos[$id_hospitalar][$codigo_produto]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_hospitalar['codigo']][$codigo_produto][$key]);
                                    }
                                }
                            }                        
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($produtos as $unidade_negocio){
                    foreach($unidade_negocio as $produto){
                        $total_valor += $produto['valor'];
                        $total_quantidade += $produto['quantidade']; 
                    }
                }
            }
    
            if(!empty($id_workwear) && ($id_unidade_negocio === $id_workwear || $id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_workwear){
                    foreach($produtos as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_workwear){
                            unset($produtos[$key_unidade_negocio]);
                        }
                    }
                }
                foreach($usuarios_workwear['detalhes'] as $usuario_workwear){
                    if(!empty($movimentacoes[$usuario_workwear['codigo']])){
                        foreach($movimentacoes[$usuario_workwear['codigo']] as $codigo_produto => $produto){
                            foreach($produto as $key => $movimentacao){
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_workwear){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){  
                                    if(empty($produtos[$id_workwear][$codigo_produto])){
                                        $produtos[$id_workwear][$codigo_produto] = [
                                            'vendedor_codigo' => $usuario_workwear['codigo'],
                                            'linha' => $movimentacao['linha'],
                                            'grupo' => $movimentacao['grupo'],
                                            'marca' => $movimentacao['marca'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_workwear['codigo']][$codigo_produto][$key]);
                                    }else{
                                        $produtos[$id_workwear][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                        $produtos[$id_workwear][$codigo_produto]['valor'] += $movimentacao['preco'];
                                        $produtos[$id_workwear][$codigo_produto]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_workwear['codigo']][$codigo_produto][$key]);
                                    }
                                }
                            }
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($produtos as $unidade_negocio){
                    foreach($unidade_negocio as $produto){
                        $total_valor += $produto['valor'];
                        $total_quantidade += $produto['quantidade']; 
                    }
                }
            }
    
            if(!empty($id_denim) && ($id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_denim){
                    foreach($produtos as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_denim){
                            unset($produtos[$key_unidade_negocio]);
                        }
                    }
                }
                foreach($usuarios_denim['detalhes'] as $usuario_denim){
                    if(!empty($movimentacoes[$usuario_denim['codigo']])){
                        foreach($movimentacoes[$usuario_denim['codigo']] as $codigo_produto => $produto){
                            foreach( $produto as $key => $movimentacao){       
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_denim){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){   
                                    if(empty($produtos[$id_denim][$codigo_produto])){
                                        $produtos[$id_denim][$codigo_produto] = [
                                            'vendedor_codigo' => $usuario_denim['codigo'],
                                            'linha' => $movimentacao['linha'],
                                            'grupo' => $movimentacao['grupo'],
                                            'marca' => $movimentacao['marca'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_denim['codigo']][$codigo_produto][$key]);
                                    }else{
                                        $produtos[$id_denim][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                        $produtos[$id_denim][$codigo_produto]['valor'] += $movimentacao['preco'];
                                        $produtos[$id_denim][$codigo_produto]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_denim['codigo']][$codigo_produto][$key]);
                                    }
                                }
                            }
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($produtos as $unidade_negocio){
                    foreach($unidade_negocio as $produto){
                        $total_valor += $produto['valor'];
                        $total_quantidade += $produto['quantidade']; 
                    }
                }
            }
        }


        if($id_unidade_negocio === "outros" || $id_unidade_negocio === ""){
            $clientes = [];
            if($id_unidade_negocio !== ""){
                $total_valor = 0;
                $total_quantidade = 0;
            }
            if(count($movimentacoes) > 0){
                $valor = 0;
                $quantidade = 0;
                foreach($movimentacoes as $codigo_vendedor => $vendedor){
                    foreach($vendedor as $codigo_produto => $produto ){
                        if(!empty($codigo_vendedor)){
                            foreach($produto as $movimentacao){
                                if($fields['filtro_produto_vendedor'] == 'vendedor'){
                                    if(empty($produtos[$id_unidade_negocio][$movimentacao['vendedor']][$codigo_produto])){
                                        $produtos[$id_unidade_negocio][$movimentacao['vendedor']][$codigo_produto] = [
                                            'unidade_nome' => "Outros",
                                            'vendedor_nome' => $movimentacao['vendedor_nome'],
                                            'vendedor_codigo' => $movimentacao['vendedor'],
                                            'linha' => $movimentacao['linha'],
                                            'grupo' => $movimentacao['grupo'],
                                            'marca' => $movimentacao['marca'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
        
                                        if(!empty($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto])){
                                            foreach($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto] as $key_devolucao => $movimentacao_devolucao){
                                                $valor -= $movimentacao_devolucao['preco'];
                                                $quantidade -= $movimentacao_devolucao['quantidade'];
        
                                                $produtos[$id_unidade_negocio][$movimentacao['vendedor']][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                                $produtos[$id_unidade_negocio][$movimentacao['vendedor']][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
                                            }
                                        }
                                    }else{
                                        $produtos[$id_unidade_negocio][$movimentacao['vendedor']][$codigo_produto]['valor'] += $movimentacao['preco'];
                                        $produtos[$id_unidade_negocio][$movimentacao['vendedor']][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                        $produtos[$id_unidade_negocio][$movimentacao['vendedor']][$codigo_produto]['custo'] += $movimentacao['custo'];
                                    }
    
                                }else{
                                    if(empty($produtos[$id_unidade_negocio][$codigo_produto])){
                                        $produtos[$id_unidade_negocio][$codigo_produto] = [
                                            'vendedor_codigo' => $movimentacao['vendedor'],
                                            'linha' => $movimentacao['linha'],
                                            'grupo' => $movimentacao['grupo'],
                                            'marca' => $movimentacao['marca'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
        
                                        if(!empty($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto])){
                                            foreach($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto] as $key_devolucao => $movimentacao_devolucao){
                                                $valor -= $movimentacao_devolucao['preco'];
                                                $quantidade -= $movimentacao_devolucao['quantidade'];
        
                                                $produtos[$id_unidade_negocio][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                                $produtos[$id_unidade_negocio][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
                                            }
                                        }
                                    }else{
                                        $produtos[$id_unidade_negocio][$codigo_produto]['valor'] += $movimentacao['preco'];
                                        $produtos[$id_unidade_negocio][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                        $produtos[$id_unidade_negocio][$codigo_produto]['custo'] += $movimentacao['custo'];
                                    }
    
                                }

                                
                                $valor += $movimentacao['preco'];
                            }
                        }
                    }
                    unset($movimentacoes[$codigo_vendedor]);
                }
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
        }

        $array_produtos = $produtos;
        $produtos = [];
        $total_custo = 0;
        if($fields['filtro_produto_vendedor'] == 'vendedor'){
            foreach($array_produtos as $unidade_negocio){
                foreach($unidade_negocio as $vendedor){
                    foreach($vendedor as $produto){
                        $produtos [] =[
                            'unidade_nome' => $produto['unidade_nome'],
                            'vendedor_codigo' => $produto['vendedor_codigo'],
                            'vendedor_nome' => $produto['vendedor_nome'],
                            'valor' => $produto['valor'],
                            'rank_codigo' => 0,
                            'grupo' => $produto['grupo'],
                            'linha' => $produto['linha'],
                            'marca' => $produto['marca'],
                            'quantidade' => $produto['quantidade'],
                            'porcetagem' => ($produto['valor'] / $total_valor) * 100,
                            'porcetagem_acumulativa' => '',
                            'preco_unitario' => empty($produto['valor'])  || empty($produto['quantidade'])? 0 : ($produto['valor'] / $produto['quantidade']),
                            'custo_gerencial' => empty($produto['custo'])  || empty($produto['quantidade'])? 0 : ($produto['custo'] / $produto['quantidade']),
                            'retabilidade_porcetagem' => $produto['valor'] > 0 && $produto['custo'] > 0? (($produto['valor'] / $produto['custo']) - 1) * 100: '',
                        ];
        
                        $total_custo += $produto['custo'];
                    }
                }
            }
        }else{
            foreach($array_produtos as $unidade_negocio){
                foreach($unidade_negocio as $produto){
                    $produtos [] =[
                        'valor' => $produto['valor'],
                        'rank_codigo' => 0,
                        'grupo' => $produto['grupo'],
                        'linha' => $produto['linha'],
                        'marca' => $produto['marca'],
                        'quantidade' => $produto['quantidade'],
                        'porcetagem' => ($produto['valor'] / $total_valor) * 100,
                        'porcetagem_acumulativa' => '',
                        'preco_unitario' => empty($produto['valor'])  || empty($produto['quantidade'])? 0 : ($produto['valor'] / $produto['quantidade']),
                        'custo_gerencial' => empty($produto['custo'])  || empty($produto['quantidade'])? 0 : ($produto['custo'] / $produto['quantidade']),
                        'retabilidade_porcetagem' => $produto['valor'] > 0 && $produto['custo'] > 0? (($produto['valor'] / $produto['custo']) - 1) * 100: '',
                    ];
    
                    $total_custo += $produto['custo'];
                }
            }
        }

        arsort($produtos);
        $rank = 0;
        $total_porcetagem = 0;
        $total_preco_unitario = 0;
        $total_custo_gerencial = 0;
        $total_quantidade = 0;
        foreach($produtos as $key => $produto){
            $rank++;
            $total_porcetagem += $produtos[$key]['porcetagem'];
            $produtos[$key]['rank_codigo'] = $rank;
            $produtos[$key]['porcetagem_acumulativa'] = $total_porcetagem;
            $total_preco_unitario += $produtos[$key]['preco_unitario'];
            $total_custo_gerencial += $produtos[$key]['custo_gerencial'];

            $total_quantidade += parserNumber($produto['quantidade']);
        }
        
        $total_porcetagem = empty($total_porcetagem)? '' : parserValor($total_porcetagem).'%';
        $total_retabilidade = $total_valor > 0 && $total_custo > 0? parserValor((($total_valor / $total_custo) - 1) * 100)."%": '';
        $quantidade_total_data_escolhida = empty($total_quantidade)? '' : parserValor($total_quantidade);
        $valor_total_data_escolhida = empty($total_valor)? '' : parserValor($total_valor);

        $produtos = $this->ajusteArrayParaValores($produtos);

        $data_escolhida_inicial = $data_escolhida_inicial->format('d/m/Y');
        $data_escolhida_final = $data_escolhida_final->format('d/m/Y');

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'produtos' => $produtos,
                'porcetagem_total' => $total_porcetagem,
                'valor_total' => $valor_total_data_escolhida,
                'quantidade_total' => $quantidade_total_data_escolhida,
                'total_retabilidade' => $total_retabilidade,
            ],
        ]);
    }

    public function modalDevolucao(Request $request){
        $fields = $request->only('filtro', 'id_unidade_negocio', 'codigo_vendedor');
        $filtro = decrypt($fields['filtro']);
        if(!empty($fields['id_unidade_negocio'])){
            $id_unidade_negocio = decrypt($fields['id_unidade_negocio']);
        }else{
            $id_unidade_negocio = "";
        }

        if(!isset($fields['codigo_vendedor'])){
            $fields['codigo_vendedor'] = "";
        }

        $data_escolhida = '01/'.$filtro['mes_ano'];
        $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
        $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

        $feriadoControllerObj = new FeriadoController;
        $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
        $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);

        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_verificacao_hospitalar_outfits = Carbon::createFromFormat('d/m/Y', '01/09/2020')->setTime(0,0,0);

        if($filtro['tipo_data'] == 'diario'){
            $data_escolhida = $filtro['data_dia'];
            $ultimo_dia = $filtro['data_dia'];

            $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

            $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
            $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);
            
            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'quinzenal'){
            if($filtro['quinzena'] == 1){
                $data_escolhida = '01/'.$filtro['mes_ano'];
                $ultimo_dia = 15;
            }else{
                $data_escolhida = '16/'.$filtro['mes_ano'];
                $ultimo_dia = '';
            }

            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0);
            $data_escolhida_final = empty($ultimo_dia)? $ultimo_dia_do_mes : Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$filtro['mes_ano'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'semanal'){
            $data_escolhida = '01/'.$filtro['mes_ano'];

            $arraySemanas = semanaDiaInicialFinal($primeiro_dia_do_mes->format('Y-m-d'));

            $data_escolhida_inicial = Carbon::parse($arraySemanas[(int)$filtro['semanas']][0]['day_week'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('Y-m-d', $arraySemanas[(int)$filtro['semanas']][count($arraySemanas[(int)$filtro['semanas']])-1]['day_week'])->setTime(0,0,0);
            
            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else{
            $dias_uteis = $divisao_meta;

            $data_escolhida_inicial = $primeiro_dia_do_mes;
            $data_escolhida_final = $ultimo_dia_do_mes;
        }

        $usuarios_workwear = $this->getUserWorkwear($primeiro_dia_do_mes);
        $usuarios_denim = $this->getUserDenim($primeiro_dia_do_mes);
        $usuarios_hospitalar = $this->getUserHospitalar($primeiro_dia_do_mes);

        $query = UnidadeNegocio::select();
        
        $query->with(['metas' => function($query) use($primeiro_dia_do_mes, $filtro){
            $query->where('data', $primeiro_dia_do_mes);
            if(!empty($filtro['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($filtro){
                    $query->where('codigo_representante', $filtro['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);

        if(($id_unidade_negocio !== "outros" && $id_unidade_negocio !== "") && !in_array($id_unidade_negocio, [5,6,7])){
            $query->where('id', $id_unidade_negocio);
        }

        $result = $query->get();

        $query_movimentacao = Movimentacao::selectRaw('vendedor, cliente_codigo, produto_codigo, descricao, marca, linha, grupo, unidade, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        $query_movimentacao->with(['cliente']);
        if(!empty($fields['codigo_vendedor'])){
            $query_movimentacao->where('vendedor', $fields['codigo_vendedor']);
        }
        if(!empty($filtro['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro['vendedor']);
        }
        $query_movimentacao->groupBy('vendedor', 'cliente_codigo', 'produto_codigo', 'descricao', 'marca', 'linha', 'grupo', 'unidade');
        $result_movimentacao = $query_movimentacao->get();

        $movimentacoes_devolucao = [];
        foreach($result_movimentacao as $movimentacao){
            if(empty($movimentacao->cliente)){
                $cliente_nome = '';
            }else{
                $cliente_nome = $movimentacao->cliente_codigo === '001'? 'BALCAO' : $movimentacao->cliente->nome." - ".$movimentacao->cliente->cpf_cnpj;
            }
            $movimentacoes_devolucao[$movimentacao->vendedor][$movimentacao->produto_codigo][] = [
                'vendedor' => $movimentacao->vendedor,
                'cliente_codigo' => $movimentacao->cliente_codigo,
                'cliente' => $cliente_nome,
                'codigo' => $movimentacao->produto_codigo,
                'descricao' => $movimentacao->descricao,
                'marca' => $movimentacao->marca,
                'linha' => $movimentacao->linha,
                'grupo' => $movimentacao->grupo,
                'quantidade' => $movimentacao->quantidade_total,
                'preco' => $movimentacao->preco_total,
                'unidade' => $movimentacao->unidade,
            ];
        }

        $unidades = [];

        $total_quantidade = 0;
        $total_valor = 0;
        $produtos = [];
        $clientes = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        
        foreach($result as $unidade_negocio){
            $valor = 0;
            $quantidade = 0;
            if($unidade_negocio->metas->count() > 0){
                if($unidade_negocio->unidade !== 'DENIM' && $unidade_negocio->unidade !== 'WORKWEAR' && $unidade_negocio->unidade !== 'HOSPITALAR'){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key_devolucao => $movimentacao_devolucao){
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
                                        $valor += $movimentacao_devolucao['preco'];
                                        $quantidade += $movimentacao_devolucao['quantidade'];

                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'codigo' => $movimentacao_devolucao['codigo'],
                                                'descricao' => $movimentacao_devolucao['descricao'],
                                                'linha' => $movimentacao_devolucao['linha'],
                                                'grupo' => $movimentacao_devolucao['grupo'],
                                                'marca' => $movimentacao_devolucao['marca'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }

                                        $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao_devolucao['preco'];
                                        
                                        if(empty($clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']])){
                                            $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao_devolucao['cliente'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['valor'] += $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }else if($unidade_negocio->unidade === 'DENIM'){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                    if(($movimentacao_devolucao['linha'] === 'DENIM IMPORTADO' || ($movimentacao_devolucao['linha'] === 'INDIGO E SARJAS' && $movimentacao_devolucao['marca'] === 'IMPORTADOS')) && ($movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL')){
                                        $valor += $movimentacao_devolucao['preco'];
                                        $quantidade += $movimentacao_devolucao['quantidade'];

                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'codigo' => $movimentacao_devolucao['codigo'],
                                                'descricao' => $movimentacao_devolucao['descricao'],
                                                'linha' => $movimentacao_devolucao['linha'],
                                                'grupo' => $movimentacao_devolucao['grupo'],
                                                'marca' => $movimentacao_devolucao['marca'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }

                                        $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao_devolucao['preco'];

                                        if(empty($clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']])){
                                            $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao_devolucao['cliente'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['valor'] += $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }else if($unidade_negocio->unidade === 'HOSPITALAR'){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                    if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") > 0){
                                        $valor += $movimentacao_devolucao['preco'];
                                        $quantidade += $movimentacao_devolucao['quantidade'];

                                        $id_hospitalar = $unidade_negocio->id;
        
                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'codigo' => $movimentacao_devolucao['codigo'],
                                                'descricao' => $movimentacao_devolucao['descricao'],
                                                'linha' => $movimentacao_devolucao['linha'],
                                                'grupo' => $movimentacao_devolucao['grupo'],
                                                'marca' => $movimentacao_devolucao['marca'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }

                                        $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao_devolucao['preco'];

                                        if(empty($clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']])){
                                            $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao_devolucao['cliente'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['valor'] += $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }else{
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                    $liberado = false;
                                    if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") === 0 && ($movimentacao_devolucao['marca'] !== 'IMPORTADOS' && $movimentacao_devolucao['marca'] !== "TEXTIL MN")){
                                            $liberado = true;
                                        }
                                    }else if($movimentacao_devolucao['marca'] !== 'IMPORTADOS' && $movimentacao_devolucao['marca'] !== "TEXTIL MN"){
                                        $liberado = true;
                                    }
                                    if($liberado){
                                        $valor += $movimentacao_devolucao['preco'];
                                        $quantidade += $movimentacao_devolucao['quantidade'];
                                        
                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'codigo' => $movimentacao_devolucao['codigo'],
                                                'descricao' => $movimentacao_devolucao['descricao'],
                                                'linha' => $movimentacao_devolucao['linha'],
                                                'grupo' => $movimentacao_devolucao['grupo'],
                                                'marca' => $movimentacao_devolucao['marca'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }

                                        $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao_devolucao['preco'];

                                        if(empty($clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']])){
                                            $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao_devolucao['cliente'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['valor'] += $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            $total_quantidade += $quantidade;
            $total_valor += $valor;
        }

        $quantidade_total = empty($total_quantidade)? '' : parserValor($total_quantidade);
        $valor_total = empty($total_valor)? '' : parserValor($total_valor);

        $produtos = $this->ajusteArrayParaValores($produtos);

        $data_escolhida_inicial = $data_escolhida_inicial->format('d/m/Y');
        $data_escolhida_final = $data_escolhida_final->format('d/m/Y');

        $tipo_usuarios = $this->getTipoVendedores();
        $unidades_negocios = $this->getUnidadeNegocio();
        $representantes = $this->getRepresentante();
        $regioes = $this->getRegiao();

        return view('programs.mapa_venda.modal.devolucao')->with(['produtos' => $produtos, 'clientes' => $clientes, 'data_escolhida_inicial' => $data_escolhida_inicial, 'data_escolhida_final' => $data_escolhida_final, 'quantidade_total' => $quantidade_total, 'valor_total' => $valor_total, 'tipo_usuarios' => $tipo_usuarios, 'unidades_negocios' => $unidades_negocios, 'representantes' => $representantes, 'regioes' => $regioes, 'id_unidade_negocio' => $id_unidade_negocio, 'codigo_vendedor' => $fields['codigo_vendedor'],"filtro" => encrypt($filtro)]);
    }

    public function filtroDevolucaoProduto(Request $request){
        $fields = $request->only('data_escolhida_inicial', 'data_escolhida_final', 'codigo_produto', 'descricao_produto', 'marca_produto', 'linha_produto', 'grupo_produto', 'subgrupo_produto', 'marca_nacional_importado');

        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $fields['data_escolhida_inicial'])->setTime(0,0,0);
        $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $fields['data_escolhida_final'])->setTime(0,0,0);
        
        $query = Movimentacao::selectRaw('cliente_codigo, cliente_cpf_cnpj, produto_codigo, descricao, linha, grupo, marca, unidade, cfop, sinal, quantidade, preco, (quantidade * preco) as preco_total');
        $query->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query->whereIn('cfop', $this->cfop_devolucao);

        if(!empty($fields['marca_nacional_importado'])){
            if($fields['marca_nacional_importado'] !== 'importado'){
                $query->where('marca', 'ilike', 'IMPORTADOS');
            }else{
                $query->where('marca', 'not ilike', 'IMPORTADOS');
            }
        }
        if(!empty($fields['codigo_produto'])){
            $query->where('produto_codigo', 'ilike', $fields['codigo_produto']);
        }
        if(!empty($fields['descricao_produto'])){
            $query->where('descricao', 'ilike', $fields['descricao_produto']);
        }
        if(!empty($fields['marca_produto'])){
            $query->where('marca', 'ilike', $fields['marca_produto']);
        }
        if(!empty($fields['linha_produto'])){
            $query->where('linha', 'ilike', $fields['linha_produto']);
        }
        if(!empty($fields['grupo_produto'])){
            $query->where('grupo', 'ilike', $fields['grupo_produto']);
        }
        if(!empty($fields['subgrupo_produto'])){
            $query->where('subgrupo', 'ilike', $fields['subgrupo_produto']);
        }

        $result = $query->get();

        $produtos = [];
        $quantidade_devolvida = 0;
        $valor_devolvido = 0;
        foreach($result as $registro){
            if(empty($produtos[$registro->produto_codigo])){
                $produtos[$registro->produto_codigo] = [
                    'codigo' => $registro->produto_codigo,
                    'descricao' => $registro->descricao,
                    'linha' => $registro->linha,
                    'grupo' => $registro->grupo,
                    'marca' => $registro->marca,
                    'quantidade' => $registro->quantidade,
                    'preco_total' => $registro->preco_total
                ];
            }else{
                $produtos[$registro->produto_codigo]['quantidade'] += $registro->quantidade;
                $produtos[$registro->produto_codigo]['preco_total'] += $registro->preco_total;
            }

            $quantidade_devolvida += $registro->quantidade;
            $valor_devolvido += $registro->preco_total;
        }

        $produtos = $this->ajusteArrayParaValores($produtos);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'produtos' => $produtos,
                'quantidade_devolvida' => empty($quantidade_devolvida)? '' : parserValor($quantidade_devolvida),
                'valor_devolvido' => empty($valor_devolvido)? '' : parserValor($valor_devolvido),
            ],
        ]);
    }

    public function filtroDevolucaoCliente(Request $request){
        $fields = $request->only('data_escolhida_inicial', 'data_escolhida_final', 'equipe', 'filtro', 'id_unidade_negocio', 'vendedor', 'regiao', 'marca_nacional_importado', 'cliente');

        $filtro = decrypt($fields['filtro']);
        if(!empty($fields['equipe'])){
            $id_unidade_negocio = $fields['equipe'];
        }else{
            $id_unidade_negocio = "";
        }

        if(!isset($fields['codigo_vendedor'])){
            $fields['codigo_vendedor'] = "";
        }

        if(!empty($fields['vendedor'])){
            $filtro['vendedor'] = $fields['vendedor'];
        }

        $data_escolhida = '01/'.$filtro['mes_ano'];
        $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
        $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

        $feriadoControllerObj = new FeriadoController;
        $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
        $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);

        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_verificacao_hospitalar_outfits = Carbon::createFromFormat('d/m/Y', '01/09/2020')->setTime(0,0,0);

        if($filtro['tipo_data'] == 'diario'){
            $data_escolhida = $filtro['data_dia'];
            $ultimo_dia = $filtro['data_dia'];

            $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

            $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
            $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);
            
            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'quinzenal'){
            if($filtro['quinzena'] == 1){
                $data_escolhida = '01/'.$filtro['mes_ano'];
                $ultimo_dia = 15;
            }else{
                $data_escolhida = '16/'.$filtro['mes_ano'];
                $ultimo_dia = '';
            }

            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0);
            $data_escolhida_final = empty($ultimo_dia)? $ultimo_dia_do_mes : Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$filtro['mes_ano'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'semanal'){
            $data_escolhida = '01/'.$filtro['mes_ano'];

            $arraySemanas = semanaDiaInicialFinal($primeiro_dia_do_mes->format('Y-m-d'));

            $data_escolhida_inicial = Carbon::parse($arraySemanas[(int)$filtro['semanas']][0]['day_week'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('Y-m-d', $arraySemanas[(int)$filtro['semanas']][count($arraySemanas[(int)$filtro['semanas']])-1]['day_week'])->setTime(0,0,0);
            
            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else{
            $dias_uteis = $divisao_meta;

            $data_escolhida_inicial = $primeiro_dia_do_mes;
            $data_escolhida_final = $ultimo_dia_do_mes;
        }

        $usuarios_workwear = $this->getUserWorkwear($primeiro_dia_do_mes);
        $usuarios_denim = $this->getUserDenim($primeiro_dia_do_mes);
        $usuarios_hospitalar = $this->getUserHospitalar($primeiro_dia_do_mes);

        $query = UnidadeNegocio::select();
        
        $query->with(['metas' => function($query) use($primeiro_dia_do_mes, $filtro){
            $query->where('data', $primeiro_dia_do_mes);
            if(!empty($filtro['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($filtro){
                    $query->where('codigo_representante', $filtro['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);

        if(($id_unidade_negocio !== "outros" && $id_unidade_negocio !== "") && !in_array($id_unidade_negocio, [5,6,7])){
            $query->where('id', $id_unidade_negocio);
        }

        $result = $query->get();

        $query_movimentacao = Movimentacao::selectRaw('vendedor, cliente_codigo, produto_codigo, descricao, marca, linha, grupo, unidade, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        $query_movimentacao->with(['cliente' => function($query)use($fields){
            if(!empty($fields['regiao'])){
                $query->whereIn('uf', $this->getEstadoPorRegiao($fields['regiao']));
            }
        }]);
        if(!empty($fields['tipo'])){
            $query_user = User::select();
            $query_user->where('tipo_usuario_id', $fields['tipo']);
            $result_user = $query_user->get();

            $query_movimentacao->whereIn('vendedor', $result_user->pluck('codigo_representante'));
        }
        if(!empty($fields['codigo_vendedor'])){
            $query_movimentacao->where('vendedor', $fields['codigo_vendedor']);
        }
        if(!empty($filtro['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro['vendedor']);
        }
        if(!empty($fields['marca_nacional_importado'])){
            $query_movimentacao->whereHas('produto', function($query) use($fields){
                if($fields['marca_nacional_importado'] !== 'importado'){
                    $query->whereIn('procedencia', [0,3,4,5]);
                }else{
                    $query->whereIn('procedencia', [1,2,6,7]);
                }
            });
        }
        if(!empty($fields['cliente'])){
            $cliente_busca = ClienteNasajon::select('codigo')->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['cliente']).'%\'')->get();
            $query_movimentacao->WhereIn('cliente_codigo', $cliente_busca->pluck('codigo'));
        }
        $query_movimentacao->groupBy('vendedor', 'cliente_codigo', 'produto_codigo', 'descricao', 'marca', 'linha', 'grupo', 'unidade');
        $result_movimentacao = $query_movimentacao->get();

        $movimentacoes_devolucao = [];
        foreach($result_movimentacao as $movimentacao){
            if(empty($movimentacao->cliente)){
                $cliente_nome = '';
            }else{
                $cliente_nome = $movimentacao->cliente_codigo === '001'? 'BALCAO' : $movimentacao->cliente->nome." - ".$movimentacao->cliente->cpf_cnpj;
                $movimentacoes_devolucao[$movimentacao->vendedor][$movimentacao->produto_codigo][] = [
                    'vendedor' => $movimentacao->vendedor,
                    'cliente_codigo' => $movimentacao->cliente_codigo,
                    'cliente' => $cliente_nome,
                    'codigo' => $movimentacao->produto_codigo,
                    'descricao' => $movimentacao->descricao,
                    'marca' => $movimentacao->marca,
                    'linha' => $movimentacao->linha,
                    'grupo' => $movimentacao->grupo,
                    'quantidade' => $movimentacao->quantidade_total,
                    'preco' => $movimentacao->preco_total,
                    'unidade' => $movimentacao->unidade,
                ];
            }
        }

        $unidades = [];

        $total_quantidade = 0;
        $total_valor = 0;
        $produtos = [];
        $clientes = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        
        foreach($result as $unidade_negocio){
            $valor = 0;
            $quantidade = 0;
            if($unidade_negocio->metas->count() > 0){
                if($unidade_negocio->unidade !== 'DENIM' && $unidade_negocio->unidade !== 'WORKWEAR' && $unidade_negocio->unidade !== 'HOSPITALAR'){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key_devolucao => $movimentacao_devolucao){
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
                                        $valor += $movimentacao_devolucao['preco'];
                                        $quantidade += $movimentacao_devolucao['quantidade'];

                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'codigo' => $movimentacao_devolucao['codigo'],
                                                'descricao' => $movimentacao_devolucao['descricao'],
                                                'linha' => $movimentacao_devolucao['linha'],
                                                'grupo' => $movimentacao_devolucao['grupo'],
                                                'marca' => $movimentacao_devolucao['marca'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }

                                        $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao_devolucao['preco'];
                                        
                                        if(empty($clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']])){
                                            $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao_devolucao['cliente'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['valor'] += $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }else if($unidade_negocio->unidade === 'DENIM'){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                    if(($movimentacao_devolucao['linha'] === 'DENIM IMPORTADO' || ($movimentacao_devolucao['linha'] === 'INDIGO E SARJAS' && $movimentacao_devolucao['marca'] === 'IMPORTADOS')) && ($movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL')){
                                        $valor += $movimentacao_devolucao['preco'];
                                        $quantidade += $movimentacao_devolucao['quantidade'];

                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'codigo' => $movimentacao_devolucao['codigo'],
                                                'descricao' => $movimentacao_devolucao['descricao'],
                                                'linha' => $movimentacao_devolucao['linha'],
                                                'grupo' => $movimentacao_devolucao['grupo'],
                                                'marca' => $movimentacao_devolucao['marca'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }

                                        $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao_devolucao['preco'];

                                        if(empty($clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']])){
                                            $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao_devolucao['cliente'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['valor'] += $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }else if($unidade_negocio->unidade === 'HOSPITALAR'){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                    if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") > 0){
                                        $valor += $movimentacao_devolucao['preco'];
                                        $quantidade += $movimentacao_devolucao['quantidade'];

                                        $id_hospitalar = $unidade_negocio->id;
        
                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'codigo' => $movimentacao_devolucao['codigo'],
                                                'descricao' => $movimentacao_devolucao['descricao'],
                                                'linha' => $movimentacao_devolucao['linha'],
                                                'grupo' => $movimentacao_devolucao['grupo'],
                                                'marca' => $movimentacao_devolucao['marca'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }

                                        $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao_devolucao['preco'];

                                        if(empty($clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']])){
                                            $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao_devolucao['cliente'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['valor'] += $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }else{
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                    $liberado = false;
                                    if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") === 0 && ($movimentacao_devolucao['marca'] !== 'IMPORTADOS' && $movimentacao_devolucao['marca'] !== "TEXTIL MN")){
                                            $liberado = true;
                                        }
                                    }else if($movimentacao_devolucao['marca'] !== 'IMPORTADOS' && $movimentacao_devolucao['marca'] !== "TEXTIL MN"){
                                        $liberado = true;
                                    }
                                    if($liberado){
                                        $valor += $movimentacao_devolucao['preco'];
                                        $quantidade += $movimentacao_devolucao['quantidade'];
                                        
                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'codigo' => $movimentacao_devolucao['codigo'],
                                                'descricao' => $movimentacao_devolucao['descricao'],
                                                'linha' => $movimentacao_devolucao['linha'],
                                                'grupo' => $movimentacao_devolucao['grupo'],
                                                'marca' => $movimentacao_devolucao['marca'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }

                                        $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao_devolucao['preco'];

                                        if(empty($clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']])){
                                            $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao_devolucao['cliente'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                            ];
                                        }
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['quantidade'] += $movimentacao_devolucao['quantidade'];
                                        $clientes[$unidade_negocio->id][$movimentacao_devolucao['cliente_codigo']]['valor'] += $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            $total_quantidade += $quantidade;
            $total_valor += $valor;
        }

        $quantidade_total = empty($total_quantidade)? '' : parserValor($total_quantidade);
        $valor_total = empty($total_valor)? '' : parserValor($total_valor);

        $produtos = $this->ajusteArrayParaValores($produtos);

        $clientes = $this->ajusteArrayParaValores($clientes);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'clientes' => $clientes,
                'quantidade_devolvida' => $quantidade_total,
                'valor_devolvido' => $valor_total,
            ],
        ]);
    }

    public function modalCliente(Request $request){
        $fields = $request->only('filtro', 'id_unidade_negocio', 'title', 'codigo_vendedor');
        $filtro = decrypt($fields['filtro']);

        if(!empty($fields['id_unidade_negocio'])){
            $id_unidade_negocio = decrypt($fields['id_unidade_negocio']);
        }else{
            $id_unidade_negocio = "";
        }

        if(!isset($fields['codigo_vendedor'])){
            $fields['codigo_vendedor'] = "";
        }

        $data_escolhida = '01/'.$filtro['mes_ano'];
        $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
        $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

        $feriadoControllerObj = new FeriadoController;
        $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
        $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);

        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_verificacao_hospitalar_outfits = Carbon::createFromFormat('d/m/Y', '01/09/2020')->setTime(0,0,0);

        if($filtro['tipo_data'] == 'diario'){
            $data_escolhida = $filtro['data_dia'];
            $ultimo_dia = $filtro['data_dia'];

            $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

            $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
            $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);
            
            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'quinzenal'){
            if($filtro['quinzena'] == 1){
                $data_escolhida = '01/'.$filtro['mes_ano'];
                $ultimo_dia = 15;
            }else{
                $data_escolhida = '16/'.$filtro['mes_ano'];
                $ultimo_dia = '';
            }

            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0);
            $data_escolhida_final = empty($ultimo_dia)? $ultimo_dia_do_mes : Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$filtro['mes_ano'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'semanal'){
            $data_escolhida = '01/'.$filtro['mes_ano'];

            $arraySemanas = semanaDiaInicialFinal($primeiro_dia_do_mes->format('Y-m-d'));

            $data_escolhida_inicial = Carbon::parse($arraySemanas[(int)$filtro['semanas']][0]['day_week'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('Y-m-d', $arraySemanas[(int)$filtro['semanas']][count($arraySemanas[(int)$filtro['semanas']])-1]['day_week'])->setTime(0,0,0);
            
            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else{
            $dias_uteis = $divisao_meta;

            $data_escolhida_inicial = $primeiro_dia_do_mes;
            $data_escolhida_final = $ultimo_dia_do_mes;
        }

        $excecoes = $this->excecoes($data_escolhida_inicial, $data_escolhida_final);

        $usuarios_workwear = $this->getUserWorkwear($primeiro_dia_do_mes);
        $usuarios_denim = $this->getUserDenim($primeiro_dia_do_mes);
        $usuarios_hospitalar = $this->getUserHospitalar($primeiro_dia_do_mes);
        $clientes_bionexo = $this->clientesBionexo();

        $query = UnidadeNegocio::select();
        
        $query->with(['metas' => function($query) use($primeiro_dia_do_mes, $filtro){
            $query->where('data', $primeiro_dia_do_mes);
            if(!empty($filtro['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($filtro){
                    $query->where('codigo_representante', $filtro['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);

        if(($id_unidade_negocio !== "outros" && $id_unidade_negocio !== "" && $id_unidade_negocio !== "nulo") && !in_array($id_unidade_negocio, [5,6,7])){
            $query->where('id', $id_unidade_negocio);
        }

        $result = $query->get();

        $query_movimentacao = Movimentacao::selectRaw('vendedor, cliente_codigo, marca, linha, grupo, unidade, documento, estabelecimento, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        if(!empty($fields['codigo_vendedor']) && $fields['codigo_vendedor'] !== "nulo"){
            $query_movimentacao->where('vendedor', $fields['codigo_vendedor']);
        }
        if($id_unidade_negocio === "nulo" || $fields['codigo_vendedor'] === "nulo"){
            $query_movimentacao->whereNull('vendedor');
        }
        $query_movimentacao->groupBy('vendedor', 'cliente_codigo', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'estabelecimento');
        $result_movimentacao = $query_movimentacao->get();

        $movimentacoes_devolucao = [];
        foreach($result_movimentacao as $movimentacao){
            if(empty($movimentacao->cliente)){
                $cliente_nome = '';
            }else{
                $cliente_nome = $movimentacao->cliente_codigo === '001'? 'BALCAO' : $movimentacao->cliente->nome." - ".$movimentacao->cliente->cpf_cnpj;
            }
            $bionexo = false;
            if(!empty($movimentacao->cliente)){
                if(in_array($movimentacao->cliente->cpf_cnpj, $clientes_bionexo)){
                    $bionexo = true;
                }
            }
            $movimentacoes_devolucao[$movimentacao->vendedor][$movimentacao->cliente_codigo][] = [
                'vendedor' => $movimentacao->vendedor,
                'cliente' => $cliente_nome,
                'marca' => $movimentacao->marca,
                'linha' => $movimentacao->linha,
                'grupo' => $movimentacao->grupo,
                'quantidade' => $movimentacao->quantidade_total,
                'preco' => $movimentacao->preco_total,
                'unidade' => $movimentacao->unidade,
                'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
                'bionexo' => $bionexo,
            ];
        }

        $query_movimentacao = Movimentacao::selectRaw('vendedor, cliente_codigo, marca, linha, grupo, unidade, documento, estabelecimento, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end ) as preco_total, sum(preco_pcmn * quantidade) as custo');
        $query_movimentacao->with(['detalhesVendedor']);
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);
        if(!empty($fields['codigo_vendedor']) && $fields['codigo_vendedor'] !== "nulo"){
            $query_movimentacao->where('vendedor', $fields['codigo_vendedor']);
        }
        if($id_unidade_negocio === "nulo" || $fields['codigo_vendedor'] === "nulo"){
            $query_movimentacao->whereNull('vendedor');
        }
        $query_movimentacao->groupBy('vendedor', 'cliente_codigo', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'estabelecimento');
        $result_movimentacao = $query_movimentacao->get();
        
        $movimentacoes = [];
        foreach($result_movimentacao as $movimentacao){
            if(empty($movimentacao->cliente)){
                $cliente_nome = '';
            }else{
                $cliente_nome = $movimentacao->cliente_codigo === '001'? 'BALCAO' : $movimentacao->cliente->nome." - ".$movimentacao->cliente->cpf_cnpj;
            }
            $bionexo = false;
            if(!empty($movimentacao->cliente)){
                if(in_array($movimentacao->cliente->cpf_cnpj, $clientes_bionexo)){
                    $bionexo = true;
                }
            }
            $movimentacoes[$movimentacao->vendedor][$movimentacao->cliente_codigo][] = [
                'vendedor' => $movimentacao->vendedor,
                'cliente' => $cliente_nome,
                'marca' => $movimentacao->marca,
                'linha' => $movimentacao->linha,
                'grupo' => $movimentacao->grupo,
                'quantidade' => $movimentacao->quantidade_total,
                'preco' => $movimentacao->preco_total,
                'custo' => $movimentacao->custo,
                'unidade' => $movimentacao->unidade,
                'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
                'bionexo' => $bionexo,
            ];
        }

        $unidades = [];

        $total_quantidade = 0;
        $total_valor = 0;
        $clientes = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";

        if($data_escolhida_inicial->gte($data_verificacao)){
            $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
            $usuarios_fashion_2 = $this->getUserFashion2($data_escolhida_inicial);
            if($data_escolhida_inicial->gte($data_verificacao_hospitalar_outfits)){
                $usuarios_outfites_confeccionados = $this->getUserOutfitsConfeccionados($data_escolhida_inicial); 
            }else{
                $usuarios_magazine = $this->getUserMagazine($data_escolhida_inicial);
            }
            foreach($result as $unidade_negocio){
                $valor = 0;
                $quantidade = 0;
                if($unidade_negocio->metas->count() > 0){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes[$codigo_vendedor] as $codigo_cliente => $cliente){
                                foreach($cliente as $key => $movimentacao){
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
        
                                    if($id_unidade_negocio === $unidade_negocio_id  || $id_unidade_negocio === "outros" || $id_unidade_negocio === "nulo"){
                                        $valor += $movimentacao['preco'];
                                        $quantidade += $movimentacao['quantidade'];

                                        if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                            $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                'rank' => 0,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao['cliente'],
                                                'quantidade' => $movimentacao['quantidade'],
                                                'custo' => $movimentacao['custo'],
                                                'valor' => $movimentacao['preco'],
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                        }else{
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                            unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                        }
                                    }
                                }
                            }
                        }
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_cliente => $cliente){
                                foreach($cliente as $key_devolucao => $movimentacao_devolucao){
                                    $unidade_negocio_id = $unidade_negocio->id;
                                    if(!empty($excecoes[$movimentacao_devolucao['documento']])){
                                        $unidade_negocio_id = $excecoes[$movimentacao['documento']];
                                    }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(in_array($movimentacao_devolucao['unidade'], $this->unidade_metro)){
                                            $unidade_negocio_id = 15;//Fashion 3
                                        }else{
                                            $unidade_negocio_id = 7;//Hospitalar
                                        }
                                    }
        
                                    if($id_unidade_negocio === $unidade_negocio_id || $id_unidade_negocio === "outros" || $id_unidade_negocio === ""  || $id_unidade_negocio === "nulo"){
                                        $valor -= $movimentacao_devolucao['preco'];
                                        $quantidade -= $movimentacao_devolucao['quantidade'];

                                        if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                            $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                'rank' => 0,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao_devolucao['cliente'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                                'custo' => 0,
                                            ];
                                        }
                                        $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                        $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] -= $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }
                
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
        }else{
            foreach($result as $unidade_negocio){
                $valor = 0;
                $quantidade = 0;
                if($unidade_negocio->metas->count() > 0){
                    if($unidade_negocio->unidade !== 'DENIM' && $unidade_negocio->unidade !== 'WORKWEAR' && $unidade_negocio->unidade !== 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key => $movimentacao){
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
                                            $quantidade += $movimentacao['quantidade'];
    
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao['cliente'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'custo' => $movimentacao['custo'],
                                                    'valor' => $movimentacao['preco'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }else{
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key_devolucao => $movimentacao_devolucao){
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
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao_devolucao['cliente'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'DENIM'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key => $movimentacao){
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
                                            $quantidade += $movimentacao['quantidade'];
        
                                            $id_denim = $unidade_negocio->id;
                                            
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao['cliente'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
            
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }else{
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key_devolucao => $movimentacao_devolucao){
                                        if(($movimentacao_devolucao['linha'] === 'DENIM IMPORTADO' || ($movimentacao_devolucao['linha'] === 'INDIGO E SARJAS' && $movimentacao_devolucao['marca'] === 'IMPORTADOS')) && ($movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL')){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao_devolucao['cliente'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $clientes[$unidade_negocio->id][$codigo_vendedor]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $clientes[$unidade_negocio->id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(substr_count($movimentacao['linha'], "HOSPITALAR") > 0 && !in_array($movimentacao['unidade'], $this->unidade_metro)){
                                            $liberado = true;
                                        }
                                        if($liberado){
                                            $valor += $movimentacao['preco'];
                                            $quantidade += $movimentacao['quantidade'];
        
                                            $id_hospitalar = $unidade_negocio->id;
            
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao['cliente'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }else{
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key_devolucao => $movimentacao_devolucao){
                                        if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") > 0){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            $id_hospitalar = $unidade_negocio->id;
            
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao_devolucao['cliente'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                             if((substr_count($movimentacao['linha'], "HOSPITALAR") === 0 || in_array($movimentacao['unidade'], $this->unidade_metro)) && ($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN")){
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
                                            $quantidade += $movimentacao['quantidade'];
    
                                            $id_workwear = $unidade_negocio->id;
        
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao['cliente'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }else{
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key_devolucao => $movimentacao_devolucao){
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
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao_devolucao['cliente'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
    
            if(!empty($id_hospitalar)){
                if($id_unidade_negocio === $id_hospitalar){
                    foreach($clientes as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_hospitalar){
                            unset($clientes[$key_unidade_negocio]);
                        }
                    }
                }
    
                foreach($usuarios_hospitalar['detalhes'] as $usuario_hospitalar){
                    if(!empty($movimentacoes[$usuario_hospitalar['codigo']])){
                        foreach($movimentacoes[$usuario_hospitalar['codigo']] as $codigo_cliente => $cliente){
                            foreach($cliente as $key => $movimentacao){
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_hospitalar){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){  
                                    if(empty($clientes[$id_hospitalar][$codigo_cliente])){
                                        $clientes[$id_hospitalar][$codigo_cliente] = [
                                            'rank' => 0,
                                            'vendedor_codigo' => $usuario_hospitalar['codigo'],
                                            'cliente' => $movimentacao['cliente'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_hospitalar['codigo']][$codigo_cliente][$key]);
                                    }else{
                                        $clientes[$id_hospitalar][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                        $clientes[$id_hospitalar][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                        $clientes[$id_hospitalar][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_hospitalar['codigo']][$codigo_cliente][$key]);
                                    }
                                }
                            }                        
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($clientes as $unidade_negocio){
                    foreach($unidade_negocio as $cliente){
                        $total_valor += $cliente['valor'];
                        $total_quantidade += $cliente['quantidade']; 
                    }
                }
            }
    
            if(!empty($id_workwear) && ($id_unidade_negocio === $id_workwear || $id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_workwear){
                    foreach($clientes as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_workwear){
                            unset($clientes[$key_unidade_negocio]);
                        }
                    }
                }
                foreach($usuarios_workwear['detalhes'] as $usuario_workwear){
                    if(!empty($movimentacoes[$usuario_workwear['codigo']])){
                        foreach($movimentacoes[$usuario_workwear['codigo']] as $codigo_cliente => $cliente){
                            foreach($cliente as $key => $movimentacao){
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_workwear){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){  
                                    if(empty($clientes[$id_workwear][$codigo_cliente])){
                                        $clientes[$id_workwear][$codigo_cliente] = [
                                            'rank' => 0,
                                            'vendedor_codigo' => $usuario_workwear['codigo'],
                                            'cliente' => $movimentacao['cliente'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_workwear['codigo']][$codigo_cliente][$key]);
                                    }else{
                                        $clientes[$id_workwear][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                        $clientes[$id_workwear][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                        $clientes[$id_workwear][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_workwear['codigo']][$codigo_cliente][$key]);
                                    }
                                }
                            }
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($clientes as $unidade_negocio){
                    foreach($unidade_negocio as $cliente){
                        $total_valor += $cliente['valor'];
                        $total_quantidade += $cliente['quantidade']; 
                    }
                }
            }
    
            if(!empty($id_denim) && ($id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_denim){
                    foreach($clientes as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_denim){
                            unset($clientes[$key_unidade_negocio]);
                        }
                    }
                }
                foreach($usuarios_denim['detalhes'] as $usuario_denim){
                    if(!empty($movimentacoes[$usuario_denim['codigo']])){
                        foreach($movimentacoes[$usuario_denim['codigo']] as $codigo_cliente => $cliente){
                            foreach( $cliente as $key => $movimentacao){   
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_denim){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){       
                                    if(empty($clientes[$id_denim][$codigo_cliente])){
                                        $clientes[$id_denim][$codigo_cliente] = [
                                            'rank' => 0,
                                            'vendedor_codigo' => $usuario_denim['codigo'],
                                            'cliente' => $movimentacao['cliente'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_denim['codigo']][$codigo_cliente][$key]);
                                    }else{
                                        $clientes[$id_denim][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                        $clientes[$id_denim][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                        $clientes[$id_denim][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_denim['codigo']][$codigo_cliente][$key]);
                                    }
                                }
                            }
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($clientes as $unidade_negocio){
                    foreach($unidade_negocio as $cliente){
                        $total_valor += $cliente['valor'];
                        $total_quantidade += $cliente['quantidade']; 
                    }
                }
            }
        }
        
        if($id_unidade_negocio === "" || $id_unidade_negocio === "nulo"){
            if(!empty($movimentacoes[""])){
                if(count($movimentacoes[""]) > 0){
                    $valor = 0;
                    $custo = 0;
                    $clientes = [];
                    foreach($movimentacoes[""] as $vendas){
                        foreach($vendas as $movimentacao){
                            if(empty($clientes['nulo'][$movimentacao['cliente']])){
                                $clientes['nulo'][$movimentacao['cliente']] = [
                                    'rank' => 0,
                                    'vendedor_codigo' => '',
                                    'cliente' => $movimentacao['cliente'],
                                    'quantidade' => $movimentacao['quantidade'],
                                    'custo' => 0,
                                    'valor' => 0,
                                ];
                            }
                            $clientes['nulo'][$movimentacao['cliente']]['custo'] += $movimentacao['custo'];
                            $clientes['nulo'][$movimentacao['cliente']]['valor'] += $movimentacao['preco'];

                            $custo += $movimentacao['custo'];
                            $valor += $movimentacao['preco'];
                        }
                    }

                    if(!empty($movimentacoes_devolucao[""])){
                        foreach($movimentacoes_devolucao[""] as $cliente){
                            foreach($cliente as $movimentacao_devolucao_dados){
                                $valor -= $movimentacao_devolucao_dados['preco'];
                                if(empty($clientes['nulo'][$movimentacao_devolucao_dados['cliente']])){
                                    $clientes['nulo'][$movimentacao_devolucao_dados['cliente']] = [
                                        'rank' => 0,
                                        'vendedor_codigo' => '',
                                        'cliente' => $movimentacao_devolucao_dados['cliente'],
                                        'quantidade' => (-1) * $movimentacao_devolucao_dados['quantidade'],
                                        'custo' => 0,
                                        'valor' => 0,
                                    ];
                                }

                                $clientes['nulo'][$movimentacao_devolucao_dados['cliente']]['valor'] -= $movimentacao_devolucao_dados['preco'];
                            }
                        }
                        unset($movimentacoes_devolucao[""]);
                    }
                    

                    $total_valor += $valor;

                    unset($movimentacoes[""]);
                }
            }
        }

        if($id_unidade_negocio === "outros" || $id_unidade_negocio === ""){
            if($id_unidade_negocio !== ""){
                $total_valor = 0;
                $total_quantidade = 0;
                $clientes = [];
            }

            if(count($movimentacoes) > 0){
                $valor = 0;
                $quantidade = 0;
                foreach($movimentacoes as $codigo_vendedor => $vendedor){
                    foreach($vendedor as $codigo_cliente => $cliente ){
                        if(!empty($codigo_vendedor)){
                            foreach($cliente as $movimentacao){
                                if(empty($clientes[$id_unidade_negocio][$codigo_cliente])){
                                    $clientes[$id_unidade_negocio][$codigo_cliente] = [
                                        'rank' => 0,
                                        'vendedor_codigo' => $movimentacao['vendedor'],
                                        'cliente' => $movimentacao['cliente'],
                                        'quantidade' => $movimentacao['quantidade'],
                                        'valor' => $movimentacao['preco'],
                                        'custo' => $movimentacao['custo'],
                                    ];
    
                                    if(!empty($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente])){
                                        foreach($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente] as $key_devolucao => $movimentacao_devolucao){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            $clientes[$id_unidade_negocio][$codigo_cliente]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $clientes[$id_unidade_negocio][$codigo_cliente]['valor'] -= $movimentacao_devolucao['preco'];
                                        }
                                    }
                                }else{
                                    $clientes[$id_unidade_negocio][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                    $clientes[$id_unidade_negocio][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                    $clientes[$id_unidade_negocio][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                }

                                $valor += $movimentacao['preco'];
                            }
                        }
                    }
                    unset($movimentacoes[$codigo_vendedor]);
                }
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
        }
  
        $coluna_ano_escolhido = $data_escolhida_inicial->format('M/y');
        
        $array_clientes = $clientes;
        $clientes = [];
        $total_custo = 0;
        foreach($array_clientes as $unidade_negocio){
            foreach($unidade_negocio as $codigo_cliente => $cliente){
                $clientes []= [
                    'valor' => $cliente['valor'],
                    'rank_codigo' => 0,
                    'vendedor_codigo' => $cliente['vendedor_codigo'],
                    'codigo_cliente' => $codigo_cliente,
                    'cliente' => $cliente['cliente'],
                    'quantidade' => $cliente['quantidade'],
                    'porcetagem' => empty($cliente['valor'])? 0 : ($cliente['valor'] / $total_valor) * 100,
                    'retabilidade_porcetagem' => $cliente['custo'] > 0 && $cliente['valor'] > 0? (($cliente['valor'] / $cliente['custo']) - 1) * 100 : '',
                    'total_porcetagem' => 0,
                ];

                $total_custo += $cliente['custo'];
            }
        }

        arsort($clientes);
        $rank = 1;
        $total_porcetagem = 0;
        foreach($clientes as $key => $cliente){
            $total_porcetagem += $clientes[$key]['porcetagem'];
            $clientes[$key]['rank_codigo'] = $rank;
            $clientes[$key]['total_porcetagem'] = $total_porcetagem;
            $rank++;
        }

        $total_porcetagem = empty($total_porcetagem)? '' : parserValor($total_porcetagem).'%';
        
        $clientes = $this->ajusteArrayParaValoresVendedor($clientes);

        $diferenca_porcetagem_quantidade_total = !empty($quantidade_total) && !empty($quantidade_total_ano_anterior)? parserValor((($quantidade_total / $quantidade_total_ano_anterior) - 1) * 100).'%' : '';
        $diferenca_porcetagem_valor_total = !empty($valor_total) && !empty($valor_total_ano_anterior)? parserValor((($valor_total / $valor_total_ano_anterior) - 1) * 100).'%' : '';

        $quantidade_total = empty($total_quantidade)? '' : parserValor($total_quantidade);
        $valor_total = empty($total_valor)? '' : parserValor($total_valor);
        $quantidade_total_ano_anterior = empty($quantidade_total_ano_anterior)? '' : parserValor($quantidade_total_ano_anterior);
        $valor_total_ano_anterior = empty($valor_total_ano_anterior)? '' : parserValor($valor_total_ano_anterior);
        $total_retabilidade = $total_custo > 0 && $total_valor > 0? parserValor((($total_valor / $total_custo) - 1) * 100).'%' : '';

        $data_escolhida_inicial = $data_escolhida_inicial->format('d/m/Y');
        $data_escolhida_final = $data_escolhida_final->format('d/m/Y');

        $tipo_usuarios = $this->getTipoVendedores();
        $unidades_negocios = $this->getUnidadeNegocio();
        $representantes = $this->getRepresentante();
        $regioes = $this->getRegiao();

        return view('programs.mapa_venda.modal.cliente')->with(['clientes' => $clientes, 'coluna_ano_escolhido' => $coluna_ano_escolhido, 'diferenca_porcetagem_quantidade_total' => $diferenca_porcetagem_quantidade_total, 'diferenca_porcetagem_valor_total' => $diferenca_porcetagem_valor_total, 'quantidade_total' => $quantidade_total, 'valor_total' => $valor_total, 'quantidade_total_ano_anterior' => $quantidade_total_ano_anterior, 'valor_total_ano_anterior' => $valor_total_ano_anterior, 'data_escolhida_inicial' => $data_escolhida_inicial, 'data_escolhida_final' => $data_escolhida_final, 'tipo_usuarios' => $tipo_usuarios, 'unidades_negocios' => $unidades_negocios, 'representantes' => $representantes, 'regioes' => $regioes, 'total_retabilidade' => $total_retabilidade, "filtro" => encrypt($filtro), "id_unidade_negocio" => encrypt($id_unidade_negocio), 'total_porcetagem' => $total_porcetagem, 'title' => $fields['title'], 'codigo_vendedor' => $fields['codigo_vendedor']]);
    }

    public function filtroCliente(Request $request){
        $fields = $request->only('filtro', 'id_unidade_negocio', 'tipo', 'equipe', 'vendedor', 'regiao', 'marca_nacional_importado', 'cliente', 'codigo_vendedor');
        $filtro = decrypt($fields['filtro']);

        if(!empty($fields['codigo_vendedor'])){
            $fields['vendedor'] = $fields['codigo_vendedor'];
        }

        if(!empty($fields['id_unidade_negocio'])){
            $id_unidade_negocio = decrypt($fields['id_unidade_negocio']);
        }else{
            $id_unidade_negocio = "";
        }
        
        $data_escolhida = '01/'.$filtro['mes_ano'];
        $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
        $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

        $feriadoControllerObj = new FeriadoController;
        $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
        $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);

        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_verificacao_hospitalar_outfits = Carbon::createFromFormat('d/m/Y', '01/09/2020')->setTime(0,0,0);

        if($filtro['tipo_data'] == 'diario'){
            $data_escolhida = $filtro['data_dia'];
            $ultimo_dia = $filtro['data_dia'];

            $primeiro_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0)->lastOfMonth();

            $feriados = $feriadoControllerObj->getFeriadosMes($primeiro_dia_do_mes->format('Y-m-d'));
        
            $divisao_meta = quantidadeDiasUteisNoPeriodo($primeiro_dia_do_mes->format('Y-m-d'), $ultimo_dia_do_mes->format('Y-m-d'),$feriados);
            
            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $filtro['data_dia'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'quinzenal'){
            if($filtro['quinzena'] == 1){
                $data_escolhida = '01/'.$filtro['mes_ano'];
                $ultimo_dia = 15;
            }else{
                $data_escolhida = '16/'.$filtro['mes_ano'];
                $ultimo_dia = '';
            }

            $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', $data_escolhida)->setTime(0,0,0);
            $data_escolhida_final = empty($ultimo_dia)? $ultimo_dia_do_mes : Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$filtro['mes_ano'])->setTime(0,0,0);

            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else if($filtro['tipo_data'] == 'semanal'){
            $data_escolhida = '01/'.$filtro['mes_ano'];

            $arraySemanas = semanaDiaInicialFinal($primeiro_dia_do_mes->format('Y-m-d'));

            $data_escolhida_inicial = Carbon::parse($arraySemanas[(int)$filtro['semanas']][0]['day_week'])->setTime(0,0,0);
            $data_escolhida_final = Carbon::createFromFormat('Y-m-d', $arraySemanas[(int)$filtro['semanas']][count($arraySemanas[(int)$filtro['semanas']])-1]['day_week'])->setTime(0,0,0);
            
            $dias_uteis = quantidadeDiasUteisNoPeriodo($data_escolhida_inicial->format('Y-m-d'), $data_escolhida_final->format('Y-m-d'),$feriados);
        }else{
            $dias_uteis = $divisao_meta;

            $data_escolhida_inicial = $primeiro_dia_do_mes;
            $data_escolhida_final = $ultimo_dia_do_mes;
        }
        $ano_anterior = intval($separado_data[2]) - 1 ;

        $excecoes = $this->excecoes($data_escolhida_inicial, $data_escolhida_final);

        $usuarios_workwear = $this->getUserWorkwear($primeiro_dia_do_mes);
        $usuarios_denim = $this->getUserDenim($primeiro_dia_do_mes);
        $usuarios_hospitalar = $this->getUserHospitalar($primeiro_dia_do_mes);
        $clientes_bionexo = $this->clientesBionexo();

        $query = UnidadeNegocio::select();
        
        $query->with(['metas' => function($query) use($primeiro_dia_do_mes, $filtro){
            $query->where('data', $primeiro_dia_do_mes);
            if(!empty($filtro['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($filtro){
                    $query->where('codigo_representante', $filtro['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);

        if(($id_unidade_negocio !== "outros" && $id_unidade_negocio !== "") && !in_array($id_unidade_negocio, [5,6,7])){
            $query->where('id', $id_unidade_negocio);
        }

        if(!empty($fields['equipe'])){
            $query->where('id', $fields['equipe']);
        }

        $result = $query->get();

        $query_movimentacao = Movimentacao::selectRaw('vendedor, cliente_codigo, marca, linha, grupo, unidade, documento, estabelecimento, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->with(['cliente' => function($query)use($fields){
            if(!empty($fields['regiao'])){
                $query->whereIn('uf', $this->getEstadoPorRegiao($fields['regiao']));
            }
        }]);
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        if(!empty($fields['tipo'])){
            $query_user = User::select();
            $query_user->where('tipo_usuario_id', $fields['tipo']);
            $result_user = $query_user->get();

            $query_movimentacao->whereIn('vendedor', $result_user->pluck('codigo_representante'));
        }
        if(!empty($fields['vendedor'])){
            $query_movimentacao->where('vendedor', $fields['vendedor']);
        }
        if(!empty($fields['marca_nacional_importado'])){
            $query_movimentacao->whereHas('produto', function($query) use($fields){
                if($fields['marca_nacional_importado'] !== 'importado'){
                    $query->whereIn('procedencia', [0,3,4,5]);
                }else{
                    $query->whereIn('procedencia', [1,2,6,7]);
                }
            });
        }
        if(!empty($fields['cliente'])){
            $cliente_busca = ClienteNasajon::select('codigo')->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['cliente']).'%\'')->get();
            $query_movimentacao->WhereIn('cliente_codigo', $cliente_busca->pluck('codigo'));
        }
        if(!empty($filtro['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro['vendedor']);
        }
        $query_movimentacao->groupBy('vendedor', 'cliente_codigo', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'estabelecimento');
        $result_movimentacao = $query_movimentacao->get();

        $movimentacoes_devolucao = [];
        foreach($result_movimentacao as $movimentacao){
            if(!empty($movimentacao->cliente)){
                if(empty($movimentacao->cliente)){
                    $cliente_nome = '';
                }else{
                    $cliente_nome = $movimentacao->cliente_codigo === '001'? 'BALCAO' : $movimentacao->cliente->nome." - ".$movimentacao->cliente->cpf_cnpj;
                }
                $bionexo = false;
                if(!empty($movimentacao->cliente)){
                    if(in_array($movimentacao->cliente->cpf_cnpj, $clientes_bionexo)){
                        $bionexo = true;
                    }
                }
                $movimentacoes_devolucao[$movimentacao->vendedor][$movimentacao->cliente_codigo][] = [
                    'vendedor' => $movimentacao->vendedor,
                    'cliente' => $cliente_nome,
                    'marca' => $movimentacao->marca,
                    'linha' => $movimentacao->linha,
                    'grupo' => $movimentacao->grupo,
                    'quantidade' => $movimentacao->quantidade_total,
                    'preco' => $movimentacao->preco_total,
                    'unidade' => $movimentacao->unidade,
                    'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
                    'bionexo' => $bionexo,
                ];
            }
        }

        $query_movimentacao = Movimentacao::selectRaw('vendedor, cliente_codigo, marca, linha, grupo, unidade, documento, estabelecimento, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end ) as preco_total, sum(preco_pcmn * quantidade) as custo');
        $query_movimentacao->with(['detalhesVendedor']);
        $query_movimentacao->with(['cliente' => function($query)use($fields){
            if(!empty($fields['regiao'])){
                $query->whereIn('uf', $this->getEstadoPorRegiao($fields['regiao']));
            }
        }]);
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);
        if(!empty($fields['tipo'])){
            $query_user = User::select();
            $query_user->where('tipo_usuario_id', $fields['tipo']);
            $result_user = $query_user->get();

            $query_movimentacao->whereIn('vendedor', $result_user->pluck('codigo_representante'));
        }
        if(!empty($fields['vendedor'])){
            $query_movimentacao->where('vendedor', $fields['vendedor']);
        }
        if(!empty($fields['marca_nacional_importado'])){
            $query_movimentacao->whereHas('produto', function($query) use($fields){
                if($fields['marca_nacional_importado'] !== 'importado'){
                    $query->whereIn('procedencia', [0,3,4,5]);
                }else{
                    $query->whereIn('procedencia', [1,2,6,7]);
                }
            });
        }
        if(!empty($fields['cliente'])){
            $cliente_busca = ClienteNasajon::select('codigo')->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['cliente']).'%\'')->get();
            $query_movimentacao->WhereIn('cliente_codigo', $cliente_busca->pluck('codigo'));
        }
        if(!empty($filtro['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro['vendedor']);
        }
        $query_movimentacao->groupBy('vendedor', 'cliente_codigo', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'estabelecimento');
        $result_movimentacao = $query_movimentacao->get();
        
        $movimentacoes = [];
        foreach($result_movimentacao as $movimentacao){
            if(!empty($movimentacao->cliente)){
                if(empty($movimentacao->cliente)){
                    $cliente_nome = '';
                }else{
                    $cliente_nome = $movimentacao->cliente_codigo === '001'? 'BALCAO' : $movimentacao->cliente->nome." - ".$movimentacao->cliente->cpf_cnpj;
                }
                $bionexo = false;
                if(!empty($movimentacao->cliente)){
                    if(in_array($movimentacao->cliente->cpf_cnpj, $clientes_bionexo)){
                        $bionexo = true;
                    }
                }
                $movimentacoes[$movimentacao->vendedor][$movimentacao->cliente_codigo][] = [
                    'vendedor' => $movimentacao->vendedor,
                    'cliente' => $cliente_nome,
                    'marca' => $movimentacao->marca,
                    'linha' => $movimentacao->linha,
                    'grupo' => $movimentacao->grupo,
                    'quantidade' => $movimentacao->quantidade_total,
                    'preco' => $movimentacao->preco_total,
                    'custo' => $movimentacao->custo,
                    'unidade' => $movimentacao->unidade,
                    'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
                    'bionexo' => $bionexo,
                ];
            }
        }

        $unidades = [];

        $total_quantidade = 0;
        $total_valor = 0;
        $clientes = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        
        if($data_escolhida_inicial->gte($data_verificacao)){
            $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
            $usuarios_fashion_2 = $this->getUserFashion2($data_escolhida_inicial);
            if($data_escolhida_inicial->gte($data_verificacao_hospitalar_outfits)){
                $usuarios_outfites_confeccionados = $this->getUserOutfitsConfeccionados($data_escolhida_inicial); 
            }else{
                $usuarios_magazine = $this->getUserMagazine($data_escolhida_inicial);
            }
            foreach($result as $unidade_negocio){
                $valor = 0;
                $quantidade = 0;
                if($unidade_negocio->metas->count() > 0){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes[$codigo_vendedor] as $codigo_cliente => $cliente){
                                foreach($cliente as $key => $movimentacao){
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
        
                                    if($id_unidade_negocio === $unidade_negocio_id  || $id_unidade_negocio === "outros"){
                                        $valor += $movimentacao['preco'];
                                        $quantidade += $movimentacao['quantidade'];

                                        if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                            $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                'rank' => 0,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao['cliente'],
                                                'quantidade' => $movimentacao['quantidade'],
                                                'custo' => $movimentacao['custo'],
                                                'valor' => $movimentacao['preco'],
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                        }else{
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                            unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                        }
                                    }
                                }
                            }
                        }
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_cliente => $cliente){
                                foreach($cliente as $key_devolucao => $movimentacao_devolucao){
                                    $unidade_negocio_id = $unidade_negocio->id;
                                    if(!empty($excecoes[$movimentacao_devolucao['documento']])){
                                        $unidade_negocio_id = $excecoes[$movimentacao['documento']];
                                    }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(in_array($movimentacao_devolucao['unidade'], $this->unidade_metro)){
                                            $unidade_negocio_id = 15;//Fashion 3
                                        }else{
                                            $unidade_negocio_id = 7;//Hospitalar
                                        }
                                    }
        
                                    if($id_unidade_negocio === $unidade_negocio_id || $id_unidade_negocio === "outros" || $id_unidade_negocio === ""){
                                        $valor -= $movimentacao_devolucao['preco'];
                                        $quantidade -= $movimentacao_devolucao['quantidade'];

                                        if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                            $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                'rank' => 0,
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'cliente' => $movimentacao_devolucao['cliente'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                                'custo' => 0,
                                            ];
                                        }
                                        $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                        $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] -= $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }
                
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
        }else{
            foreach($result as $unidade_negocio){
                $valor = 0;
                $quantidade = 0;
                if($unidade_negocio->metas->count() > 0){
                    if($unidade_negocio->unidade !== 'DENIM' && $unidade_negocio->unidade !== 'WORKWEAR' && $unidade_negocio->unidade !== 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key => $movimentacao){
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
                                            $quantidade += $movimentacao['quantidade'];
    
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao['cliente'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'custo' => $movimentacao['custo'],
                                                    'valor' => $movimentacao['preco'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }else{
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key_devolucao => $movimentacao_devolucao){
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
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao_devolucao['cliente'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'DENIM'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key => $movimentacao){
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
                                            $quantidade += $movimentacao['quantidade'];
        
                                            $id_denim = $unidade_negocio->id;
                                            
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao['cliente'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
            
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }else{
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key_devolucao => $movimentacao_devolucao){
                                        if(($movimentacao_devolucao['linha'] === 'DENIM IMPORTADO' || ($movimentacao_devolucao['linha'] === 'INDIGO E SARJAS' && $movimentacao_devolucao['marca'] === 'IMPORTADOS')) && ($movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL')){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao_devolucao['cliente'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $clientes[$unidade_negocio->id][$codigo_vendedor]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $clientes[$unidade_negocio->id][$codigo_vendedor]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(substr_count($movimentacao['linha'], "HOSPITALAR") > 0 && !in_array($movimentacao['unidade'], $this->unidade_metro)){
                                            $liberado = true;
                                        }
                                        if($liberado){
                                            $valor += $movimentacao['preco'];
                                            $quantidade += $movimentacao['quantidade'];
        
                                            $id_hospitalar = $unidade_negocio->id;
            
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao['cliente'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }else{
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key_devolucao => $movimentacao_devolucao){
                                        if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") > 0){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            $id_hospitalar = $unidade_negocio->id;
            
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao_devolucao['cliente'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                             if((substr_count($movimentacao['linha'], "HOSPITALAR") === 0 || in_array($movimentacao['unidade'], $this->unidade_metro)) && ($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN")){
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
                                            $quantidade += $movimentacao['quantidade'];
    
                                            $id_workwear = $unidade_negocio->id;
        
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao['cliente'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }else{
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                                $clientes[$unidade_negocio->id][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_cliente][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_cliente => $cliente){
                                    foreach($cliente as $key_devolucao => $movimentacao_devolucao){
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
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            if(empty($clientes[$unidade_negocio->id][$codigo_cliente])){
                                                $clientes[$unidade_negocio->id][$codigo_cliente] = [
                                                    'rank' => 0,
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'cliente' => $movimentacao_devolucao['cliente'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
    
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $clientes[$unidade_negocio->id][$codigo_cliente]['valor'] -= $movimentacao_devolucao['preco'];
    
                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
    
            if(!empty($id_hospitalar)){
                if($id_unidade_negocio === $id_hospitalar){
                    foreach($clientes as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_hospitalar){
                            unset($clientes[$key_unidade_negocio]);
                        }
                    }
                }
    
                foreach($usuarios_hospitalar['detalhes'] as $usuario_hospitalar){
                    if(!empty($movimentacoes[$usuario_hospitalar['codigo']])){
                        foreach($movimentacoes[$usuario_hospitalar['codigo']] as $codigo_cliente => $cliente){
                            foreach($cliente as $key => $movimentacao){
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_hospitalar){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){  
                                    if(empty($clientes[$id_hospitalar][$codigo_cliente])){
                                        $clientes[$id_hospitalar][$codigo_cliente] = [
                                            'rank' => 0,
                                            'vendedor_codigo' => $usuario_hospitalar['codigo'],
                                            'cliente' => $movimentacao['cliente'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_hospitalar['codigo']][$codigo_cliente][$key]);
                                    }else{
                                        $clientes[$id_hospitalar][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                        $clientes[$id_hospitalar][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                        $clientes[$id_hospitalar][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_hospitalar['codigo']][$codigo_cliente][$key]);
                                    }
                                }
                            }                        
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($clientes as $unidade_negocio){
                    foreach($unidade_negocio as $cliente){
                        $total_valor += $cliente['valor'];
                        $total_quantidade += $cliente['quantidade']; 
                    }
                }
            }
    
            if(!empty($id_workwear) && ($id_unidade_negocio === $id_workwear || $id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_workwear){
                    foreach($clientes as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_workwear){
                            unset($clientes[$key_unidade_negocio]);
                        }
                    }
                }
                foreach($usuarios_workwear['detalhes'] as $usuario_workwear){
                    if(!empty($movimentacoes[$usuario_workwear['codigo']])){
                        foreach($movimentacoes[$usuario_workwear['codigo']] as $codigo_cliente => $cliente){
                            foreach($cliente as $key => $movimentacao){
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_workwear){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){  
                                    if(empty($clientes[$id_workwear][$codigo_cliente])){
                                        $clientes[$id_workwear][$codigo_cliente] = [
                                            'rank' => 0,
                                            'vendedor_codigo' => $usuario_workwear['codigo'],
                                            'cliente' => $movimentacao['cliente'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_workwear['codigo']][$codigo_cliente][$key]);
                                    }else{
                                        $clientes[$id_workwear][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                        $clientes[$id_workwear][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                        $clientes[$id_workwear][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_workwear['codigo']][$codigo_cliente][$key]);
                                    }
                                }
                            }
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($clientes as $unidade_negocio){
                    foreach($unidade_negocio as $cliente){
                        $total_valor += $cliente['valor'];
                        $total_quantidade += $cliente['quantidade']; 
                    }
                }
            }
    
            if(!empty($id_denim) && ($id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_denim){
                    foreach($clientes as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_denim){
                            unset($clientes[$key_unidade_negocio]);
                        }
                    }
                }
                foreach($usuarios_denim['detalhes'] as $usuario_denim){
                    if(!empty($movimentacoes[$usuario_denim['codigo']])){
                        foreach($movimentacoes[$usuario_denim['codigo']] as $codigo_cliente => $cliente){
                            foreach( $cliente as $key => $movimentacao){   
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_denim){
                                        $liberado = false;
                                    }
                                }
    
                                if($liberado){       
                                    if(empty($clientes[$id_denim][$codigo_cliente])){
                                        $clientes[$id_denim][$codigo_cliente] = [
                                            'rank' => 0,
                                            'vendedor_codigo' => $usuario_denim['codigo'],
                                            'cliente' => $movimentacao['cliente'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_denim['codigo']][$codigo_cliente][$key]);
                                    }else{
                                        $clientes[$id_denim][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                        $clientes[$id_denim][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                        $clientes[$id_denim][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_denim['codigo']][$codigo_cliente][$key]);
                                    }
                                }
                            }
                        }
                    }
                }
    
                $total_quantidade = 0;
                $total_valor = 0;
                foreach($clientes as $unidade_negocio){
                    foreach($unidade_negocio as $cliente){
                        $total_valor += $cliente['valor'];
                        $total_quantidade += $cliente['quantidade']; 
                    }
                }
            }
        }

        if($id_unidade_negocio === ""){
            if(!empty($movimentacoes[""])){
                if(count($movimentacoes[""]) > 0){
                    $valor = 0;
                    foreach($movimentacoes[""] as $movimentacao){
                        $valor += $movimentacao['preco'];
                    }

                    if(!empty($movimentacoes_devolucao[""])){
                        foreach($movimentacoes_devolucao[""] as $movimentacao_devolucao){
                            $valor -= $movimentacao_devolucao['preco'];
                        }
                        unset($movimentacoes_devolucao[""]);
                    }
                    $vendedores = [];
                    $vendedores[""][$unidade_negocio->id][$codigo_vendedor] = [
                        'vendedor_codigo' => "",
                        'vendedor' => "",
                        'meta' => "",
                        'meta_codigo' => "",
                        'valor' => $valor,
                        'valor_codigo' => $valor,
                        'atingimento_metal_porcetagem' => 0,
                        'diferenca_meta' => 0,
                    ];

                    $total_valor += $valor;

                    unset($movimentacoes[""]);
                }
            }
        }

        if($id_unidade_negocio === "outros" || $id_unidade_negocio === ""){
            $clientes = [];
            if($id_unidade_negocio !== ""){
                $total_valor = 0;
                $total_quantidade = 0;
            }
            if(count($movimentacoes) > 0){
                $valor = 0;
                $quantidade = 0;
                foreach($movimentacoes as $codigo_vendedor => $vendedor){
                    foreach($vendedor as $codigo_cliente => $cliente ){
                        if(!empty($codigo_vendedor)){
                            foreach($cliente as $movimentacao){
                                if(empty($clientes[$id_unidade_negocio][$codigo_cliente])){
                                    $clientes[$id_unidade_negocio][$codigo_cliente] = [
                                        'rank' => 0,
                                        'vendedor_codigo' => $movimentacao['vendedor'],
                                        'cliente' => $movimentacao['cliente'],
                                        'quantidade' => $movimentacao['quantidade'],
                                        'valor' => $movimentacao['preco'],
                                        'custo' => $movimentacao['custo'],
                                    ];
    
                                    if(!empty($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente])){
                                        foreach($movimentacoes_devolucao[$codigo_vendedor][$codigo_cliente] as $key_devolucao => $movimentacao_devolucao){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            $clientes[$id_unidade_negocio][$codigo_cliente]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $clientes[$id_unidade_negocio][$codigo_cliente]['valor'] -= $movimentacao_devolucao['preco'];
                                        }
                                    }
                                }else{
                                    $clientes[$id_unidade_negocio][$codigo_cliente]['valor'] += $movimentacao['preco'];
                                    $clientes[$id_unidade_negocio][$codigo_cliente]['quantidade'] += $movimentacao['quantidade'];
                                    $clientes[$id_unidade_negocio][$codigo_cliente]['custo'] += $movimentacao['custo'];
                                }

                                $valor += $movimentacao['preco'];
                            }
                        }
                    }
                    unset($movimentacoes[$codigo_vendedor]);
                }
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
        }

        $coluna_ano_escolhido = $data_escolhida_inicial->format('M/y');

        $array_clientes = $clientes;
        $clientes = [];
        $total_custo = 0;
        foreach($array_clientes as $unidade_negocio){
            foreach($unidade_negocio as $codigo_cliente => $cliente){
                $clientes []= [
                    'valor' => $cliente['valor'],
                    'rank_codigo' => 0,
                    'vendedor_codigo' => $cliente['vendedor_codigo'],
                    'codigo_cliente' => $codigo_cliente,
                    'cliente' => $cliente['cliente'],
                    'quantidade' => $cliente['quantidade'],
                    'porcetagem' => empty($cliente['valor'])? 0 : ($cliente['valor'] / $total_valor) * 100,
                    'retabilidade_porcetagem' => $cliente['custo'] > 0 && $cliente['valor'] > 0? (($cliente['valor'] / $cliente['custo']) - 1) * 100 : '',
                    'total_porcetagem' => 0,
                ];

                $total_custo += $cliente['custo'];
            }
        }

        arsort($clientes);
        $rank = 1;
        $total_porcetagem = 0;
        foreach($clientes as $key => $cliente){
            $total_porcetagem += $clientes[$key]['porcetagem'];
            $clientes[$key]['rank_codigo'] = $rank;
            $clientes[$key]['total_porcetagem'] = $total_porcetagem;
            $rank++;
        }

        $total_porcetagem = empty($total_porcetagem)? '' : parserValor($total_porcetagem).'%';
        
        $clientes = $this->ajusteArrayParaValores($clientes);

        $diferenca_porcetagem_quantidade_total = !empty($quantidade_total) && !empty($quantidade_total_ano_anterior)? parserValor((($quantidade_total / $quantidade_total_ano_anterior) - 1) * 100).'%' : '';
        $diferenca_porcetagem_valor_total = !empty($valor_total) && !empty($valor_total_ano_anterior)? parserValor((($valor_total / $valor_total_ano_anterior) - 1) * 100).'%' : '';

        $quantidade_total = empty($total_quantidade)? '' : parserValor($total_quantidade);
        $valor_total = empty($total_valor)? '' : parserValor($total_valor);
        $quantidade_total_ano_anterior = empty($quantidade_total_ano_anterior)? '' : parserValor($quantidade_total_ano_anterior);
        $valor_total_ano_anterior = empty($valor_total_ano_anterior)? '' : parserValor($valor_total_ano_anterior);
        $total_retabilidade = $total_custo > 0 && $total_valor > 0? parserValor((($total_valor / $total_custo) - 1) * 100).'%' : '';

        $data_escolhida_inicial = $data_escolhida_inicial->format('d/m/Y');
        $data_escolhida_final = $data_escolhida_final->format('d/m/Y');

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'clientes' => $clientes,
                'quantidade_total' => $quantidade_total,
                'valor_total' => $valor_total,
                'quantidade_total_ano_anterior' => $quantidade_total_ano_anterior,
                'total_porcetagem' => $total_porcetagem,
                'total_retabilidade' => $total_retabilidade,
                'filtro_cliente' => encrypt($fields)
            ],
        ]);
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
                                //$array[$key] = empty($value)? '': parserValor($value);
                                if($key == 'diferenca_meta'){
                                    $array[$key] = empty($value)? '': parserValor($value);
                                }else{
                                    $array[$key] = $value > 0 ?  parserValor($value) : '';
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

    private function ajusteArrayParaValoresVendedor($array){
        if(is_array($array)){
            foreach($array as $key => $value){
                if(is_array($value)){
                    $array[$key] = $this->ajusteArrayParaValoresVendedor($value);
                }else{
                    if(is_numeric($value)){
                        if(substr_count($key, "codigo") === 0){
                            if(substr_count($key, "porcetagem") === 0){
                                //$array[$key] = empty($value)? '': parserValor($value);
                                if($key == 'diferenca_meta'){
                                    $array[$key] = empty($value)? '': parserValor($value);
                                }else{
                                    $array[$key] = !empty($value)?  parserValor($value) : '';
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

    public function clientesExcluido(){
        $clientes_exluir = ClienteNasajon::select('codigo')
            ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
            ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
            ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '97544848%'")
            ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        return $clientes_exluir;
    }

    public function clientesBionexo(){
        $clientes_bionexo = ClienteBionexo::select()->get()->pluck('cpf_cnpj')->toArray();

        return $clientes_bionexo;
    }

    private function getTipoVendedores(){
        $tiposObj = TipoUsuario::select('id', 'nome');
		$tiposObj->where('nome', 'Representante');
		$tiposObj->orWhere('nome', 'Vendedor Interno');
		$tiposObj->orWhere('nome', 'Agente de Venda');
		$result = $tiposObj->orderBy('nome')->get(); 
        $tipos = [
            '' => 'Todos tipos'
        ];
		
        foreach($result as $value){
            $tipos[$value->id] = $value["nome"];
        }
        
        return $tipos;
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
        $representantes_busca = User::select('codigo_representante', 'name')->where('codigo_representante', '!=', '')->orderBy('codigo_representante')->get()->toArray();
		$representantes = [];
		
		foreach ($representantes_busca as $key => $value) {
			$representantes[$value['codigo_representante']] = $value['codigo_representante']." - ".strtoupper($value['name']);
        }
        
        return $representantes;
    }

    private function getRegiao(){
        $regioes['N'] = "Norte";
        $regioes['NE'] = "Nordeste";
        $regioes['CO'] = "Centro-Oeste";
        $regioes['SE'] = "Sudeste";
        $regioes['S'] = "Sul";

        return $regioes;
    }

    public function getUserFashion3($data_escolhida_inicial){
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

    public function getUserMagazine($data_escolhida_inicial){
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

    public function getUserWorkwear($data_escolhida_inicial){
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

    public function getUserDenim($data_escolhida_inicial){
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

    public function getUserHospitalar($data_escolhida_inicial){
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

    private function getEstadoPorRegiao($regiao){
        $estados = [];

        $query = CepEstado::select();
        $query->where('regiao', $regiao);
        $result = $query->get();

        foreach($result as $estado){
            $estados [] = $estado->uf;
        }

        return $estados;
    }

    public function modalProduto(Request $request){
        $fields = $request->only('filtro', 'id_unidade_negocio', 'grupo', 'codigo_cliente', 'codigo_vendedor', 'filtro_cliente', 'linha', 'marca');
        
        if(isset($fields['filtro_cliente'])){
            $filtro_clientes = decrypt($fields['filtro_cliente']);

            $filtro_cliente = [
                "tipo" => $filtro_clientes['tipo'],
                "vendedor" => isset($fields['codigo_vendedor'])? $fields['codigo_vendedor'] : $filtro_clientes['vendedor'],
                "regiao" => $filtro_clientes['regiao'],
                "marca_nacional_importado" => $filtro_clientes['marca_nacional_importado'],
            ];
        }else{
            $filtro_cliente = [
                "tipo" => "",
                "vendedor" => isset($fields['codigo_vendedor'])? $fields['codigo_vendedor'] : "",
                "regiao" => "",
                "marca_nacional_importado" => "",
            ];
        }

        if(!isset($fields['codigo_vendedor'])){
            $fields['codigo_vendedor'] = "";
        }

        $filtro = decrypt($fields['filtro']);
        if(!empty($fields['id_unidade_negocio'])){
            $id_unidade_negocio = decrypt($fields['id_unidade_negocio']);
        }else{
            $id_unidade_negocio = "";
        }
        
        $data_escolhida = '01/'.$filtro['mes_ano'];
        $separado_data = explode('/', $data_escolhida);
        $ultimo_dia = Carbon::createFromFormat('d/m/Y', '01/'.$filtro['mes_ano'])->setTime(0,0,0)->lastOfMonth()->format('d');

        $data_escolhida_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$filtro['mes_ano'])->setTime(0,0,0);
        $data_verificacao = Carbon::createFromFormat('d/m/Y', '01/06/2020')->setTime(0,0,0);
        $data_escolhida_final = Carbon::createFromFormat('d/m/Y', $ultimo_dia.'/'.$filtro['mes_ano'])->setTime(0,0,0);

        $ano_anterior = intval($separado_data[2]) - 1 ;

        $excecoes = $this->excecoes($data_escolhida_inicial, $data_escolhida_final);

        $usuarios_workwear = $this->getUserWorkwear($data_escolhida_inicial);
        $usuarios_denim = $this->getUserDenim($data_escolhida_inicial);
        $usuarios_hospitalar = $this->getUserHospitalar($data_escolhida_inicial);

        $query = UnidadeNegocio::select();
        
        $query->with(['metas' => function($query) use($data_escolhida_inicial, $filtro){
            $query->where('data', $data_escolhida_inicial);
            if(!empty($filtro['vendedor'])){
                $query->whereHas('usuarios.detalhesUsuario', function ($query) use($filtro){
                    $query->where('codigo_representante', $filtro['vendedor']);
                });
            }else{  
                $query->with(['usuarios.detalhesUsuario']);
            }
        }]);

        if(($id_unidade_negocio !== "outros" && $id_unidade_negocio !== "") && !in_array($id_unidade_negocio, [5,6,7])){
            $query->where('id', $id_unidade_negocio);
        }

        $result = $query->get();

        $query_movimentacao = Movimentacao::selectRaw('vendedor, produto_codigo, descricao, marca, linha, grupo, unidade, cliente_codigo, documento, estabelecimento, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto) as preco_total');
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        $query_movimentacao->where('grupo', 'ilike', $fields['grupo']);
        $query_movimentacao->where('linha', 'ilike', $fields['linha']);
        $query_movimentacao->where('marca', 'ilike', $fields['marca']);
        if(!empty($fields['codigo_cliente'])){
            $query_movimentacao->where('cliente_codigo', 'ilike', $fields['codigo_cliente']);
        }
        if(!empty($fields['codigo_vendedor'])){
            $query_movimentacao->where('vendedor', $fields['codigo_vendedor']);
        }        
        if(!empty($filtro['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro['vendedor']);
        }
        $query_movimentacao->groupBy('vendedor', 'produto_codigo', 'descricao', 'marca', 'linha', 'grupo', 'unidade', 'cliente_codigo', 'documento', 'estabelecimento');
        $result_movimentacao = $query_movimentacao->get();

        $movimentacoes_devolucao = [];
        foreach($result_movimentacao as $movimentacao){
            $movimentacoes_devolucao[$movimentacao->vendedor][$movimentacao->produto_codigo][] = [
                'vendedor' => $movimentacao->vendedor,
                'codigo' => $movimentacao->produto_codigo,
                'descricao' => $movimentacao->descricao,
                'marca' => $movimentacao->marca,
                'linha' => $movimentacao->linha,
                'grupo' => $movimentacao->grupo,
                'quantidade' => $movimentacao->quantidade_total,
                'preco' => $movimentacao->preco_total,
                'unidade' => $movimentacao->unidade,
                'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
            ];
        }

        $query_movimentacao = Movimentacao::selectRaw('vendedor, produto_codigo, descricao, marca, linha, grupo, cliente_codigo, unidade, documento, estabelecimento, sum(quantidade) as quantidade_total, sum((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end ) as preco_total, sum(preco_pcmn * quantidade) as custo');
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$data_escolhida_inicial, $data_escolhida_final]);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);
        $query_movimentacao->where('grupo', 'ilike', $fields['grupo']);
        $query_movimentacao->where('linha', 'ilike', $fields['linha']);
        $query_movimentacao->where('marca', 'ilike', $fields['marca']);
        if(!empty($fields['codigo_cliente'])){
            $query_movimentacao->where('cliente_codigo', 'ilike', $fields['codigo_cliente']);
        }
        if(!empty($fields['codigo_vendedor'])){
            $query_movimentacao->where('vendedor', $fields['codigo_vendedor']);
        }       
        if(!empty($filtro['vendedor'])){
            $query_movimentacao->where('vendedor', $filtro['vendedor']);
        } 
        $query_movimentacao->groupBy('vendedor', 'produto_codigo', 'descricao', 'marca', 'linha', 'grupo', 'unidade', 'documento', 'estabelecimento', 'cliente_codigo');
        $result_movimentacao = $query_movimentacao->get();

        $movimentacoes = [];
        foreach($result_movimentacao as $movimentacao){
            $movimentacoes[$movimentacao->vendedor][$movimentacao->produto_codigo][] = [
                'vendedor' => $movimentacao->vendedor,
                'codigo' => $movimentacao->produto_codigo,
                'descricao' => $movimentacao->descricao,
                'marca' => $movimentacao->marca,
                'linha' => $movimentacao->linha,
                'grupo' => $movimentacao->grupo,
                'quantidade' => $movimentacao->quantidade_total,
                'preco' => $movimentacao->preco_total,
                'custo' => $movimentacao->custo,
                'unidade' => $movimentacao->unidade,
                'documento' => $movimentacao->documento.$movimentacao->estabelecimento,
            ];
        }

        $unidades = [];

        $total_quantidade = 0;
        $total_valor = 0;
        $produtos = [];
        $id_hospitalar = "";
        $id_denim = "";
        $id_workwear = "";
        
        if($data_escolhida_inicial->gte($data_verificacao)){
            $usuarios_fashion_3 = $this->getUserFashion3($data_escolhida_inicial);
            foreach($result as $unidade_negocio){
                $valor = 0;
                $quantidade = 0;
                if($unidade_negocio->metas->count() > 0){
                    foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                        $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                        if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key => $movimentacao){
                                    $unidade_negocio_id = $unidade_negocio->id;
                                    if(!empty($excecoes[$movimentacao['documento']])){
                                        $unidade_negocio_id = $excecoes[$movimentacao['documento']];
                                    }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(in_array($movimentacao['unidade'], $this->unidade_metro)){
                                            $unidade_negocio_id = 15;//Fashion 3
                                        }else{
                                            $unidade_negocio_id = 7;//Hospitalar
                                        }
                                    }
        
                                    if($id_unidade_negocio === $unidade_negocio_id){
                                        $valor += $movimentacao['preco'];
                                        $quantidade += $movimentacao['quantidade'];

                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'codigo' => $movimentacao['codigo'],
                                                'descricao' => $movimentacao['descricao'],
                                                'linha' => $movimentacao['linha'],
                                                'grupo' => $movimentacao['grupo'],
                                                'marca' => $movimentacao['marca'],
                                                'quantidade' => $movimentacao['quantidade'],
                                                'valor' => $movimentacao['preco'],
                                                'custo' => $movimentacao['custo'],
                                            ];
                                            unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                        }else{
                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                            unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                        }
                                    }
                                }
                            }
                        }
                        if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                            foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                    $unidade_negocio_id = $unidade_negocio->id;
                                    if(!empty($excecoes[$movimentacao_devolucao['documento']])){
                                        $unidade_negocio_id = $excecoes[$movimentacao_devolucao['documento']];
                                    }else if(in_array($codigo_vendedor, $usuarios_fashion_3['codigos']) && in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                        if(in_array($movimentacao_devolucao['unidade'], $this->unidade_metro)){
                                            $unidade_negocio_id = 15;//Fashion 3
                                        }else{
                                            $unidade_negocio_id = 7;//Hospitalar
                                        }
                                    }
        
                                    if($id_unidade_negocio === $unidade_negocio_id){
                                        $valor -= $movimentacao_devolucao['preco'];
                                        $quantidade -= $movimentacao_devolucao['quantidade'];

                                        if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                            $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                'vendedor_codigo' => $codigo_vendedor,
                                                'codigo' => $movimentacao_devolucao['codigo'],
                                                'descricao' => $movimentacao_devolucao['descricao'],
                                                'linha' => $movimentacao_devolucao['linha'],
                                                'grupo' => $movimentacao_devolucao['grupo'],
                                                'marca' => $movimentacao_devolucao['marca'],
                                                'quantidade' => 0,
                                                'valor' => 0,
                                                'custo' => 0,
                                            ];
                                        }
                                        $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                        $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];

                                        unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                    }
                                }
                            }
                        }
                    }
                }
                
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
        }else{
            foreach($result as $unidade_negocio){
                $valor = 0;
                $quantidade = 0;
                if($unidade_negocio->metas->count() > 0){
                    if($unidade_negocio->unidade !== 'DENIM' && $unidade_negocio->unidade !== 'WORKWEAR' && $unidade_negocio->unidade !== 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
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
                                            $quantidade += $movimentacao['quantidade'];

                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'codigo' => $movimentacao['codigo'],
                                                    'descricao' => $movimentacao['descricao'],
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
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
                                            $quantidade -= $movimentacao_devolucao['quantidade'];

                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'codigo' => $movimentacao_devolucao['codigo'],
                                                    'descricao' => $movimentacao_devolucao['descricao'],
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }
                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];

                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'DENIM'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
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
                                            $quantidade += $movimentacao['quantidade'];
        
                                            $id_denim = $unidade_negocio->id;
                                            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'codigo' => $movimentacao['codigo'],
                                                    'descricao' => $movimentacao['descricao'],
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
            
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                        if(($movimentacao_devolucao['linha'] === 'DENIM IMPORTADO' || ($movimentacao_devolucao['linha'] === 'INDIGO E SARJAS' && $movimentacao_devolucao['marca'] === 'IMPORTADOS')) && ($movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL PLUS' && $movimentacao_devolucao['grupo'] !== 'DENIM SANIBEL')){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];

                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'codigo' => $movimentacao_devolucao['codigo'],
                                                    'descricao' => $movimentacao_devolucao['descricao'],
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }

                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];

                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else if($unidade_negocio->unidade === 'HOSPITALAR'){
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(substr_count($movimentacao['linha'], "HOSPITALAR") > 0 && !in_array($movimentacao['unidade'], $this->unidade_metro)){
                                            $liberado = true;
                                        }
                                        if($liberado){
                                            $valor += $movimentacao['preco'];
                                            $quantidade += $movimentacao['quantidade'];
        
                                            $id_hospitalar = $unidade_negocio->id;
            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'codigo' => $movimentacao['codigo'],
                                                    'descricao' => $movimentacao['descricao'],
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
                                        if(substr_count($movimentacao_devolucao['linha'], "HOSPITALAR") > 0){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];

                                            $id_hospitalar = $unidade_negocio->id;
            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'codigo' => $movimentacao_devolucao['codigo'],
                                                    'descricao' => $movimentacao_devolucao['descricao'],
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }

                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];

                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        foreach($unidade_negocio->metas[0]->usuarios as $usuario){
                            $codigo_vendedor = $usuario->detalhesUsuario->codigo_representante;
                            if(!empty($movimentacoes[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key => $movimentacao){
                                        $liberado = false;
                                        if(!empty($excecoes[$movimentacao['documento']])){
                                            if($excecoes[$movimentacao['documento']] === $unidade_negocio->id){
                                                $liberado = true;
                                            }
                                        }else if(in_array($codigo_vendedor, $usuarios_hospitalar['codigos'])){
                                            if((substr_count($movimentacao['linha'], "HOSPITALAR") === 0 || in_array($movimentacao['unidade'], $this->unidade_metro)) && ($movimentacao['marca'] !== 'IMPORTADOS' && $movimentacao['marca'] !== "TEXTIL MN")){
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
                                            $quantidade += $movimentacao['quantidade'];

                                            $id_workwear = $unidade_negocio->id;
        
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'codigo' => $movimentacao['codigo'],
                                                    'descricao' => $movimentacao['descricao'],
                                                    'linha' => $movimentacao['linha'],
                                                    'grupo' => $movimentacao['grupo'],
                                                    'marca' => $movimentacao['marca'],
                                                    'quantidade' => $movimentacao['quantidade'],
                                                    'valor' => $movimentacao['preco'],
                                                    'custo' => $movimentacao['custo'],
                                                ];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }else{
                                                $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['valor'] += $movimentacao['preco'];
                                                $produtos[$unidade_negocio->id][$codigo_produto]['custo'] += $movimentacao['custo'];
                                                unset($movimentacoes[$codigo_vendedor][$codigo_produto][$key]);
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($movimentacoes_devolucao[$codigo_vendedor]) && !empty($codigo_vendedor)){
                                foreach($movimentacoes_devolucao[$codigo_vendedor] as $codigo_produto => $produto){
                                    foreach($produto as $key_devolucao => $movimentacao_devolucao){
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
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
                                            
                                            if(empty($produtos[$unidade_negocio->id][$codigo_produto])){
                                                $produtos[$unidade_negocio->id][$codigo_produto] = [
                                                    'vendedor_codigo' => $codigo_vendedor,
                                                    'codigo' => $movimentacao_devolucao['codigo'],
                                                    'descricao' => $movimentacao_devolucao['descricao'],
                                                    'linha' => $movimentacao_devolucao['linha'],
                                                    'grupo' => $movimentacao_devolucao['grupo'],
                                                    'marca' => $movimentacao_devolucao['marca'],
                                                    'quantidade' => 0,
                                                    'valor' => 0,
                                                    'custo' => 0,
                                                ];
                                            }

                                            $produtos[$unidade_negocio->id][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$unidade_negocio->id][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];

                                            unset($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto][$key_devolucao]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }

            if(!empty($id_hospitalar)){
                if($id_unidade_negocio === $id_hospitalar){
                    foreach($produtos as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_hospitalar){
                            unset($produtos[$key_unidade_negocio]);
                        }
                    }
                }

                foreach($usuarios_hospitalar['detalhes'] as $usuario_hospitalar){
                    if(!empty($movimentacoes[$usuario_hospitalar['codigo']])){
                        foreach($movimentacoes[$usuario_hospitalar['codigo']] as $codigo_produto => $produto){
                            foreach($produto as $key => $movimentacao){
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_hospitalar){
                                        $liberado = false;
                                    }
                                }

                                if($liberado){  
                                    if(empty($produtos[$id_hospitalar][$codigo_produto])){
                                        $produtos[$id_hospitalar][$codigo_produto] = [
                                            'vendedor_codigo' => $usuario_hospitalar['codigo'],
                                            'codigo' => $movimentacao['codigo'],
                                            'descricao' => $movimentacao['descricao'],
                                            'linha' => $movimentacao['linha'],
                                            'grupo' => $movimentacao['grupo'],
                                            'marca' => $movimentacao['marca'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_hospitalar['codigo']][$codigo_produto][$key]);
                                    }else{
                                        $produtos[$id_hospitalar][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                        $produtos[$id_hospitalar][$codigo_produto]['valor'] += $movimentacao['preco'];
                                        $produtos[$id_hospitalar][$codigo_produto]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_hospitalar['codigo']][$codigo_produto][$key]);
                                    }
                                }
                            }                        
                        }
                    }
                }

                $total_quantidade = 0;
                $total_valor = 0;
                foreach($produtos as $unidade_negocio){
                    foreach($unidade_negocio as $produto){
                        $total_valor += $produto['valor'];
                        $total_quantidade += $produto['quantidade']; 
                    }
                }
            }

            if(!empty($id_workwear) && ($id_unidade_negocio === $id_workwear || $id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_workwear){
                    foreach($produtos as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_workwear){
                            unset($produtos[$key_unidade_negocio]);
                        }
                    }
                }
                foreach($usuarios_workwear['detalhes'] as $usuario_workwear){
                    if(!empty($movimentacoes[$usuario_workwear['codigo']])){
                        foreach($movimentacoes[$usuario_workwear['codigo']] as $codigo_produto => $produto){
                            foreach($produto as $key => $movimentacao){
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_workwear){
                                        $liberado = false;
                                    }
                                }

                                if($liberado){  
                                    if(empty($produtos[$id_workwear][$codigo_produto])){
                                        $produtos[$id_workwear][$codigo_produto] = [
                                            'vendedor_codigo' => $usuario_workwear['codigo'],
                                            'codigo' => $movimentacao['codigo'],
                                            'descricao' => $movimentacao['descricao'],
                                            'linha' => $movimentacao['linha'],
                                            'grupo' => $movimentacao['grupo'],
                                            'marca' => $movimentacao['marca'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_workwear['codigo']][$codigo_produto][$key]);
                                    }else{
                                        $produtos[$id_workwear][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                        $produtos[$id_workwear][$codigo_produto]['valor'] += $movimentacao['preco'];
                                        $produtos[$id_workwear][$codigo_produto]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_workwear['codigo']][$codigo_produto][$key]);
                                    }
                                }
                            }
                        }
                    }
                }

                $total_quantidade = 0;
                $total_valor = 0;
                foreach($produtos as $unidade_negocio){
                    foreach($unidade_negocio as $produto){
                        $total_valor += $produto['valor'];
                        $total_quantidade += $produto['quantidade']; 
                    }
                }
            }

            if(!empty($id_denim) && ($id_unidade_negocio === $id_denim || $id_unidade_negocio === "" || $id_unidade_negocio === "outros")){
                if($id_unidade_negocio === $id_denim){
                    foreach($produtos as $key_unidade_negocio => $unidade_negocio){
                        if($key_unidade_negocio !== $id_denim){
                            unset($produtos[$key_unidade_negocio]);
                        }
                    }
                }
                foreach($usuarios_denim['detalhes'] as $usuario_denim){
                    if(!empty($movimentacoes[$usuario_denim['codigo']])){
                        foreach($movimentacoes[$usuario_denim['codigo']] as $codigo_produto => $produto){
                            foreach( $produto as $key => $movimentacao){      
                                $liberado = true;
                                if(!empty($excecoes[$movimentacao['documento']])){
                                    if($excecoes[$movimentacao['documento']] !== $id_denim){
                                        $liberado = false;
                                    }
                                }

                                if($liberado){    
                                    if(empty($produtos[$id_denim][$codigo_produto])){
                                        $produtos[$id_denim][$codigo_produto] = [
                                            'vendedor_codigo' => $usuario_denim['codigo'],
                                            'codigo' => $movimentacao['codigo'],
                                            'descricao' => $movimentacao['descricao'],
                                            'linha' => $movimentacao['linha'],
                                            'grupo' => $movimentacao['grupo'],
                                            'marca' => $movimentacao['marca'],
                                            'quantidade' => $movimentacao['quantidade'],
                                            'valor' => $movimentacao['preco'],
                                            'custo' => $movimentacao['custo'],
                                        ];
                                        unset($movimentacoes[$usuario_denim['codigo']][$codigo_produto][$key]);
                                    }else{
                                        $clientes[$id_denim][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                        $clientes[$id_denim][$codigo_produto]['valor'] += $movimentacao['preco'];
                                        $clientes[$id_denim][$codigo_produto]['custo'] += $movimentacao['custo'];
                                        unset($movimentacoes[$usuario_denim['codigo']][$codigo_produto][$key]);
                                    }
                                }
                            }
                        }
                    }
                }

                $total_quantidade = 0;
                $total_valor = 0;
                foreach($produtos as $unidade_negocio){
                    foreach($unidade_negocio as $produto){
                        $total_valor += $produto['valor'];
                        $total_quantidade += $produto['quantidade']; 
                    }
                }
            }
        }

        if($id_unidade_negocio === ""){
            if(!empty($movimentacoes[""])){
                if(count($movimentacoes[""]) > 0){
                    $valor = 0;
                    foreach($movimentacoes[""] as $movimentacao){
                        $valor += $movimentacao['preco'];
                    }

                    if(!empty($movimentacoes_devolucao[""])){
                        foreach($movimentacoes_devolucao[""] as $movimentacao_devolucao){
                            $valor -= $movimentacao_devolucao['preco'];
                        }
                        unset($movimentacoes_devolucao[""]);
                    }
                    $vendedores = [];
                    $vendedores[""][$unidade_negocio->id][$codigo_vendedor] = [
                        'vendedor_codigo' => "",
                        'vendedor' => "",
                        'meta' => "",
                        'meta_codigo' => "",
                        'valor' => $valor,
                        'valor_codigo' => $valor,
                        'atingimento_metal_porcetagem' => 0,
                        'diferenca_meta' => 0,
                    ];

                    $total_valor += $valor;

                    unset($movimentacoes[""]);
                }
            }
        }


        if($id_unidade_negocio === "outros" || $id_unidade_negocio === ""){
            $clientes = [];
            if($id_unidade_negocio !== ""){
                $total_valor = 0;
                $total_quantidade = 0;
            }
            if(count($movimentacoes) > 0){
                $valor = 0;
                $quantidade = 0;
                foreach($movimentacoes as $codigo_vendedor => $vendedor){
                    foreach($vendedor as $codigo_produto => $produto ){
                        if(!empty($codigo_vendedor)){
                            foreach($produto as $movimentacao){
                                if(empty($produtos[$id_unidade_negocio][$codigo_produto])){
                                    $produtos[$id_unidade_negocio][$codigo_produto] = [
                                        'vendedor_codigo' => $movimentacao['vendedor'],
                                        'codigo' => $movimentacao['codigo'],
                                        'descricao' => $movimentacao['descricao'],
                                        'linha' => $movimentacao['linha'],
                                        'grupo' => $movimentacao['grupo'],
                                        'marca' => $movimentacao['marca'],
                                        'quantidade' => $movimentacao['quantidade'],
                                        'valor' => $movimentacao['preco'],
                                        'custo' => $movimentacao['custo'],
                                    ];
    
                                    if(!empty($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto])){
                                        foreach($movimentacoes_devolucao[$codigo_vendedor][$codigo_produto] as $key_devolucao => $movimentacao_devolucao){
                                            $valor -= $movimentacao_devolucao['preco'];
                                            $quantidade -= $movimentacao_devolucao['quantidade'];
    
                                            $produtos[$id_unidade_negocio][$codigo_produto]['quantidade'] -= $movimentacao_devolucao['quantidade'];
                                            $produtos[$id_unidade_negocio][$codigo_produto]['valor'] -= $movimentacao_devolucao['preco'];
                                        }
                                    }
                                }else{
                                    $produtos[$id_unidade_negocio][$codigo_produto]['valor'] += $movimentacao['preco'];
                                    $produtos[$id_unidade_negocio][$codigo_produto]['quantidade'] += $movimentacao['quantidade'];
                                    $produtos[$id_unidade_negocio][$codigo_produto]['custo'] += $movimentacao['custo'];
                                }

                                $valor += $movimentacao['preco'];
                            }
                        }
                    }
                    unset($movimentacoes[$codigo_vendedor]);
                }
                $total_quantidade += $quantidade;
                $total_valor += $valor;
            }
        }

        $array_produtos = $produtos;
        $produtos = [];
        $total_custo = 0;
        foreach($array_produtos as $unidade_negocio){
            foreach($unidade_negocio as $produto){
                $produtos [] =[
                    'valor' => $produto['valor'],
                    'rank_codigo' => 0,
                    'codigo' => $produto['codigo'],
                    'descricao' => $produto['descricao'],
                    'grupo' => $produto['grupo'],
                    'linha' => $produto['linha'],
                    'marca' => $produto['marca'],
                    'quantidade' => $produto['quantidade'],
                    'porcetagem' => ($produto['valor'] / $total_valor) * 100,
                    'porcetagem_acumulativa' => '',
                    'preco_unitario' => ($produto['valor'] / $produto['quantidade']),
                    'custo_gerencial' => ($produto['custo'] / $produto['quantidade']),
                    'retabilidade_porcetagem' => $produto['valor'] > 0 && $produto['custo']? (($produto['valor'] / $produto['custo']) - 1) * 100: '',
                ];

                $total_custo += $produto['custo'];
            }
        }

        arsort($produtos);
        $rank = 1;
        $total_porcetagem = 0;
        foreach($produtos as $key => $produto){
            $total_porcetagem += $produtos[$key]['porcetagem'];
            $produtos[$key]['rank_codigo'] = $rank;
            $produtos[$key]['porcetagem_acumulativa'] = $total_porcetagem;
            $rank++;
        }

        $total_porcetagem = empty($total_porcetagem)? '' : parserValor($total_porcetagem).'%';
        $total_retabilidade = $total_valor > 0 && $total_custo > 0? parserValor((($total_valor / $total_custo) - 1) * 100)."%": '';
        $quantidade_total_data_escolhida = empty($total_quantidade)? '' : parserValor($total_quantidade);
        $valor_total_data_escolhida = empty($total_valor)? '' : parserValor($total_valor);

        $produtos = $this->ajusteArrayParaValores($produtos);

        $coluna_ano_escolhido = $data_escolhida_inicial->format('M/y');

        $data_escolhida_inicial = $data_escolhida_inicial->format('d/m/Y');
        $data_escolhida_final = $data_escolhida_final->format('d/m/Y');

        $tipo_usuarios = $this->getTipoVendedores();
        $unidades_negocios = $this->getUnidadeNegocio();
        $representantes = $this->getRepresentante();
        $regioes = $this->getRegiao();
        $id_unidade_negocio = encrypt($id_unidade_negocio);
        $codigo_cliente = $fields['codigo_cliente'];

        return view('programs.mapa_venda.modal.produto')->with(['coluna_ano_escolhido' => $coluna_ano_escolhido, 'produtos' => $produtos, 'quantidade_total_data_escolhida' => $quantidade_total_data_escolhida, 'valor_total_data_escolhida' => $valor_total_data_escolhida, 'data_escolhida_inicial' => $data_escolhida_inicial, 'data_escolhida_final' => $data_escolhida_final, 'total_retabilidade' => $total_retabilidade, 'total_porcetagem' => $total_porcetagem, 'tipo_usuarios' => $tipo_usuarios, 'unidades_negocios' => $unidades_negocios, 'representantes' => $representantes, 'regioes' => $regioes, 'codigo_vendedor' => $fields['codigo_vendedor'], 'id_unidade_negocio' => $id_unidade_negocio, 'filtro_cliente' => $filtro_cliente, 'codigo_cliente' => $codigo_cliente, 'grupo' => $fields['grupo'], 'linha' => $fields['linha'], 'marca' => $fields['marca']]);
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
}