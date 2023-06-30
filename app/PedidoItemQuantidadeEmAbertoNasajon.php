<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PedidoItemQuantidadeEmAbertoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_pedido_item_qtd_aberto';
    public $timestamps = false;
    public $incrementing = false;

    protected $guarded = [
        'codigo_estabelecimento',
        'codigo_produto',
        'quantidade'
    ];
}
