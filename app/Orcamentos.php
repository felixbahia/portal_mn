<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Orcamentos extends Model
{
    protected $connection = 'srv_ww';
    protected $table = 'WWORM';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['CODWEB', 'NUMORC'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'CODWEB', 'NUMORC', 'DH_ORC', 'TIPO_PESSOA', 'CODCLI', 'COMISSAO', 'CONTATO', 'EMAIL', 'OBSINTERNA', 'SITORC', 'DATASIT', 'NOVOORC', 'ESTABEL', 'NUMPED', 'SITPVM', 'MODALIDADE', 'TIPO_FRETE', 'CODTRAN', 'CODVCTO', 'PENDENCIA', 'DATA_ENTREGA', 'HORARIO_ENTREGA', 'TXT_TRANSP', 'TXT_PAGTO', 'TXT_CADASTRO', 'CODUSU', 'OBS_CODUSU', 'COPIA_PEDIDO_EMITIDA', 'NUMPEDCMP', 'EQUIPE', 'AREA_CRYSTAL', 'DH_ENTRADA', 'FAIXA_PRAZO', 'PRAZO_VCTO', 'APROVACAO_PRZ', 'OK_DO_PRAZO', 'DH_OK_PRAZO', 'CODCLI_PCO', 'NOME_PCO'
    ];

    public function itensOrcamento(){
        return $this->belongsTo('App\ItensOrcamento', 'CODWEB', 'CODWEB')->where('NUMORC',$this->NUMORC);
    }

    public function formapagamento(){
        return $this->belongsTo('App\Vencimentos', 'CODVCTO', 'CODVCT');
    }

    public function vendedor(){
        return $this->belongsTo('App\VendedorWw', 'CODWEB', 'CODWEB');
    }

    public function transportador(){
        return $this->belongsTo('App\Transportador', 'CODTRAN', 'CODTRAN');
    }
}
