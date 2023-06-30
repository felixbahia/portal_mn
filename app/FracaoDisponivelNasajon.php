<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FracaoDisponivelNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_fracoes_disponiveis';
    public $timestamps = false;
    public $incrementing = false;

    public $guarded = [
        'estabelecimento_codigo', 'produto_codigo', 'saldo'
    ];

    public function produtoDados(){
        return $this->hasOne('App\ProdutoNasajon', 'produto', 'produto_codigo');
    }
    
    public function produtoDadosPortal(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }
    
}
