<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasCreditoReceberNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_creditosemaberto';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'codigo','cod_cliente','nome_cliente','numero','parcela','vencimento','valor','conta_agencia','conta_agencia_digito','conta_numero','conta_digito'
    ];
}
