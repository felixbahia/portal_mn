<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FracoesDisponiveisNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.fracoes_disponiveis';
    public $timestamps = false;
    public $incrementing = false;

    public $guarded = [
        'estabelecimento_id',
        'estabelecimento_codigo',
        'produto_codigo',
        'produto_id',
        'fracao_codigo',
        'fracao_id',
        'endereco',
        'saldo',
        'empenhado',
        'fracao_pai',
        'peca_veio_de_fracionamento',
        'grupodeinventario'
    ];

    public function produtoDados(){
        return $this->hasOne('App\ProdutoNasajon', 'produto', 'produto_codigo');
    }

    public function especificacao(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }

    public function estoque(){
        return $this->hasMany('App\ProdutosEstoque', 'codigo_produto', 'produto_codigo');
    }
}
