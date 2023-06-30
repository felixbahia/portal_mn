<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoComprasAbertoTituloFuturo extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'pedido_compras_numero', 'pedido_compras_uuid', 'pedido_compras_emissao', 
        'pedido_compras_previsao_chegada',
        'pedido_compras_condicao_pagamento', 'nota_entrada_numero', 'parcela', 'valor', 'parcela_data', 'fornecedor_cnpj', 
        'fornecedor_nome', 'nao_lancado'
    ];

    protected $dates = ['parcela_data'];

    public function detalhesCompras(){
        return $this->hasOne('App\ComprasNasajon', 'id_nota', 'pedido_compras_uuid');
    }
}
