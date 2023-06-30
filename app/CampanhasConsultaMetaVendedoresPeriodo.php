<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampanhasConsultaMetaVendedoresPeriodo extends Model
{
    
	use SoftDeletes;
    use \Awobaz\Compoships\Compoships;
    
    protected $fillable = [
        'id',
		'campanha_id',
		'campanhas_apuracao_comissoe_id',
		'created_by',
		'updated_by',
		'deleted_by',
	];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function campanhasConsultaMetaVendedores(){
        return $this->hasOne('App\CampanhasConsultaMetaVendedore', 'campanhas_consulta_meta_vendedores_periodo_id', 'id');
    }

    public function apuracaoComissoes(){
        return $this->hasOne('App\CampanhasApuracaoComissoe', 'id', 'campanhas_apuracao_comissoe_id');
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
