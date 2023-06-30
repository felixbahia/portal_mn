<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

use App\TituloInformacao;
use App\TitulosEmAbertoNasajonPortal;
use App\TitulosPagosNasajon;
use App\PedidosPrePago;
use App\User;
use App\TituloInformacaoMes;
use App\TitulosAPagarNasajon;
use App\TituloAPagarInformacaoMes;
use App\TituloAPagarInformacao;
use App\TituloInformacaoGrupo;
use App\TituloAPagar;

class TituloInformacaoController extends Controller
{
    private $data_inicial = '2019-01-01';
    private $estabelecimentos_particular = ['00', '20','25', '30', 'MARCELO', 'MN_NAMURA', 'NAMURA_NN','NELSON'];
    private $codigos_bancos_fornecedores = ['0000304199999', '0000012009999', '60746948325500', '62232889000190', 'FINIMPBB', 'FINIMPITAU', 'BRADESCOFINIMP', '00000000504580'];
    private $codigos_despesas_fornecedores = [
        '17980', 
        '0000000749999', 
        'DARF', 
        '0000000429999', 
        'GARE-ICMS', 
        'GICMS_RO',
        'GICMS_SP', 
        'GICMS_TO',
        '02558157000162',
        '62173620009306',
        '04740876000125',
        '0000030409999',
        '92693118000160'
    ];
    private $codigos_transferencia_fornecedores = [
        'MN',
        '05075884000248',
        '97544848000113',
        '97544848000202',
        '0310556730001',
        '08723659807',
        '06311274000340',
        '05075884000167',
        '06311274000269',
        '08', 
        '07', 
        '06311274000501', 
        '06311274000420', 
        '97544848000202'
    ];
    private $codigos_compras_fornecedores = [
        '7342292',
        '00326659000132',
        '00326659000213',
        '05686419000162',
        '0000013399999', 
        '0523265270001',
        '7342201',
        '0608069990001',
        '7342303',
        '0000019019999',
        '0000309269999',
        '40136981000202'
    ];

    private $codigos_compras_importado_fornecedores = [
        '0000089119999', 
        '0000089119999', 
        'EX-3611-000002'
    ];

    public function gerarTituloInformacaoTotal(){
        ini_set('memory_limit','2024M');
        $data_atual = Carbon::now();

        $cnpj = $this->cnpjINtercompany();

        $query_aberto = TitulosEmAbertoNasajonPortal::select();
        $query_aberto->whereBetween('titulo_emissao', [$this->data_inicial, $data_atual]);
        $query_aberto->whereNotIn("codigo", ['25', 'TREINAMENTO']);
        $query_aberto->whereNotIn("cod_cliente", $cnpj);
        $result_aberto = $query_aberto->get();

        $array = []; 
        $array_mes = [];

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->titulo_emissao);
            $data_vencimento = Carbon::parse($value->vencimento_original);
            $diferencas_dias = $data_emissao->diffInDays($data_vencimento);
            $diferencas_meses = $data_emissao->diffInMonths($data_vencimento);

