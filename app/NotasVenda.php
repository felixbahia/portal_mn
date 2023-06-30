<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasVenda extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'notas_vendas';
    protected $fillable = ['estabelecimento','mes_data_entrada','ano_data_entrada','codigo_produto','quantidade','valor','valor_tabela'];

    public function estoque(){
        return $this->hasMany('App\ProdutosEstoque', 'codigo_produto', 'codigo_produto');
    }

}
