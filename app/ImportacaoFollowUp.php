<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportacaoFollowUp extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','importacaos_id','tipo_cor', 'envio_cor_data_previsao', 'envio_cor_data_envio', 'envio_cor_data_recebido','quality_sample_aprovacao','quality_sample_data_previsao_envio','quality_sample_data_envio','quality_sample_data_recebido','quality_sample_data_previsao_aprovacao','quality_sample_data_aprovacao','laboratorio_aprovacao','laboratorio_data_previsao_envio','laboratorio_data_envio','laboratorio_data_recebido','laboratorio_data_aprovacao','tempo_producao_previsao_termino','tempo_producao_termino','amostra_embarque_aprovacao','amostra_embarque_data_previsao_envio','amostra_embarque_data_envio','amostra_embarque_data_recebido','amostra_embarque_data_previsao_aprovacao','amostra_embarque_data_aprovacao','autorizacao_embarque_data_previsao_envio','autorizacao_embarque_data_envio','created_by','updated_by','deleted_by','envio_cor_data_revisao','quality_sample_transportadora','quality_sample_awb','amostra_embarque_transportadora','amostra_embarque_awb', 'produto_codigo'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['envio_cor_data_previsao', 'envio_cor_data_envio', 'envio_cor_data_recebido','quality_sample_data_previsao_envio','quality_sample_data_envio','quality_sample_data_recebido','quality_sample_data_previsao_aprovacao','quality_sample_data_aprovacao', 'laboratorio_data_previsao_envio','laboratorio_data_envio','laboratorio_data_recebido','laboratorio_data_aprovacao','tempo_producao_previsao_termino','tempo_producao_termino', 'amostra_embarque_data_previsao_envio','amostra_embarque_data_envio','amostra_embarque_data_recebido','amostra_embarque_data_previsao_aprovacao','amostra_embarque_data_aprovacao','autorizacao_embarque_data_previsao_envio','autorizacao_embarque_data_envio','created_at', 'updated_at', 'deleted_at', 'envio_cor_data_revisao'];

    public function importacaoDetalhes(){
    	return $this->hasOne('App\Importacao', 'id', 'importacaos_id');
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

    public function detalhesProduto(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }
}
