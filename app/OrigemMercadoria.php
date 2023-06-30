<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrigemMercadoria extends Model
{
    use SoftDeletes;

    protected $table = "origem_mercadoria";
    protected $fillable = ['id','descricao'];
}
