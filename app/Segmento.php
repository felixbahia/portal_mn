<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Segmento extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'descricao',
        'imagem',
        'posicao',
        'cor_codigo',
        'mostrar',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function booksVirtuais(){
        return $this->hasMany('App\BooksVirtuais', 'segmentos_id', 'id');
    }
    
    public function createdBy(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updatedBy(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function deletedBy(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
