<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotasImportadasEntradasTitulo extends Model
{
    use \Awobaz\Compoships\Compoships;
    use SoftDeletes;

    protected $dates = ['emissao'];

    protected $fillable = [
        'id',
        'estabelecimento',
        'fatura',
        'nota',
        'cfop',
        'chave_nfe',
        'fornecedor_documento',
        'fornecedor_nome',
        'valor',
        'emissao',
        'lancado',
        'notas_importadas_entrada_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function notaImportada(){
        return $this->hasOne('App\NotasImportadasEntrada','id','notas_importadas_entrada_id');
    }

    public function notasEntradas(){
        return $this->hasOne('App\NotasEntradasNasajon',['Chave NE','Estabelecimento'],['chave_nfe','estabelecimento']);
    }
    
    public function duplicatas(){
        return $this->hasMany('App\NotasImportadasEntradasTitulosDuplicata','notas_importadas_entradas_titulo_id','id');
    }
}
