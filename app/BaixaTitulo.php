<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaixaTitulo extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'titulo_nasajon_id', 'titulo_numero','data_baixa','valor_recebido','desconto_valor','taxa_boleto_banco_valor','honorarios_cliente_valor','honorarios_mn_valor','created_by','updated_by','deleted_by', 'juros_valor', 'quitar', 'observacao', 'retorno_nasajon', 'tarifa_bancaria_mn', 'multa'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
