<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampanhasConsultaMetaVendedoresContagemProduto extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'id',
		'campanhas_consulta_meta_vendedores_produto_id',
		'produto_codigo',
		'metros',
		'unidade',
		'kilo',
		'valor',
		'devolucao_metragem',
		'devolucao_valor',
        'pedido_id',
		'created_by',
		'updated_by',
		'deleted_by',
		'created_at',
		'updated_at',
		'deleted_at',
	];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function campanhasConsultaMetaVendedores(){
        return $this->hasOne('App\CampanhasConsultaMetaVendedoresProduto', 'id', 'campanhas_consulta_meta_vendedores_produto_id');
    }

    public function produtoEspecificacao(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }

    public function comissaoCampanhaCalculo(){
        return $this->hasOne('App\CampanhasComissaoCalculo', 'pedido_id', 'pedido_id');
    }

    public function pedidoPortal(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
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
