<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarioCodigoLeitura extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';

    public $guarded = [
        'id',
        'inventario_codigos_id',
        'estabelecimento_posse',
        'produto_codigo',
        'fracao_codigo',
        'contagem_1',
        'contagem_2',
        'contagem_3',
        'saldo',
        'endereco',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
        'importar_inventario_produto'
    ];

    public function produtoEspecificacao(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }
}
