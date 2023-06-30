<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBCAD1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'CODCAD';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'CODCAD', 'NOME', 'GUERRA', 'CEP', 'ENDERECO', 'BAIRRO', 'CIDADE', 'ESTADO', 'CGC_CPF', 'IEST', 'IMUN', 'TELEFONE', 'FAX', 'EMAIL', 'CODREGIAO', 'CODAREA', 'CODZONA', 'CONTATO', 'DTDESDE', 'PENDENCIA', 'ATIVIDADE', 'DIVISAO', 'TIPOPER', 'REPASSE', 'FATOR_CLIENTE', 'CODVND', 'COMISSAO', 'CODVCT', 'FORMA_PGTO', 'SITUACAO_CR', 'CODTRAN', 'TPEMISNF', 'TPAGRUPA', 'CONCEITO', 'LIMCRED', 'INDJUROS', 'ALERTA', 'SUFRAMA', 'TARE', 'CODCAD_ENTREGA', 'CEP_COBRANCA', 'ENDERECO_COBRANCA', 'BAIRRO_COBRANCA', 'CIDADE_COBRANCA', 'ESTADO_COBRANCA', 'FONE_COBRANCA', 'DTULTVND', 'VLULTVND', 'DTMAIORVND', 'VLMAIORVND', 'DTMAIORACUM', 'VLMAIORACUM', 'DTULTVND_DONO', 'DTULTATR', 'DIASULTATR', 'DTMAIORATR', 'DIASMAIORATR', 'DIASATR_1', 'DIASATR_2', 'DIASATR_3', 'DIASATR_4', 'DIASATR_5', 'SIGLAIED', 'DTULTPRCREP', 'INDPRCBASE', 'PRAZOVCT', 'PARCELA_IPI', 'DTULTLNF_E', 'ESTABEL', 'DHALTCAD', 'FLAG_IAD', 'COMISSAO_SERVICO', 'IND_GNRE', 'USO_CLIENTE_ALFA', 'USO_CLIENTE_NUM', 'DADOS1', 'DADOS2', 'DADOS3', 'CELULAR', 'EMAIL_ADIC1', 'EMAIL_ADIC2', 'DADOS4', 'IND_PROTESTAR_SN', 'IND_VALMINVCT_SN', 'IND_TARIFABOL_SN'
    ];

   public function pedido_portal(){
        return $this->belongsTo('App\PedidoPortal', 'cod_cliente', 'CODCAD');
   }

    function cliente_condicao(){
        return $this->belongsTo('App\Cliente', 'cliente_id', 'CODCAD');
    }

    function estado_detalhe(){
        return $this->hasOne('App\CepEstado', 'uf', 'ESTADO');
    }
}
