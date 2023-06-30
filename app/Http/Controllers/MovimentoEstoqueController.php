<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use App\Estoque;
use App\SaldoPecasEnviada;
use App\NotasNasajon;
use App\Movimentacao;
use App\FornecedorNasajon;
use App\ProdutoEspecificacao;
use App\NotasEntradasNasajon;
use App\EstoquePoderTerceiro;

class MovimentoEstoqueController extends Controller
{

    public $codigo_cliente_armazem = '97544848000202'; // O Código do cliente MN ARMAZENS GERAIS LTDA
    private $cfop_movimento = ['6905','6905'];
    private $cfop_terceiro_entrada = ['1554', '1902', '1902', '1903', '1905', '1905', '1908', '1909', '1915', '1916', '1918', '1919', '1925', '1925', '1949', '2554', '2902', '2902', '2903', '2905', '2905', '2906', '2908', '2909', '2915', '2916', '2918', '2919', '2925', '2925', '2949', '2949.1'];
    private $cfop_terceiro_saida = ['5106', '5106', '5106', '5123', '5123', '5124', '5554', '5901', '5905', '5905', '5906', '5906', '5909', '5910', '5911', '5911', '5915', '5917', '5923', '5923', '5949', '6106', '6106', '6106', '6107', '6108', '6108', '6108', '6108', '6117', '6123', '6123', '6124', '6156', '6554', '6901', '6905', '6905', '6906', '6906', '6909', '6910', '6911', '6911', '6915', '6917', '6923', '6923', '6949'];

    private $operacao_terceiro = [
        'Retorno de bem do ativo imobilizado remetido para uso fora do estabelecimento', 
        'Retorno de Mercadoria Remetida para Industrialização / Beneficiamento (Cfops:1902/2902 e 1925/2925)', 
        'Industrialização Efetuada e Retorno Remetido para Industrialização (1124/2124 e 1902/2902)', 
        'Retorno de Mercadorias Remetida para Industrialização Não Aplicada', 
        'Entrada de Mercadorias Recebida para Depósito', 
        'Entrada de Remessa para Armazenagem (Com Movimento de Estoque - TORO)', 
        'Entrada de bem por conta de contrato de comodato', 
        'Entrada de bem por conta de contrato de comodato', 
        'Retorno de Remessa de Mercadorias para Conserto', 
        'Retorno de Remessa de Mercadorias para Conserto', 
        'Devolução de Mercadoria recebida em consignação (Saldo estoque)', 
        'Devolução Simbolica recebida em Consignação (produtos vendidos)', 
        'Industrialização Efetuada e Retorno Remetido para Industrialização (1925/2925 e 1125/2125)', 
        'Retorno de Mercadoria Remetida para Industrialização / Beneficiamento (Cfops:1902/2902 e 1925/2925)', 
        'Outras Entradas  Remessa para Industrialização - Simbólica ', 
        'Retorno de bem do ativo imobilizado remetido para uso fora do estabelecimento', 
        'Retorno de Mercadoria Remetida para Industrialização / Beneficiamento (Cfops:1902/2902 e 1925/2925)', 
        'Industrialização Efetuada e Retorno Remetido para Industrialização (1124/2124 e 1902/2902)', 
        'Retorno de Mercadorias Remetida para Industrialização Não Aplicada', 
        'Entrada de Mercadorias Recebida para Depósito', 
        'Entrada de Remessa para Armazenagem (Com Movimento de Estoque - TORO)', 
        'Entrada de Retorno de Mercadoria remetida para Dep. ou Armazém (Movimento Estoque)', 
        'Entrada de bem por conta de contrato de comodato', 
        'Entrada de bem por conta de contrato de comodato', 
        'Retorno de Remessa de Mercadorias para Conserto', 
        'Retorno de Remessa de Mercadorias para Conserto', 
        'Devolução de Mercadoria recebida em consignação (Saldo estoque)', 
        'Devolução Simbolica recebida em Consignação (produtos vendidos)', 
        'Industrialização Efetuada e Retorno Remetido para Industrialização (1925/2925 e 1125/2125)', 
        'Retorno de Mercadoria Remetida para Industrialização / Beneficiamento (Cfops:1902/2902 e 1925/2925)', 
        'Outras Entradas  Remessa para Industrialização - Simbólica', 
        'Outras Entradas  Remessa para Industrialização - Simbólica',
        'Venda de mercadorias Consumidor Final / Não Contribuintes (Origem TO/RO)', 
        'Venda de Mercadorias para Orgão Público (TO / RO)', 
        'Venda de Mercadoria TO /RO ', 
        'Venda de Mercadorias por Conta e  Ordem de Terceiro (TO / RO)', 
        'Venda de Mercadorias por Conta e  Ordem de Terceiro (TO / RO) ZFM', 
        'Industrialização Efetuada por Outra Empresa (Saída)', 
        'Remessa de bem do ativo imobilizado para uso fora do estabelecimento', 
        'Remessa para Industrialização por Encomenda - Beneficiamento', 
        'Remessa ou Depósito para Armazenagem (Com Movto de Estoque)', 
        'Remessa ou Depósito para Armazenagem (Sem Movto de Estoque)', 
        'Remessa ou Depósito para Armazenagem (Sem Movto de Estoque)', 
        'Retorno de Mercadoria Depositada em Depósito Fechado ou Armazém (Com Mov. Estoque) ', 
        'RETORNO DE COMODATO', 
        'Remessa de Mercadorias para Bonificação Doação e Brinde (TO/RO)', 
        'Remessa de amostra grátis ( Origem TO/RO)', 
        'Remessa de amostra grátis ( Origem TO/RO)ZFM', 
        'Remessa de Mercadorias para Conserto ou Reparo ', 
        'Remessa em Consignação (Origem SP) ', 
        'Remessa de Mercadoria por Conta e Ordem de Terceiros (MN Armazem - Amostra Grátis)', 
        'Remessa de Mercadoria por Conta e Ordem de Terceiros (Mn Armazem)', 
        'Outras Saidas Remessa para Industrialização - Simbólica ', 
        'Venda de Mercadorias para Orgão Público (TO / RO)', 
        'Venda de Mercadoria TO /RO ', 
        'Venda Zona franca De Manaus TORO', 
        'Venda de mercadorias Consumidor Final / Não Contribuintes (Origem TO/RO)', 
        'Venda de mercadorias Consumidor Final / Não Contribuintes (Origem TO/RO)', 
        'Venda de Mercadorias para Orgão Público (TO / RO)', 
        'Venda Zona franca De Manaus TORO', 
        'Venda de mercadorias Consumidor Final / Não Contribuintes (Origem TO/RO)ZFM', 
        'Venda de mercadoria de encomenda para entrega futura (Origem TORO)', 
        'Venda de Mercadorias por Conta e  Ordem de Terceiro (TO / RO)', 
        'Venda de Mercadorias por Conta e  Ordem de Terceiro (TO / RO) ZFM', 
        'Industrialização Efetuada por Outra Empresa (Saída)', 
        'Transferência de Mercadorias entre Estabelecimentos (Origem de Tocantins/Rondonia)', 
        'Remessa de bem do ativo imobilizado para uso fora do estabelecimento', 
        'Remessa para Industrialização por Encomenda - Beneficiamento', 
        'Remessa ou Depósito para Armazenagem (Com Movto de Estoque)', 
        'Remessa ou Depósito para Armazenagem (Sem Movto de Estoque)', 
        'Remessa ou Depósito para Armazenagem (Sem Movto de Estoque)', 
        'Retorno de Mercadoria Depositada em Depósito Fechado ou Armazém (Com Mov. Estoque) ', 
        'RETORNO DE COMODATO', 
        'Remessa de Mercadorias para Bonificação Doação e Brinde (TO/RO)', 
        'Remessa de amostra grátis ( Origem TO/RO)', 
        'Remessa de amostra grátis ( Origem TO/RO)ZFM', 
        'Remessa de Mercadorias para Conserto ou Reparo ', 
        'Remessa em Consignação (Origem SP) ', 
        'Remessa de Mercadoria por Conta e Ordem de Terceiros (MN Armazem - Amostra Grátis)', 
        'Remessa de Mercadoria por Conta e Ordem de Terceiros (Mn Armazem)', 
        'Outras Saidas Remessa para Industrialização - Simbólica ',
    ];

