<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RenegociacaoTituloParcelaCnpjEstabel extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'renegociacao_titulos_id', 'renegociacao_titulo_parcelas_id', 'estabelecimento_codigo', 'cpf_cnpj', 'data_parcela', 'numero', 'encargos', 'tarifa_bancaria', 'juros', 'valor', 'created_by', 'updated_by', 'deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj','cpf_cnpj');
    }
}
