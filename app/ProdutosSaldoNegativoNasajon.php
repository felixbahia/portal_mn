<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProdutosSaldoNegativoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_saldo_negativo';

    protected $guarded = [
        'estabelecimento_codigo',
        'produto_codigo',
        'produto_descricao',
        'saldo'
    ];

    public function produtoEspecificacao(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }

}
