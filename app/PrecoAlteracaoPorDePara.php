<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PrecoAlteracaoPorDePara extends Model
{
    protected $table = "preco_alteracao_por_de_para";
    
    protected $fillable = [
        'id',
        'produto_codigo_de',
        'preco_venda_de',
        'produto_codigo_para',
        'preco_venda_para',
        'preco_venda_novo_para',
    ];
}
