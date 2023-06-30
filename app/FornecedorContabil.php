<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FornecedorContabil extends Model
{
    protected $table = 'fornecedor_contactb';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['codcad', 'estabel'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'codcad', 'contactb', 'estabel'
    ];

}
