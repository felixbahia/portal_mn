<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class ChequesRecebidoNasajon extends Model{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_chequespago';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['estabelecimento', 'cod_cliente', 'banco', 'agencia', 'numero_conta', 'numero_cheque'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'estabelecimento', 'cod_cliente', 'nome_cliente', 'banco', 'agencia', 'numero_conta', 'numero_cheque', 'valor', 'observacao', 'data_entrada', 'data_pagamento'
    ];

    function cliente(){
        return $this->hasOne('App\Cliente', 'codigo', 'cod_cliente');
    }
}
