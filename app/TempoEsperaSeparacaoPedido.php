<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempoEsperaSeparacaoPedido extends Model
{
    use SoftDeletes;
    
    protected $connection = 'pgsql';

    protected $fillable = [
        'id', 
        'pedido_id', 
        'pedido_nasajon',
        'id_pedido_nasajon', 
        'inicio_separacao_manual', 
        'fim_separacao_manual',
        'fim_separacao_nasajon',
        'seperacao_finalizada',
        'em_faturamento_nasajon',
        'created_by', 
        'updated_by', 
        'deleted_by',
        'created_at'
    ];

    public function pedidoPortal(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
    }
    public function pedidoNasajon(){
        return $this->hasOne('App\PedidosVendaNasajon', 'id', 'id_pedido_nasajon');
    }
}
