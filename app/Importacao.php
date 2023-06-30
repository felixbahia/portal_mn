<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Importacao extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $connection = 'pgsql';
    protected $fillable = [
        'id',
        'estabelecimento_codigo',
        'fornecedor_codigo',
        'numero_proforma',
        'pedido_compras',
        'referencia',
        'data_proforma',
        'data_previsao_carta_programa',
        'data_previsao_recebimento',
        'respresentante_codigo',
        'data_carga_pronta_previsao',
        'data_carga_pronta_realizado',
        'data_embarque_previsao',
        'data_embarque_realizado',
        'data_chegada_porto_previsao',
        'data_chegada_porto_realizado',
        'data_di_previsao',
        'data_di_realizado',
        'data_devolucao_cntr_previsao',
        'data_devolucao_cntr_realizado',
        'data_quality_sample_enviado',
        'data_quality_sample_recebido',
        'data_handlooms_enviado',
        'data_handlooms_recebido',
        'data_strike_off_enviado',
        'data_strike_off_recebido',
        'data_amostra_embarque_enviado',
        'data_amostra_embarque_recebido',
        'aprovacao_amostra_embarque',
        'aprovado_embarque_produto_codigo',
        'tempo_producao',
        'arquivo_carta_programada',
        'created_by',
        'updated_by',
        'deleted_by',
        'importacao_valor_padraos_id',
        'pdf_enviado'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'data_chegada_porto_previsao', 'data_chegada_porto_realizado', 'data_embarque_previsao', 'data_embarque_realizado'];

    public function fornecedor(){
    	return $this->hasOne('App\FornecedorNasajon', 'codigo', 'fornecedor_codigo');
    }

    public function pedidoCompras(){
    	return $this->hasOne('App\ComprasNasajon', 'proforma', 'numero_proforma');
    }

    public function respresentante(){
    	return $this->hasOne('App\FornecedorNasajon', 'codigo', 'respresentante_codigo');
    }

    public function embarqueDetalhes(){
    	return $this->hasOne('App\ImportacaoEmbarque', 'importacaos_id', 'id');
    }

    public function pedidoComprasItens(){
    	return $this->hasMany('App\ComprasNasajon', 'proforma', 'numero_proforma')->orderBy('cod_produto');
    }
    
    public function aprovadoEmbarqueProdutoDetalhes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'aprovado_embarque_produto_codigo');
    }

    public function documentos(){
    	return $this->hasMany('App\ImportacaoDocumento', 'importacaos_id', 'id');
    }

   public function custos(){
    	return $this->hasOne('App\ImportacaoCusto', 'importacaos_id', 'id');
    }

    public function custosOutraDespesa(){
    	return $this->hasMany('App\ImportacaoCustoOutraDespesa', 'importacaos_id', 'id');
    }

    public function financeiro(){
    	return $this->hasOne('App\ImportacaoFinanceiro', 'importacaos_id', 'id');
    }

    public function itens(){
    	return $this->hasMany('App\ImportacaoItem', 'importacaos_id', 'id')->orderBy('produto_codigo');
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

    public function valorPadrao(){
        return $this->hasOne('App\ImportacaoValorPadrao', 'id', 'importacao_valor_padraos_id')->withTrashed();
    }

    public function valorPadraoTotal(){
        return $this->hasOne('App\ImportacaoValorPadrao', 'id', 'importacao_valor_padraos_id')
            ->withTrashed()
            ->select(DB::raw("SUM(taxa_siscomex+sda+honorarios+expediente+armazenagem+laudo) as total"));
    }

    public function pedidoComprasDetalhes(){
    	return $this->hasOne('App\ComprasNasajon', 'proforma', 'numero_proforma');
    }

    public function valorPadraoTotalItemFob(){
        return $this->hasOne('App\ComprasNasajon', 'proforma', 'numero_proforma')
            ->select(DB::raw("SUM(preco_compra) as total"));
    }

    public function valorPadraoTotalItemContabil(){
        return $this->hasOne('App\ImportacaoItem', 'importacaos_id', 'id')
            ->select(DB::raw("SUM(valor_contabil_unitario) as total"));
    }

    public function followUpDetalhes(){
    	return $this->hasOne('App\ImportacaoFollowUp', 'importacaos_id', 'id')->whereNull('produto_codigo');
    }
    public function followUpDetalhesItens(){
    	return $this->hasMany('App\ImportacaoFollowUp', 'importacaos_id', 'id')->whereNotNull('produto_codigo');
    }
}
