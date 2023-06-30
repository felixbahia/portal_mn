<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LiberacaoPilotagemHistorico extends Model
{
    protected $connection = 'pgsql';
    protected $fillable = [
        'id',
        'liberacao_pilotagems_id',
        'estado',
        'users_id'
    ];

    public function liberacaoPilotagem(){
        return $this->hasMany('App\LiberacaoPilotagem', 'id', 'liberacao_pilotagems_id');
    }

    public function usuario(){
        return $this->hasOne('App/User', 'id', 'users_id');
    }

}
