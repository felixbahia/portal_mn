<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventarioHistoricoProduto extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'inventario_historicos_id', 'codigo_produto', 'quantidade', 'codigo_barras', 'created_by'
    ];

}
