<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoricoPedidoCompra extends Model
{
    use SoftDeletes;
    protected $table = 'historicos_pedidos_compras';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','lancamento_projetos_id','pedido_compra_uuid','pedido_compra_numero','tipo','estabelecimento_codigo','fornecedor_cnpj_cpf','condicoes_pagamento_web_id','forma_pagamento_uuid','parcelamento_uuid','indicador_pagamento','cfop','tipo_operacao','modo_compra','data_entrega','valor_total','created_by','updated_by','deleted_by'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
