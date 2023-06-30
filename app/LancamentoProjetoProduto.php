<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class LancamentoProjetoProduto extends Model
{
    use SoftDeletes;
    protected $table = 'lancamento_projeto_produtos';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','lancamento_projetos_id','codigo_produto','quantidade','created_by','updated_by','deleted_by','created_at','updated_at','deleted_at','descricao','preco_venda','detalhe_producao'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function projeto_detalhes(){
        return $this->hasOne('App\LancamentoProjeto', 'id', 'lancamento_projetos_id');
    }

    public function produto_detalhes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }

    public function servico_detalhes(){
        return $this->hasOne('App\LancamentoProjetoFaccao', 'lancamento_projeto_produtos_id', 'id')->orderBy('lancamento_projeto_tecidos_id', 'desc')->orderBy('tipo_servico_id', 'asc');
    }

    public function composicao_tecidos(){
        return $this->hasMany('App\LancamentoProjetoTecido', 'lancamento_projeto_produtos_id', 'id');
    }

    public function composicao_insumos(){
        return $this->hasMany('App\LancamentoProjetoInsumo', 'lancamento_projeto_produtos_id', 'id');
    }

    public function composicao_servicos(){
        return $this->hasMany('App\LancamentoProjetoFaccao', 'lancamento_projeto_produtos_id', 'id');
    }

    public function composicao_servicos_tecido(){
        return $this->hasMany('App\LancamentoProjetoFaccao', 'lancamento_projeto_produtos_id', 'id')->whereNotNull('lancamento_projeto_tecidos_id');
    }

    public function composicao_servicos_produto(){
        return $this->hasMany('App\LancamentoProjetoFaccao', 'lancamento_projeto_produtos_id', 'id')->whereNull('lancamento_projeto_tecidos_id');
    }

    public function valor_total_tecido(){
        return $this->hasOne('App\LancamentoProjetoTecido', 'lancamento_projeto_produtos_id', 'id')
            ->select(DB::raw("SUM(valor_total) as total"));
    }

    public function valor_total_insumo(){
        return $this->hasOne('App\LancamentoProjetoInsumo', 'lancamento_projeto_produtos_id', 'id')
            ->select(DB::raw("SUM(valor_total) as total"));
    }

    public function valor_total_servico(){
        return $this->hasOne('App\LancamentoProjetoFaccao', 'lancamento_projeto_produtos_id', 'id')
            ->select(DB::raw("SUM(valor_total) as total"));
    }

    public function getUrlAramazenamentoArquivo(){
        return "public/projeto/arquvivo/";
    }

    public function arquivos(){
        return $this->hasMany('App\LancamentoProjetoProdutoArquivo', 'lancamento_projeto_produtos_id', 'id');
    }

    public function fichaTecnica(){
        return $this->hasOne('App\FichaTecnicaProduto', 'codigo_produto', 'codigo_produto');
    }
}
