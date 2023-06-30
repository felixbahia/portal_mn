<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProformaArtigo extends Model
{
    protected $connection = 'srv_rondonia';
    protected $table = 'TBPFA1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['ESTABEL', 'NUM_PROFORMA', 'ID_ART'];

    protected function getEstabelecimentoAttribute(){
        return $this->ESTABEL;
    }
    protected function getIdArtigoAttribute(){
        return $this->ID_ART;
    }

    protected $filable = [
        'ESTABEL',
        'NUM_PROFORMA',
        'ID_ART',
        'COD_ART',
        'DESCR_ART',
        'QTD_ART',
        'UNID_ART',
        'PU_FOB_ART',
        'PU_ESTIM_ART',
        'PU_REAL_ART',
        'USO_CLIENT_ALFA',
        'FLAG_IAD'
    ];

    public function proforma_produto(){
        return $this->hasMany('App\ProformaProduto', 'NUM_PROFORMA', 'NUM_PROFORMA');
    }

    public function proforma(){
        return $this->belongsTo('App\Proforma', 'NUM_PROFORMA', 'NUM_PROFORMA');
    }
}
