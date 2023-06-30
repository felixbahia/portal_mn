<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class pedido_portal_itens extends Model
{

    protected $connection = 'srv_pedido';
    protected $table = 'pedido_portal_items';
    public $timestamps = false;
    public $incrementing = false;

	public $fillable = ['estabelecimento', 'id_app', 'id_pedido_web', 'sequencia_item', 'codprd', 'qtd', 'modalidade_preco', 'desconto_item_porcentagem', 'preco_unitario_completo', 'preco_nota_composto', 'ipi_porcentagem', 'desconto_total_item'];
}
