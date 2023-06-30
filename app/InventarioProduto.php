<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarioProduto extends Model
{   
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'estabelecimento', 'codigo_barras', 'codigo_produto', 'quantidade_produto', 'endereco', 'contagem', 'created_by', 'updated_by', 'produto_codigobarras'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [ ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function createdby(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function produto(){
        return $this->hasOne('App\Produto', 'CODPRD', 'codigo_produto');
    }

	public function produtoNasajon(){
		return $this->hasOne('App\ProdutoNasajon', 'codigo', 'codigo_produto');
	}

	public function produtoEspecificacao(){
		return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
	}
}
