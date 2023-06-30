<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrupoDeInventario extends Model
{
    use SoftDeletes;

    protected $table = "grupo_de_inventario";
	protected $fillable = ['id','descricao'];
}
