<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AjusteEstoque extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'estabelecimento_codigo', 'produto_codigo', 'pecas_codigo', 'produto_lote_nasajon', 'quantidade_anterior', 'quantidade_ajuste', 'motivos_ajuste_estoque_id', 'data', 'hora', 'created_by', 'updated_by', 'deleted_by', 'local_de_estoque_codigo', 'local_de_estoque_nasajon', 'movimento_ajuste_estoque_nasajon','ajuste_fracao'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function motivo(){
        return $this->hasOne('App\MotivoAjusteEstoque', 'id', 'motivos_ajuste_estoque_id');
    }

    public function detalhesProduto(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
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


    public function localDeEstoque(){
        return $this->hasOne('App\LocalDeEstoqueNasajon', 'localdeestoque', 'local_de_estoque_nasajon');
    }
}
