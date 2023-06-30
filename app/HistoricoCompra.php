<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoricoCompra extends Model
{
    protected $connection = 'pgsql';
    protected $fillable = [
        'id',
        'produto_codigo',
        'numero_pedido',
        'quantidade',
        'data_alteracao',
        'previsao_entrega',
        'status',
        'estabelecimento',
        'pedido_id',
        'produto_descricao',
        'quantidade_restante'
    ];
}
