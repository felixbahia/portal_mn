<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ParcelasParcelamentoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'crm.parcelas';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = 'parcela';
    protected $keyType = 'string';

    public $guarded = ['quantidadediapagamento', 'percentualpagamento', 'versao', 'parcelamento', 'parcela', 'lastupdate', 'tenant'];

}
