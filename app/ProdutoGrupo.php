<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProdutoGrupo extends Model
{
    protected $table = 'produto_grupos';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'descricao',
        'largura',
        'gramatura_tipo',
        'gramatura_gm2',
        'gramatura_gml',
        'rendimento',
        'tipo_genero',
        'segmentos_id',
        'familias_id',
        'padrao_codigo',
        'caracteristicas',
        'pecas',
        'origem',
        'composicaos_id',
        'construcaos_id',
        'sazonalidades_id',
        'imagem',
        'status',
        'encolhimento',
        'ligamento',
        'titulo_trama',
        'titulo_urdume',
        'informacao_adicional',
        'nome_arquivo',
        'caminho',
        'mostrar',
        'construcao',
        'created_by', 
        'updated_by', 
        'deleted_by',
        'imagem_zoom'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function laudo(){
        return $this->hasOne('App\Laudo', 'produto_grupos_id', 'id')->orderBy('created_at', 'desc');
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

    public function segmento(){
        return $this->hasOne('App\Segmento', 'id', 'segmentos_id');
    }

    public function familia(){
        return $this->hasOne('App\Familia', 'id', 'familias_id');
    }

    public function composicao(){
        return $this->hasOne('App\Composicao', 'id', 'composicaos_id');
    }

    public function construcaos(){
        return $this->hasOne('App\Construcao', 'id', 'construcaos_id');
    }

    public function sazonalidade(){
        return $this->hasOne('App\Sazonalidade', 'id', 'sazonalidades_id');
    }

    public function produtoEspecificacao(){
        return $this->hasMany('App\ProdutoEspecificacao', 'produto_grupos_id', 'id');
    }

    public function produtoGrupoDesenho(){
        return $this->hasMany('App\ProdutoGrupoDesenho', 'produto_grupos_id', 'id');
    }

    public function produtoEstoque(){
        return $this->hasMany('App\ProdutosEstoque', 'produto_grupos_id', 'id');
    }
}
