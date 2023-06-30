<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventarioHistorico extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'inventario_nasajaon', 'estabelecimento', 'data', 'created_by'
    ];
}
