<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteBlackListHistorico extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'cpf_cnpj', 'titulo', 'motivo', 'cliente_black_lists_id', 'created_by', 'updated_by', 'deleted_by', 'observacao'
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

    public function baixaTitulo(){
        return $this->hasOne('App\BaixaTitulo', 'titulo_numero', 'titulo');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cpf_cnpj');
    }

    public function blackList(){
        return $this->hasOne('App\ClienteBlackList', 'cpf_cnpj', 'cpf_cnpj');
    }
}
