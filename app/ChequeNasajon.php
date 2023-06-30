<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChequeNasajon extends Model
{

    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_cheques';

    public $fillable = [
        'cheque_id',
        'estabelecimento',
        'cliente_id',
        'cliente_codigo',
        'cliente_nome',
        'cliente_cnpj',
        'banco',
        'agencia',
        'numero_conta',
        'numero_cheque',
        'valor',
        'data_entrada',
        'data_vencimento',
        'data_pagamento',
        'observacao',
        'status'
    ];

    public function chequeTitulo(){
        return $this->hasMany('App\ChequeTituloNasajon', 'cheque_id', 'cheque_id');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'id', 'cliente_id');
    }
}
