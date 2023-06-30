<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarrinhoCompra extends Model
{
    use SoftDeletes;
   
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'estabelecimento', 'pedido_id', 'transportadora_codigo', 'transportadora_redespacho', 'dados_cliente_pedido_id', 'codigo_cliente_conta_e_ordem', 'finalizado','created_by', 'updated_by', 'deleted_by'];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

    public function pedido(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
    }

    public function dadosClientePedido(){
        return $this->hasOne('App\DadosClientePedido', 'id', 'dados_cliente_pedido_id');
    }

    public function carrinhoCompraItens(){
        return $this->hasMany('App\CarrinhoCompraItem', 'carrinho_compra_id', 'id');
    }

    public function transportadora(){
        return $this->hasOne('App\TransportadorNasajon', 'codigo', 'transportadora_codigo');
    }

    public function transportadoraRedespacho(){
        return $this->hasOne('App\TransportadorNasajon', 'codigo', 'transportadora_redespacho');
    }
}
