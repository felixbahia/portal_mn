<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProdutoSubGrupo extends Model
{
    protected $table = 'produto_subgrupos';
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