    public function dialog(Request $request){
        $fields = $request->only('estabel', 'codigo', 'inicial', 'fim');
        $movimento_estoque = $this->movimentoEstoque($fields['estabel'], $fields['codigo'],$fields['inicial'], $fields['fim']);
        return view('programs.movimento_estoque.dialog')->with(['movimento_estoque' => $movimento_estoque]);
    }

    public static function checarSeTeveMovimento($estabelecimento, $codigo_produto, $data_inicial){
        $data_final = date('Y-m-d');
        $sql_movimento_estoque = "select count(*) as c from integracoes.exportar_produtos_movimentacoes('{$estabelecimento}', '{$data_inicial}', '{$data_final}, '{$codigo_produto}'')";
        try{
            $movimentos = DB::connection('nasajon')->select($sql_movimento_estoque);
        }catch(\Exception $e){
            return false;
        }
        $movimentos = reset($movimentos);
        if($movimentos->c > 0){
            return true;
        }else{
            return false;
        }

    }

    private function estoqueFinalPrologos($estabelecimento, $codigo){
        $query = Estoque::select('CODPRD', 'EST_PRATELEIRA', 'EST_DEPOSITO', 'EMPENHO', 'ESTABEL');
        $query->where('CODPRD', $codigo);
        $query->where('ESTABEL', $estabelecimento);
        $query = $query->first();
        
        if(empty($query)){
            $result = 0.0;
        }else{
            $result = ($query->EST_PRATELEIRA + $query->EST_DEPOSITO) - $query->EMPENHO;
        }
        
        return parserValor($result);
    }

    public function movimentoEstoque($estabelecimento, $codigo, $data_inicial, $data_final, $first = false){
        $sql_movimento_estoque = "select sum(quantidade) as quantidade, estabelecimento_codigo, produto_codigo, data, data_criacao, sinal, origem, documento_id, documento_numero, cliente_codigo, slot, efetivado from integracoes.exportar_produtos_movimentacoes('{$estabelecimento}', '{$data_inicial}', '{$data_final}', '{$codigo}') group by  estabelecimento_codigo, produto_codigo, data, data_criacao, sinal, origem, documento_id, documento_numero, cliente_codigo, slot, efetivado order by data, sinal";
        if($first){
            $sql_movimento_estoque = $sql_movimento_estoque." limit 1;";
        }else{
            $sql_movimento_estoque = $sql_movimento_estoque.";";
        }
        try{
            $movimentos = DB::connection('nasajon')->select($sql_movimento_estoque); 
        }catch(\Exception $e){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => $e,
                'response' => []
            ];
        }
        $movimento_estoque = [];
        $saldo = 0;

        $notas = NotasNasajon::with('itens_nota', 'pedido')->whereIn('id', collect($movimentos)->pluck('documento_id'))->get();
        $nota_entradas = NotasEntradasNasajon::whereIn('Identificador Documento', collect($movimentos)->pluck('documento_id'))->get();

        $saldo_entrada = 0;
        $saldo_saida = 0;
        $total_fiscal = 0;
        $total_em_terceiros = 0;
        $total_armazem = 0;

        $fiscal_saldo = 0;
        $em_terceiros_saldo = 0;
        $armazem_saldo = 0;

