<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportacaoItem extends Model
{
    use SoftDeletes;
    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'importacaos_id', 'produto_codigo', 'valor_contabil_unitario', 'estabelecimento_codigo', 'numero_proforma', 'pedido_compras', 'created_by', 'updated_by', 'deleted_by', 'nota_uuid_nasajon'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

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

    public function detalhesProdutoNasajon(){
        return $this->hasOne('App\ProdutoNasajon', 'codigo', 'produto_codigo');
    }

    public function detalhesProdutoInformacoesAdicionais(){
        return $this->hasOne('App\InformacaoAdicionalProduto', 'cod_produto', 'produto_codigo');
    }

    public function detalhesCompras(){
        return $this->hasOne('App\ComprasNasajon', ['cod_produto', 'id_nota'], ['produto_codigo', 'nota_uuid_nasajon']);
    }
    
}
