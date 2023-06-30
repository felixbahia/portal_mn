<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatusPedido extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id';

    protected $table = "status_pedido";
    protected $fillable = ['id', 'status', 'exibir', 'created_by', 'updated_by'];

    public function pedido_portal(){
        return $this->belongsTo('App\PedidoPortal', 'status_pedido', 'id');
    }
}
