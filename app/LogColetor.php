<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class LogColetor extends Model
{
    use SoftDeletes;
    use \Awobaz\Compoships\Compoships; 
    protected $connection = 'pgsql';
 
    public $guarded = [
        'id', 'ra_id', 'alerta_erro', 'estabelecimento', 'peca_id', 'produto_codigo', 'peca_codigo','endereco_id','endereco_codigo','numero_pedido'
        ,'numero_nota','numero_ra','tipo_operacao','log_operacao','created_by','user_name','quantidade','operacao','confirmado','confirmacao_entrada_saida','produto_defeito_id'
    ];

    public function romaneio(){
        return $this->hasOne('App\RomaneioNasajon', 'peca_codigo','peca_codigo');
    }


    public function defeito(){
        return $this->hasOne('App\ProdutoDefeito', 'id', 'produto_defeito_id');
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

 
}
