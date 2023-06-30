<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampanhasConsultaMetaVendedoresProduto extends Model
{
	use SoftDeletes;
    
    protected $fillable = [
        'id',
		'vendedor_codigo',
		'bruto',
		'liquido',
		'POS',
        'vendedor_descricao',
		'campanhas_consulta_meta_vendedor_id',
		'kilo',
		'metros',
		'unidade',
		'created_by',
		'updated_by',
		'deleted_by',
	];

    public function vendedor(){
        return $this->hasOne('App\User', 'codigo_representante', 'vendedor_codigo');
    }

    public function produtosContagem(){
        return $this->hasMany('App\CampanhasConsultaMetaVendedoresContagemProduto', 'campanhas_consulta_meta_vendedores_produto_id', 'id');
    }

    public function consultaMeta(){
        return $this->hasOne('App\CampanhasConsultaMetaVendedoresPeriodo', 'id', 'campanhas_consulta_meta_vendedor_id');
    }

    public function usuario(){
        return $this->hasOne('App\User', 'codigo_representante', 'vendedor_codigo');
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
