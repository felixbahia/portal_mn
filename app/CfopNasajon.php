<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CfopNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_cfop';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'cfop_id', 'cfop_codigo', 'cfop_descricao'
    ];
}
