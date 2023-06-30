<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientePrePago extends Model
{

    use SoftDeletes;

    protected $connection = 'pgsql';
    protected $table = 'cliente_pre_pago';
    protected $fillable = ['cpf_cnpj', 'created_by', 'updated_by', 'deleted_by'];

    public function cliente_nasajon(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cpf_cnpj');
    }

    public function created_by(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updated_by(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function deleted_by(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
