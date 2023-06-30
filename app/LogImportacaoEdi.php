<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogImportacaoEdi extends Model
{
    protected $fillable = [
        'id',
        'processo',
        'arquivo',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
