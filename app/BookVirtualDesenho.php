<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookVirtualDesenho extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    
    public $fillable = [
        'books_virtuals_id',
        'codigo_desenho',
        'imagem',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function bookVirtual(){
        return $this->hasOne('App\BookVirtual', 'id', 'books_virtuals_id');
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
