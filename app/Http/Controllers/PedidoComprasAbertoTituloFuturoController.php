<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use Carbon\Carbon;

use App\PedidoComprasAbertoTituloFuturo;
use App\ComprasNasajon;
use App\NotasImportadasEntradasTitulo;

class PedidoComprasAbertoTituloFuturoController extends Controller
{
    public function atualizarPedidoComprasAbertoTituloFuturo(){
        $comprasNasajonObj = ComprasNasajon::select('numero_pedido', 
        'id_nota', 'preco_compra_total', 'data_compra', 'previsao_entrega', 'estabelecimento', 'fornecedor_cnpj', 'fornecedor_nome');
        $comprasNasajonObj->whereIn('situacao', ['Aberto', 'Aguardando Documento']);
        $comprasNasajonObj->with(['condicoesPagamento.parcelas', 'condicoesPagamento.formaPagamento']);
        $comprasNasajonObj->distinct();
        $comprasNasajonObj =  $comprasNasajonObj->get();

        foreach($comprasNasajonObj as $value){           
            $pedido_compras_numero = $value->numero_pedido;
            $pedido_compras_uuid = $value->id_nota;
            $pedido_compras_emissao = $value->data_compra;
            $pedido_compras_previsao_chegada = $value->previsao_entrega;
            $estabelecimento_codigo = $value->estabelecimento;
            $fornecedor_cnpj = $value->fornecedor_cnpj;
            $fornecedor_nome = $value->fornecedor_nome;
            
            foreach($value->condicoesPagamento as $condicao_pagamento){
                $quantidade_parcelas = empty($condicao_pagamento->parcelas)? 1 : $condicao_pagamento->parcelas->quantidadeparcelas;
                $preco_total = $value->preco_compra_total;
                $nota_entrada_numero = '';
    
                $dias = empty($condicao_pagamento->parcelas->nome)? "1 DDL" : str_replace('DDL', '', $condicao_pagamento->parcelas->nome);
                $dias = explode('/', $dias);
                
                $i = 1;
                foreach($dias as $dia){                   
                    $valor = $condicao_pagamento->formapagamento_valor/$quantidade_parcelas;
                    $forma_pagamento_descricao = $condicao_pagamento->formaPagamento->descricao;
                    $parcela_data = Carbon::parse($value->previsao_entrega)->addDays(intval($dia));
                    $pedido_compras_condicao_pagamento = $condicao_pagamento->formaPagamento->descricao;

                    $pedidoComprasAbertoTituloFuturo = new PedidoComprasAbertoTituloFuturo;
                    $pedidoComprasAbertoTituloFuturo->estabelecimento_codigo = $estabelecimento_codigo;
                    $pedidoComprasAbertoTituloFuturo->fornecedor_cnpj = $fornecedor_cnpj;
                    $pedidoComprasAbertoTituloFuturo->fornecedor_nome = $fornecedor_nome;
                    $pedidoComprasAbertoTituloFuturo->pedido_compras_numero = $pedido_compras_numero;
                    $pedidoComprasAbertoTituloFuturo->pedido_compras_uuid = $pedido_compras_uuid;
                    $pedidoComprasAbertoTituloFuturo->pedido_compras_emissao = $pedido_compras_emissao;
                    $pedidoComprasAbertoTituloFuturo->pedido_compras_previsao_chegada = $pedido_compras_previsao_chegada;
                    $pedidoComprasAbertoTituloFuturo->pedido_compras_condicao_pagamento = $pedido_compras_condicao_pagamento;
                    $pedidoComprasAbertoTituloFuturo->nota_entrada_numero = $nota_entrada_numero;
                    $pedidoComprasAbertoTituloFuturo->forma_pagamento_descricao = $forma_pagamento_descricao;
                    $pedidoComprasAbertoTituloFuturo->parcela = $i;
                    $pedidoComprasAbertoTituloFuturo->valor = $valor;
                    $pedidoComprasAbertoTituloFuturo->parcela_data = $parcela_data;
                    $pedidoComprasAbertoTituloFuturo->save();

                    $i++;
                }
            }          
        }

        $this->verificarPedidoLiquidado();
    }

