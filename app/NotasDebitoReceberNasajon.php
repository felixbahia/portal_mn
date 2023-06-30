<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasDebitoReceberNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_debitosemaberto';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['codigo', 'cod_cliente'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'codigo', 'cod_cliente', 'nome_cliente', 'numero', 'parcela', 'vencimento', 'valor'
    ];
}
