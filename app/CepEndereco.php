<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CepEndereco extends Model
{
    protected $connection = 'cep';
    protected $table = 'endereco';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'cep';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cep', 'logradouro', 'tipo_logradouro', 'complemento', 'local', 'id_cidade', 'id_bairro'
    ];

    protected $hidden = [
        'id_cidade',
        'id_bairro',
    ];

	public function cidadeBusca(){
		return $this->hasOne('App\CepCidade', 'id_cidade', 'id_cidade')->select('cidade', 'id_cidade', "uf", "cod_ibge", 'id_municipio_subordinado');
	}

	public function bairroBusca(){
		return $this->hasOne('App\CepBairro', 'id_bairro', 'id_bairro')->select('bairro', 'id_bairro');
	}
}
