<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TitulosPagos extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBPGP2';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['ESTABEL', 'MOTIVO', 'DTBAIXA', 'TIPREG', 'NPARC', 'NDOC'];

    protected $fillable = [
    	'CODCAD', 'NDOC', 'NPARC', 'TIPREG', 'DTBAIXA', 'MOTIVO', 'DTEMIS', 'DTVCTO', 'VALBAIXA', 'JUROS', 'DESCONTO', 'REFERENCIA', 'DINHEIRO', 'VALNC_UTIL', 'VALRP_UTIL', 'VALCH_UTIL', 'CODBCO', 'NCHEQUE', 'ESTABEL','FLAG_IAD', 'CODUSU', 'OBSERVACAO_CP', 'BOR_REMESSA', 'BANCOPGTO', 'CODBAR_CP', 'RETORNO_CP', 'AUTENTICACAO_CP',
    ];
}
