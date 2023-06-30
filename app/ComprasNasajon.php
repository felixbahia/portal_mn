<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ComprasNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_produtos_compras';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = ['estabelecimento', 'cod_produto'];
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'id_nota', 'estabelecimento', 'cod_produto', 'descricao_produto', 'numero_pedido', 'data_compra', 'previsao_entrega', 'proforma', 'quantidade', 'quantidade_restante', 'situacao', 'fornecedor_cnpj', 'fornecedor_nome', 'fornecedor_id', 'preco_compra', 'preco_compra_unitario', 'preco_compra_total', 'item', 'estabelecimento_id', 'data_alteracao', 'data_criacao', 'unidade_comercial','observacao','transportadora_codigo','transportadora_cnpj','data_entrega','cfop','situacao_item'
    ];

    protected $dates = ['previsao_entrega'];

    public function produto(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'cod_produto');
    }

    public function informacoes_adicionais(){
        return $this->hasOne('App\InformacaoAdicionalProduto', 'cod_produto', 'cod_produto');
    }

    public function fornecedor(){
        return $this->hasOne('App\FornecedorNasajon', 'id', 'fornecedor_id');
    }

    public function notas(){
        return $this->hasMany('App\PedidoComprasAssociacaoNotaNasajon', 'id_pedido', 'id_nota');
    }

    public function produtoNasajon(){
        return $this->hasOne('App\ProdutoNasajon', 'codigo', 'cod_produto');
    }

    public function produtoDetalhesImportacao(){
        return $this->hasOne('App\ImportacaoItem',  ['produto_codigo', 'nota_uuid_nasajon'], ['cod_produto', 'id_nota']);
    }

    public function condicoesPagamento(){
        return $this->hasMany('App\CondicoesPagamentoNasajon', 'id_docfis', 'id_nota');
    }
}
