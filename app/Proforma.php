<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Proforma extends Model
{
    protected $connection = 'srv_rondonia';
    protected $table = 'TBPFM1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['ESTABEL', 'NUM_PROFORMA', 'NUMPEDC_GERADO'];

    protected $fillable = [
        'ESTABEL',
        'NUM_PROFORMA',
        'DATA_PROFORMA',
        'NUMPEDC_GERADO',
        'CODFOR',
        'EXPORTADOR',
        'REPRESENTANTE',
        'TAXA_ESTIMADA',
        'MOEDA',
        'TOTAL_PROFORMA',
        'DT_ENVIO_CORDES',
        'DT_APROVACAO_AMOSTRA',
        'DT_TERMINO_PRODUCAO',
        'DT_EMBARQUE',
        'DT_RECEBIMENTO',
        'CONDPAGTO',
        'SITATUAL',
        'USO_CLIENT_ALFA',
        'FLAG_IAD',
    ];

    public function proforma_artigos(){
        return $this->hasMany('App\ProformaArtigo', 'NUM_PROFORMA', 'NUM_PROFORMA');
    }

    public function compra_ativa(){
        return $this->hasOne('App\ComprasRondonia', 'NUMPED', 'NUMPEDC_GERADO');
    }
}
