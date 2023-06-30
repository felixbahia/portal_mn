<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EnderecoEntradaNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_configuracao_endereco_entrada';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'estabelecimento_codigo', 
        'endereco_codigo', 
        'estabelecimento_id', 
        'endereco_id'
    ];

    public function conferirPeca(){
        return $this->hasMany('App\RaProdutoAssociacaoNasajon', 'endereco', 'endereco_id');
    }
}
