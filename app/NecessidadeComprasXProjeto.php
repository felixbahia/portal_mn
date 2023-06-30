<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NecessidadeComprasXProjeto extends Model
{
    use SoftDeletes;
    protected $table = 'necessidades_compras_x_projetos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','necessidades_compras_id', 'lancamento_projetos_id','lancamento_projeto_produtos_id','lancamento_projeto_tecidos_id','lancamento_projeto_insumos_id','lancamento_projeto_faccoes_id','tipo','created_by','updated_by','deleted_by', 'pedido_id'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function projeto(){
        return $this->hasOne('App\LancamentoProjeto', 'id', 'lancamento_projetos_id');
    }

    public function produto_projeto(){
        return $this->hasOne('App\LancamentoProjetoProduto', 'id', 'lancamento_projeto_produtos_id');
    }
    public function tecido_projeto(){
        return $this->hasOne('App\LancamentoProjetoTecido', 'id', 'lancamento_projeto_tecidos_id');
    }
    public function insumo_projeto(){
        return $this->hasOne('App\LancamentoProjetoInsumo', 'id', 'lancamento_projeto_insumos_id');
    }
    public function produto_acabado_projeto(){
        return $this->hasOne('App\LancamentoProjetoFaccao', 'id', 'lancamento_projeto_faccoes_id');
    }

    public function necessidade_compras_detalhes(){
        return $this->hasOne('App\NecessidadeCompras', 'id', 'necessidades_compras_id');
    }
    
    public function detalhesPedido(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
    }
}
