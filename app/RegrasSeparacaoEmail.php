<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegrasSeparacaoEmail extends Model
{
    protected $fillable = [
		'estabelecimento',
		'email',
		'created_by',
		'updated_by',
		'deleted_by',
	];
}
