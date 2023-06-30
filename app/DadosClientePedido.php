<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DadosClientePedido extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'cliente_codigo',
        'transportadora_codigo',
        'tipo_frete',
        'condicao_pagamento',
        'data_previsao_entrega',
        'transportadora_redespacho_codigo',
        'tipo_frete_redespacho',
        'valor_frete',
        'valor_frete_redespacho',
        'nome_contato',
        'email_contato',
        'no_pedido_compra',
        'cliente_telefone',
        'observacao',
        'tipo_venda',
        'created_by',
        'updated_by',
        'deleted_by'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function deletadoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'cliente_codigo');
    }

    public function clienteContaEOrdem(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'codigo_cliente_conta_e_ordem');
    }
}
