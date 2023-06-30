<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class InventarioCodigo extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';

    protected $fillable = [
        'id',
        'estabelecimento',
        'codigo',
        'data_inicial',
        'data_final',
        'verificacao_estoque_atual',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $dates = ['data_inicial', 'data_final', 'created_at', 'updated_at', 'deleted_at'];

    public function estoqueAtualTotal(){
        return $this->hasOne('App\InventarioCodigoEstoqueAtual', 'inventario_codigos_id', 'id')
            ->select('inventario_codigos_id', DB::raw("SUM(saldo) as total"))
            ->groupBy('inventario_codigos_id');
    }

    public function estoqueVolumeAtualTotal(){
        return $this->hasOne('App\InventarioCodigoEstoqueAtual', 'inventario_codigos_id', 'id')
            ->select('inventario_codigos_id', DB::raw("count(*) as total"))
            ->groupBy('inventario_codigos_id');
    }

    public function contagemAtualTotal(){
        return $this->hasOne('App\InventarioCodigoLeitura', 'inventario_codigos_id', 'id')
            ->select('inventario_codigos_id', DB::raw("SUM(contagem_1) as contagem_1_total, SUM(contagem_2) as contagem_2_total, SUM(contagem_3) as contagem_3_total"))
            ->groupBy('inventario_codigos_id');
    }

    public function contagem1VolumeAtualTotal(){
        return $this->hasOne('App\InventarioCodigoLeitura', 'inventario_codigos_id', 'id')
            ->select('inventario_codigos_id', DB::raw("count(*) as total"))
            ->whereNotNull('contagem_1')
            ->groupBy('inventario_codigos_id');
    }

    public function contagem2VolumeAtualTotal(){
        return $this->hasOne('App\InventarioCodigoLeitura', 'inventario_codigos_id', 'id')
            ->select('inventario_codigos_id', DB::raw("count(*) as total"))
            ->whereNotNull('contagem_2')
            ->groupBy('inventario_codigos_id');
    }

    public function contagem3VolumeAtualTotal(){
        return $this->hasOne('App\InventarioCodigoLeitura', 'inventario_codigos_id', 'id')
            ->select('inventario_codigos_id', DB::raw("count(*) as total"))
            ->whereNotNull('contagem_3')
            ->groupBy('inventario_codigos_id');
    }

    public function produtosEstoqueAtual(){
        return $this->hasMany('App\InventarioCodigoEstoqueAtual', 'inventario_codigos_id', 'id');
    }

    public function produtosEstoquLeitura(){
        return $this->hasMany('App\InventarioCodigoLeitura', 'inventario_codigos_id', 'id');
    }

    public function contagemPecaNaoEncontrado(){
        return $this->hasOne('App\InventarioCodigoLeituraLog', 'inventario_codigos_id', 'id')
            ->select('inventario_codigos_id', DB::raw("count(*) as total"))
            ->where('importado', false)
            ->groupBy('inventario_codigos_id');
    }
}
