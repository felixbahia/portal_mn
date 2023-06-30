<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CepCidade extends Model
{
    protected $connection = 'cep';
    protected $table = 'cidade';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'id_cidade';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_cidade', 'cidade', 'uf', 'cod_ibge', 'area', 'id_municipio_subordinado'
    ];

	public function estadoBusca(){
		return $this->hasOne('App\CepEstado', 'uf', 'uf');
	}

    public function estabelecimento(){
        $this->belongsTo('App\Estabelecimento', 'UF', 'uf');
    }
}
