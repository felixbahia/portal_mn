<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoricoPedidoCompraItem extends Model
{
    use SoftDeletes;
    protected $table = 'historicos_pedidos_compras_itens';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','historicos_pedidos_compras_id','necessidades_compras_id','lancamento_projeto_produtos_id','pedido_compra_item_uuid','produto_codigo','produto_unidade','valor_unitario','valor_unitario_original','quantidade','quantidade_original','valor_total','valor_total_original','created_by','updated_by','deleted_by'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
