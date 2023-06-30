<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampanhasComissaoCalculoIten extends Model
{
    protected $fillable = [
		'campanha_comissao_calculo_id',
		'campanha_id',
		'produto_codigo',
		'comissao_padrao',
		'incetivo_percentual_comissao',
		'total_percentual',
		'tipo_comissao_campanha',
		'created_by',
		'updated_by',
		'deleted_by',
	];
    
    public function campanhaComissao(){
        return $this->hasOne('App\CampanhasComissaoCalculo', 'id', 'nota_id')->withTrashed();
    }
    public function campanha(){
        return $this->hasOne('App\Campanha', 'id', 'campanha_id')->withTrashed();
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
