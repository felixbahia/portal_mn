<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PedidoFormaPagamentoNasajon extends Model
{

    use \Awobaz\Compoships\Compoships;

    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_df_formaspagamentos';
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;
    public $primaryKey = 'id_docfis';

    protected $fillable = [
        'id_docfis',
        'parcelas_documento',
        'pagamento',
        'pagamento_nomeconta',
        'formapagamento',
        'formapagamento_codigo',
        'formapagamento_descricao',
        'parcelamento',
        'parcelamento_codigo',
        'parcelamento_nome',
    ];

    public function pedidoVendaNasajon(){
        return $this->belongTo('App\PedidosVendaNasajon', 'id', 'id_docfis');
    }

    public function condicao(){
        return $this->belongsTo('App\CondicoesPagamentoWeb', ['formapagamento', 'parcelamento'],['nasajon_forma_pagamento','nasajon_parcela'])->where('ativo', true);
    }

}

