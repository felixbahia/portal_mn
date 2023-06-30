<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstoquePoderTerceiro extends Model
{
    use \Awobaz\Compoships\Compoships;
    use SoftDeletes;

    protected $fillable = [
	    'id',
        'estabelecimento_codigo',
        'produto_codigo',
        'produto_nome',
        'fornecedor_codigo',
        'fornecedor_nome',
        'saldo_em_terceiro',
        'valor_custo_contabil_nasajon',
        'valor_custo_gerencial',
        'valor_custo_contabil_portal',
        'valor_custo_gerencial_portal',
        'created_at',
        'updated_at',
        'deleted_at'
	];

    public function produtoDetalhe(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
	}

    public function precos(){
        return $this->hasMany('App\Preco', 'codigo_produto', 'produto_codigo');
    }

    public function custoPortal(){
        return $this->hasMany('App\ProdutosCusto', 'produto_codigo', 'produto_codigo');
    }

    public function estoque(){
        return $this->hasMany('App\ProdutosEstoque', 'codigo_produto', 'produto_codigo');
    }
}
