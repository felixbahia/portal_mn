<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventarioHistoricoProdutoPeca extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'inventario_historico_produtos_id', 'codigo_peca', 'endereco', 'quantidade', 'created_by', 'usuario_id'
    ];

}
