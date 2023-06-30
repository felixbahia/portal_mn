<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CepBairro extends Model
{
    protected $connection = 'cep';
    protected $table = 'bairro';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'id_bairro';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bairro', 'id_cidade'
    ];
    
	public function cidadeBusca2(){
		return $this->hasOne('App\CepCidade', 'id_cidade', 'id_cidade');
	}
}
