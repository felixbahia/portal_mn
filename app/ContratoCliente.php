<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContratoCliente extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $connection = 'pgsql';
    
    protected $fillable = [
        'id', 'user_id', 'ip', 'email', 'cliente_cpf_cnpj', 'cpf_cnpj_cliente', 'created_by', 'updated_by', 'deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function detalhesUsuario(){
        return $this->hasOne('App\User', 'id', 'users_id')->withTrashed();
    }

    public function createdBy(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updateBy(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedBy(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj','cpf_cnpj_cliente');
    }
}
