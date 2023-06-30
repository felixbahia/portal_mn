<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AjusteEstoqueNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $connection = 'nasajon';
    protected $table = "integracoes.vw_ajustesestoque";
    public $incrementing = false;
    public $primaryKey = 'id';
    protected $keyType = 'string';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'data', 'numero_movimento', 'codigo_estabelecimento', 'codigo_produto', 'descricao_produto', 'tipo', 'quantidade', 'usuario', 'id'
    ];

    public function detalhes_produto(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }

    public function custos(){
        return $this->hasOne('App\Preco', 'codigo_produto', 'codigo_produto');
    }

    public function detalhes_ajuste_estoque(){
        return $this->hasOne('App\AjusteEstoque', 'movimento_ajuste_estoque_nasajon', 'id');
    }    
}
