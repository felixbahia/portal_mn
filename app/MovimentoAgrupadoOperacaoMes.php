<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MovimentoAgrupadoOperacaoMes extends Model
{
    protected $table = 'movimento_agrupado_operacao_mes';
    public $connection = 'pgsql';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'estabelecimento', 'ano_mes', 'produto_codigo', 'descricao', 'grupo', 'marca', 'linha', 'subgrupo', 'produto_procedencia', 'cfop', 'quantidade', 'valor_gerencial'
    ];

    protected $dates = ['ano_mes'];
}
