<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampanhasProduto extends Model
{
    use \Awobaz\Compoships\Compoships;
    use SoftDeletes;

    protected $fillable = [
		'id',
		'campanha_id',
		'produto_codigo',
		'created_by',
		'updated_by',
		'deleted_by',
		'created_at',
		'updated_at',
		'deleted_at',
	];

    public function contagemProdutoCampanha(){
        return $this->hasMany('App\CampanhasConsultaMetaVendedoresContagemProduto', 'produto_codigo', 'produto_codigo');
    }

    public function produtoEspecificacao(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }

    public function pedidoItem(){
        return $this->hasMany('App\PedidoItemPortal', ['cod_produto','campanha_id'], ['produto_codigo','campanha_id']);
    }

    public function produtoEstoque(){
        return $this->hasMany('App\ProdutosEstoque', 'codigo_produto', 'produto_codigo');
    }

    public function campanha(){
        return $this->hasOne('App\Campanha', 'id', 'campanha_id');
    }

    public function createdBy(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updatedBy(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function deletedBy(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
