<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatusPeca extends Model
{
    use SoftDeletes;
    protected $fillable = ['status'];

    public function pecas(){
        return $this->belongsTo('App\PecaProduto', 'status_peca_id', 'id');
    }
}
