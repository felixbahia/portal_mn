<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportadoraEstabelecimento extends Model
{
    protected $connection = 'pgsql';
   
    use SoftDeletes;
 
    public $guarded = [
        'id', 'transportadora_codigo', 'transportadora_nome','estabelecimento',  'tipo_frete', 'uf_origem','uf_destino' ,'created_by','updated_by','deleted_by'
        
    ];

    public function transportadora(){
        return $this->hasOne('App\TransportadorNasajon', 'codigo', 'transportadora_codigo');
    }

    public function createdBy(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedBy(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedBy(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

}