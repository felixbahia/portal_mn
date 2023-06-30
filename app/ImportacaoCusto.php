<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportacaoCusto extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','importacaos_id','ii','ipi','pis','cofins','afrmm','taxa_siscomex','sda','honorarios','expediente','valor_li','agencia_maritima','armazem','laudo','outras_despesas','icms_saida','seguro','created_by','updated_by','deleted_by', 'transporte_rodoviario'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function importacaoDetalhes(){
    	return $this->hasOne('App\Importacao', 'id', 'importacaos_id');
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
}
