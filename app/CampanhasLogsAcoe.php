<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampanhasLogsAcoe extends Model
{
    
    use SoftDeletes;

    protected $fillable = [
		'id',
		'acao',
		'created_by',
		'updated_by',
		'deleted_by',
	];

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
