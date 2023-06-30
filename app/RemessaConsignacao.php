<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RemessaConsignacao extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'pedido_id', 'arquivo', 'created_by'
    ];
    
	public function getCaminhoAttribute(){
		return 'public/remessa_consignacao/';
	}

	public function getCaminhoArquivoAttribute(){
		return $this->caminho . $this->arquivo;
	}

	public function getUrlArquivoAttribute(){
		if(isset($this->arquivo) && Storage::exists($this->caminho . $this->arquivo)){
			return Storage::url($this->caminho . $this->arquivo);
		}else{
			return '';
		}
	}

    public function pedido(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id')->withTrashed();
    }
}
