<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProdutoMarca extends Model
{
    protected $table = 'produto_marcas';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'descricao'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
