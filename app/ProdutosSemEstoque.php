<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProdutosSemEstoque extends Model
{
    use \Awobaz\Compoships\Compoships; 

    protected $fillable = ['pedido', 'cod_produto', 'qtd', 'cliente', 'data_pedido', 'pedido_futuro', 'data_entrega', 'user'];

    public function pedido_detalhes(){
    	return $this->belongsTo('App\PedidoPortal', 'pedido', 'id');
    }

    public function produto_detalhes(){
    	return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'cod_produto');
    }

    public function cliente_detalhes(){
    	return $this->hasOne('App\ClienteNasajon', 'codigo', 'cliente');
    }
    
    public function user_detalhe(){
    	return $this->hasOne('App\User', 'id', 'user');
    }

    public function pedido_item_detalhes(){
    	return $this->hasOne('App\PedidoItemPortal', ['pedido','cod_produto'], ['pedido','cod_produto']);
    }
}
