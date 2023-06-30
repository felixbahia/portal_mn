<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\VendedorTituloNasajon;
use App\ClienteNasajon;

use Illuminate\Support\Facades\DB;

class ComissaoRagazziController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ComissaoRagazzi") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ComissaoRagazzi');

        $data_inicial = Carbon::now()->startOfMonth()->format("d/m/Y");
        $data_final = Carbon::now()->endOfMonth()->format("d/m/Y");

        return view('programs.comissao_ragazzi.index')->with(["data_inicial" => $data_inicial, "data_final" => $data_final]);
    }

    public function filtro(Request $request, $array = false){
        $fields = $request->only('data_de', 'data_ate', 'cliente');
        $retorno = [];

        $empresas = returnEmpresasNasajonView();

        $query = VendedorTituloNasajon::select();
        $query->where('vendedor_codigo', '998');
        $query->whereHas('contaReceberBaixado', function($query) use($fields){
            if(!empty($fields['data_de'])){
                $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
                $query->where('data_pagamento', '>=', $data);
            }
            if(!empty($fields['data_ate'])){
                $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
                $query->where('data_pagamento', '<=', $data);
            }
            $query->where('renegociado', false);
            $query->where('emissao', '<=', '2022-06-30');
            if(!empty($fields['cliente'])){
                $cliente_busca = ClienteNasajon::select('id')
                    ->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($fields['cliente']).'%');
                $cliente_busca = $cliente_busca->get();

                $query->WhereIn('cliente_id', $cliente_busca->pluck('id'));
            }
        });
        $query->with(['contaReceberBaixado' => function($query) use($fields){
            if(!empty($fields['data_de'])){
                $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
                $query->where('data_pagamento', '>=', $data);
            }
            if(!empty($fields['data_ate'])){
                $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
                $query->where('data_pagamento', '<=', $data);
            }
            $query->where('renegociado', false);
            if(!empty($fields['cliente'])){
                $cliente_busca = ClienteNasajon::select('id')
                    ->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($fields['cliente']).'%');
                $cliente_busca = $cliente_busca->get();
    
                $query->WhereIn('cliente_id', $cliente_busca->pluck('id'));
            }
            $query->where('emissao', '<=', '2022-06-30');
        }]);
        $query->with(['cenprotTitulos' => function($query){
            $query->where('cenprot_status', '<>', 'PAGO');
        }]);        
        $query->with(['contaReceberBaixado.baixarPortal']);
        $query->with(['detalhesRenegociaoPortal.detalhesRenegociacao']);
        $result = $query->get();


        $total_valor_titulo = 0;
        $total_valor_juro = 0;
        $total_valor_pago = 0;
        $total_valor_comissao = 0;
        $total_valor_honrarios = 0;

        foreach($result as $titulo){
            if(!empty($titulo->contaReceberBaixado) && empty($titulo->cenprotTitulos)){
                $dias_atrasos = "";

                $data_vencimento = Carbon::parse($titulo->contaReceberBaixado->vencimento);
                $data_pagamento = Carbon::parse($titulo->contaReceberBaixado->data_pagamento);

                $data_para_saber_nota_debito_pagamento = Carbon::parse('5432-01-01');
    
                $dias_atrasos = $data_vencimento->diffInDays($data_pagamento);
    
                if($dias_atrasos > 10 || !empty($titulo->detalhesRenegociaoPortal->detalhesRenegociacao)){
                    $porcetagem_comissao = 0;
                    $valor_comissao = 0;
                    $nota_debito = "N";

                    $juros = empty(floatval($titulo->contaReceberBaixado->valorjuros))? 0 : floatval($titulo->contaReceberBaixado->valorjuros);

                    $honorarios_cliente_valor = 0;
                    if(!empty($titulo->contaReceberBaixado->baixarPortal)){
                        $honorarios_cliente_valor = empty($titulo->contaReceberBaixado->baixarPortal->honorarios_cliente_valor)? 0 : $titulo->contaReceberBaixado->baixarPortal->honorarios_cliente_valor;

                        $data_lancamento = Carbon::parse($titulo->contaReceberBaixado->baixarPortal->updated_at);
                        $data_verificacao = Carbon::parse('2022-07-19');

                        $juros = empty($titulo->contaReceberBaixado->baixarPortal->juros_valor)? 0 : $titulo->contaReceberBaixado->baixarPortal->juros_valor;                       
                    }else if(!empty($titulo->detalhesRenegociaoPortal)){
                        $honorarios_cliente_valor = empty($titulo->detalhesRenegociaoPortal->detalhesRenegociacao->encargos)? 0 : $titulo->detalhesRenegociaoPortal->detalhesRenegociacao->encargos/$titulo->detalhesRenegociaoPortal->detalhesRenegociacao->parcela_quantidade;
                    }

                    if($dias_atrasos <= 30){
                        $porcetagem_comissao = 50;
                        $valor_comissao = empty($juros)? 0 : ($juros/100) * $porcetagem_comissao;
                    }else{
                        $porcetagem_comissao = empty($juros)?  5 : 10;
                        $valor_comissao = ($titulo->contaReceberBaixado->valor/100) * $porcetagem_comissao;
                    }

                    $valor_comissao += $honorarios_cliente_valor;
                    
                    $retorno [] = [
                        "estabelecimento" => $empresas[intval($titulo->contaReceberBaixado->codigo)],
                        "numero_titulo_codigo" => $titulo->contaReceberBaixado->numero,
                        "cliente" => $titulo->contaReceberBaixado->nome_cliente." - ".$titulo->contaReceberBaixado->cliente->cpf_cnpj,
                        "valor_titulo" => $titulo->contaReceberBaixado->valor_titulo,
                        "valor_juro" => empty($juros)? '' : $juros,
                        "valor_pago" => $titulo->contaReceberBaixado->valor,
                        "porcetagem_comissao" => $porcetagem_comissao,
                        "valor_comissao" => $valor_comissao,
                        "nota_debito" => $nota_debito,
                        "dias_atrasos" => $dias_atrasos < 10? '' : $dias_atrasos,
                        "data_pagamento" => parserData($data_pagamento),
                        "honorarios_cliente_valor" => $honorarios_cliente_valor,
                        "data_vencimento" => parserData($data_vencimento),
                    ];
    
                    $total_valor_titulo += $titulo->contaReceberBaixado->valor_titulo;
                    $total_valor_juro += $juros;
                    $total_valor_pago += $titulo->contaReceberBaixado->valor;
                    $total_valor_comissao += $valor_comissao;
                    $total_valor_honrarios += $honorarios_cliente_valor;
                }
                
            }
        }

        if($array){
            return $retorno;
        }else{
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'dados' => $this->ajusteArrayParaValores($retorno),
                    'total_valor_titulo' => parserValor($total_valor_titulo),
                    'total_valor_juro' => parserValor($total_valor_juro),
                    'total_valor_pago' => parserValor($total_valor_pago),
                    'total_valor_comissao' => parserValor($total_valor_comissao),
                    'total_valor_honrarios' => empty($total_valor_honrarios)? '' : parserValor($total_valor_honrarios),
                ]
            ];
            return response()->json($response);
        }
    }

    private function ajusteArrayParaValores($array){
        if(is_array($array)){
            foreach($array as $key => $value){
                if(is_array($value)){
                    $array[$key] = $this->ajusteArrayParaValores($value);
                }else{
                    if(is_numeric($value)){
                        if(substr_count($key, "codigo") === 0 && substr_count($key, 'dias') === 0){
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

    public function indexNovo(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ComissaoRagazzi") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ComissaoRagazzi');

        $data_inicial = Carbon::now()->startOfMonth()->format("d/m/Y");
        $data_final = Carbon::now()->endOfMonth()->format("d/m/Y");

        return view('programs.comissao_ragazzi.index_novo')->with(["data_inicial" => $data_inicial, "data_final" => $data_final]);
    }

    public function filtroNovo(Request $request, $array = false){
        $fields = $request->only('data_de', 'data_ate', 'cliente');
        $retorno = [];

        $empresas = returnEmpresasNasajonView();

        $query = VendedorTituloNasajon::select();
        $query->where('vendedor_codigo', '998');
        $query->whereHas('contaReceberBaixado', function($query) use($fields){
            $query->where('emissao', '>', '2022-06-30');
            if(!empty($fields['data_de'])){
                $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
                $query->where('data_pagamento', '>=', $data);
            }
            if(!empty($fields['data_ate'])){
                $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
                $query->where('data_pagamento', '<=', $data);
            }
            $query->where('renegociado', false);
            if(!empty($fields['cliente'])){
                $cliente_busca = ClienteNasajon::select('id')
                    ->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($fields['cliente']).'%');
                $cliente_busca = $cliente_busca->get();

                $query->WhereIn('cliente_id', $cliente_busca->pluck('id'));
            }
        });
        $query->with(['contaReceberBaixado' => function($query) use($fields){
            $query->where('emissao', '>', '2022-06-30');
            if(!empty($fields['data_de'])){
                $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
                $query->where('data_pagamento', '>=', $data);
            }
            if(!empty($fields['data_ate'])){
                $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
                $query->where('data_pagamento', '<=', $data);
            }
            $query->where('renegociado', false);
            if(!empty($fields['cliente'])){
                $cliente_busca = ClienteNasajon::select('id')
                    ->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($fields['cliente']).'%');
                $cliente_busca = $cliente_busca->get();
    
                $query->WhereIn('cliente_id', $cliente_busca->pluck('id'));
            }
        }]);
        $query->with(['cenprotTitulos' => function($query){
            $query->where('cenprot_status', '<>', 'PAGO');
        }]);
        $query->with(['contaReceberBaixado.baixarPortal']);
        $result = $query->get();


        $total_valor_titulo = 0;
        $total_valor_juro = 0;
        $total_valor_pago = 0;
        $total_valor_comissao = 0;
        $total_valor_multa = 0;
        $total_valor_cobranca = 0;

        $data_verificacao_multa = Carbon::parse('2022-10-01');

        foreach($result as $titulo){
            if(!empty($titulo->contaReceberBaixado) && empty($titulo->cenprotTitulos)){
                $dias_atrasos = "";

                $porcetagem_comissao = 0;
                $valor_comissao = 0;
                $nota_debito = "N";
                $dias_atrasos = "";
                $multa = 0;
                $honorarios_cliente_valor = 0;

                $data_vencimento = Carbon::parse($titulo->contaReceberBaixado->vencimento);
                $data_pagamento = Carbon::parse($titulo->contaReceberBaixado->data_pagamento);    
                $dias_atrasos = $data_vencimento->diffInDays($data_pagamento);

                $titulo_valor = $titulo->contaReceberBaixado->valor_titulo;
                $juros_valor = empty(floatval($titulo->contaReceberBaixado->valorjuros))? 0 : $titulo->contaReceberBaixado->valorjuros;
                $pago_valor = $titulo->contaReceberBaixado->valor;

                $multa_valor = $pago_valor - ($titulo_valor + $juros_valor);
                $multa_valor = $multa_valor > 0.01? $multa_valor : 0;

                if(substr_count($titulo->contaReceberBaixado->numeroexterno, "ND") !== 0){
                    $nota_debito = "S";
                    $porcetagem_comissao = 50;
                    $valor_comissao = ($titulo->contaReceberBaixado->valor/100) * $porcetagem_comissao;
                }

                if(!empty($titulo->contaReceberBaixado->baixarPortal)){
                    $honorarios_cliente_valor = $titulo->contaReceberBaixado->baixarPortal->honorarios_cliente_valor;
                    $valor_comissao += $honorarios_cliente_valor;
                }


                if($data_vencimento->lt($data_verificacao_multa)){
                    $valor_comissao += $multa_valor;
                }                
                
                $retorno [] = [
                    "estabelecimento" => $empresas[intval($titulo->contaReceberBaixado->codigo)],
                    "numero_titulo" => $titulo->contaReceberBaixado->numero,
                    "cliente" => $titulo->contaReceberBaixado->nome_cliente." - ".$titulo->contaReceberBaixado->cliente->cpf_cnpj,
                    "valor_titulo" => $titulo->contaReceberBaixado->valor_titulo,
                    "valor_juro" => empty(floatval($titulo->contaReceberBaixado->valorjuros))? '' : $titulo->contaReceberBaixado->valorjuros,
                    "valor_pago" => $titulo->contaReceberBaixado->valor,
                    "porcetagem_comissao" => $porcetagem_comissao,
                    "valor_comissao" => $valor_comissao,
                    "nota_debito" => $nota_debito,
                    "dias_atrasos" => $dias_atrasos,
                    "data_pagamento" => parserData($data_pagamento),
                    "multa" => $multa_valor,
                    "honorarios" => $honorarios_cliente_valor,
                ];

                $total_valor_titulo += $titulo->contaReceberBaixado->valor_titulo;
                $total_valor_juro += $titulo->contaReceberBaixado->valorjuros;
                $total_valor_pago += $titulo->contaReceberBaixado->valor;
                $total_valor_comissao += $valor_comissao;
                $total_valor_multa += $multa;
                $total_valor_cobranca += $honorarios_cliente_valor;               
            }
        }

        if($array){
            return $retorno;
        }else{
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'dados' => $this->ajusteArrayParaValores($retorno),
                    'total_valor_titulo' => empty($total_valor_titulo)? '' : parserValor($total_valor_titulo),
                    'total_valor_juro' => empty($total_valor_juro)? '' : parserValor($total_valor_juro),
                    'total_valor_pago' => empty($total_valor_pago)? '' : parserValor($total_valor_pago),
                    'total_valor_comissao' => empty($total_valor_comissao)? '' : parserValor($total_valor_comissao),
                    'total_valor_multa' => empty($total_valor_multa)? '' : parserValor($total_valor_multa),
                    'total_valor_cobranca' => empty($total_valor_cobranca)? '' : parserValor($total_valor_cobranca),
                ]
            ];
            return response()->json($response);
        }
    }
}
