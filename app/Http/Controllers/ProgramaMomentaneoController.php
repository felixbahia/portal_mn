<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\PedidoPortal;
use App\InformativoVendaComDesconto;
use App\PedidosVendaNasajon;
use App\FaturamentoNotaNasajon;
use App\CampanhasComissaoCalculo;
use App\VendedorNasajon;
use App\TituloPagamentoNasajon;
use App\User;
use App\ComissaoDataFechamento;
use App\ComissaoCampanhaOuro;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use App\Http\Requests\ComissaoDuplicatasFiltroRequest;

class ProgramaMomentaneoController extends Controller
{
    public function VendaDesconto(){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');

        $data_mes_inicial = Carbon::parse('2022-01-01 00:00:00')->firstOfMonth();
        $data_mes_final = Carbon::parse('2022-01-01 00:00:00')->lastOfMonth();

        $data_atual = Carbon::parse('2023-05-01 00:00:00')->lastOfMonth();

        $teste = [];

        while($data_atual->gte($data_mes_inicial)){
            $query_pedido = PedidoPortal::select()
            ->whereBetween('data_pedido', [$data_mes_inicial, $data_mes_final])
            ->where('status_pedido', 3)
            ->whereHas('itens_pedido', function($query){
                $query->whereColumn('preco_unitario', '<', 'coluna_a');
            })
            ->with(['itens_pedido' => function($query){
                $query->whereColumn('preco_unitario', '<', 'coluna_a');
            }])
            ->orderBy('data_pedido')->get();

            $retorno = [];
            foreach($query_pedido  as $pedido){
                $PedidosVendaNasajon = PedidosVendaNasajon::select();
                $PedidosVendaNasajon->where('numero', $pedido->pedido_gerado);
                $PedidosVendaNasajon->where('estabelecimento_codigo', $pedido->estabelecimento_pad);
                $PedidosVendaNasajon->where('operacao_codigo', $pedido->codigo_operacao);
                $PedidosVendaNasajon->where(function($query){
                    $query->orWhere('grupodeoperacao', 'VENDA');
                    $query->orWhereNull('grupodeoperacao');
                });
                $PedidosVendaNasajon = $PedidosVendaNasajon->first();
                foreach($pedido->itens_pedido as $item){
                    if($item->preco_unitario < $item->coluna_a){
                        if(!empty($PedidosVendaNasajon->nota->itens_nota)){
                            $item_nota = $PedidosVendaNasajon->nota->itens_nota->firstWhere('codigo', $item->cod_produto);
                            if(!empty($item_nota)){
                                $InformativoVendaComDesconto = new InformativoVendaComDesconto;
                                $InformativoVendaComDesconto->estabelecimento = str_pad($pedido->estabelecimento, 2, 0, STR_PAD_LEFT);
                                $InformativoVendaComDesconto->pedido_portal_numero = $item->pedido;
                                $InformativoVendaComDesconto->pedido_data_emissao = $pedido->data_pedido;
                                $InformativoVendaComDesconto->pedido_nasajon_numero = $PedidosVendaNasajon->numero;
                                $InformativoVendaComDesconto->nota_numero = $PedidosVendaNasajon->nota->numero;
                                $InformativoVendaComDesconto->vendedor_codigo = $pedido->usuario_detalhes->codigo_representante;
                                $InformativoVendaComDesconto->vendedor_nome = $pedido->usuario_detalhes->name;
                                $InformativoVendaComDesconto->produto_codigo = $item->cod_produto;
                                $InformativoVendaComDesconto->produto_descricao = $item->especificacoes->descricao;
                                $InformativoVendaComDesconto->faturamento_data = $PedidosVendaNasajon->nota->datasaida;
                                $InformativoVendaComDesconto->valor_unitario_sem_desconto = $item->coluna_a;
                                $InformativoVendaComDesconto->valor_unitario_real_faturado = $item_nota->valorunitariocomercial;
                                $InformativoVendaComDesconto->quantidade_vendida = $item_nota->quantidadecomercial;
                                $InformativoVendaComDesconto->save();
                            }                            
                        }                        
                    }                
                }
            }
            $data_mes_inicial = $data_mes_inicial->addMonth();
            $data_mes_final = $data_mes_final->addMonth();
        }
    }

