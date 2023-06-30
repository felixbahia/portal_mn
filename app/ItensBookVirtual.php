<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItensBookVirtual extends Model 
{
    use SoftDeletes;
    protected $table = 'itens_books_virtuals';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'num_book',
        'cod_produto'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function produto(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'cod_produto');
    }

    public function produtoNasajon(){
        return $this->hasOne('App\ProdutoNasajon', 'codigo', 'cod_produto');
    }

    public function preco(){
        return $this->hasOne('App\Preco', 'codigo_produto', 'cod_produto');
    }
    public function item_foto(){
        return $this->hasOne('App\ProdutoFoto', 'codigo_produto', 'cod_produto');
    }
    public function bookVirtual(){
        return $this->hasOne('App\BookVirtual', 'num_book', 'num_book');
    }

    public function estoque(){
        return $this->hasMany('App\ProdutosEstoque', 'codigo_produto', 'cod_produto');
    }
    public function item_book(){
        return $this->hasOne('App\ItensBookVirtual', 'cod_produto', 'codigo_produto');
    }
}
