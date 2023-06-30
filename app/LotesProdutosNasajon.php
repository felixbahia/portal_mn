<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LotesProdutosNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'estoque.vw_lotes';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = ['produtolote', 'produto']; 
    protected $keyType = 'uuid';

    protected $fillable = ['produtolote', 'produto', 'codigoproduto', 'codigo', 'fornecedor', 'fornecedorcodigo', 'dataentrada', 'id_docfis', 'numerodf', 'df_item', 'localestoque', 'quantidade', 'unidadecodigo', 'unidade', 'volume', 'detentor'];

    public function produtoNasajon(){
        return $this->hasOne('App\ProdutoNasajon', 'produto', 'produto');
    }
}
