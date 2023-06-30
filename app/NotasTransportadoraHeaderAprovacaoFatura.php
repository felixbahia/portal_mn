<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotasTransportadoraHeaderAprovacaoFatura extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
		'situacao',
		'notas_transportadora_headers_id',
		'created_by',
		'updated_by',
		'deleted_by',
    ];

    public function fatura(){
    	return $this->hasOne("App\NotasTransportadoraHeader", 'id', 'notas_transportadora_headers_id');
    }

    public function createdby(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedby(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedby(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
