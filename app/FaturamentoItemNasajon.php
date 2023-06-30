<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FaturamentoItemNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_faturamentos_itens';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'Identificador Documento','Identificador Documento Rateado','Identificador Estabelecimento','Estabelecimento','Nome do Estabelecimento','Origem do Documento','Número Documento','Data de Emissão','Identificador Cliente','Cliente','Nome do Cliente','Documento do Cliente','Valor Documento','Tipo do Documento','Identificador Operação','Código da Operação','Descrição da Operação','Mês','Ano','Item - Código','Item - Descricao','Item - Quantidade','Item - Valor Total','Item - CFOP'
    ];
    
    public function faturamentoDetalhes(){
        return $this->hasOne('App\FaturamentoNotaNasajon', 'Identificador Documento', 'Identificador Documento');
    }

    public function produtoNasajonDetalhes(){
        return $this->hasOne('App\ProdutoNasajon', 'codigo', 'Item - Código');
    }

    public function produtoDetalhes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'Item - Código');
    }
}
