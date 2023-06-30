<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Movimentacao20 extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'movimentacao_20';

    protected $fillable = [
        'id',
        'estabelecimento_codigo',
        'produto_codigo',
        'data_movimentacao',
        'quantidade',
        'sinal',
        'origem',
        'documento_id',
        'documento_numero',
        'movimento_id',
        'cliente_codigo',
        'item_cfop',
        'item_aliquota',
        'item_preco_unitario',
        'item_preco_total',
        'item_unidade',
        'item_frete',
        'item_ipi',
        'item_desconto',
        'item_seguro',
        'slot',
        'efetivado',
        'item_valor_icms',
        'custo',
    ];

    protected $dates = ['data_movimentacao'];

    public function notaEntrada(){
        return $this->hasOne('App\NotasEntradasNasajon', 'Identificador Documento', 'documento_id');
    }

    public function notaSaida(){
        return $this->hasOne('App\NotasNasajon', 'id', 'documento_id');
    }
}
