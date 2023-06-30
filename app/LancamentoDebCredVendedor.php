<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LancamentoDebCredVendedor extends Model
{
    use SoftDeletes;
    protected $table = 'lancamentos_deb_cred_vendedor';
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','data_lancamento', 'num_documento', 'nota_uuid', 'codigo_vendedor', 'codigo_motivo','tipo','valor', 'parcela', 'created_by', 'updated_by', 'deleted_by'
    ];

    public function vendedor(){
        return $this->hasOne('App\User', 'id', 'codigo_vendedor');
    }

    public function documento(){
        return $this->hasOne('App\PedidoVenda', 'NUMULTDUE', 'num_documento');
    }


    public function motivofinanceiro(){
        return $this->hasOne('App\MotivoFinanceiro', 'id', 'codigo_motivo');
    }

    public function nota(){
        return $this->hasOne('App\NotasNasajon', 'id', 'nota_uuid');
    }

    public function devolucaoNota(){
        return $this->hasOne('App\DevolucaoNota', 'nota_id', 'nota_uuid');
    }
}
