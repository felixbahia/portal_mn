<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteBlackList extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'cpf_cnpj', 'created_by', 'updated_by', 'deleted_by', 'status_cliente_black_lists_id', 'motivo_cliente_black_lists_id', 'observacao'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cpf_cnpj');
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

    public function statusDetalhes(){
        return $this->hasOne('App\StatusClienteBlackList', 'id', 'status_cliente_black_lists_id');
    }

    public function titulos(){
        return $this->hasMany('App\ClienteBlackListTitulo', 'cliente_black_lists_id', 'id');
    }

    public function detalhesMotivo(){
        return $this->hasOne('App\MotivoClienteBlackList', 'id', 'motivo_cliente_black_lists_id');
    }
}
