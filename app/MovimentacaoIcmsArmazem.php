<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MovimentacaoIcmsArmazem extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'movimentacao_icms_armazem';

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
