<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoOperacao extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBTOP1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'TIPOPER';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'TIPOPER', 'IDENTIFICACAO', 'CODCFO', 'CODCFO_ST', 'DESCRICAO', 'TIPDOC', 'ESPDOC', 'MODDOC', 'ALIQICM', 'BALICM', 'REDICM', 'APLIC_ICM', 'APLIC_IPI', 'MSGFISC1', 'MSGFISC2', 'FLAGCR', 'FLAGCP', 'FLAGLF', 'FLAGEN', 'FLAGCT', 'FLAGDT', 'FLAGED', 'FLAGEF', 'FLAGEC', 'FLAGES', 'FLAGPM', 'FLAGRT', 'FLAGEV', 'FLAGCV', 'MODALIDADE', 'PORC_PREPAGO', 'DESC_PREPAGO', 'EVENTO_CTB', 'FLAG_IAD', 'CODCFO_CMB', 'CODCFO_SS', 'CODCFO_PP', 'CODCFO_PP_ST', 'APLIC_PIS', 'CODCFO_IMPORT', 'CODCFO_IMPORT_ST', 'PORCDED_ICMST_SS', 'PORC_CREDDEST_SS', 'FLAGDP', 'CONTA_CTB_SPED', 'ENQIPI'
    ];
}
