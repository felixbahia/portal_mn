<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LancamentoProjetoFaccao extends Model
{
    use SoftDeletes;
    protected $table = 'lancamento_projeto_faccoes';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','lancamento_projetos_id','faccao_id','tipo_servico_id','quantidade','custo_unitario','valor_total','created_by','updated_by','deleted_by','created_at','updated_at','deleted_at','lancamento_projeto_produtos_id','lancamento_projeto_tecidos_id', 'codigo_produto_acabado'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    public function faccao(){
        return $this->hasOne('App\Faccao', 'id', 'faccao_id');
    }
    public function produto(){
        return $this->hasOne('App\LancamentoProjetoProduto', 'id', 'lancamento_projeto_produtos_id');
    }
    public function tecido(){
        return $this->hasOne('App\LancamentoProjetoTecido', 'id', 'lancamento_projeto_tecidos_id');
    }
    public function produto_acabado(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto_acabado');
    }
    public function tipo_de_servico(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'tipo_servico_id');
    }
    public function projeto(){
        return $this->hasOne('App\LancamentoProjeto', 'id', 'lancamento_projetos_id');
    }
    public function preco(){
        return $this->hasOne('App\Preco', 'codigo_produto', 'tipo_servico_id')->withTrashed()->orderBy('created_at', 'desc');
    }
    protected $dates = ['deleted_at'];

    public function proprioTabelaComparacaoProduto(){
        return $this->hasMany('App\LancamentoProjetoFaccao', 'lancamento_projeto_produtos_id', 'lancamento_projeto_produtos_id');
    }
}
