<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AuditoriaComissao extends Model
{
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'pedido_item_id', 'pedido_id', 'campanha_id','comissao_anterior','comissao_nova'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */

    public function pedido(){
        return $this->hasOne('App\Pedido', 'id', 'pedido_id');
    }

    public function pedidoItem(){
        return $this->hasOne('App\PedidoItemPortal', 'id', 'pedido_item_id');
    }
    
    public function campanha(){
        return $this->hasOne('App\Campanha', 'id', 'campanha_id');
    }
}
