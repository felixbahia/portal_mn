<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProdutosCusto extends Model
{
    use \Awobaz\Compoships\Compoships;

    public $connection = 'pgsql';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = ['estabelecimento', 'produto_codigo'];
    protected $keyType = 'string';

    public $guarded = ['estabelecimento', 'produto_codigo', 'custo_medio_contabil', 'custo_medio_gerencial', 'data_atualizacao', 'custo_medio_armazem','custo_armazem'];

    protected $dates = [
        'data_atualizacao',
    ];

    public function estoqueDetalhes(){
        return $this->hasOne('App\ProdutosEstoque', ['codigo_produto', 'estabelecimento'], ['produto_codigo', 'estabelecimento']);
    }

    public function produtoDetalhes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }
}
