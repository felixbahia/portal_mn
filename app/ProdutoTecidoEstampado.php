<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdutoTecidoEstampado extends Model
{
    use SoftDeletes;
    protected $table = 'produto_tecidos_estampados';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','produto_codigo_final','produto_codigo_desenho','produto_tecidos_bases_id','created_by','updated_by','deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function tecido_base(){
        return $this->hasOne('App\ProdutoTecidoBase', 'id', 'produto_tecidos_bases_id');
    }

    public function produto_final_detalhes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo_final');
    }

    public function desenho_detalhes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo_desenho');
    }
}