        foreach($movimentos as $movimento){
            $fiscal_quantidade = 0;
            $em_terceiros_quantidade = 0;
            $armazem_quantidade = 0;
            switch($movimento->sinal){
                case 'SAÍDA':
                    $saldo = $saldo - floatval($movimento->quantidade);
                    $saldo_saida += floatval($movimento->quantidade);
                    if($movimento->slot == 'PROP-EMPODERTERC'){
                        if(($movimento->efetivado && in_array($estabelecimento, ['03', '04'])) || ($movimento->cliente_codigo === $this->codigo_cliente_armazem)){
                            $armazem_quantidade = (-1) * $movimento->quantidade;
                            $armazem_saldo -= $movimento->quantidade;
                            $total_armazem -= $movimento->quantidade;
                        }else{
                            $em_terceiros_quantidade = (-1) * $movimento->quantidade;
                            $em_terceiros_saldo -= $movimento->quantidade;
                            $total_em_terceiros -= $movimento->quantidade;
                        }
                    }else{
                        $fiscal_quantidade = (-1) * $movimento->quantidade;
                        $total_fiscal -= $movimento->quantidade;
                        $fiscal_saldo -= $movimento->quantidade;
                    }
                    break;
                case 'ENTRADA':
                    $saldo = $saldo + floatval($movimento->quantidade);
                    $saldo_entrada += floatval($movimento->quantidade);
                    if($movimento->slot == 'PROP-EMPODERTERC'){
                        if(($movimento->efetivado && in_array($estabelecimento, ['03', '04'])) || ($movimento->cliente_codigo === $this->codigo_cliente_armazem)){
                            $armazem_quantidade = $movimento->quantidade;
                            $armazem_saldo += $movimento->quantidade;
                            $total_armazem += $movimento->quantidade;
                        }else{
                            $em_terceiros_quantidade = $movimento->quantidade;
                            $em_terceiros_saldo += $movimento->quantidade;
                            $total_em_terceiros += $movimento->quantidade;
                        }
                    }else{
                        $fiscal_quantidade = $movimento->quantidade;
                        $total_fiscal += $movimento->quantidade;
                        $fiscal_saldo += $movimento->quantidade;
                    }
                    break;
            }

            $nota = $notas->firstWhere('id', $movimento->documento_id);
            $nota_entrada = $nota_entradas->firstWhere('Identificador Documento', $movimento->documento_id);
            
            if(!is_null($nota)){
                $documento = "<a class='exibir-nota' href='#' data-id='" . $movimento->documento_id . "'>" . $movimento->documento_numero . "</a>";
            }
            else if(!empty($nota_entrada)){
                $documento = "<a class='exibir-nota-entrada' href='#' data-id='" . $movimento->documento_id . "'>" . $movimento->documento_numero . "</a>";
            }
            else{
                $documento = $movimento->documento_numero;
            }

            $cliente_fornecedor = '';
            if (!is_null($nota)){
                $cliente_fornecedor =  $nota->cliente_nome;
            }
            else if(!empty($nota_entrada)){
                $cliente_fornecedor = $nota_entrada["Nome do Fornecedor"];
            }

            $movimento_estoque [] = [
                'origem' => $movimento->origem,
                'documento' => $documento,
                'cliente_fornecedor' => $cliente_fornecedor,
                'data_atualizacao' => parserDataEHora($movimento->data_criacao),
                'data' => parserData($movimento->data),
                'tipo' => $movimento->sinal,
                
                'fiscal_quantidade' => empty($fiscal_quantidade) ? '' : parserQtd($fiscal_quantidade),
                'fiscal_saldo' => !empty($fiscal_saldo) && empty($em_terceiros_quantidade) && empty($armazem_quantidade) ? parserQtd($fiscal_saldo) : '',

                'em_terceiros_quantidade' => empty($em_terceiros_quantidade)? '' : parserQtd($em_terceiros_quantidade),
                'em_terceiros_saldo' =>  empty($fiscal_quantidade) && !empty($em_terceiros_saldo) && empty($armazem_quantidade) ? parserQtd($em_terceiros_saldo) : '',

                'armazem_quantidade' => empty($armazem_quantidade)? '' : parserQtd($armazem_quantidade),
                'armazem_saldo' => empty($fiscal_quantidade) && empty($em_terceiros_quantidade) && !empty($armazem_saldo) ? parserQtd($armazem_saldo) : '',
                
            ];
        }

        $retorno = [
            'movimento_estoque' => $movimento_estoque,
            'saldo' => intval($estabelecimento) == 3 || intval($estabelecimento) == 4? parserQtd($total_armazem) : parserQtd($total_fiscal),
            'saldo_entrada' => parserQtd($saldo_entrada),
            'saldo_saida' => parserQtd($saldo_saida),
            'saldo_fiscal' => empty($total_fiscal)? '' : parserQtd($total_fiscal),
            'saldo_em_terceiros' => empty($total_em_terceiros)? '' : parserQtd($total_em_terceiros),
            'saldo_armazem' => empty($total_armazem)? '' : parserQtd($total_armazem),
        ];

