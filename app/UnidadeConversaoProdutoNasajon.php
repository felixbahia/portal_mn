<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UnidadeConversaoProdutoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_produtos_unidadesdeconversoes';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'produto', 'codigo_produto', 'codigo_unidadepadrao', 'descricao_unidadepadrao', 'codigo_unidadeconversao', 'descricao_unidadeconversao', 'razao'
    ];
}
