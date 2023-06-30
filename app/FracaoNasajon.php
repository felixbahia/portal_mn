<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FracaoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_fracoes';
    public $timestamps = false;
    public $incrementing = false;

    public $guarded = [
        'quantidade', 'descricao', 'codigo_produto', 'codigo', 'fracao','localdeestoqueendereco'
    ];

    public function produtoDados(){
        return $this->hasOne('App\ProdutoNasajon', 'produto', 'codigo_produto');
    }
    public function localEstoqueEndereco(){
        return $this->hasOne('App\LocalDeEstoqueEnderecoNasajon', 'localdeestoqueendereco', 'localdeestoqueendereco');
    }
}