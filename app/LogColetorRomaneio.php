<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogColetorRomaneio extends Model
{
    protected $connection = 'pgsql';
   
    use SoftDeletes;
 
    public $guarded = [
        'id', 'log_coletor_id', 'produto_defeito_id', 'estabelecimento', 'numero_nota', 'data_nota','fornecedor' ,'data_coletor','produto_codigo'
        ,'peca_coletor','quantidade_coletor','peca_romaneio','quantidade_romaneio','created_by','updated_by','deleted_by','motivo_divergencia_id','peca_codigo','peca_id'
    ];

    public function solucao(){
        return $this->hasOne('App\MotivoDivergencia', 'id', 'motivo_divergencia_id');
    }

}
