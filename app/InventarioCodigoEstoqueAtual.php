<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarioCodigoEstoqueAtual extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';

    protected $fillable = [
        'inventario_codigos_id',
        'produto_codigo',
        'produto_id',
        'fracao_codigo',
        'fracao_id',
        'endereco',
        'saldo',
        'empenhado',
        'fracao_pai',
        'peca_veio_de_fracionamento',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function produtoEspecificacao(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }
}
