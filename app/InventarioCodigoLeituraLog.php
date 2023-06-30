<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarioCodigoLeituraLog extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';

    public $guarded = [
        'id',
        'inventario_codigos_id',
        'fracao_codigo',
        'contagem',
        'quantidade',
        'endereco',
        'importado',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function inventarioCodigoDetalhes(){
        return $this->hasOne('App\InventarioCodigo', 'id', 'inventario_codigos_id');
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

}
