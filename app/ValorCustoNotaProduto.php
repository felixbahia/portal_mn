<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ValorCustoNotaProduto extends Model
{

	use SoftDeletes;

    protected $fillable = [
        'valor_custo_notas_id', 'codigo_produto', 'custo', 'numero_pedido', 'proforma', 'quantidade', 'valor_dolar', 'custo_gerencial', 'created_by', 'updated_by', 'deleted_by'
    ];

    public function nota(){
        return $this->hasOne('App\ValorCustoNota','id', 'valor_custo_notas_id');
    }
    public function compras_nasajon(){
        return $this->hasOne('App\ItensNotasCompraNasajon', 'numero', 'nota_numero')->where('produto_codigo', $this->codigo_produto);
    }
    public function especificacoes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
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
}
