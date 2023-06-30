<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campanha extends Model
{
	use SoftDeletes;
	
    protected $fillable = [
		'id',
		'nome',
		'inicio_campanha',
		'fim_campanha',
		'comissao_representante',
		'comissao_vendedor_interno',
		'comissao_gerente',
		'tipo_comissao_representante',
		'tipo_comissao_vendedor_interno',
		'tipo_comissao_gerente',
        'ativo',
        'ultima_alteracao',
		'created_at',
		'created_by',
		'updated_by',
		'deleted_by',
	];

    public function consultaPeriodo(){
        return $this->hasMany('App\CampanhasConsultaMetaVendedoresPeriodo', 'campanha_id', 'id');
    }
    public function log(){
        return $this->hasMany('App\CampanhasLog', 'campanha_id', 'id')->orderBy('id');
    }
	public function periodoApuracao(){
        return $this->hasMany('App\CampanhasApuracaoComissoe', 'campanha_id', 'id')->orderBy('id');
    }
	public function estabelecimentosCampanha(){
        return $this->hasMany('App\CampanhasEstabelecimento', 'campanha_id', 'id');
    }
	public function tipoComissaoVendedorInterno(){
        return $this->hasOne('App\CampanhasComissoesTipo', 'id', 'tipo_comissao_vendedor_interno');
    }
	public function tipoComissaoRepresentante(){
        return $this->hasOne('App\CampanhasComissoesTipo', 'id', 'tipo_comissao_representante');
    }
	public function tipoComissaoGerente(){
        return $this->hasOne('App\CampanhasComissoesTipo', 'id', 'tipo_comissao_gerente');
    }
	public function associacaoPedido(){
        return $this->hasMany('App\CampanhasAssociacaoPedido', 'campanha_id', 'id');
    }
	public function produtosCampanha(){
        return $this->hasMany('App\CampanhasProduto', 'campanha_id', 'id');
    }
    public function createdBy(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedBy(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedBy(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
    public function produtosEstoque(){
        return $this->hasMany('App\ProdutosEstoque', 'campanha_id', 'id');
    }
}
