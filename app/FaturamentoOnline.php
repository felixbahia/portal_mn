<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FaturamentoOnline extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $connection = 'pgsql';
    protected $table = 'faturamento_online';
	protected $fillable = [
        'estabelecimento', 'numero_documento', 'codigo_cadastro', 'tipo_operacao', 'codigo_vendedor', 'data', 'valor_compra', 'valor_frete', 'valor_ipi', 'valor_troco', 'chave_validacao', 'tabela_data', 'numero_nota', 'serie_nota', 'nome_cliente', 'nasajon', 'valor_prepago', 'valor_frete_cobrado', 'transportador_uuid', 'transportador_codigo', 'tipo_frete', 'nota_uuid'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['data'];

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'codigo_cadastro');
    }

    public function fornecedor(){
        return $this->hasOne('App\FornecedorNasajon', 'codigo', 'codigo_cadastro');
    }

    public function nota(){
        return $this->hasOne('App\NotasNasajon', 'id', 'nota_uuid');
    }

    public function transportador(){
        return $this->hasOne('App\TransportadorNasajon', 'codigo', 'transportador_codigo');
    }

    public function notaEntrada(){
        return $this->hasOne('App\NotasEntradasNasajon', 'Identificador Documento', 'nota_uuid');
    }
    
    public function faturamentoNotaNasajon(){
        return $this->hasOne('App\FaturamentoNotaNasajon', 'Identificador Documento', 'nota_uuid');
    }

    public function itensFaturamentoNotaNasajon(){
        return $this->hasMany('App\FaturamentoItemNasajon', 'Identificador Documento', 'nota_uuid');
    }

    public function detalhesCondicoesPagamentos(){
        return $this->hasMany('App\FaturamentoOnlineCondicaoDePagamento', 'faturamento_online_id', 'id')->whereNull('deleted_at');
    }

    public function notasCanceladadas(){
        return $this->hasOne('App\NotasCanceladasNasajon', 'id', 'nota_uuid');
    }
}
