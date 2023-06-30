<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class LogEmail extends Model
{
   

    protected $connection = 'pgsql';

    protected $fillable = [
        'id','emails_id', 'tipo_doc', 'numero_doc','nome_pessoa','valor', 'observacao','enviado','created_at','updated_at'
    ];


    public function email(){
        return $this->hasOne('App\Email', 'id', 'emails_id');
    }

}
