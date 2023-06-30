<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class StoneErroTransacoesPedido extends Model
{
    use SoftDeletes;
    
    protected $connection = 'pgsql';

    protected $fillable = [
        'id', 
        'pedido_id', 
        'stone_cadastro_maquininha_id', 
        'erro_msg', 
        'created_by', 
        'updated_by', 
        'deleted_by'
    ];

    public function pedido(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
    }

    public function maquininha(){
        return $this->hasOne('App\StoneCadastroMaquininha', 'id', 'stone_cadastro_maquininha_id');
    }
    public function createdby(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedby(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedby(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
