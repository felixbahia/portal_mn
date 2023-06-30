<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RaProdutoAssociacaoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_ra_produto_associacao';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'ra_numero', 
        'produto', 
        'produto_codigo', 
        'fracao', 
        'associado', 
        'codigo_ra',
        'quantidade',  
        'localdeestoque',
        'localdeestoque_codigo',
        'endereco',  
        'id_nota',
        'sinal', 
        'codigo_produto'
    ];

    public function nomeFornecedor(){
        return $this->hasOne('App\ImportacaoNotaFornecedorNasajon', 'id', 'id_nota');
    }
}
