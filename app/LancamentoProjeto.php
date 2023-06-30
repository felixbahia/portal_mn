<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LancamentoProjeto extends Model
{
    use SoftDeletes;
    protected $table = 'lancamento_projetos';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','nome_projeto','preco_venda', 'cliente_codigo','cliente_cpf_cnpj', 'tipo_frete','pedido','condicoes_pagamento_web_id','valor_total_pedido','quantidade_total','comissao','custo_total_margem','mark_up_real','acima_tabela','custo_tecido','custo_insumo','mao_obra','custo_total','custo_unitario_mn','margem','created_by','updated_by','deleted_by','created_at','updated_at','deleted_at', 'data', 'users_codigo_representante', 'revisor_user_id', 'produto_linhas_id'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function getEstabelecimentoPadAttribute(){
        return str_pad($this->estabelecimento, 2, 0, STR_PAD_LEFT); 
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cliente_cpf_cnpj');
    }

    public function condicoes_pagamento_web(){
        return $this->hasOne('App\CondicoesPagamentoWeb', 'id', 'condicoes_pagamento_web_id');
    }

    public function detalhes_representante(){
        return $this->hasOne('App\User', 'codigo_representante', 'users_codigo_representante');
    }

    public function detalhes_revisor(){
        return $this->hasOne('App\User', 'id', 'revisor_user_id');
    }

    public function criado_por(){
        return $this->hasOne('App\User', 'id', 'created_by')->withTrashed();
    }

    public function atualizado_por(){
        return $this->hasOne('App\User', 'id', 'updated_by')->withTrashed();
    }

    public function estabelecimentoDetalhes(){
        return $this->hasOne('App\NasajonEstabelecimento', 'codigo', 'estabelecimento_pad');
    }

    public function itens(){
        return $this->hasMany('App\LancamentoProjetoProduto', 'lancamento_projetos_id', 'id');
    }

    public function tecidos(){
        return $this->hasMany('App\LancamentoProjetoTecido', 'lancamento_projetos_id', 'id');
    }

    public function insumos(){
        return $this->hasMany('App\LancamentoProjetoInsumo', 'lancamento_projetos_id', 'id');
    }
    
    public function faccoes(){
        return $this->hasMany('App\LancamentoProjetoFaccao', 'lancamento_projetos_id', 'id');
    }

    public function produto_acabado(){
        return $this->hasMany('App\LancamentoProjetoFaccao', 'lancamento_projetos_id', 'id')->whereNotNull('codigo_produto_acabado');
    }

    public function detalhes_status(){
        return $this->hasOne('App\StatusProjeto', 'posicao', 'status');
    }

    public function detalhes_linha(){
        return $this->hasOne('App\ProdutoLinha', 'id', 'produto_linhas_id');
    }

    public function detalhes_pedido(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id')->withTrashed();
    }

    public function historico(){
        return $this->hasMany('App\HistoricoProjeto', 'lancamento_projetos_id', 'id');
    }
}
