<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CadastroProdutosPrecos extends Model
{

    protected $fillable = [
		'codigo',
		'marca',
		'linha',
		'grupo',
		'subgrupo',
		'composicao',
		'gramatura',
		'largura',
		'unidade',
		'preco',
	];
}
