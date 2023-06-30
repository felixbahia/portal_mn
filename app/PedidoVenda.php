<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PedidoVenda extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBPVM4';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['ESTABEL', 'NUMPED'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'ESTABEL', 'NUMPED', 'DATA_PEDIDO', 'TIPOPER', 'CODCAD', 'CODVND', 'COMISSAO_VND', 'ATENDENTE', 'CONTATO', 'SEUNUMPED', 'CODTRAN', 'VIATRAN', 'LOCAL_ENTREGA', 'FORMAPGTO', 'CODVCT', 'DESCRICAO_VCT', 'FATORPRAZO', 'FATORCLIENTE', 'DESCGERAL', 'VALTOTPED', 'SALDOTOTPED', 'DESCONTO_APLICADO', 'TIPO_FRETE', 'PORC_FRETE', 'VALM_FRETE', 'PORC_SEGURO', 'VALM_SEGURO', 'PORC_OUTRAS', 'VALM_OUTRAS', 'DATA_INI', 'HORA_INI', 'CODUSU_ABRIU', 'CODUSU_ALTEROU', 'SIT_CRED', 'CODUSU_CRED', 'SIT_COND', 'CODUSU_COND', 'SIT_PRECO', 'CODUSU_PRECO', 'MODALIDADE', 'SITATUAL', 'EMPENHO_OK', 'FLAG_NOVO_EMPENHO', 'ROMANEIO_OK', 'PAGAMENTO_OK', 'PENDENCIA', 'MLD_GUERRA', 'OBSINTERNA', 'DATA_ENTREGA', 'HORARIO_ENTREGA', 'EXECUTOR1', 'EXECUTOR2', 'DATA_FIM', 'HORA_FIM', 'MSGPED_NF1', 'MSGPED_NF2', 'DESC_STOTAL', 'ESTABEL_FAT', 'NUMULTNF', 'NUMULTDUE', 'FLAG_IAD', 'NFREF_NUMDOC', 'NFREF_CODCAD', 'NFREF_MA', 'NFREF_DTEMIS', 'USO_CLIENTE_ALFA', 'DH_APROVACAO'
    ];

    public function itens_pedido(){
        return $this->hasMany('App\ItensPedido', 'NUMPED', 'NUMPED');
    }

    public function itensPedido(){
        return $this->belongsTo('App\ItensPedido', 'NUMPED', 'NUMPED');
    }

    public function formaPagamento(){
        return $this->belongsTo('App\Vencimentos', 'CODVCT', 'CODVCT');
    }

    public function vendedor(){
        return $this->belongsTo('App\Vendedor', 'CODVND', 'CODVND');
    }

    public function transportador(){
        return $this->belongsTo('App\Transportador', 'CODTRAN', 'CODTRAN');
    }

    public function tipoOperacao(){
        return $this->belongsTo('App\TipoOperacao', 'TIPOPER', 'TIPOPER');
    }

    public function cliente(){
        return $this->belongsTo('App\Cliente', 'CODCAD', 'CODCAD');
    }

    public function tipo_operacao(){
        return $this->hasOne('App\Cliente', 'TIPOPER', 'TIPOPER');
    }

    public function condicao_pagamento(){
        return $this->hasOne('App\Vencimentos', 'CODVCT', 'CODVCT');
    }

    public function pedido_portal(){
        return $this->hasOne('App\PedidoPortal', 'pedido_gerado', 'NUMPED');
    }
}
