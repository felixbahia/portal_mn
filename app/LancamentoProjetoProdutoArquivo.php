<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class LancamentoProjetoProdutoArquivo extends Model
{
    use SoftDeletes;
    protected $table = 'lancamento_projeto_produto_arquivos';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','lancamento_projeto_produtos_id','caminho','nome','created_by','updated_by','deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function produto(){
        return $this->hasOne('App\LancamentoProjetoProduto', 'id', 'lancamento_projeto_produtos_id');
    }
}
