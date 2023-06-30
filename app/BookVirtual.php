<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookVirtual extends Model
{
    use SoftDeletes;
    protected $table = 'books_virtuals';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'num_book',
        'segmentos_id',
        'tipo_material',
        'origem',
        'artigo',
        'nome',
        'pecas',
        'img_instrucoes_lavagem',
        'caracteristicas',
        'thumb_instrucoes_lavagem',
        'imagem_tamanho_real',
        'estoque',
        'padrao_codigo',
        'familias_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function itens_book_virtual(){
        return $this->hasMany('App\ItensBookVirtual', 'num_book', 'num_book');
    }

    public function segmento(){
        return $this->hasOne('App\Segmento', 'id', 'segmentos_id');
    }

    public function desenhos(){
        return $this->hasMany('App\BookVirtualDesenho', 'books_virtuals_id', 'id');
    }

    public function familia(){
        return $this->hasOne('App\Familia', 'id', 'familias_id');
    }
}
