<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ParcelamentoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'crm.parcelamentos';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = 'parcelamento';
    protected $keyType = 'string';

    public $guarded = ['codigo', 'nome', 'incluirissprimeiraparcela', 'intervaloexatoentrevencimentos', 'quantidadeparcelas', 'percentualjuros', 'versao', 'parcelamento', 'lastupdate', 'tenant'];

    public function parcelas(){
        return $this->hasMany('App\ParcelasParcelamentoNasajon', 'parcelamento', 'parcelamento');
    }

}
