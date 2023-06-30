<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidosMonitorado extends Model
{
    use SoftDeletes;

    protected $fillable = ['estabelecimento', 'pedido', 'status_pedido_monitorado', 'emissao_data_hora', 'emissao_user', 'aprovacao_data_hora', 'aprovacao_user', 'faturamento_data_hora', 'faturamento_user', 'tempo_separacao', 'quantidade_alertas'];

    public function pedidoCompleto(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido');
    }
}
