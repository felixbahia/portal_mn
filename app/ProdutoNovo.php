<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdutoNovo extends Model
{
    use SoftDeletes;
    protected $table = 'produto_novos';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','lancamento_projeto_produtos_id', 'lancamento_projeto_tecidos_id', 'codigo_produto','descricao','preco_venda','created_by','updated_by','deleted_by','created_at','updated_at','deleted_at'
    ];

    public function projeto_produto(){
        return $this->hasOne('App\LancamentoProjetoProduto', 'id', 'lancamento_projeto_produtos_id');
    }

    public function projeto_tecido(){
        return $this->hasOne('App\LancamentoProjetoTecido', 'id', 'lancamento_projeto_tecidos_id');
    }
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
