<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Margem extends Model
{
    use SoftDeletes;

	protected $table = "margem";
	protected $fillable = ['empresa', 'grupo', 'produto', 'marca', 'margem_a', 'margem_b', 'margem_c'];

}
