<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampanhasApuracaoComissoe extends Model
{
	use SoftDeletes;
    use \Awobaz\Compoships\Compoships;
    
    protected $fillable = [
        'id',
		'campanha_id',
		'campanhas_apuracao_comissoe_tipo_id',
		'inicio_periodo',
		'fim_periodo',
		'meta_reais',
		'meta_metros',
		'created_by',
		'updated_by',
		'deleted_by',
	];

    public function consultaMetaVendedoresPeriodo(){
        return $this->hasOne('App\CampanhasConsultaMetaVendedoresPeriodo', ['campanha_id','campanhas_apuracao_comissoe_id'], ['campanha_id','id']);
    }

    public function tipoApuracao(){
        return $this->hasOne('App\CampanhasApuracaoTipo', 'id', 'campanhas_apuracao_comissoe_tipo_id');
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
