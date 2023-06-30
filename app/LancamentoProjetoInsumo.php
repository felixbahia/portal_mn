<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LancamentoProjetoInsumo extends Model
{
    use SoftDeletes;
    protected $table = 'lancamento_projeto_insumos';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','lancamento_projetos_id','codigo_estabelecimento','codigo_produto','consumo_unitario','quantidade','consumo_total','custo_unitario','valor_total','created_by','updated_by','deleted_by','created_at','updated_at','deleted_at'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    public function insumo_detalhes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }
    
    public function produto(){
        return $this->hasOne('App\LancamentoProjetoProduto', 'id', 'lancamento_projeto_produtos_id');
    }

    public function preco(){
        return $this->hasOne('App\Preco', 'codigo_produto', 'codigo_produto');
    }

    public function projeto_detalhes(){
        return $this->hasOne('App\LancamentoProjeto', 'id', 'lancamento_projetos_id');
    }

    protected $dates = ['deleted_at'];
}
