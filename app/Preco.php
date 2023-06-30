<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Preco extends Model
{
    use SoftDeletes;

    public $incrementing = false;
    protected $primaryKey = 'codigo_produto';
    protected $keyType = 'string';
    protected $connection = 'pgsql';

    protected $fillable = [
        'codigo_produto',
        'preco_real',
        'preco_dolar',
        'compra_real',
        'compra_dolar',
        'ultima_compra_real',
        'ultima_compra_dolar',
        'valor_compra',
        'numero_proforma',
        'previsao_entrega',
        'created_by',
    ];

    public function criadoPor(){
        return $this->belongsTo('App\User', 'id', 'created_by');
    }

    public function modificadoPor(){
        return $this->belongsTo('App\User', 'id', 'updated_by');
    }

    public function excluidoPor(){
        return $this->belongsTo('App\User', 'id', 'deleted_by');
    }

    public function produtoPrologos(){
        return $this->hasOne('App\Produto', 'CODPRD', 'codigo_produto');
    }

    public function produtoNasajon(){
        return $this->hasOne('App\ProdutoNasajon', 'codigo', 'codigo_produto');
    }
    public function estoque(){
        return $this->hasMany('App\ProdutosEstoque', 'codigo_produto', 'codigo_produto');
    }

    public function estoquePrologos(){
        return $this->hasMany('App\Estoque', 'CODPRD', 'codigo_produto');
    }

    public function compras(){
        return $this->hasOne('App\CompraProdutoImportacao', 'codigo_produto', 'codigo_produto')->orderBy('data_faturamento', 'desc');
    }

    public function informacoes_adicionais(){
        return $this->hasOne('App\InformacaoAdicionalProduto', 'cod_produto', 'codigo_produto');
    }

    public function especificacoes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }

    public function custos(){
        return $this->hasMany('App\ProdutosCusto', 'produto_codigo', 'codigo_produto')->orderBy('estabelecimento');
    }
}
