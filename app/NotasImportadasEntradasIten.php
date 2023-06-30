<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasImportadasEntradasIten extends Model
{
    protected $fillable = [
        'id',
        'notas_importadas_entradas_id',
        'codigo_produto',
        'codigo_ean',
        'codigo_ncm',
        'codigo_cest',
        'codigo_cfop',
        'comercial_unidade',
        'comercial_quantidade',
        'comercial_valor_unitario',
        'valor_total',
        'produto_nome',
        'tributavel_codigo_ean',
        'tributavel_unidade',
        'tributavel_quantidade',
        'icms_mercadoria_origem',
        'icms_tributacao_cts',
        'icms_modalidade_bc',
        'icms_aliquota',
        'icms_valor',
        'pis_cts',
        'pis_base_calculo',
        'pis_aliquota',
        'pis_valor',
        'cofins_cts',
        'cofins_base_calculo',
        'cofins_aliquota',
        'cofins_valor',
        'descricao'
    ];

    public function notasEntradaImportadas(){
        return $this->belongsTo('App\NotasImportadasEntrada', 'id', 'notas_importadas_entradas_id');
    }

    public function produtoEspecicacoes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }
}
