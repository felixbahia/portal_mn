<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AtualizacaoCron extends Model
{

    protected $connection = 'pgsql';
    public $timestamps = false;    
    protected $table = 'atualizacao_cron';
    
    public $fillable = [
        'id', 'token', 'descricao', 'atualizacao', 'erro', 'alerta_erro','inicio_atualizacao'
    ];
}
