<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegrasSeparacao extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
		'estabelecimento',
		'quantidade_pecas',
		'tempo',
		'created_by',
		'updated_by',
		'deleted_by',
    ];

    public function emails(){
    	return $this->hasOne("App\Email", 'estabelecimento', 'estabelecimento')->where('token_email', 'alerta_separacao_regra');
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
