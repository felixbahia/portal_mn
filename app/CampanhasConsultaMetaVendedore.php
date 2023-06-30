<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampanhasConsultaMetaVendedore extends Model
{
	use SoftDeletes;
    
    protected $fillable = [
        'id',
		'metros',
		'valor',
		'devolucao',
		'kilo',
		'logo',
		'unidade',
		'metros_medida',
		'periodo',
		'campanhas_consulta_meta_vendedores_periodo_id',
		'created_by',
		'updated_by',
		'deleted_by',
	];

    public function periodoMetaProduto(){
        return $this->hasMany('App\CampanhasConsultaMetaVendedoresProduto', 'campanhas_consulta_meta_vendedor_id', 'id');
    }
    public function periodoMetaVendedores(){
        return $this->hasOne('App\CampanhasConsultaMetaVendedoresPeriodo', 'id', 'campanhas_consulta_meta_vendedores_periodo_id');
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
