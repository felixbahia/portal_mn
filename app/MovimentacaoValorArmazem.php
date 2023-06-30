<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MovimentacaoValorArmazem extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'movimentacao_valor_armazem';

    protected $fillable = [
        'id',
        'data_movimentacao',
        'saldo_anterior',
        'saldo_atual',
        'entrada',
        'saida',
    ];

    protected $dates = ['data_movimentacao'];
}
