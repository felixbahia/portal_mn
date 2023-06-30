<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConfirmacaoNotaSaida extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $connection = 'pgsql';
    
    protected $table = 'confirmacao_notas_de_saida';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','estabelecimento','nota','cod_cliente','peso', 'data_saida','foto_canhoto','created_by','updated_by','deleted_by','created_at','updated_at','deleted_at','nfce','id_cupom'
    ];

    public function cliente_cnpj(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cod_cliente');
    }

    public function cliente_codigo(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'cod_cliente');
    }

    public function nota_detalhe(){
        return $this->hasOne('App\NotasNasajon', ['numero', 'estabelecimento_codigo'], ['nota', 'estabelecimento']);
    }

    public function nfce_detalhe(){
        return $this->hasOne('App\ContasReceberBaixadoNasajon', ['numero', 'codigo'], ['nota', 'estabelecimento']);
    }
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
