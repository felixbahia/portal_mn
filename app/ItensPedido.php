<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ItensPedido extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBPVI4';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['ESTABEL', 'CODIGO', 'NUMPED'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'ESTABEL', 'NUMPED', 'CODIGO', 'MARCA', 'DESCR', 'TPDESCR', 'UNIDADE', 'LOCAL_FISICO1', 'LOCAL_FISICO2', 'QTDPED', 'QTDEMP', 'QTDFAT', 'MODALIDADE_PRECO', 'PORCDESC', 'PU_ITEM_LIQ', 'ALIQIPI', 'DESCTO_APLICADO', 'SIT_DESC', 'FLAGEN', 'DTENTREGA', 'TRIBUTACAO', 'SIT_FAT', 'DATA_FAT', 'W_CFO', 'W_CST', 'W_ALIQICM', 'W_BASE_ICMST', 'W_PRECOTOT', 'W_VALOR_PREPAGO', 'FLAG_IAD', 'W_FATOR_REDICM', 'PU_ND_AGREGADO', 'W_IND_GNRE', 'W_VALOR_FRETE', 'W_VALOR_SEGURO', 'W_VALOR_OUTRAS', 'W_VALOR_ICMST', 'W_VALOR_IPI', 'W_CST_PIS', 'W_CST_COFINS', 'W_ALIQ_PIS', 'W_ALIQ_COFINS', 'W_FATOR_REDICM_ST', 'W_BASECALC_ICM', 'W_VALRED_BASEICM', 'W_VALOR_ICM', 'W_TRIBIPI', 'W_CST_IPI', 'W_BASECALC_IPI'
    ];

    public function informacao_produto(){
        return $this->hasOne('App\Produto', 'CODPRD', 'CODIGO');
    }

    public function produto(){
        return $this->belongsTo('App\Produto', 'CODIGO', 'CODPRD');
    }
}
