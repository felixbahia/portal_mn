<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProformaProduto extends Model
{
    protected $connection = 'srv_rondonia';
    protected $table = 'TBPFP1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['ESTABEL', 'NUM_PROFORMA', 'ID_ART'];

    protected $fillable = [
        'ESTABEL',
        'NUM_PROFORMA',
        'ID_ART',
        'CODPRD',
        'QTD',
        'USO_CLIENTE_ALFA',
        'FLAG_IAD'
    ];

    public function proforma_artigo(){
        return $this->belongsTo('App\ProformaArtigo', 'NUM_PROFORMA', 'NUM_PROFORMA');
    }

}
