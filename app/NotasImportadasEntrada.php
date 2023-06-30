<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NotasImportadasEntrada extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $connection = 'pgsql';

    protected $fillable = [
        'id',
        'nota_id',
        'documento_chave',
        'documento_numero',
        'documento_serie',
        'documento_cfop',
        'fornecedor_id',
        'fornecedor_cnpj',
        'fornecedor_nome',
        'remetente_id',
        'remetente_cnpj',
        'remetente_nome',
        'data_emissao',
        'peso_bruto',
        'peso_liquido',
        'peso_base_calculo',
        'valor_total_servico',
        'valor_a_receber',
        'valor_frete',
        'valor_despacho',
        'valor_pedagio',
        'valor_gris',
        'valor_tas',
        'valor_total_carga',
        'valor_seguro',
        'valor_desconto',
        'valor_ipi',
        'valor_ipi_devolucao',
        'valor_outros',
        'valor_trt',
        'valor_Taxa_emi_ctrc',
        'icms_cst',
        'icms_base_calculo',
        'icms_aliquota',
        'icms_valor',
        'quantidade',
        'tipo',
        'informacao_complementar',
        'estabelecimento',
        'xml',
        'destinatario_cpf_cnpj',
        'destinatario_nome',
        'destinatario_id',
        'expedidor_nome',
        'expedidor_cnpj',
        'expedidor_id',
        'natureza_operacao',
        'produto_predominante',
        'caracteristica_carga',
        'peso_declarado',
        'unidade_peso_declarado',
        'peso_real',
        'unidade_peso_real',
        'cobranca',
        'volume'
    ];

    public function itensNotas(){
        return $this->hasMany('App\NotasImportadasEntradasIten','notas_importadas_entradas_id','id');
    }

    public function fornecedor(){
        return $this->hasOne('App\FornecedorNasajon','cnpj_cpf','fornecedor_cnpj');
    }

    public function cteNfe(){
        return $this->hasMany('App\NotasImportadasEntradaRelacaoNota', 'notas_importadas_entradas_id_cte', 'id');
    }

    public function cfop(){
        return $this->hasOne('App\CfopNasajon', 'cfop_codigo', 'documento_cfop');
    }

    public function comprasQuantidade(){
        return $this->hasMany('App\ComprasNasajon', ['fornecedor_cnpj','estabelecimento'], ['fornecedor_cnpj','estabelecimento'])->whereIn('situacao', ['Aberto','Aguardando Documento','Parcialmente Liquidado'])
        ->select(DB::raw('fornecedor_cnpj, estabelecimento, numero_pedido, sum(preco_compra) as preco_compra'))
        ->groupBy('fornecedor_cnpj','estabelecimento','numero_pedido');
    }

    public function referenciaDevolucao(){
        return $this->hasMany('App\NotasImportadasEntradaDevolucaoReferencia','notas_importadas_entradas_id','id');
    }

    public function notasEntradas(){
        return $this->hasOne('App\NotasEntradasNasajon',['Chave NE','Estabelecimento'],['documento_chave','estabelecimento']);
    }

    public function transportadora(){
        return $this->hasOne('App\TransportadorNasajon','id','fornecedor_id');
    }

    public function destinatario(){
        return $this->hasOne('App\ClienteNasajon','cpf_cnpj','destinatario_cpf_cnpj');
    }

    public function devolucao(){
        return $this->hasOne('App\DevolucaoNota', ['estabelecimento','nota_cliente_numero','cliente_cpf_cnpj'],['estabelecimento','documento_numero','fornecedor_cnpj']);
    }

    public function faturas(){
        return $this->hasOne('App\NotasImportadasEntradasTitulo', 'notas_importadas_entrada_id','id');
    }
}