    public function CorrecaoDeComissaoSupervisor(){

        $codigo_vendedor = '018';

        $FaturamentoNotaNasajon = FaturamentoNotaNasajon::select()
            ->where('Vendedor - Código', $codigo_vendedor)
            ->where('Data de Emissão', '>', '2022-10-01')
            ->where('Vendedor - Percentual Comissão', '>', '0.3')
            ->get();

        $VendedorNasajon = VendedorNasajon::select()->where('codigo', $codigo_vendedor)->first();

        $campanhas = [];
        foreach($FaturamentoNotaNasajon as $faturado){
            $CampanhasComissaoCalculo = CampanhasComissaoCalculo::select()->where('nota_id', $faturado['Identificador Documento'])->first();

            if(empty($CampanhasComissaoCalculo)){
                $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                    '{$faturado['Identificador Documento']}', /* id_nota */
                    '{$VendedorNasajon->id}', /* id_vendedor */
                    '100', /* participacao */
                    '0.30', /* comissao */
                    true /* vendedor_principal */
                );";
                $return = DB::connection('nasajon')->select($sql_comissao);
            }else{
                $campanhas[] = $CampanhasComissaoCalculo;
            }            
        }
        dd($campanhas);
    }

    public function SeparacaoComissaoCampanha(){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');
        $CampanhasComissaoCalculo =  CampanhasComissaoCalculo::select()->whereNotNull('nota_id')->whereNotNull('porcetagem_comissao_gerente')->get();
        foreach($CampanhasComissaoCalculo as $campanha){
            $TituloPagamentoNasajon = TituloPagamentoNasajon::select()->with(['comissaoVendedor'])->where('documento_id', $campanha->nota_id)->get();

            foreach($TituloPagamentoNasajon as $titulo){
                if(!empty($titulo->comissaoVendedor->vendedor_codigo)){
                    $User = User::select()->with(['supervisor', 'unidadeNegocioMetaUserUltimo.detalhesUnidadeNegocioMeta.detalhesUnidadeNegocio'])->where('codigo_representante', $titulo->comissaoVendedor->vendedor_codigo)->first();

                    $ComissaoDataFechamento = ComissaoDataFechamento::select()
                        ->where('data_inicio', '<=', $titulo->data_lancamento_pagamento)
                        ->where('data_fim', '>=', $titulo->data_lancamento_pagamento)
                        ->orderBy('data_inicio', 'desc')
                        ->first();
    
                    if(!empty($User->supervisor->codigo_representante)){
                        $ComissaoCampanhaOuro = new ComissaoCampanhaOuro;
                        $ComissaoCampanhaOuro->equipe = $User->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->unidade;
                        $ComissaoCampanhaOuro->vendedor_codigo = $User->supervisor->codigo_representante;
                        $ComissaoCampanhaOuro->vendedor_nome = $User->supervisor->name;
                        $ComissaoCampanhaOuro->titulo_valor = $titulo->valor;
                        $ComissaoCampanhaOuro->titulo_numero = $titulo->numero;
                        $ComissaoCampanhaOuro->comissao_gol_de_ouro = $titulo->valor * ($campanha->porcetagem_comissao_gerente/ 100);
                        $ComissaoCampanhaOuro->data_lancamento_pagamento = $titulo->data_lancamento_pagamento;
                        $ComissaoCampanhaOuro->periodo = $ComissaoDataFechamento->periodo;
                        $ComissaoCampanhaOuro->data_atualizacao = $campanha->updated_at;
                        $ComissaoCampanhaOuro->save();
    
                        $ComissaoCampanhaOuro = new ComissaoCampanhaOuro;
                        $ComissaoCampanhaOuro->equipe = $User->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->unidade;
                        $ComissaoCampanhaOuro->vendedor_codigo = $titulo->comissaoVendedor->vendedor_codigo;
                        $ComissaoCampanhaOuro->vendedor_nome = $User->name;
                        $ComissaoCampanhaOuro->titulo_valor = $titulo->valor;
                        $ComissaoCampanhaOuro->titulo_numero = $titulo->numero;
                        $ComissaoCampanhaOuro->comissao_gol_de_ouro = $titulo->valor * ($titulo->comissaoVendedor->percentual_comissao / 100);
                        $ComissaoCampanhaOuro->data_lancamento_pagamento = $titulo->data_lancamento_pagamento;
                        $ComissaoCampanhaOuro->periodo = $ComissaoDataFechamento->periodo;
                        $ComissaoCampanhaOuro->data_atualizacao = $campanha->updated_at;
                        $ComissaoCampanhaOuro->save();
                    }else{
                        $ComissaoCampanhaOuro = new ComissaoCampanhaOuro;
                        $ComissaoCampanhaOuro->equipe = $User->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->unidade;
                        $ComissaoCampanhaOuro->vendedor_codigo = $titulo->comissaoVendedor->vendedor_codigo;
                        $ComissaoCampanhaOuro->vendedor_nome = $User->name;
                        $ComissaoCampanhaOuro->titulo_valor = $titulo->valor;
                        $ComissaoCampanhaOuro->titulo_numero = $titulo->numero;
                        $ComissaoCampanhaOuro->comissao_gol_de_ouro = $titulo->valor * ($campanha->porcetagem_comissao_gerente/ 100);
                        $ComissaoCampanhaOuro->data_lancamento_pagamento = $titulo->data_lancamento_pagamento;
                        $ComissaoCampanhaOuro->periodo = $ComissaoDataFechamento->periodo;
                        $ComissaoCampanhaOuro->data_atualizacao = $campanha->updated_at;
                        $ComissaoCampanhaOuro->save();
                    }
                }
                
            }
        }

        $ComissaoCampanhaOuro = ComissaoCampanhaOuro::select('periodo')->distinct()->get();
        
        foreach($ComissaoCampanhaOuro as $periodo){
            $arr['data'] = $periodo->periodo;
            $arr['estabelecimento'] = "";
            $arr['representantes'] = "";
            $arr['tipo'] = "";

            $ComissaoDuplicatasFiltroRequest = new ComissaoDuplicatasFiltroRequest($arr);

            $ComissaoDuplicatasController = new ComissaoDuplicatasController();
            $comissoes = $ComissaoDuplicatasController->filter($ComissaoDuplicatasFiltroRequest, true);

            foreach($comissoes['titulos'] as $comissao){
                $query = ComissaoCampanhaOuro::select();
                $query->where('periodo', $periodo->periodo);
                $query->where('vendedor_codigo', $comissao['cod_representante']);
                $query->update(['comissao_paga' => parserNumber($comissao['valor_comissao']), 'valor' => parserNumber($comissao['base_comissao'])]);
            }
        }
    }
}
