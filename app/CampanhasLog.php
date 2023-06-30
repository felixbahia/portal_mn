<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampanhasLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
		'id',
		'campanha_id',
		'campanhas_acoe_id',
        'descricao',
		'created_by',
		'updated_by',
		'deleted_by',
	];

    public function tipoAcoes(){
        return $this->hasOne('App\CampanhasLogsAcoe', 'id', 'campanhas_acoe_id');
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
