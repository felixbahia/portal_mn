<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FaturamentoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'nsview.vw_faturamento_cfop';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'Identificador Documento', 'Identificador Documento Rateado', 'Identificador Estabelecimento', 'Estabelecimento', 'Nome do Estabelecimento', 'Origem do Documento', 'Número Documento', 'Data de Emissão', 'Identificador Cliente', 'Cliente', 'Nome do Cliente', 'Documento do Cliente', 'Valor Documento', 'Tipo do Documento', 'Identificador Operação', 'Código da Operação', 'Descrição da Operação', 'Cfop'
    ];

    function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'id', 'Identificador Cliente');
    }

    public function detalhesDeCondicoesPagamentos(){
        return $this->hasMany('App\CondicoesPagamentoNasajon', 'id_docfis', 'Identificador Documento');
    }

    public function itens_nota(){
        return $this->hasMany('App\NotaItensNasajon', 'id_docfis', 'Identificador Documento');
    }
}
