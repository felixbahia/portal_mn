<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PedidoComprasAssociacaoNotaNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_pedidosassociacoes';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
    	'id_nota', 'id_pedido'
    ];

    public function notasentradas(){
        return $this->hasMany('App\NotasEntradasNasajon', 'Identificador Documento', 'id_nota');
    }

    public function pedido(){
        return $this->hasOne('App\ComprasNasajon', 'id_nota', 'id_pedido');
    }

    public function pedidosDetalhes(){
        return $this->hasMany('App\ComprasNasajon', 'id_nota', 'id_pedido');
    }
}
