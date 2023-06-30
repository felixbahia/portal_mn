<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdutoTecidoBase extends Model
{
    use SoftDeletes;
    protected $table = 'produto_tecidos_bases';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'codigo_produto','codigo_produto_base','created_by','updated_by','deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function tecido_base_detalhes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }
}
