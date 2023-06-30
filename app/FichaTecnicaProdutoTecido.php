<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FichaTecnicaProdutoTecido extends Model
{
    use SoftDeletes;

    protected $fillable = [
	    'id','ficha_tecnica_produtos_id','codigo_produto','consumo_unitario','created_by','updated_by','deleted_by','created_at','updated_at','deleted_at'
    ];
    
    public function tecido_detalhes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }

    public function preco(){
        return $this->hasOne('App\Preco', 'codigo_produto', 'codigo_produto');
    }

	public function estoque(){
		return $this->hasMany('App\ProdutosEstoque', 'codigo_produto', 'codigo_produto');
    }
    
    public function custoPortal(){
        return $this->hasMany('App\ProdutosCusto', 'produto_codigo', 'codigo_produto');
    }

    public function produtoNasajon(){
		return $this->hasOne('App\ProdutoNasajon', 'codigo', 'codigo_produto');
	}
}
