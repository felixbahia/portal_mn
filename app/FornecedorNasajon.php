<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FornecedorNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_fornecedores';
    public $incrementing = false;

    protected $fillable = [
        'id','codigo','cnpj_cpf','nome','nomefantasia','email','logradouro','cep','bairro','uf','pais','ibge','municipio','inscricaoestadual','ddd','telefone','tiposimples'
    ];

    function faccao(){
    	return $this->hasOne('App\Faccao', 'cod_fornecedor', 'cnpj_cpf');
    }

}
