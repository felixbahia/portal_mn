<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TituloAPagar extends Model
{
    use \Awobaz\Compoships\Compoships;
    use SoftDeletes;
    protected $connection = 'pgsql';
    protected $table = 'titulo_a_pagar';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'estabelecimento_codigo',
        'titulo_numero',
        'titulo_parcela',
        'titulo_tipo',
        'titulo_situacao',
        'titulo_emissao',
        'titulo_vencimento',
        'titulo_data_baixa',
        'titulo_valor',
        'titulo_valor_liquido',
        'titulo_valor_baixa',
        'titulo_valor_saldo_adiantamento',
        'fornecedor_codigo',
        'fornecedor_nome',
        'fornecedor_razao_social',
        'tipo',
        'condicao_pagamento_periodo',
        'created_at',
        'updated_at',
        'deleted_at',
        'nota_numero'
    ];
    
    protected $dates = ['titulo_emissao', 'updated_at', 'deleted_at'];

    function notaEntradaDetalhes(){
        return $this->hasOne('App\NotasEntradasNasajon', ['Número do Documento', 'Fornecedor'], ['nota_numero', 'fornecedor_codigo']);
    }

    public function detalhesFornecedor(){
    	return $this->hasOne('App\FornecedorNasajon', 'codigo', 'fornecedor_codigo');
    }
}
