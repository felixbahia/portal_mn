<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CenprotTitulo extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    protected $table = 'cenprot_titulos';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'devedor_documento', 'titulo_id', 'titulo_numero', 'nosso_umero', 'especie', 'vencimento', 'created_by', 'updated_by', 'deleted_by', 'cenprot_titulo', 'cenprot_status', 'cenprot_mensagem'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function titulosEmAbertoNasajon(){
        return $this->hasOne('App\TitulosEmAbertoNasajon', 'titulo_id', 'titulo_id');
    }

    public function titulosPagosNasajon(){
        return $this->hasOne('App\TitulosPagosNasajon', 'titulo_id', 'id_titulo');
    }

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