            if(empty($array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias])){
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias] = [
                    'estabelecimento_codigo' => $value->codigo, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'periodo_vencimento' => $diferencas_dias, 
                    'quantidade' => 1, 
                    'valor' => $value->valor, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo
                ];
            }else{
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['quantidade']++;
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['valor'] += $value->valor;
            }

            if(empty($array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_meses])){
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_meses] = [
                    'estabelecimento_codigo' => $value->codigo, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'diferencas_meses' => $diferencas_meses, 
                    'quantidade' => 1, 
                    'valor' => $value->valor, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo
                ];
            }else{
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_meses]['quantidade']++;
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_meses]['valor'] += $value->valor;
            }
        }

        $query_aberto = TitulosPagosNasajon::select();
        $query_aberto->whereBetween('emissao', [$this->data_inicial, $data_atual]);
        $query_aberto->whereNotIn("codigo", ['25', 'TREINAMENTO']);
        $query_aberto->whereNotIn("cod_cliente", $cnpj);
        $result_aberto = $query_aberto->get();

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->emissao);
            $data_vencimento = Carbon::parse($value->vencimento_original);
            $diferencas_dias = $data_emissao->diffInDays($data_vencimento);
            $diferencas_meses = $data_emissao->diffInMonths($data_vencimento);

            if(empty($array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias])){
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias] = [
                    'estabelecimento_codigo' => $value->codigo, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'periodo_vencimento' => $diferencas_dias, 
                    'quantidade' => 1, 
                    'valor' => $value->valor, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo
                ];
            }else{
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['quantidade']++;
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['valor'] += $value->valor;
            }

            if(empty($array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_meses])){
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_meses] = [
                    'estabelecimento_codigo' => $value->codigo, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'diferencas_meses' => $diferencas_meses, 
                    'quantidade' => 1, 
                    'valor' => $value->valor, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo
                ];
            }else{
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_meses]['quantidade']++;
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_meses]['valor'] += $value->valor;
            }
        }

        $query_aberto = PedidosPrePago::select();
        $query_aberto->with(['pedido.usuario_detalhes']);
        $query_aberto->whereBetween('created_at', [$this->data_inicial, $data_atual]);
        $result_aberto = $query_aberto->get();

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->created_at);
            $data_vencimento = Carbon::parse($value->created_at);
            $diferencas_dias = 0;
            $diferencas_meses = 0;
            $estabelecimento = str_pad($value->pedido->estabelecimento, 2, '0', STR_PAD_LEFT);
            $vendedor_codigo = empty($value->pedido->usuario_detalhes)? '001' : $value->pedido->usuario_detalhes->codigo_representante;

            if(empty($array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias])){
                $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias] = [
                    'estabelecimento_codigo' => $estabelecimento, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'periodo_vencimento' => $diferencas_dias, 
                    'quantidade' => 1, 
                    'valor' => $value->valor_pago, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => $vendedor_codigo
                ];
            }else{
                $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias]['quantidade']++;
                $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias]['valor'] += $value->valor_pago;
            }

            if(empty($array_mes[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_meses])){
                $array_mes[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_meses] = [
                    'estabelecimento_codigo' => $estabelecimento, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'diferencas_meses' => $diferencas_meses, 
                    'quantidade' => 1, 
                    'valor' => $value->valor_pago, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => $vendedor_codigo
                ];
            }else{
                $array_mes[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_meses]['quantidade']++;
                $array_mes[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_meses]['valor'] += $value->valor_pago;
            }
        }

        foreach($array as $value){
            $query_verificacao = TituloInformacao::select();
            $query_verificacao->where('estabelecimento_codigo', $value['estabelecimento_codigo']);
            $query_verificacao->where('mes_emissao', $value['mes_emissao']);
            $query_verificacao->where('ano_emissao', $value['ano_emissao']);
            $query_verificacao->where('periodo_vencimento', $value['periodo_vencimento']);
            $query_verificacao->where('representante_codigo', $value['representante_codigo']);
            $result_verificacao = $query_verificacao->first();
            
            if(empty($result_verificacao)){
                $tituloInformacaoObj = new TituloInformacao;
                $tituloInformacaoObj->estabelecimento_codigo = $value['estabelecimento_codigo'];
                $tituloInformacaoObj->mes_emissao = $value['mes_emissao'];
                $tituloInformacaoObj->ano_emissao = $value['ano_emissao'];
                $tituloInformacaoObj->periodo_vencimento = $value['periodo_vencimento'];
                $tituloInformacaoObj->quantidade = $value['quantidade'];
                $tituloInformacaoObj->valor = $value['valor'];
                $tituloInformacaoObj->valor_pre_pago = $value['valor_pre_pago'];
                $tituloInformacaoObj->representante_codigo = $value['representante_codigo'];
                $tituloInformacaoObj->save();
            }else{
                $result_verificacao->quantidade = $value['quantidade'];
                $result_verificacao->valor = $value['valor'];
                $result_verificacao->valor_pre_pago = $value['valor_pre_pago'];
                $result_verificacao->save();
            }
        }

        foreach($array_mes as $value){
            $query_verificacao = TituloInformacaoMes::select();
            $query_verificacao->where('estabelecimento_codigo', $value['estabelecimento_codigo']);
            $query_verificacao->where('mes_emissao', $value['mes_emissao']);
            $query_verificacao->where('ano_emissao', $value['ano_emissao']);
            $query_verificacao->where('diferenca_mes', $value['diferencas_meses']);
            $query_verificacao->where('representante_codigo', $value['representante_codigo']);
            $result_verificacao = $query_verificacao->first();
            
            if(empty($result_verificacao)){
                $tituloInformacaoObj = new TituloInformacaoMes;
                $tituloInformacaoObj->estabelecimento_codigo = $value['estabelecimento_codigo'];
                $tituloInformacaoObj->mes_emissao = $value['mes_emissao'];
                $tituloInformacaoObj->ano_emissao = $value['ano_emissao'];
                $tituloInformacaoObj->diferenca_mes = $value['diferencas_meses'];
                $tituloInformacaoObj->quantidade = $value['quantidade'];
                $tituloInformacaoObj->valor = $value['valor'];
                $tituloInformacaoObj->valor_pre_pago = $value['valor_pre_pago'];
                $tituloInformacaoObj->representante_codigo = $value['representante_codigo'];
                $tituloInformacaoObj->save();
            }else{
                $result_verificacao->quantidade = $value['quantidade'];
                $result_verificacao->valor = $value['valor'];
                $result_verificacao->valor_pre_pago = $value['valor_pre_pago'];
                $result_verificacao->save();
            }
        }
    }

    public function gerarTituloInformacaoDiario(){
        $data_inicial = Carbon::now()->subMonth()->setTime(0, 0, 0)->firstOfMonth();
        $data_atual = Carbon::now();

        $cnpj = $this->cnpjINtercompany();

        $query_aberto = TitulosEmAbertoNasajonPortal::select();
        $query_aberto->whereBetween('titulo_emissao', [$data_inicial, $data_atual]);
        $query_aberto->whereNotIn("codigo", ['25', 'TREINAMENTO']);
        $query_aberto->whereNotIn("cod_cliente", $cnpj);
        $result_aberto = $query_aberto->get();

        $array = []; 
        $array_mes = [];

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->titulo_emissao);
            $data_vencimento = Carbon::parse($value->vencimento_original);
            $diferencas_dias = $data_emissao->diffInDays($data_vencimento);

            if(empty($array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias])){
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias] = [
                    'estabelecimento_codigo' => $value->codigo, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'periodo_vencimento' => $diferencas_dias, 
                    'quantidade' => 1, 
                    'valor' => $value->valor, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo
                ];
            }else{
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['quantidade']++;
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['valor'] += $value->valor;
            }

            if(empty($array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias])){
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias] = [
                    'estabelecimento_codigo' => $value->codigo, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'periodo_vencimento' => $diferencas_dias, 
                    'quantidade' => 1, 
                    'valor' => $value->valor, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo
                ];
            }else{
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['quantidade']++;
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['valor'] += $value->valor;
            }
        }

        $query_aberto = TitulosPagosNasajon::select();
        $query_aberto->whereBetween('emissao', [$data_inicial, $data_atual]);
        $query_aberto->whereNotIn("codigo", ['25', 'TREINAMENTO']);
        $query_aberto->whereNotIn("cod_cliente", $cnpj);
        $result_aberto = $query_aberto->get();

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->emissao);
            $data_vencimento = Carbon::parse($value->vencimento_original);
            $diferencas_dias = $data_emissao->diffInDays($data_vencimento);

            if(empty($array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias])){
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias] = [
                    'estabelecimento_codigo' => $value->codigo, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'periodo_vencimento' => $diferencas_dias, 
                    'quantidade' => 1, 
                    'valor' => $value->valor, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo
                ];
            }else{
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['quantidade']++;
                $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['valor'] += $value->valor;
            }

            if(empty($array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias])){
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias] = [
                    'estabelecimento_codigo' => $value->codigo, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'periodo_vencimento' => $diferencas_dias, 
                    'quantidade' => 1, 
                    'valor' => $value->valor, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo
                ];
            }else{
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['quantidade']++;
                $array_mes[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias]['valor'] += $value->valor;
            }
        }

        $query_aberto = PedidosPrePago::select();
        $query_aberto->with(['pedido.usuario_detalhes']);
        $query_aberto->whereBetween('created_at', [$data_inicial, $data_atual]);
        $result_aberto = $query_aberto->get();

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->created_at);
            $data_vencimento = Carbon::parse($value->created_at);
            $diferencas_dias = 0;
            $estabelecimento = str_pad($value->pedido->estabelecimento, 2, '0', STR_PAD_LEFT);
            $vendedor_codigo = empty($value->pedido->usuario_detalhes)? '001' : $value->pedido->usuario_detalhes->codigo_representante;

            if(empty($array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias])){
                $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias] = [
                    'estabelecimento_codigo' => $estabelecimento, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'periodo_vencimento' => $diferencas_dias, 
                    'quantidade' => 1, 
                    'valor' => $value->valor_pago, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => $vendedor_codigo
                ];
            }else{
                $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias]['quantidade']++;
                $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias]['valor'] += $value->valor_pago;
            }

            if(empty($array_mes[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias])){
                $array_mes[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias] = [
                    'estabelecimento_codigo' => $estabelecimento, 
                    'mes_emissao' => $data_emissao->month, 
                    'ano_emissao'=> $data_emissao->year, 
                    'periodo_vencimento' => $diferencas_dias, 
                    'quantidade' => 1, 
                    'valor' => $value->valor_pago, 
                    'valor_pre_pago' => 0, 
                    'representante_codigo' => $vendedor_codigo
                ];
            }else{
                $array_mes[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias]['quantidade']++;
                $array_mes[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias]['valor'] += $value->valor_pago;
            }
        }

        foreach($array as $value){
            $query_verificacao = TituloInformacao::select();
            $query_verificacao->where('estabelecimento_codigo', $value['estabelecimento_codigo']);
            $query_verificacao->where('mes_emissao', $value['mes_emissao']);
            $query_verificacao->where('ano_emissao', $value['ano_emissao']);
            $query_verificacao->where('periodo_vencimento', $value['periodo_vencimento']);
            $query_verificacao->where('representante_codigo', $value['representante_codigo']);
            $result_verificacao = $query_verificacao->first();
            
            if(empty($result_verificacao)){
                $tituloInformacaoObj = new TituloInformacao;
                $tituloInformacaoObj->estabelecimento_codigo = $value['estabelecimento_codigo'];
                $tituloInformacaoObj->mes_emissao = $value['mes_emissao'];
                $tituloInformacaoObj->ano_emissao = $value['ano_emissao'];
                $tituloInformacaoObj->periodo_vencimento = $value['periodo_vencimento'];
                $tituloInformacaoObj->quantidade = $value['quantidade'];
                $tituloInformacaoObj->valor = $value['valor'];
                $tituloInformacaoObj->valor_pre_pago = $value['valor_pre_pago'];
                $tituloInformacaoObj->representante_codigo = $value['representante_codigo'];
                $tituloInformacaoObj->save();
            }else{
                $result_verificacao->quantidade = $value['quantidade'];
                $result_verificacao->valor = $value['valor'];
                $result_verificacao->valor_pre_pago = $value['valor_pre_pago'];
                $result_verificacao->save();
            }
        }

        foreach($array_mes as $value){
            $query_verificacao = TituloInformacaoMes::select();
            $query_verificacao->where('estabelecimento_codigo', $value['estabelecimento_codigo']);
            $query_verificacao->where('mes_emissao', $value['mes_emissao']);
            $query_verificacao->where('ano_emissao', $value['ano_emissao']);
            $query_verificacao->where('diferenca_mes', $value['periodo_vencimento']);
            $query_verificacao->where('representante_codigo', $value['representante_codigo']);
            $result_verificacao = $query_verificacao->first();
            
            if(empty($result_verificacao)){
                $tituloInformacaoObj = new TituloInformacaoMes;
                $tituloInformacaoObj->estabelecimento_codigo = $value['estabelecimento_codigo'];
                $tituloInformacaoObj->mes_emissao = $value['mes_emissao'];
                $tituloInformacaoObj->ano_emissao = $value['ano_emissao'];
                $tituloInformacaoObj->diferenca_mes = $value['periodo_vencimento'];
                $tituloInformacaoObj->quantidade = $value['quantidade'];
                $tituloInformacaoObj->valor = $value['valor'];
                $tituloInformacaoObj->valor_pre_pago = $value['valor_pre_pago'];
                $tituloInformacaoObj->representante_codigo = $value['representante_codigo'];
                $tituloInformacaoObj->save();
            }else{
                $result_verificacao->quantidade = $value['quantidade'];
                $result_verificacao->valor = $value['valor'];
                $result_verificacao->valor_pre_pago = $value['valor_pre_pago'];
                $result_verificacao->save();
            }
        }
    }

    public function gerarTituloInformacaoEquipe(){
        $query = TituloInformacao::select('representante_codigo');
        $query->whereNull('equipe');
        $query->whereNotIn('representante_codigo', ['', 'None', '001']);
        $query->distinct();
        $result = $query->get();

        foreach($result as $value){
            $query_user = User::select();
            $query_user->with(['unidadeNegocioMetaUserUltimo.detalhesUnidadeNegocioMeta.detalhesUnidadeNegocio']);
            $query_user->where('codigo_representante', $value->representante_codigo);
            $result_user = $query_user->first();

            if(!empty($result_user->unidadeNegocioMetaUserUltimo)){
                if(!empty($result_user->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta)){
                    if(!empty($result_user->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio)){
                            $equipe = $result_user->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->unidade;
                            $unidades_negocios_id = $result_user->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->id;
            
                            $tituloInformacaoObj = TituloInformacao::select(); 
                            $tituloInformacaoObj->where('representante_codigo', $value->representante_codigo);
                            $tituloInformacaoObj->update(['equipe' => $equipe]);
                            $tituloInformacaoObj->update(['unidades_negocios_id' => $unidades_negocios_id]);
                    }
                }
            }
        }
    }

    public function gerarTituloAPagarInformacaoTotal(){
        ini_set('memory_limit','2024M');
        $data_inicial = Carbon::now()->subMonth()->setTime(0, 0, 0)->firstOfMonth();
        $data_atual = Carbon::now();

        $cnpj = $this->cnpjINtercompany();

        $query_aberto = TitulosAPagarNasajon::select();
        $query_aberto->whereBetween('Data de Emissão', [$data_inicial, $data_atual]);
        $query_aberto->whereNotIn('Estabelecimento', $this->estabelecimentos_particular);
        $query_aberto->with('notaEntradaDetalhes');
        $query_aberto->orderBy('Data de Emissão');
        $result_aberto = $query_aberto->get();

        $array = []; 
        $array_mes = []; 

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value['Data de Emissão']);
            $data_vencimento = Carbon::parse($value['Data do Vencimento']);
            $diferencas_dias = $data_emissao->diffInDays($data_vencimento);

            $tituloAPagarObj = TituloAPagar::select();
            $tituloAPagarObj->where('estabelecimento_codigo', $value['Estabelecimento']);
            $tituloAPagarObj->where('titulo_numero', $value['Número do Título']);
            $tituloAPagarObj->where('fornecedor_codigo', $value['Fornecedor']);
            $tituloAPagarObj = $tituloAPagarObj->first();

            if(empty($tituloAPagarObj)){
                $tituloAPagarObj = new TituloAPagar;
            }

            $tipo = '';

            if(in_array($value['Fornecedor'] , $this->codigos_bancos_fornecedores)){
                $tipo = 'banco';
            }else  if(substr_count($value['Número do Título'], "prev.") !== 0){
                $tipo = 'previsao';
            }else if(in_array($value['Fornecedor'] , $this->codigos_despesas_fornecedores)){
                $tipo = 'despesas';
            }else if(in_array($value['Fornecedor'] , $this->codigos_transferencia_fornecedores)){
                $tipo = 'transferencia';
            }else if(in_array($value['Fornecedor'] , $this->codigos_compras_fornecedores)){
                $tipo = 'compras';
            }else if(in_array($value['Fornecedor'] , $this->codigos_compras_importado_fornecedores)){
                $tipo = 'compras_internacional';
            }else if(in_array($value['Código da Operação'] , ['INDUSTERETOR'])){
                $tipo = 'compras';
            }else if(!empty($value['Fornecedor']['proformaDetalhes'])){
                $tipo = 'compras_internacional';
            }else if(!empty($value['notaEntradaDetalhes'])){
                if(empty($value['notaEntradaDetalhes']['itens_nota'][0]['produtoDetalhesNasajon'])){
                    $tipo = 'despesas';
                }else{
                    $tipo = 'compras';
                }
            }else{
                $tipo = 'despesas';
            }
            
            $tituloAPagarObj->estabelecimento_codigo = $value['Estabelecimento'];
            $tituloAPagarObj->titulo_numero = $value['Número do Título'];
            $tituloAPagarObj->titulo_parcela = $value['Parcela'];
            $tituloAPagarObj->titulo_tipo = $value['Tipo de Título'];
            $tituloAPagarObj->titulo_situacao = $value['Situação do Título'];
            $tituloAPagarObj->titulo_emissao = $value['Data de Emissão'];
            $tituloAPagarObj->titulo_vencimento = $value['Data do Vencimento'];
            $tituloAPagarObj->titulo_data_baixa = $value['Data da Baixa'];
            $tituloAPagarObj->titulo_valor  = $value['Valor'];
            $tituloAPagarObj->titulo_valor_liquido = $value['Valor Líquido'];
            $tituloAPagarObj->titulo_valor_baixa = $value['Valor da Baixa'];
            $tituloAPagarObj->titulo_valor_saldo_adiantamento = empty($value['Saldo do Adiantamento'])? 0 : $value['Saldo do Adiantamento'];
            $tituloAPagarObj->fornecedor_codigo = $value['Fornecedor'];
            $tituloAPagarObj->fornecedor_nome = $value['Nome do Fornecedor'];
            $tituloAPagarObj->fornecedor_razao_social = $value['Razão Social do Fornecedor'];
            $tituloAPagarObj->tipo = $tipo;
            $tituloAPagarObj->condicao_pagamento_periodo = $diferencas_dias;
            $tituloAPagarObj->nota_numero = $value['Número Nota'];
            $tituloAPagarObj->save();     
        }

    }

    public function gerarTituloAPagarInformacaoDiario(){
        $data_inicial = Carbon::now()->subMonth()->setTime(0, 0, 0)->firstOfMonth();
        $data_atual = Carbon::now();

        $cnpj = $this->cnpjINtercompany();

        $query_aberto = TitulosAPagarNasajon::select();
        $query_aberto->whereHas('notaEntrada');
        $query_aberto->whereBetween('Data de Emissão', [$data_inicial, $data_atual]);
        $query_aberto->whereNotIn('Estabelecimento', $this->estabelecimentos_particular);
        $query_aberto->whereNotIn("Fornecedor", $cnpj);
        $query_aberto->whereNotIn('Origem', ['Nota Serviços Publicos']);
        $query_aberto->with('notaEntradaDetalhes', 'proformaDetalhes', 'notaEntrada');
        $result_aberto = $query_aberto->get();

        $array = []; 
        $array_mes = []; 

        foreach($result_aberto as $value){
            if ((!empty($titulo->notaEntradaDetalhes) || !empty($titulo->proformaDetalhes) || !empty($titulo->notaEntrada))) {
                $data_emissao = Carbon::parse($value['Data de Emissão']);
                $data_vencimento = Carbon::parse($value['Data do Vencimento']);
                $diferencas_meses = $data_emissao->diffInMonths($data_vencimento);
                $diferencas_dias = $data_emissao->diffInDays($data_vencimento);

                if(empty($array[$value["Estabelecimento"].$data_emissao->month.$data_emissao->year.$diferencas_dias])){
                    $array[$value["Estabelecimento"].$data_emissao->month.$data_emissao->year.$diferencas_dias] = [
                        'estabelecimento_codigo' => $value["Estabelecimento"], 
                        'mes_emissao' => $data_emissao->month, 
                        'ano_emissao'=> $data_emissao->year, 
                        'periodo_vencimento' => $diferencas_dias, 
                        'quantidade' => 1, 
                        'valor' => $value["Valor"], 
                        'valor_pre_pago' => 0, 
                    ];
                }else{
                    $array[$value["Estabelecimento"].$data_emissao->month.$data_emissao->year.$diferencas_dias]['quantidade']++;
                    $array[$value["Estabelecimento"].$data_emissao->month.$data_emissao->year.$diferencas_dias]['valor'] += $value["Valor"];
                }

                if(empty($array_mes[$value["Estabelecimento"].$data_emissao->month.$data_emissao->year.$diferencas_meses])){
                    $array_mes[$value["Estabelecimento"].$data_emissao->month.$data_emissao->year.$diferencas_meses] = [
                        'estabelecimento_codigo' => $value["Estabelecimento"], 
                        'mes_emissao' => $data_emissao->month, 
                        'ano_emissao'=> $data_emissao->year, 
                        'periodo_vencimento' => $diferencas_meses, 
                        'quantidade' => 1, 
                        'valor' => $value["Valor"], 
                        'valor_pre_pago' => 0, 
                        'representante_codigo' => '001'
                    ];
                }else{
                    $array_mes[$value["Estabelecimento"].$data_emissao->month.$data_emissao->year.$diferencas_meses]['quantidade']++;
                    $array_mes[$value["Estabelecimento"].$data_emissao->month.$data_emissao->year.$diferencas_meses]['valor'] += $value["Valor"];
                }
            }
        }

        foreach($array_mes as $value){
            $query_verificacao = TituloAPagarInformacaoMes::select();
            $query_verificacao->where('estabelecimento_codigo', $value['estabelecimento_codigo']);
            $query_verificacao->where('mes_emissao', $value['mes_emissao']);
            $query_verificacao->where('ano_emissao', $value['ano_emissao']);
            $query_verificacao->where('diferenca_mes', $value['periodo_vencimento']);
            $query_verificacao->where('representante_codigo', $value['representante_codigo']);
            $result_verificacao = $query_verificacao->first();
            
            if(empty($result_verificacao)){
                $tituloInformacaoObj = new TituloAPagarInformacaoMes;
                $tituloInformacaoObj->estabelecimento_codigo = $value['estabelecimento_codigo'];
                $tituloInformacaoObj->mes_emissao = $value['mes_emissao'];
                $tituloInformacaoObj->ano_emissao = $value['ano_emissao'];
                $tituloInformacaoObj->diferenca_mes = $value['periodo_vencimento'];
                $tituloInformacaoObj->quantidade = $value['quantidade'];
                $tituloInformacaoObj->valor = $value['valor'];
                $tituloInformacaoObj->valor_pre_pago = $value['valor_pre_pago'];
                $tituloInformacaoObj->representante_codigo = $value['representante_codigo'];
                $tituloInformacaoObj->save();
            }else{
                $result_verificacao->quantidade = $value['quantidade'];
                $result_verificacao->valor = $value['valor'];
                $result_verificacao->valor_pre_pago = $value['valor_pre_pago'];
                $result_verificacao->save();
            }
        }

        foreach($array as $value){
            $query_verificacao = TituloAPagarInformacao::select();
            $query_verificacao->where('estabelecimento_codigo', $value['estabelecimento_codigo']);
            $query_verificacao->where('mes_emissao', $value['mes_emissao']);
            $query_verificacao->where('ano_emissao', $value['ano_emissao']);
            $query_verificacao->where('periodo_vencimento', $value['periodo_vencimento']);
            $result_verificacao = $query_verificacao->first();
            
            if(empty($result_verificacao)){
                $tituloInformacaoObj = new TituloAPagarInformacao;
                $tituloInformacaoObj->estabelecimento_codigo = $value['estabelecimento_codigo'];
                $tituloInformacaoObj->mes_emissao = $value['mes_emissao'];
                $tituloInformacaoObj->ano_emissao = $value['ano_emissao'];
                $tituloInformacaoObj->periodo_vencimento = $value['periodo_vencimento'];
                $tituloInformacaoObj->quantidade = $value['quantidade'];
                $tituloInformacaoObj->valor = $value['valor'];
                $tituloInformacaoObj->valor_pre_pago = $value['valor_pre_pago'];
                $tituloInformacaoObj->save();
            }else{
                $result_verificacao->quantidade = $value['quantidade'];
                $result_verificacao->valor = $value['valor'];
                $result_verificacao->valor_pre_pago = $value['valor_pre_pago'];
                $result_verificacao->save();
            }
        }
    }

    public function gerarTituloInformacaoGrupoTotal(){
        ini_set('memory_limit','2024M');
        $data_atual = Carbon::parse('2019-12-31');

        $cnpj = $this->cnpjINtercompany();

        $query_aberto = TitulosEmAbertoNasajonPortal::select();
        $query_aberto->with('notaDetalhes.itens_nota', 'notaDetalhes.itens_nota.especificacaos');
        $query_aberto->whereBetween('titulo_emissao', [$this->data_inicial, $data_atual]);
        $query_aberto->whereNotIn("codigo", ['25', 'TREINAMENTO']);
        $query_aberto->whereNotIn("cod_cliente", $cnpj);
        $result_aberto = $query_aberto->get();

        $array = []; 

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->titulo_emissao);
            $data_vencimento = Carbon::parse($value->vencimento_original);
            $diferencas_dias = $data_emissao->diffInDays($data_vencimento);
            $diferencas_meses = $data_emissao->diffInMonths($data_vencimento);

            if(!empty($value->notaDetalhes->itens_nota)){
                foreach($value->notaDetalhes->itens_nota as $item){
                    if(empty($array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo])){
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo] = [
                            'estabelecimento_codigo' => $value->codigo, 
                            'mes_emissao' => $data_emissao->month, 
                            'ano_emissao'=> $data_emissao->year, 
                            'periodo_vencimento' => $diferencas_dias, 
                            'quantidade' => 1, 
                            'valor' => $item->valortotal, 
                            'valor_pre_pago' => 0, 
                            'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo,
                            'produto_grupo' => empty($item->especificacaos)? '' : $item->especificacaos->grupo,
                            'produto_codigo' => $item->codigo,
                            'produto_descricao' => empty($item->especificacaos)? '' : $item->especificacaos->descricao,
                        ];
                    }else{
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo]['quantidade']++;
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo]['valor'] += $item->valortotal;
                    }
                }
            }            
        }

        $query_aberto = TitulosPagosNasajon::select();
        $query_aberto->with('notaDetalhes.itens_nota', 'notaDetalhes.itens_nota.especificacaos');
        $query_aberto->whereBetween('emissao', [$this->data_inicial, $data_atual]);
        $query_aberto->whereNotIn("codigo", ['25', 'TREINAMENTO']);
        $query_aberto->whereNotIn("cod_cliente", $cnpj);
        $result_aberto = $query_aberto->get();

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->emissao);
            $data_vencimento = Carbon::parse($value->vencimento_original);
            $diferencas_dias = $data_emissao->diffInDays($data_vencimento);
            $diferencas_meses = $data_emissao->diffInMonths($data_vencimento);

            if(!empty($value->notaDetalhes->itens_nota)){
                foreach($value->notaDetalhes->itens_nota as $item){
                    if(empty($array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo])){
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo] = [
                            'estabelecimento_codigo' => $value->codigo, 
                            'mes_emissao' => $data_emissao->month, 
                            'ano_emissao'=> $data_emissao->year, 
                            'periodo_vencimento' => $diferencas_dias, 
                            'quantidade' => 1, 
                            'valor' => $value->valortotal, 
                            'valor_pre_pago' => 0, 
                            'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo,
                            'produto_grupo' => $item->especificacaos->grupo,
                            'produto_codigo' => $item->codigo,
                            'produto_descricao' => $item->especificacaos->descricao,
                        ];
                    }else{
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo]['quantidade']++;
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo]['valor'] += $value->valortotal;
                    }
                }
            }
        }
 
        $query_aberto = PedidosPrePago::select();
        $query_aberto->with(['pedido', 'pedido.usuario_detalhes', 'pedido.itens_pedido', 'pedido.itens_pedido.especificacoes']);
        $query_aberto->whereBetween('created_at', [$this->data_inicial, $data_atual]);
        $result_aberto = $query_aberto->get();

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->created_at);
            $data_vencimento = Carbon::parse($value->created_at);
            $diferencas_dias = 0;
            $diferencas_meses = 0;
            $estabelecimento = str_pad($value->pedido->estabelecimento, 2, '0', STR_PAD_LEFT);
            $vendedor_codigo = empty($value->pedido->usuario_detalhes)? '001' : $value->pedido->usuario_detalhes->codigo_representante;

            foreach($value->pedido->itens_pedido as $item){
                if(empty($array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias.$item->cod_produto])){
                    $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias.$item->cod_produto] = [
                        'estabelecimento_codigo' => $estabelecimento, 
                        'mes_emissao' => $data_emissao->month, 
                        'ano_emissao'=> $data_emissao->year, 
                        'periodo_vencimento' => $diferencas_dias, 
                        'quantidade' => 1, 
                        'valor' => $value->valor_pago * ($item->valor_total / $value->pedido->valor_total_nota), 
                        'valor_pre_pago' => 0, 
                        'representante_codigo' => $vendedor_codigo,
                        'produto_grupo' => $item->especificacoes->grupo,
                        'produto_codigo' => $item->cod_produto,
                        'produto_descricao' => $item->especificacoes->descricao,
                    ];
                }else{
                    $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias.$item->cod_produto]['quantidade']++;
                    $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias.$item->cod_produto]['valor'] += $value->valor_pago * ($item->valor_total / $value->pedido->valor_total_nota);
                }
            }            
        }

        foreach($array as $value){
            $query_verificacao = TituloInformacaoGrupo::select();
            $query_verificacao->where('estabelecimento_codigo', $value['estabelecimento_codigo']);
            $query_verificacao->where('mes_emissao', $value['mes_emissao']);
            $query_verificacao->where('ano_emissao', $value['ano_emissao']);
            $query_verificacao->where('periodo_vencimento', $value['periodo_vencimento']);
            $query_verificacao->where('representante_codigo', $value['representante_codigo']);
            $query_verificacao->where('produto_codigo', $value['produto_codigo']);
            $result_verificacao = $query_verificacao->first();
            
            if(empty($result_verificacao)){
                $tituloInformacaoObj = new TituloInformacaoGrupo;
                $tituloInformacaoObj->estabelecimento_codigo = $value['estabelecimento_codigo'];
                $tituloInformacaoObj->mes_emissao = $value['mes_emissao'];
                $tituloInformacaoObj->ano_emissao = $value['ano_emissao'];
                $tituloInformacaoObj->periodo_vencimento = $value['periodo_vencimento'];
                $tituloInformacaoObj->quantidade = $value['quantidade'];
                $tituloInformacaoObj->valor = $value['valor'];
                $tituloInformacaoObj->valor_pre_pago = $value['valor_pre_pago'];
                $tituloInformacaoObj->representante_codigo = $value['representante_codigo'];
                $tituloInformacaoObj->produto_grupo = $value['produto_grupo'];
                $tituloInformacaoObj->produto_codigo = $value['produto_codigo'];
                $tituloInformacaoObj->produto_descricao = $value['produto_descricao'];
                $tituloInformacaoObj->save();
            }else{
                $result_verificacao->quantidade = $value['quantidade'];
                $result_verificacao->valor = $value['valor'];
                $result_verificacao->valor_pre_pago = $value['valor_pre_pago'];
                $result_verificacao->save();
            }
        }

    }

    public function gerarTituloInformacaoGrupoDiario(){
        ini_set('memory_limit','2024M');
        $data_inicial = Carbon::now()->subMonth()->setTime(0, 0, 0)->firstOfMonth();
        $data_atual = Carbon::now();

        $cnpj = $this->cnpjINtercompany();

        $query_aberto = TitulosEmAbertoNasajonPortal::select();
        $query_aberto->with('notaDetalhes.itens_nota', 'notaDetalhes.itens_nota.especificacaos');
        $query_aberto->whereBetween('titulo_emissao', [$data_inicial, $data_atual]);
        $query_aberto->whereNotIn("codigo", ['25', 'TREINAMENTO']);
        $query_aberto->whereNotIn("cod_cliente", $cnpj);
        $result_aberto = $query_aberto->get();

        $array = []; 

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->titulo_emissao);
            $data_vencimento = Carbon::parse($value->vencimento_original);
            $diferencas_dias = $data_emissao->diffInDays($data_vencimento);
            $diferencas_meses = $data_emissao->diffInMonths($data_vencimento);

            if(!empty($value->notaDetalhes->itens_nota)){
                foreach($value->notaDetalhes->itens_nota as $item){
                    if(empty($array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo])){
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo] = [
                            'estabelecimento_codigo' => $value->codigo, 
                            'mes_emissao' => $data_emissao->month, 
                            'ano_emissao'=> $data_emissao->year, 
                            'periodo_vencimento' => $diferencas_dias, 
                            'quantidade' => 1, 
                            'valor' => $item->valortotal, 
                            'valor_pre_pago' => 0, 
                            'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo,
                            'produto_grupo' => empty($item->especificacaos)? '' : $item->especificacaos->grupo,
                            'produto_codigo' => $item->codigo,
                            'produto_descricao' => empty($item->especificacaos)? '' : $item->especificacaos->descricao,
                        ];
                    }else{
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo]['quantidade']++;
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo]['valor'] += $item->valortotal;
                    }
                }
            }            
        }

        $query_aberto = TitulosPagosNasajon::select();
        $query_aberto->with('notaDetalhes.itens_nota', 'notaDetalhes.itens_nota.especificacaos');
        $query_aberto->whereBetween('emissao', [$data_inicial, $data_atual]);
        $query_aberto->whereNotIn("codigo", ['25', 'TREINAMENTO']);
        $query_aberto->whereNotIn("cod_cliente", $cnpj);
        $result_aberto = $query_aberto->get();

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->emissao);
            $data_vencimento = Carbon::parse($value->vencimento_original);
            $diferencas_dias = $data_emissao->diffInDays($data_vencimento);
            $diferencas_meses = $data_emissao->diffInMonths($data_vencimento);

            if(!empty($value->notaDetalhes->itens_nota)){
                foreach($value->notaDetalhes->itens_nota as $item){
                    if(empty($array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo])){
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo] = [
                            'estabelecimento_codigo' => $value->codigo, 
                            'mes_emissao' => $data_emissao->month, 
                            'ano_emissao'=> $data_emissao->year, 
                            'periodo_vencimento' => $diferencas_dias, 
                            'quantidade' => 1, 
                            'valor' => $value->valortotal, 
                            'valor_pre_pago' => 0, 
                            'representante_codigo' => empty($value->vendedor_codigo)? '001' : $value->vendedor_codigo,
                            'produto_grupo' => empty($item->especificacaos)? '' : $item->especificacaos->produtoGrupo->descricao,
                            'produto_codigo' => $item->codigo,
                            'produto_descricao' => empty($item->especificacaos)? '' : $item->especificacaos->descricao,
                        ];
                    }else{
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo]['quantidade']++;
                        $array[$value->codigo.$data_emissao->month.$data_emissao->year.$value->vendedor_codigo.$diferencas_dias.$item->codigo]['valor'] += $value->valortotal;
                    }
                }
            }
        }
 
        $query_aberto = PedidosPrePago::select();
        $query_aberto->with(['pedido', 'pedido.usuario_detalhes', 'pedido.itens_pedido', 'pedido.itens_pedido.especificacoes']);
        $query_aberto->whereBetween('created_at', [$data_inicial, $data_atual]);
        $result_aberto = $query_aberto->get();

        foreach($result_aberto as $value){
            $data_emissao = Carbon::parse($value->created_at);
            $data_vencimento = Carbon::parse($value->created_at);
            $diferencas_dias = 0;
            $diferencas_meses = 0;
            $estabelecimento = str_pad($value->pedido->estabelecimento, 2, '0', STR_PAD_LEFT);
            $vendedor_codigo = empty($value->pedido->usuario_detalhes)? '001' : $value->pedido->usuario_detalhes->codigo_representante;

            foreach($value->pedido->itens_pedido as $item){
                if(empty($array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias.$item->cod_produto])){
                    $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias.$item->cod_produto] = [
                        'estabelecimento_codigo' => $estabelecimento, 
                        'mes_emissao' => $data_emissao->month, 
                        'ano_emissao'=> $data_emissao->year, 
                        'periodo_vencimento' => $diferencas_dias, 
                        'quantidade' => 1, 
                        'valor' => $value->valor_pago * ($item->valor_total / $value->pedido->valor_total_nota), 
                        'valor_pre_pago' => 0, 
                        'representante_codigo' => $vendedor_codigo,
                        'produto_grupo' => empty($item->especificacaos)? '' : $item->especificacoes->grupo,
                        'produto_codigo' => $item->cod_produto,
                        'produto_descricao' => empty($item->especificacaos)? '' : $item->especificacoes->descricao,
                    ];
                }else{
                    $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias.$item->cod_produto]['quantidade']++;
                    $array[$estabelecimento.$data_emissao->month.$data_emissao->year.$vendedor_codigo.$diferencas_dias.$item->cod_produto]['valor'] += $value->valor_pago * ($item->valor_total / $value->pedido->valor_total_nota);
                }
            }            
        }

        foreach($array as $value){
            $query_verificacao = TituloInformacaoGrupo::select();
            $query_verificacao->where('estabelecimento_codigo', $value['estabelecimento_codigo']);
            $query_verificacao->where('mes_emissao', $value['mes_emissao']);
            $query_verificacao->where('ano_emissao', $value['ano_emissao']);
            $query_verificacao->where('periodo_vencimento', $value['periodo_vencimento']);
            $query_verificacao->where('representante_codigo', $value['representante_codigo']);
            $query_verificacao->where('produto_codigo', $value['produto_codigo']);
            $result_verificacao = $query_verificacao->first();
            
            if(empty($result_verificacao)){
                $tituloInformacaoObj = new TituloInformacaoGrupo;
                $tituloInformacaoObj->estabelecimento_codigo = $value['estabelecimento_codigo'];
                $tituloInformacaoObj->mes_emissao = $value['mes_emissao'];
                $tituloInformacaoObj->ano_emissao = $value['ano_emissao'];
                $tituloInformacaoObj->periodo_vencimento = $value['periodo_vencimento'];
                $tituloInformacaoObj->quantidade = $value['quantidade'];
                $tituloInformacaoObj->valor = empty($value['valor'])? 0 : $value['valor'];
                $tituloInformacaoObj->valor_pre_pago = $value['valor_pre_pago'];
                $tituloInformacaoObj->representante_codigo = $value['representante_codigo'];
                $tituloInformacaoObj->produto_grupo = $value['produto_grupo'];
                $tituloInformacaoObj->produto_codigo = $value['produto_codigo'];
                $tituloInformacaoObj->produto_descricao = $value['produto_descricao'];
                $tituloInformacaoObj->save();
            }else{
                $result_verificacao->quantidade = $value['quantidade'];
                $result_verificacao->valor = empty($value['valor'])? 0 : $value['valor'];
                $result_verificacao->valor_pre_pago = $value['valor_pre_pago'];
                $result_verificacao->save();
            }
        }

    }

    private function cnpjINtercompany(){
        $cnpj_excluir[] = '05075884000167';
        $cnpj_excluir[] = '05075884000248';
        $cnpj_excluir[] = '06311274000269';
        $cnpj_excluir[] = '06311274000340';
        $cnpj_excluir[] = '08';
        $cnpj_excluir[] = '07';
        $cnpj_excluir[] = '06311274000501';
        $cnpj_excluir[] = '06311274000420';
        $cnpj_excluir[] = '97544848000202';
        return $cnpj_excluir;
    }

    public function gerarTituloAPagarInformacaoTotalBI(){
        ini_set('memory_limit','2024M');
        $data_atual = Carbon::now();

        $cnpj = $this->cnpjINtercompany();

        $query_aberto = TituloAPagar::select();
        $query_aberto->whereNotIn('titulo_situacao', ['Quitado (Renegociado)', 'Quitado', 'Cancelado']);
        $result_aberto = $query_aberto->get();

        foreach($result_aberto as $value){
            $tituloAPagarObj = TitulosAPagarNasajon::select();
            $tituloAPagarObj->where('Estabelecimento', $value['estabelecimento_codigo']);
            $tituloAPagarObj->where('Número do Título', $value['titulo_numero']);
            $tituloAPagarObj->where('Fornecedor', $value['fornecedor_codigo']);
            $tituloAPagarObj = $tituloAPagarObj->first();

            if(!empty($tituloAPagarObj)){
                $data_emissao = Carbon::parse($tituloAPagarObj['Data de Emissão']);
                $data_vencimento = Carbon::parse($tituloAPagarObj['Data do Vencimento']);
                $diferencas_dias = $data_emissao->diffInDays($data_vencimento);

                $value->titulo_situacao = $tituloAPagarObj['Situação do Título'];
                $value->estabelecimento_codigo = $tituloAPagarObj['Estabelecimento'];
                $value->titulo_numero = $tituloAPagarObj['Número do Título'];
                $value->titulo_parcela = $tituloAPagarObj['Parcela'];
                $value->titulo_tipo = $tituloAPagarObj['Tipo de Título'];
                $value->titulo_situacao = $tituloAPagarObj['Situação do Título'];
                $value->titulo_emissao = $tituloAPagarObj['Data de Emissão'];
                $value->titulo_vencimento = $tituloAPagarObj['Data do Vencimento'];
                $value->titulo_data_baixa = $tituloAPagarObj['Data da Baixa'];
                $value->titulo_valor  = $tituloAPagarObj['Valor'];
                $value->titulo_valor_liquido = $tituloAPagarObj['Valor Líquido'];
                $value->titulo_valor_baixa = $tituloAPagarObj['Valor da Baixa'];
                $value->titulo_valor_saldo_adiantamento = empty($tituloAPagarObj['Saldo do Adiantamento'])? 0 : $tituloAPagarObj['Saldo do Adiantamento'];
                $value->fornecedor_codigo = $tituloAPagarObj['Fornecedor'];
                $value->fornecedor_nome = $tituloAPagarObj['Nome do Fornecedor'];
                $value->fornecedor_razao_social = $tituloAPagarObj['Razão Social do Fornecedor'];
                $value->condicao_pagamento_periodo = $diferencas_dias;
                $value->nota_numero = $tituloAPagarObj['Número Nota']; 
                $value->save();
            }else{
                $value->delete();
            }     
        }
    }
}
