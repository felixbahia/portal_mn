<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProdutoNasajon extends Model
{

    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_produtos';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = 'produto';
    protected $keyType = 'string';

    public $guarded = [
        'produto', 'codigo', 'especificacao', 'precovenda', 'unidade', 'marca', 'grupo', 'linha', 'precovenda_dolar', 'subgrupo', 'ncm', 'composicao', 'largura', 'gramatura', 'codigodebarras', 'pesobruto', 'pesoliquido', 'ipi', 'incentivo_sp', 'origemmercadoria', 'data_criacao', 'controlalote', 'figuratributaria', 'controlafracao'
    ];

    public function informacoes_adicionais(){
        return $this->hasOne('App\InformacaoAdicionalProduto', 'cod_produto', 'codigo');
    }

    public function especificacoes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo');
    }

    public function unidadeMedida(){
        return $this->hasOne('App\UnidadeMedidaNasajon', 'codigo', 'unidade');
    }

    public function estoquePortal(){
		return $this->hasMany('App\ProdutosEstoque', 'codigo_produto', 'codigo');
	}
    
}
