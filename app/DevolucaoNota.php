<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevolucaoNota extends Model
{
    use \Awobaz\Compoships\Compoships;
    use SoftDeletes;
    
    protected $connection = 'pgsql';

    protected $fillable = [
        'nota_id',
        'nota_fiscal',
        'serie',
        'data_emissao',
        'estabelecimento',
        'cliente_cpf_cnpj',
        'cliente_razao_social',
        'valor_parcial',
        'valor',
        'motivo',
        'devolucao_nota_status_id',
        'nome_contato',
        'telefone_contato',
        'responsabilidade_frete',
        'frete_valor',
        'email_contato',
        'observacao',
        'arquivo',
        'motivo_reprovacao',
        'laudo_imagem',
        'nota_cliente_numero',
        'nota_cliente_arquivo',
        'romaneio_arquivo',
        'transportador',
        'transportador_email',
        'nota_cliente',
        'nota_remessa',
        'created_by',
        'updated_by',
        'deleted_by',
        'titulo_credito_uuid_nasajon',
        'titulo_credito_valor',
        'titulo_credito_numero',

    ];

    protected $dates = ['deleted_at'];

    public function nota_nasajon(){
        return $this->hasOne('App\NotaVendaNasajon', 'id_nota', 'nota_id');
    }

    public function nota_remessa_nasajon(){
        return $this->hasOne('App\NotasNasajon', 'id', 'nota_remessa');
    }

    public function motivo_devolucao(){
        return $this->hasOne('App\DevolucaoNotaMotivo', 'id', 'motivo');
    }

    public function produtos(){
        return $this->hasMany('App\DevolucaoNotaProduto', 'devolucao_nota_id', 'id');
    }

    public function status_detalhes(){
        return $this->hasOne('App\DevolucaoNotaStatus', 'id', 'devolucao_nota_status_id');
    }

    public function aprovadores(){
        return $this->hasMany('App\DevolucaoNotaAprovador', 'devolucao_nota_id', 'id');
    }

    public function logs(){
        return $this->hasMany('App\DevolucaoNotaLog', 'devolucao_nota_id', 'id');
    }

    public function createdby(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updatedby(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function deletedby(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

    public function estabelecimentoDetalhes(){
        return $this->hasOne('App\NasajonEstabelecimento', 'codigo', 'estabelecimento');
    }
    
    public function pedido(){
        return $this->hasOne('App\PedidosVendaNasajon', 'notafiscal_id', 'nota_id');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cliente_cpf_cnpj');
    }

    public function tituloPagamentos(){
        return $this->hasMany('App\TituloPagamentoNasajon', 'documento_id', 'nota_id');
    }

    public function titulosAbertos(){
        return $this->hasMany('App\TitulosEmAbertoNasajonPortal', 'nota_id', 'nota_id')->orderBy('vencimento');
    }

    public function titulosDescontados(){
        return $this->hasMany('App\DevolucaoNotaTituloAbertoDescontado', 'devolucao_notas_id', 'id');
    }

    public function titulosCancelados(){
        return $this->hasMany('App\DevolucaoNotaTituloCancelado', 'devolucao_notas_id', 'id');
    }

    public function documentos(){
        return $this->hasMany('App\DevolucaoNotasDocumento', 'devolucao_nota_id', 'id');
    }

    public function lancamentoDebCredVendedor(){
        return $this->hasOne('App\LancamentoDebCredVendedor', 'nota_uuid', 'nota_id');
    }
   
}
