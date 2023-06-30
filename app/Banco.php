<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBBCO1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'CODBCODIF';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'CODBCODIF', 'NOME', 'AGENCIA', 'NUMCTA', 'CONTAMOV', 'DX', 'PRAZOBOR', 'VERSAO_CE', 'CODEMPRH', 'CODEMPRT', 'CARTEIRA', 'NUMCARTEIRA', 'ESPECIE', 'ACEITE', 'INSTR1', 'INSTR2', 'INSTR3', 'REMESSA', 'ARQREMESSA', 'ARQRETORNO', 'ESTABEL', 'FLAG_IAD', 'NUMDUPINI1', 'NUMDUPFIM1', 'NUMDUPINI2', 'NUMDUPFIM2', 'ESPECIE_NP', 'INF_ESP_BCO', 'MENSAGEM_1', 'MENSAGEM_2', 'MENSAGEM_3', 'MENSAGEM_4', 'INIBE_FLUXO', 'QUEM_EMITE_BOLETO', 'ESPECIE_S', 'CONTA_RED', 'CONVENIO_CP', 'REMESSA_CP', 'RETORNO_CP', 'DC_AGENCIA_CP', 'DC_AGENCIA_E_CONTA_CP'
    ];
}
