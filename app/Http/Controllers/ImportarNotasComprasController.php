<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Exception;

use App\NotasEntradasNasajon;
use App\NotasImportadasCompra;
use App\NotasImportadasComprasIten;

class ImportarNotasComprasController extends Controller
{
    private $cfop_compras = ['1100','1101'.'1102','1111','1113','1116','1117','1118','1120','1121','1122','1124','1124','1126','1128','1150','1403','1406','1407','1408','1551','1556',
    '1651','1652','1653','2100','2101','2102','2111','2113','2116','2117','2118','2120','2121','2122','2126','2128','2401','2403','2406','2407','2551','2556','2651','2652','2653','3100',
    '3102','3126','3127','3551','3556','3651','3652','3653'];

    public function importar($inicio_periodo,$fim_periodo){
        $notas_entrada_nasajon = NotasEntradasNasajon::whereBetween('Data de Emissão',[$inicio_periodo,$fim_periodo])
        ->where(function($query){
            $query->orWhereIn('Código da Operação',['COMPRA','COMPRACOMBUSTIVEL','COMPRADIFERIMENTO','COMPRAIMOBILIZADO','COMPRAIMPORTACAO','COMPRAIMPORTCOURIER','COMPRAMIGRACAO','COMPRAUSECONS','COMPRAUSOCONSSFIN','INDUSTERETOR']);
            $query->orWhereHas('itens',function($query){
                $cfop = $this->cfop_compras;
                $query->whereIn('cfop',$cfop);
            });
        })
        ->where('Tipo do Documento','COMPRA')
        ->with(['itens' =>function($query){
            $cfop = $this->cfop_compras;
            $query->whereIn('cfop',$cfop);
        }])
        ->get();
        
        $notas_entrada_nasajon->each(function($query){
            $verifica_importacao = NotasImportadasCompra::where('nota_id',$query['Identificador Documento'])->first();
            if(empty($verifica_importacao)){
                $importacao_compras = new NotasImportadasCompra;
                $importacao_compras->nota_id = $query['Identificador Documento'];
                $importacao_compras->estabelecimento_id = $query['Identificador Estabelecimento'];
                $importacao_compras->estabelecimento_codigo = $query['Estabelecimento'];
                $importacao_compras->estabelecimento_nome = $query['Nome do Estabelecimento'];
                $importacao_compras->documento_numero = $query['Número do Documento'];
                $importacao_compras->emissao = $query['Data de Emissão'];
                $importacao_compras->fornecedor_id = $query['Identificador do Fornecedor'];
                $importacao_compras->fornecedor_codigo = $query['Fornecedor'];
                $importacao_compras->fornecedor_nome = $query['Nome do Fornecedor'];
                $importacao_compras->fornecedor_documento = $query['CNPJ/CPF do Fornecedor'];
                $importacao_compras->valor_total = $query['Valor do Documento'];
                $importacao_compras->codigo_operacao = $query['Código da Operação'];
                $importacao_compras->descricao_operacao = $query['Descrição da Operação'];
                $importacao_compras->transportadora_id = $query['Identificador da Transportadora"'];
                $importacao_compras->transportadora_codigo = $query['Transportadora'];
                $importacao_compras->transportadora_nome = $query['Nome da Transportadora'];
                $importacao_compras->transportadora_documento = $query['CNPJ/CPF da Transportadora'];
                $importacao_compras->modalidade_frete = $query['Modalidade do Frete'];
                $importacao_compras->quantidade = $query['Quantidade Volumes'];
                $importacao_compras->peso_liquido = $query['Peso Líquido'];
                $importacao_compras->desconto = $query['Desconto'];
                $importacao_compras->valor_frete = $query['Valor Frete'];
                $importacao_compras->valor_seguro = $query['Valor Seguro'];
                $importacao_compras->valor_outras_despesas = $query['Valor Outras Despesas'];
                $importacao_compras->valor_ipi = $query['Valor IPI'];
                $importacao_compras->valor_icms = $query['Valor ICMS-ST'];
                $importacao_compras->pedido = $query['Pedido'];
                $importacao_compras->data_entrada = $query['Data de Entrada'];
                $importacao_compras->chave = $query['Chave NE'];
                $importacao_compras->informacao_complementar = $query['Informações Adicionais'];
                $importacao_compras->score = false;
                if(!$importacao_compras->save()){
                    throw new Exception($importacao_compras->save());
                }

                if(!empty($query->itens)){
                    foreach($query->itens as $itens){
                        $importacao_compras_itens = new NotasImportadasComprasIten;
                        $importacao_compras_itens->nota_id = $itens->id_item_nota;
                        $importacao_compras_itens->notas_importadas_compras_id = $importacao_compras->id;
                        $importacao_compras_itens->produto_codigo = $itens->cod_produto;
                        $importacao_compras_itens->produto_descricao = $itens->desc_produto;
                        $importacao_compras_itens->ncm = $itens->ncm;
                        $importacao_compras_itens->cfop = $itens->cfop;
                        $importacao_compras_itens->unidade = $itens->unidade;
                        $importacao_compras_itens->quantidade = $itens->quantidade;
                        $importacao_compras_itens->valor_unitario = $itens->valor_unitario;
                        $importacao_compras_itens->valor_total = $itens->valor_total;
                        $importacao_compras_itens->icms_aliquota = $itens->aliquota_icms;
                        $importacao_compras_itens->icms_base = $itens->base_icms;
                        $importacao_compras_itens->icms_valor = $itens->valor_icms;
                        $importacao_compras_itens->icms_aliquota_st = $itens->aliquota_icms_st;
                        $importacao_compras_itens->icms_base_st = $itens->base_icms_st;
                        $importacao_compras_itens->icms_valor_st = $itens->valor_icms_st;
                        $importacao_compras_itens->ipi_aliquota = $itens->aliquota_ipi;
                        $importacao_compras_itens->ipi_base = $itens->base_ipi;
                        $importacao_compras_itens->ipi_valor = $itens->valor_ipi;
                        if(!$importacao_compras_itens->save()){
                            throw new Exception($importacao_compras_itens->save());
                        }
                    }
                }
            }
        });
    }   
}
