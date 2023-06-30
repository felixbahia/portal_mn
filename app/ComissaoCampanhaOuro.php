<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ComissaoCampanhaOuro extends Model
{
    protected $connection = 'pgsql';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'equipe',
        'vendedor_codigo',
        'vendedor_nome',
        'titulo_numero',
        'titulo_valor',
        'comissao_gol_de_ouro',
        'data_lancamento_pagamento',
        'periodo',
        'data_atualizacao'
    ];
}
