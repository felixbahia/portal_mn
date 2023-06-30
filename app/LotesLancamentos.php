<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LotesLancamentos extends Model
{
    protected $table = 'lotes_lancamentos';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['lancamentos_id', 'lotes_id'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'lancamentos_id', 'lotes_id', 'status'
    ];
}
