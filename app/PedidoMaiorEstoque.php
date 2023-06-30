<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PedidoMaiorEstoque extends Model
{
    protected $connection = 'pgsql';
    protected $fillable = [
       'id',
        'estabelecimento',
        'produto_codigo',
        'produto_descricao',
        'unidade',
        'estoque',
        'compras_aberto',
        'quantidade',
        'necessidade_compras',
        'estoque_em_transito'
    ];
}
