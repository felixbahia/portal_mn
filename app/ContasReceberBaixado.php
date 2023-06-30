<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ContasReceberBaixado extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBPGR2';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['ESTABEL', 'MOTIVO', 'DTBAIXA', 'TIPREG', 'NPARC', 'NUMDOC'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ESTABEL', 'NUMDOC', 'NPARC', 'TIPREG', 'DTBAIXA', 'MOTIVO', 'CODCAD', 'VALOR', 'JUROSREC', 'DESCONTO', 'CODVND', 'ATENDENTE', 'DTEMIS', 'DTVCTO', 'TAXAMES', 'NUMDUPBCO', 'DTMOV', 'SITUACAO_CR', 'PORTADOR_CR', 'ABATIMENTO', 'DESPESA', 'REEMBOLSO', 'VALTOTREC', 'OBSERVACAO', 'FLAG_IAD', 'CODUSU'
    ];
}
