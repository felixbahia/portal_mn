<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetrinApiRetorno extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_netrin_api', 'json_retorno', 'alerta_erro', 'status_code','mensagem','created_at','updated_at','deleted_at','atualizado_nasajon'
    ];

    protected $dates = ['deleted_at', 'created_at', 'updated_at'];



    public function netrinApi(){
        return $this->hasOne('App\NetrinApi', 'id', 'id_netrin_api');
    }


}