        return $retorno;
    }

    private function somaPecas($estabelecimento, $codigo){
        $query = SaldoPecasEnviada::select();
        $query->where('empresa', $estabelecimento);
        $query->where('produto', $codigo);
        $peças_total = $query->sum('quantidades');
        return empty($peças_total)? '' : parserQtd($peças_total);
    }

    public function movimentacaoPortal(Request $request){
        $field = $request->only('codigo', 'estabel');

        $MovimentacaoObj = Movimentacao::with(['cliente', 'fornecedor', 'estabelecimento_detalhe'])
            ->where('produto_codigo', $field['codigo'])
            ->where('estabelecimento', $field['estabel'])
          //  ->orderBy('estabelecimento', 'produto_codigo', 'data_movimentacao', 'tipo_operacao', 'id')
          ->orderBy('estabelecimento', 'asc')
            ->orderBy('produto_codigo', 'asc')
            ->orderBy('data_movimentacao', 'asc')
            ->orderBy('tipo_operacao', 'asc')
            ->orderBy('id', 'asc')
            ->get(); 
            
        $movimentos = [];
        $saldo_armazem = 0;
        $saldo_fiscal = 0;
        $custo_armazem_saldo_anterior =0;
        $custo_armazem_valor_anterior =0;
        $saldo_custo_armazem = 0;
        foreach($MovimentacaoObj as $movimento){
            $documento = $movimento->documento;
            if(Carbon::parse($movimento->data_movimentacao)->gte('2019-07-07') && !empty($documento)){
                if($movimento->sinal == 'ENTRADA'){
                    $nota = NotasEntradasNasajon::query()->
                        where('Estabelecimento', $movimento->estabelecimento)->
                        where('Número do Documento', $movimento->documento)->
                        where('Fornecedor', $movimento->cliente_codigo)->
                        first();
                    if(!empty($nota)){
                        $documento = "<a class='exibir-nota-entrada' href='#' data-id='" . $nota['Identificador Documento'] . "'>" . $movimento->documento . "</a>";
                    }else{
                        $empresa = $movimento->estabelecimento_detalhe->raizcnpj.$movimento->estabelecimento_detalhe->ordemcnpj;
                        $empresa = mask($empresa, '##.###.###/####-##');
                        $nota = NotasNasajon::query()->
                            where('cliente_documento', $empresa)->
                            where('numero', $movimento->documento)->
                            first();
                        if(!empty($nota)){
                            $documento = "<a class='exibir-nota' href='#' data-id='" . $nota->id . "'>" . $movimento->documento . "</a>";
                        }
                    }
                }else{
                    $nota = NotasNasajon::query()->
                        where('estabelecimento_codigo', $movimento->estabelecimento)->
                        where('numero', $movimento->documento)->first();
                    if(!empty($nota)){
                        $documento = "<a class='exibir-nota' href='#' data-id='" . $nota->id . "'>" . $movimento->documento . "</a>";
                    }
                }
            }
            else{
                if($movimento->sinal == 'ENTRADA'){
                    $documento = $movimento->documento;
                }
                else{
                    $documento = $movimento->documento;

                }
            }
              

            if(in_array($field['estabel'], ['03', '04'])){
                if(Carbon::parse($movimento->data_movimentacao)->gte('2019-07-07')){
                    if((substr($movimento->cfop, 0, 1) == '6' || substr($movimento->cfop, 0, 1) == '0') && !($movimento->cfop == '6905' && $movimento->sinal == 'SAIDA')){
                        if($movimento->sinal == 'ENTRADA'){
                            $saldo_armazem += $movimento->quantidade;
                        }else{
                            $saldo_armazem -= $movimento->quantidade;
                        }
                    }else{
                        if($movimento->sinal == 'ENTRADA'){
                            $saldo_fiscal += $movimento->quantidade;
                        }else{
                            $saldo_fiscal -= $movimento->quantidade;
                        }
                    }
                }
   
                $movimentos[] = [
                    'id' => encrypt($movimento->id),
                    'data' => parserData($movimento->data_movimentacao),
                    'documento' => $documento,
                    'cfop' => $movimento->cfop,

                    'preco_nota' => parserValor($movimento->preco),
                    'preco_pcmn' => parserValor($movimento->preco_pcmn),
                    'preco_composicao' => parserValor($movimento->preco_composicao),

                    'aliquota' => $movimento->aliquota,

                    'quantidade_nota' => (substr($movimento->cfop, 0, 1) == '6' || substr($movimento->cfop, 0, 1) == '0') && !($movimento->cfop == '6905' && $movimento->sinal == 'SAIDA') && Carbon::parse($movimento->data_movimentacao)->gte('2019-07-07')? parserQtd($movimento->quantidade) : '',
                    'quantidade_saldo' => empty($saldo_armazem)? '' : parserQtd($saldo_armazem),

                    'quantidade_nota_fiscal' => (substr($movimento->cfop, 0, 1) != '6' && substr($movimento->cfop, 0, 1) != '0') || ($movimento->cfop == '6905' && $movimento->sinal == 'SAIDA') && Carbon::parse($movimento->data_movimentacao)->gte('2019-07-07')? parserQtd($movimento->quantidade) : '',
                    'quantidade_saldo_fiscal' => empty($saldo_fiscal)? '' : parserQtd($saldo_fiscal),

                    'quantidade_saldo_total' => parserQtd($movimento->saldo_movimentos),

                    'custo_contabil_valor' => parserValor($movimento->custo_sem_imposto),
                    'custo_contabil_saldo' => parserValor($movimento->custo_sem_imposto * $movimento->saldo_movimentos),

                    'custo_mediogerencial_valor' => parserValor($movimento->custo_pcmn),
                    'custo_mediogerencial_saldo' => parserValor($movimento->custo_pcmn * $movimento->saldo_movimentos),

                    'custo_gerencial_valor' => parserValor($movimento->preco_pcmn),
                    'custo_gerencial_saldo' => parserValor($movimento->preco_pcmn * $movimento->saldo_movimentos),
                    'tipo' => $movimento->sinal,
                    'unidade' => $movimento->unidade,
                    'custo_armazem' => empty($movimento->custo_armazem)? '' :  parserValor($movimento->custo_armazem),
                    'custo_armazem_saldo' => empty($movimento->custo_armazem)? '' : parserQtd($movimento->custo_armazem * $movimento->saldo_movimentos),

                ];
            }else{
                $movimentos[] = [
                    'id' => encrypt($movimento->id),
                    'data' => parserData($movimento->data_movimentacao),
                    'documento' => $documento,
                    'cfop' => $movimento->cfop,

                    'preco_nota' => parserValor($movimento->preco),
                    'preco_pcmn' => parserValor($movimento->preco_pcmn),
                    'preco_composicao' => parserValor($movimento->preco_composicao),

                    'aliquota' => $movimento->aliquota,

                    'quantidade_nota' => parserQtd($movimento->quantidade),
                    'quantidade_saldo' => parserQtd($movimento->saldo_movimentos),

                    'quantidade_nota_fiscal' => '',
                    'quantidade_saldo_fiscal' => '',

                    'custo_contabil_valor' => parserValor($movimento->custo_sem_imposto),
                    'custo_contabil_saldo' => parserValor($movimento->custo_sem_imposto * $movimento->saldo_movimentos),

                    'custo_mediogerencial_valor' => parserValor($movimento->custo_pcmn),
                    'custo_mediogerencial_saldo' => parserValor($movimento->custo_pcmn * $movimento->saldo_movimentos),

                    'custo_gerencial_valor' => parserValor($movimento->preco_pcmn),
                    'custo_gerencial_saldo' => parserValor($movimento->preco_pcmn * $movimento->saldo_movimentos),

                    'tipo' => $movimento->sinal,
                    'unidade' => $movimento->unidade,
                    'custo_armazem' => empty($movimento->custo_armazem)? '' :  parserValor($movimento->custo_armazem),
                    'custo_armazem_saldo' => empty($movimento->custo_armazem)? '' : parserQtd($movimento->custo_armazem * $movimento->saldo_movimentos),
                ];
            }
        }

        $ProdutoOjb = ProdutoEspecificacao::with(['custos' => function($query) use ($field){
                $query->where('estabelecimento', $field['estabel']);
            }])->
            where('codigo_produto', $field['codigo'])->
            first();
        unset($MovimentacaoObj);
        $dados = [
            'custo_medio_gerencial' => '',
            'custo_medio_contabil' => '',
            'unidade' => $ProdutoOjb->unidade,
            'estabelecimento' => $field['estabel'],
        ];
        if(!empty($ProdutoOjb->custos)){
            $custos = $ProdutoOjb->custos->first();
            if(!empty($custos)){
                $dados['custo_medio_contabil'] = parserValor($custos->custo_medio_contabil);
                $dados['custo_medio_gerencial'] = parserValor($custos->custo_medio_gerencial);
            }
        }
        return view('programs.movimento_estoque.dialog_portal')->with(['movimentos' => $movimentos, 'dados' => $dados]);
        
    }
    
    public function movimentacaoPortalGrupo(Request $request){
        $field = $request->only('codigos');
        $codigos = $field['codigos'];
        $produtos = [];
        $produtosEspecificaoObj = ProdutoEspecificacao::whereIn('codigo_produto', $codigos)->get();
        $produtosEspecificaoObj->each(function($produto) use (&$produtos){
            $produtos[] = [
                'codigo' => $produto->codigo_produto,
                'descricao' => $produto->descricao
            ];
        });
        return view('programs.movimento_estoque.produtos_movimentos')->with(['produtos' => $produtos]);
    }

    public function movimentacaoPortalEstabelecimentos(Request $request){
        $field = $request->only('codigo');
        $MovimentacaoObj = Movimentacao::with(['estabelecimento_detalhe'])
            ->where('produto_codigo', $field['codigo'])
            ->orderBy('estabelecimento', 'produto_codigo', 'data_movimentacao desc', 'tipo_operacao', 'id')
            ->get();
        $estabelecimentos = [];
        $movimentos = [];
        $empresas = returnEmpresasNasajonView();

        foreach($MovimentacaoObj as $movimento){
            $documento = $movimento->documento;
            if(Carbon::parse($movimento->data_movimentacao)->gte('2019-07-07') && !empty($documento)){
                if($movimento->sinal == 'ENTRADA'){
                    $nota = NotasEntradasNasajon::query()->
                        where('Estabelecimento', $movimento->estabelecimento)->
                        where('Número do Documento', $movimento->documento)->
                        first();
                    if(!empty($nota)){
                        $documento = "<a class='exibir-nota-entrada' href='#' data-id='" . $nota['Identificador Documento'] . "'>" . $movimento->documento . "</a>";
                    }else{
                        $empresa = $movimento->estabelecimento_detalhe->raizcnpj.$movimento->estabelecimento_detalhe->ordemcnpj;
                        $empresa = mask($empresa, '##.###.###/####-##');
                        $nota = NotasNasajon::query()->
                            where('cliente_documento', $empresa)->
                            where('numero', $movimento->documento)->
                            first();
                        if(!empty($nota)){
                            $documento = "<a class='exibir-nota' href='#' data-id='" . $nota->id . "'>" . $movimento->documento . "</a>";
                        }
                    }
                }else{
                    $nota = NotasNasajon::query()->
                        where('estabelecimento_codigo', $movimento->estabelecimento)->
                        where('numero', $movimento->documento)->first();
                    if(!empty($nota)){
                        $documento = "<a class='exibir-nota' href='#' data-id='" . $nota->id . "'>" . $movimento->documento . "</a>";
                    }
                }
            }
            else{
                if($movimento->sinal == 'ENTRADA'){
                    $documento = $movimento->documento;
                }
                else{
                    $documento = $movimento->documento;

                }
            }
            if(!isset($estabelecimentos[$movimento->estabelecimento])){
                $estabelecimentos[$movimento->estabelecimento] = $empresas[intval($movimento->estabelecimento)];
            }
            $data_movimento = Carbon::parse($movimento->data_movimentacao);
      
           
            $movimentos[$movimento->estabelecimento][] = [
                'id' => encrypt($movimento->id),

                'data' => ($data_movimento->format('d/m/Y')),
                'data_ordem' => ($data_movimento->format('Ymd')),
                
                'documento' => $documento,
                'cfop' => $movimento->cfop,

                'preco_nota' => parserValor($movimento->preco),
                'preco_pcmn' => parserValor($movimento->preco_pcmn),
                'preco_composicao' => parserValor($movimento->preco_composicao),

                'aliquota' => $movimento->aliquota,

                'quantidade_nota' => parserQtd($movimento->quantidade),
                'quantidade_saldo' => parserQtd($movimento->saldo_movimentos),

                'custo_contabil_valor' => parserValor($movimento->custo_sem_imposto),
                'custo_contabil_saldo' => parserValor($movimento->custo_sem_imposto * $movimento->saldo_movimentos),

                'custo_mediogerencial_valor' => parserValor($movimento->custo_pcmn),
                'custo_mediogerencial_saldo' => parserValor($movimento->custo_pcmn * $movimento->saldo_movimentos),

                'custo_gerencial_valor' => parserValor($movimento->preco_pcmn),
                'custo_gerencial_saldo' => parserValor($movimento->preco_pcmn * $movimento->saldo_movimentos),

                'tipo' => $movimento->sinal,
                'unidade' => $movimento->unidade,
                'custo_armazem' => empty($movimento->custo_armazem)? '' :  parserValor($movimento->custo_armazem),
                'custo_armazem_saldo' => empty($movimento->custo_armazem)? '' : parserQtd($movimento->custo_armazem * $movimento->saldo_movimentos),
            ];
        }
        ksort($estabelecimentos);
        return view('programs.movimento_estoque.dialog_portal_estabelecimentos')->with(['movimentos' => $movimentos, 'estabelecimentos' => $estabelecimentos]);
        
    }

    public function movimentoEstoqueTerceiro(Request $request){
        $fields = $request->only('estabel', 'codigo', 'inicial', 'fim');

        $estabelecimento = $fields['estabel'];
        $codigo = $fields['codigo'];
        $data_inicial = $fields['inicial'];
        $data_final = $fields['fim'];

        $sql_movimento_estoque = "select sum(quantidade) as quantidade, estabelecimento_codigo, produto_codigo, data, data_criacao, sinal, origem, documento_id, documento_numero, cliente_codigo, slot, efetivado from integracoes.exportar_produtos_movimentacoes('{$estabelecimento}', '{$data_inicial}', '{$data_final}', '{$codigo}') group by  estabelecimento_codigo, produto_codigo, data, data_criacao, sinal, origem, documento_id, documento_numero, cliente_codigo, slot, efetivado order by data, sinal";

        try{
            $movimentos = DB::connection('nasajon')->select($sql_movimento_estoque); 
        }catch(\Exception $e){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => $e,
                'response' => []
            ];
        }
        $movimento_estoque = [];
        $saldo = 0;

        $notas = NotasNasajon::with('primeiro_itens_nota')->whereIn('id', collect($movimentos)->pluck('documento_id'))->get();
        $nota_entradas = NotasEntradasNasajon::with('primeiro_itens_nota')->whereIn('Identificador Documento', collect($movimentos)->pluck('documento_id'))->get();

        $saldo_entrada = 0;
        $saldo_saida = 0;
        $total_fiscal = 0;
        $total_em_terceiros = 0;
        $total_armazem = 0;

        $fiscal_saldo = 0;
        $em_terceiros_saldo = 0;
        $armazem_saldo = 0;

        foreach($movimentos as $movimento){
            if($movimento->slot == 'PROP-EMPODERTERC' && $movimento->origem != 'ACERTO DE SALDO'){
                $nota = $notas->firstWhere('id', $movimento->documento_id);
                $nota_entrada = $nota_entradas->firstWhere('Identificador Documento', $movimento->documento_id);

                if(!is_null($nota)){
                    try{
                        $documento = "<a class='exibir-nota' href='#' data-id='" . $movimento->documento_id . "'>" . $movimento->documento_numero . "</a>";
                        $cfop = empty($nota->primeiro_itens_nota)? '' : $nota->primeiro_itens_nota->cfop;
                    }catch(\Exception $e){
                        $teste = $nota->toArray();
                    }
                }
                else if(!empty($nota_entrada)){
                    try{
                        $documento = "<a class='exibir-nota-entrada' href='#' data-id='" . $movimento->documento_id . "'>" . $movimento->documento_numero . "</a>";
                        $cfop = empty($nota_entrada->primeiro_itens_nota)? '' : $nota_entrada->primeiro_itens_nota->cfop;
                    }catch(\Exception $e){
                        $teste = $nota_entrada->toArray();
                    }
                }else{
                    $documento = $movimento->documento_numero;
                    $cfop = '';
                }

                //if(($movimento->sinal == 'ENTRADA' && ($cfop == '5901' || $cfop == '6901')) || ($movimento->sinal == 'ENTRADA' && ($cfop == '1902' || $cfop == '2902'))){
                    $fiscal_quantidade = 0;
                    $em_terceiros_quantidade = 0;
                    $armazem_quantidade = 0;
                    switch($movimento->sinal){
                        case 'SAÍDA':
                            $saldo = $saldo - floatval($movimento->quantidade);
                            $saldo_saida += floatval($movimento->quantidade);
                            if($movimento->slot == 'PROP-EMPODERTERC'){
                                if(($movimento->efetivado && in_array($estabelecimento, ['03', '04'])) || ($movimento->cliente_codigo === $this->codigo_cliente_armazem)){
                                    $armazem_quantidade = (-1) * $movimento->quantidade;
                                    $armazem_saldo -= $movimento->quantidade;
                                    $total_armazem -= $movimento->quantidade;
                                }else{
                                    $em_terceiros_quantidade = (-1) * $movimento->quantidade;
                                    $em_terceiros_saldo -= $movimento->quantidade;
                                    $total_em_terceiros -= $movimento->quantidade;
                                }
                            }
                            break;
                        case 'ENTRADA':
                            $saldo = $saldo + floatval($movimento->quantidade);
                            $saldo_entrada += floatval($movimento->quantidade);
                            if($movimento->slot == 'PROP-EMPODERTERC'){
                                if(($movimento->efetivado && in_array($estabelecimento, ['03', '04'])) || ($movimento->cliente_codigo === $this->codigo_cliente_armazem)){
                                    $armazem_quantidade = $movimento->quantidade;
                                    $armazem_saldo += $movimento->quantidade;
                                    $total_armazem += $movimento->quantidade;
                                }else{
                                    $em_terceiros_quantidade = $movimento->quantidade;
                                    $em_terceiros_saldo +=  $movimento->quantidade;
                                    $total_em_terceiros +=  $movimento->quantidade;
                                }
                            }
                            break;
                    }

                    $cliente_fornecedor = '';
                    if (!is_null($nota)){
                        $cliente_fornecedor =  $nota->cliente_nome;
                    }
                    else if(!empty($nota_entrada)){
                        $cliente_fornecedor = $nota_entrada["Nome do Fornecedor"];
                    }

                    $tipo = '' ;
                    if($movimento->origem == 'ACERTO DE SALDO'){
                        $tipo = $movimento->sinal ;
                    }else{
                        $tipo = $movimento->sinal == 'ENTRADA'? 'Remessa' : 'Retorno';
                    }

                    $movimento_estoque [] = [
                        'origem' => $movimento->origem,
                        'documento' => $documento,
                        'cliente_fornecedor' => $cliente_fornecedor == 'FRESNO IND E COM DE VESTUARIO E ACESSORIOS TEXTIL* DUPLICADO'? 'FRESNO IND E COM DE VESTUARIO E ACESSORIOS TEXTIL EIRELI' : $cliente_fornecedor,
                        'data_atualizacao' => parserDataEHora($movimento->data_criacao),
                        'data' => parserData($movimento->data),
                        'tipo' => $tipo,
                        'cfop' => $cfop,
                        
                        'fiscal_quantidade' => empty($fiscal_quantidade) ? '' : parserQtd($fiscal_quantidade),
                        'fiscal_saldo' => !empty($fiscal_saldo) && empty($em_terceiros_quantidade) && empty($armazem_quantidade) ? parserQtd($fiscal_saldo) : '',

                        'em_terceiros_quantidade' => empty($em_terceiros_quantidade)? '' : parserQtd($em_terceiros_quantidade),
                        'em_terceiros_saldo' =>  empty($fiscal_quantidade) && !empty($em_terceiros_saldo) && empty($armazem_quantidade) ? parserQtd($em_terceiros_saldo) : '',

                        'armazem_quantidade' => empty($armazem_quantidade)? '' : parserQtd($armazem_quantidade),
                        'armazem_saldo' => empty($fiscal_quantidade) && empty($em_terceiros_quantidade) && !empty($armazem_saldo) ? parserQtd($armazem_saldo) : '',
                        
                    ];
                //}
            }
        }

        $estoquePoderTerceiroObj = EstoquePoderTerceiro::select();
        $estoquePoderTerceiroObj->where('produto_codigo', 'ilike', $codigo);
        $estoquePoderTerceiroObj->where('saldo_em_terceiro', '>', 0);
        $saldo_nasajon = $estoquePoderTerceiroObj->sum('saldo_em_terceiro');

        $retorno = [
            'movimento_estoque' => $movimento_estoque,
            'saldo' => intval($estabelecimento) == 3 || intval($estabelecimento) == 4? parserQtd($total_armazem) : parserQtd($total_fiscal),
            'saldo_entrada' => parserQtd($saldo_entrada),
            'saldo_saida' => parserQtd($saldo_saida),
            'saldo_em_terceiros' => empty($total_em_terceiros)? '' : parserQtd($total_em_terceiros),
            'saldo_nasajon' => empty($saldo_nasajon)? '' : parserValor($saldo_nasajon),
        ];

        return view('programs.movimento_estoque.dialog_em_terceiros')->with(['movimento_estoque' => $retorno]);
    }

    public function movimentoEstoqueTerceiroFornecedor(Request $request){
        $field = $request->only('codigo', 'fornecedor_codigo');
        $MovimentacaoObj = Movimentacao::with(['fornecedor', 'estabelecimento_detalhe'])
            ->where('produto_codigo', $field['codigo'])
            ->where('cliente_codigo', $field['fornecedor_codigo'])
            ->where(function($query){
                $query->where(function($query){
                    $query->whereIn('cfop', $this->cfop_terceiro_entrada);
                    $query->where('sinal', 'SAIDA');
                });
                $query->orWhere(function($query){
                    $query->whereIn('cfop', $this->cfop_terceiro_saida);
                    $query->where('sinal', 'ENTRADA');
                });
            })
            ->orderBy('estabelecimento', 'asc')
            ->orderBy('produto_codigo', 'asc')
            ->orderBy('data_movimentacao', 'asc')
            ->orderBy('tipo_operacao', 'asc')
            ->orderBy('id', 'asc')
            ->get(); 

        $movimento_estoque = [];
        $saldo = 0;

        $saldo_entrada = 0;
        $saldo_saida = 0;
        $total_fiscal = 0;
        $total_em_terceiros = 0;
        $total_armazem = 0;

        $fiscal_saldo = 0;
        $em_terceiros_saldo = 0;
        $armazem_saldo = 0;
        $teste = [];
        foreach($MovimentacaoObj as $movimento){
            $documento = $movimento->documento;
            if(empty($movimento_estoque[$documento])){
                $documento = $movimento->documento;
                $cfop = '';
    
                if($movimento->sinal == 'SAIDA'){
                    $nota = NotasEntradasNasajon::query()->
                        where('Estabelecimento', $movimento->estabelecimento)->
                        where('Número do Documento', $movimento->documento)->
                        where('Fornecedor', $movimento->cliente_codigo)->
                        whereIn('Descrição da Operação', $this->operacao_terceiro)->
                        first();
                    if(!empty($nota)){
                        $documento = "<a class='exibir-nota-entrada' href='#' data-id='" . $nota['Identificador Documento'] . "'>" . $movimento->documento . "</a>";
                    }else{
                        $nota = NotasNasajon::query()->
                            whereIn('operacao_descricao', $this->operacao_terceiro)->
                            where('estabelecimento_codigo', $movimento->estabelecimento)->
                            where('numero', $movimento->documento)->
                            first();
                        if(!empty($nota)){
                            $documento = "<a class='exibir-nota' href='#' data-id='" . $nota->id . "'>" . $movimento->documento . "</a>";
                        }
                    }
                }else{
                    $nota = NotasEntradasNasajon::query()->
                        where('Estabelecimento', $movimento->estabelecimento)->
                        where('Número do Documento', $movimento->documento)->
                        where('Fornecedor', $movimento->cliente_codigo)->
                        whereIn('Descrição da Operação', $this->operacao_terceiro)->
                        first();
                    if(!empty($nota)){
                        $documento = "<a class='exibir-nota-entrada' href='#' data-id='" . $nota['Identificador Documento'] . "'>" . $movimento->documento . "</a>";
                    }else{
                        $nota = NotasNasajon::query()->
                            whereIn('operacao_descricao', $this->operacao_terceiro)->
                            where('estabelecimento_codigo', $movimento->estabelecimento)->
                            where('numero', $movimento->documento)->
                            first();
                        if(!empty($nota)){
                            $documento = "<a class='exibir-nota' href='#' data-id='" . $nota->id . "'>" . $movimento->documento . "</a>";
                        }
                    }
                }
    
                //if(($movimento->sinal == 'ENTRADA' && ($cfop == '5901' || $cfop == '6901')) || ($movimento->sinal == 'ENTRADA' && ($cfop == '1902' || $cfop == '2902'))){
                    $fiscal_quantidade = 0;
                    $em_terceiros_quantidade = 0;
                    $armazem_quantidade = 0;
                    switch($movimento->sinal){
                        case 'SAIDA':
                            $saldo = $saldo - floatval($movimento->quantidade);
                            $saldo_saida += floatval($movimento->quantidade);
                            $em_terceiros_quantidade = (-1) * $movimento->quantidade;
                            $em_terceiros_saldo -= $movimento->quantidade;
                            $total_em_terceiros -= $movimento->quantidade;
                            break;
                        case 'ENTRADA':
                            $saldo = $saldo + floatval($movimento->quantidade);
                            $saldo_entrada += floatval($movimento->quantidade);
                            $em_terceiros_quantidade = $movimento->quantidade;
                            $em_terceiros_saldo +=  $movimento->quantidade;
                            $total_em_terceiros +=  $movimento->quantidade;
                            break;
                    }
    
                    $tipo = '' ;
                    if($movimento->origem == 'ACERTO DE SALDO'){
                        $tipo = $movimento->sinal ;
                    }else{
                        $tipo = $movimento->sinal == 'ENTRADA'? 'Remessa' : 'Retorno';
                    }
    
                    $movimento_estoque[$movimento->documento] = [
                        'origem' => "DOCUMENTO FISCAL",
                        'documento' => $documento,
                        'data_atualizacao' => parserDataEHora($movimento->data_criacao),
                        'data' => parserData($movimento->data_movimentacao),
                        'tipo' => $tipo,
                        'cfop' => $movimento->cfop,
                        
                        'fiscal_quantidade' => empty($fiscal_quantidade) ? '' : parserQtd($fiscal_quantidade),
                        'fiscal_saldo' => !empty($fiscal_saldo) && empty($em_terceiros_quantidade) && empty($armazem_quantidade) ? parserQtd($fiscal_saldo) : '',
    
                        'em_terceiros_quantidade' => empty($em_terceiros_quantidade)? '' : parserQtd($em_terceiros_quantidade),
                        'em_terceiros_saldo' =>  empty($fiscal_quantidade) && !empty($em_terceiros_saldo) && empty($armazem_quantidade) ? parserQtd($em_terceiros_saldo) : '',
    
                        'armazem_quantidade' => empty($armazem_quantidade)? '' : parserQtd($armazem_quantidade),
                        'armazem_saldo' => empty($fiscal_quantidade) && empty($em_terceiros_quantidade) && !empty($armazem_saldo) ? parserQtd($armazem_saldo) : '',
                        
                    ];
                //}
            }            
        }

        $estoquePoderTerceiroObj = EstoquePoderTerceiro::select();
        $estoquePoderTerceiroObj->where('fornecedor_codigo', $field['fornecedor_codigo']);
        $estoquePoderTerceiroObj->where('produto_codigo', 'ilike', $field['codigo']);
        $estoquePoderTerceiroObj->where('saldo_em_terceiro', '>', 0);
        $saldo_nasajon = $estoquePoderTerceiroObj->sum('saldo_em_terceiro');

        $retorno = [
            'movimento_estoque' => $movimento_estoque,
            'saldo_entrada' => parserQtd($saldo_entrada),
            'saldo_saida' => parserQtd($saldo_saida),
            'saldo_em_terceiros' => empty($total_em_terceiros)? '' : parserQtd($total_em_terceiros),
            'saldo_nasajon' => empty($saldo_nasajon)? '' : parserValor($saldo_nasajon),
        ];

        return view('programs.movimento_estoque.dialog_em_terceiro_fornecedor')->with(['movimento_estoque' => $retorno]);
    }
}
