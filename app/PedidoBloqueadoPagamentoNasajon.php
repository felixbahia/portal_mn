<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PedidoBloqueadoPagamentoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'estoque.pedidos_bloqueados';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
    	'id_docfis'
    ];

    public function pedido(){
        return $this->hasOne('App\PedidosVendaNasajon', 'id', 'id_docfis');
    }
}
