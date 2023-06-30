<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class ContasReceber extends Model{
    protected $connection = 'srv_prologos';
    protected $table = 'TBCAR2';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['ESTABEL', 'NUMDOC', 'NPARC', 'TIPREG'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ESTABEL', 'NUMDOC', 'NPARC', 'TIPREG', 'CODCAD', 'CODVND', 'ANTEDENTE', 'SITUACAO_CR', 'PORTADOR_CR', 'POSICAO_CR', 'VALOR', 'DTEMIS', 'DTVCTO', 'TXAJUROS', 'NUMDUPBCO', 'ABATIMENTO_CE', 'FLAG_AVISO', 'DATA_AVISO', 'OBSERVACAO', 'FLAG_IAD'
    ];

    public function banco(){
        return $this->hasOne('App\Banco', 'CODBCODIF', 'PORTADOR_CR');
    }

    public function portador(){
        return $this->hasOne('App\Portador', 'CODPORT', 'PORTADOR_CR');
    }
}
