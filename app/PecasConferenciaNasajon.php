<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PecasConferenciaNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_pecas_conferencia';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'estabelecimento_codigo', 
        'id_nota', 
        'numero_nota', 
        'codigo_produto', 
        'descricao_produto', 
        'quantidade_item',
        'romaneio_importado',  
        'codigo_peca',
        'quantidade_peca',
        'conferido'
    ];

    public function nomeFornecedor(){
        return $this->hasOne('App\ImportacaoNotaFornecedorNasajon', 'id', 'id_nota');
    }
}
