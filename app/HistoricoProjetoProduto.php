<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoricoProjetoProduto extends Model
{
	use SoftDeletes;
	
	protected $table = 'historicos_projetos_produtos';

	protected $fillable = 
	[
		"id","lancamento_projeto_produtos_id","natureza","motivo","created_by","updated_by","deleted_by"
	];

}
