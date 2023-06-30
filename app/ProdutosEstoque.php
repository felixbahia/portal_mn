<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ProdutosEstoque extends Model
{
    use \Awobaz\Compoships\Compoships;

    public $connection = 'pgsql';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = ['estabelecimento', 'codigo_produto'];
    protected $keyType = 'string';

    public $guarded = ['estabelecimento', 'codigo_produto', 'descricao', 'grupo', 'subgrupo', 'marca', 'linha', 'unidade', 'peso', 'estoque', 'compras', 'empenho', 'data_atulizacao', 'custo', 'compras_aberto', 'saldo_fiscal', 'saldo_em_terceiros', 'saldo_de_terceiros', 'saldo_armazem', 'data_ultima_venda', 'venda_mes_1','campanha_id'];
    
    public function produtoPrologos(){
        return $this->hasOne('App\Produto', 'CODPRD', 'codigo_produto');
    }

    public function getEstabelecimentoPadAttribute(){
        return str_pad($this->estabelecimento, 2, 0, STR_PAD_LEFT); 
    }
    
    public function informacoes_adicionais(){
        return $this->hasOne('App\InformacaoAdicionalProduto', 'cod_produto', 'codigo_produto');
    }

    public function precos(){
        return $this->hasMany('App\Preco', 'codigo_produto', 'codigo_produto');
    }

    public function estoquePrologos(){
        return $this->hasOne('App\Estoque', 'CODPRD', 'codigo_produto')->where('ESTABEL', str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT));
    }

    public function especificacao(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }

    public function produtoNasajon(){
		return $this->belongsTo('App\ProdutoNasajon', 'codigo_produto', 'codigo');
	}

    public function estabelecimentosNasajon(){
		return $this->hasOne('App\NasajonEstabelecimento', 'codigo', 'estabelecimento');
    }
    
    public function custoPortal(){
        return $this->hasMany('App\ProdutosCusto', 'produto_codigo', 'codigo_produto');
    }

    public function custoDetalhes(){
        return $this->hasOne('App\ProdutosCusto', ['produto_codigo', 'estabelecimento'], ['codigo_produto', 'estabelecimento']);
    }

    public function precoDetalhes(){
        return $this->hasOne('App\Preco', 'codigo_produto', 'codigo_produto');
    }

    public function itensBook(){
        return $this->hasOne('App\ItensBookVirtual', 'cod_produto', 'codigo_produto');
    }

    public function estoqueEmTerceiros(){
        return $this->hasMany('App\EstoquePoderTerceiro', 'produto_codigo', 'codigo_produto');
    }

    public function produtoGrupo(){
        return $this->hasOne('App\ProdutoGrupo', 'id', 'produto_grupos_id');
    }

    public function fracaoDisponivelNasajon(){
        return $this->hasMany('App\FracaoDisponivelNasajon', 'produto_codigo', 'codigo_produto');
    }

    public function campanha(){
        return $this->hasOne('App\Campanha', 'id', 'campanha_id');
    }
}
