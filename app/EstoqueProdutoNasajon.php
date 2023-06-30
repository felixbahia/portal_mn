<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EstoqueProdutoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'estoque.vw_saldos_tecidos_mn';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = 'produto';
    protected $keyType = 'string';

    public $guarded = ['estabelecimento_id', 'estabelecimento_codigo', 'item_id', 'item_codigo', 'item_especificacao', 'produto_id', 'bloqueado', 'saldo_fiscal', 'saldo_primeiro_mes_primeira_quinzena', 'saldo_primeiro_mes_segunda_quinzena', 'saldo_segundo_mes_primeira_quinzena', 'saldo_segundo_mes_segunda_quinzena', 'saldo_terceiro_mes_primeira_quinzena', 'saldo_terceiro_mes_segunda_quinzena', 'saldo_quarto_mes_primeira_quinzena', 'saldo_quarto_mes_segunda_quinzena', 'saldo_futuro'];

}
