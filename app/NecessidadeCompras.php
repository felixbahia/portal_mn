<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class NecessidadeCompras extends Model
{
    use SoftDeletes;
    protected $table = 'necessidades_compras';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','produto_codigo','fornecedor_cnpj_cpf','quantidade','quantidade_enviada','saldo','created_by','updated_by','deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function produto_detalhes(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }

    function fornecedor_detalhes(){
    	return $this->hasOne('App\FornecedorNasajon', 'cnpj_cpf', 'fornecedor_cnpj_cpf');
    }

    function necessidade_x_projetos(){
        return $this->hasMany('App\NecessidadeComprasXProjeto', 'necessidades_compras_id', 'id');
    }

    function preco_detalhes(){
        return $this->hasOne('App\Preco', 'codigo_produto', 'produto_codigo');
    }
}