    public function verificarNaoLancado(){
        $PedidoComprasAbertoTituloFuturoObj = PedidoComprasAbertoTituloFuturo::select();
        $PedidoComprasAbertoTituloFuturoObj = $PedidoComprasAbertoTituloFuturoObj->get();

        foreach($PedidoComprasAbertoTituloFuturoObj as $value){
            $notasImportadasEntradasTituloObj = NotasImportadasEntradasTitulo::select();
            $notasImportadasEntradasTituloObj->where('fornecedor_documento', $value->fornecedor_cnpj);
            $notasImportadasEntradasTituloObj->where(function($query){
                $query->whereNull('lancado')
                ->orWhere('lancado',false);
            });
            $notasImportadasEntradasTituloObj->where('estabelecimento', $value->estabelecimento_codigo);
            $notasImportadasEntradasTituloObj->where('valor', $value->valor);
            $notasImportadasEntradasTituloObj = $notasImportadasEntradasTituloObj->first();
            
            if(!empty($notasImportadasEntradasTituloObj)){
                $value->nao_lancado = true;
                $value->save();
            }
        }
    }

    public function modalDetalhes(Request $request){
        $campos = $request->only(['mes','filtro_ano']);

        try{
            $filtro_ano = decrypt($campos['filtro_ano']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }
        
        if (empty($campos['mes'])) {
            $primeiro_dia_do_mes = Carbon::parse($filtro_ano . "-01-01")->setTime(0, 0, 0);
            $ultimo_dia_do_mes = Carbon::parse($filtro_ano . "-12-31")->setTime(23, 59, 59);
        } else if ($campos['mes'] == 'parcial') {
            $primeiro_dia_do_mes = Carbon::parse($filtro_ano . "-01-01")->setTime(0, 0, 0);
            $ultimo_dia_do_mes = Carbon::now()->subMonth()->setTime(23, 59, 59)->lastOfMonth();
        } else {
            $primeiro_dia_do_mes = Carbon::parse($filtro_ano . "-" . $campos['mes'] . "-01")->setTime(0, 0, 0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::parse($filtro_ano . "-" . $campos['mes'] . "-01")->setTime(23, 59, 59)->lastOfMonth();
        }

        $PedidoComprasAbertoTituloFuturoObj = PedidoComprasAbertoTituloFuturo::select();
        $PedidoComprasAbertoTituloFuturoObj->whereBetween('parcela_data', [$primeiro_dia_do_mes, $ultimo_dia_do_mes]);
        $PedidoComprasAbertoTituloFuturoObj = $PedidoComprasAbertoTituloFuturoObj->get();

        $estabelecimentos = returnEmpresasNasajonView();
        $retorno = [];
        $total = [
            'valor' => 0,
            'valor_nao_lancado' => 0,
            'valor_final' => 0,
        ];
        foreach($PedidoComprasAbertoTituloFuturoObj as $value){
            $retorno[] = [
                'estabelecimento' => $estabelecimentos[(integer)$value->estabelecimento_codigo],
                'fornecedor' => $value->fornecedor_nome.' - '.$value->fornecedor_cnpj,
                'pedido_numero' => $value->pedido_compras_numero,
                'previsao_chegada' => parserData($value->pedido_compras_previsao_chegada),
                'vencimento' => parserData($value->parcela_data),
                'parcela' => $value->parcela,
                'valor' => parserValor($value->valor),
                'valor_nao_lancado' => $value->nao_lancado == true? parserValor($value->valor) : '',
                'valor_final' => $value->nao_lancado == true? '' : parserValor($value->valor),
                'pedido_compras_uuid' => encrypt($value->pedido_compras_uuid),
            ];

            $total['valor'] += $value->valor;
            $total['valor_nao_lancado'] += $value->nao_lancado == true? $value->valor : 0;
            $total['valor_final'] += $value->nao_lancado == true? 0 : $value->valor;
        }

        $total['valor'] = empty($total['valor'])? '' : parserValor($total['valor']);
        $total['valor_nao_lancado'] = empty($total['valor_nao_lancado'])? '' : parserValor($total['valor_nao_lancado']);
        $total['valor_final'] = empty($total['valor_final'])? '' : parserValor($total['valor_final']);

        return view('programs.pedidos_compras_abertos_titulos_futuros.modal.detalhes')->with(['retorno' => $retorno,'total' => $total]);
    }

    public function verificarPedidoLiquidado(){
        $pedidoComprasAbertoTituloFuturoObj = PedidoComprasAbertoTituloFuturo::select()->with(['detalhesCompras'])->get();

        foreach($pedidoComprasAbertoTituloFuturoObj as $value){
            if(!empty($value->detalhesCompras)){
                if(in_array($value->detalhesCompras->situacao, ['Cancelado', 'Parcialmente Liquidado', 'Liquidado'])){
                    $value->delete();
                }
            }
        }
    }
}
