<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoneAuthentication extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';
    
    protected $fillable = [
        'id', 
        'estabelecimento_codigo',
        'stone_code',
        'client_id', 
        'chave_secreta', 
        'serial',
        'producao'
    ];

    public function maquininha(){
        return $this->hasOne('App\StoneCadastroMaquininha', 'stone_code', 'stone_code');
    }
}
