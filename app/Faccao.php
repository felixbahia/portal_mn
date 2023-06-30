<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faccao extends Model
{
    use SoftDeletes;
    protected $table = 'faccaos';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','cod_fornecedor','created_by','updated_by','deleted_by','created_at','updated_at','deleted_at'
    ];

    function fornecedor(){
    	return $this->hasOne('App\FornecedorNasajon', 'cnpj_cpf', 'cod_fornecedor');
    }
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
