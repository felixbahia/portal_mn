<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class ChequesRecebido extends Model{
    protected $connection = 'srv_prologos';
    protected $table = 'TBCHQ2';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['NCHEQUE', 'NUMCTA', 'AGENCIA', 'BANCO'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'BANCO', 'AGENCIA', 'NUMCTA', 'NCHEQUE', 'CODCAD', 'VALOR', 'INDTERC', 'BOMPARA', 'ORIGEM', 'DTENTRADA', 'ACAO', 'DTACAO', 'DTDEVOLVIDO', 'LIQUIDACAO', 'DTLIQUIDACAO', 'PORTADOR', 'ESTABEL', 'FLAG_IAD', 'NUMDOC_ORIGEM', 'TIPDOC_ORIGEM', 'NAOAUTORIZADO'
    ];
}
