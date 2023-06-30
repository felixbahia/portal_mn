<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProdutoEmpenhoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_pedido_item_qtd_aberto';
    public $timestamps = false;
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'codigo_estabelecimento', 'codigo_produto', 'quantidade'
    ];
}
