<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarrinhoCompraItem extends Model
{
    
    use SoftDeletes;
   
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'carrinho_compra_id', 'pedido_item_id', 'produto_codigo', 'produto_quantidade', 'produto_preco', 'created_by', 'updated_by', 'deleted_by'
    ];
    
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

    public function pedidoItem(){
        return $this->hasOne('App\PedidoItemPortal', 'id', 'pedido_item_id');
    }

    public function carrinhoCompras(){
        return $this->hasOne('App\CarrinhoCompra', 'id', 'carrinho_compra_id');
    }

    public function produtoEspecificacao(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }
}
