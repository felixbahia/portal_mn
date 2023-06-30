<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NcmNasajon extends Model
{
	protected $connection = 'nasajon';
	protected $table = 'ns.tipi';
	public $timestamps = false;
	public $incrementing = false;
	public $primaryKey = 'id';
    protected $keyType = 'uuid';

	public $fillable = [
		'ncm', 'texto', 'unidade', 'tipoipi', 'ipi', 'ipivalor', 'ii', 'taxadepreciacao', 'descricao', 'id', 'perfil_importacao', 'fimvigencia', 'unidadetributada', 'lastupdate', 'tenant'
    ];
    
}
