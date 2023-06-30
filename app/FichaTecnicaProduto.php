<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FichaTecnicaProduto extends Model
{
	use SoftDeletes;

    protected $fillable = [
	    'id','lancamento_projetos_id','codigo_produto','preco_venda','created_by','updated_by','deleted_by','created_at','updated_at','deleted_at'
	];

	public function produto_detalhes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
	}
	
	public function preco(){
        return $this->hasOne('App\Preco', 'codigo_produto', 'codigo_produto');
	}
	
	public function tecidos(){
		return $this->hasMany('App\FichaTecnicaProdutoTecido', 'ficha_tecnica_produtos_id', 'id');
	}

	public function insumos(){
		return $this->hasMany('App\FichaTecnicaProdutoInsumo', 'ficha_tecnica_produtos_id', 'id');
	}

	public function servicos(){
		return $this->hasMany('App\FichaTecnicaProdutoServico', 'ficha_tecnica_produtos_id', 'id');
	}

	public function estoque(){
		return $this->hasOne('App\ProdutosEstoque', 'codigo_produto', 'codigo_produto');
	}

	public function info_adicional(){
		return $this->hasOne('App\FichaTecnicaInfoAdicional', 'ficha_tecnica_produtos_id', 'id');
	}

	public function etiquetas(){
		return $this->hasMany('App\FichaTecnicaArquivo', 'ficha_tecnica_produtos_id', 'id')->where('tipo', 'etiqueta');
	}

	public function medidas(){
		return $this->hasMany('App\FichaTecnicaTabelaMedidas', 'ficha_tecnica_produtos_id', 'id');
	}

	public function sequencia_operacional(){
		return $this->hasMany('App\FichaTecnicaSequenciaOperacional', 'ficha_tecnica_produtos_id', 'id');
	}

	public function montagem(){
		return $this->hasMany('App\FichaTecnicaArquivo', 'ficha_tecnica_produtos_id', 'id')->where('tipo', 'montagem');
	}

	public function produtoNasajon(){
		return $this->hasOne('App\ProdutoNasajon', 'codigo', 'codigo_produto');
	}


	public function tecidosTodos(){
		return $this->hasMany('App\FichaTecnicaProdutoTecido', 'ficha_tecnica_produtos_id', 'id')->withTrashed();
	}

	public function insumosTodos(){
		return $this->hasMany('App\FichaTecnicaProdutoInsumo', 'ficha_tecnica_produtos_id', 'id')->withTrashed();
	}

	public function servicosTodos(){
		return $this->hasMany('App\FichaTecnicaProdutoServico', 'ficha_tecnica_produtos_id', 'id')->withTrashed();
	}
	
}
