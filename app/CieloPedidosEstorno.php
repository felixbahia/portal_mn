<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CieloPedidosEstorno extends Model
{
    use SoftDeletes;
    
    public $fillable = [
        'cielo_pedido_transacao_id',
        'cielo_pedido_id',
        'token_estorno',
        'valor_estorno',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function cieloPedidoTransacao(){
        return $this->hasOne('App\BookVirtual', 'id', 'books_virtuals_id');
    }
    public function cieloPedido(){
        return $this->hasOne('App\BookVirtual', 'id', 'books_virtuals_id');
    }
    public function createdBy(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedBy(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedBy(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
