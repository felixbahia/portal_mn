<?php

namespace App\Exports;

use Carbon\Carbon;
use App\NetrinApiRetorno;
use App\NotaVendaNasajon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\FromCollection;

class ClienteNetrinExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
 
        $inicio_periodo = Carbon::now()->subMonths(12);
		
        $notaVendaNasajon = NotaVendaNasajon::select('documento_cliente',DB::RAW('max(emissao) as emissao'))
        ->where(DB::RAW('LENGTH(documento_cliente)'),'>','15')
        
            ->where('emissao','>=',$inicio_periodo->format('Y-m-d'))
            ->groupBy('documento_cliente');
		
			
		
			$notaVendaNasajon = $notaVendaNasajon->get();
 

  
	
		$dados = collect([[
			'Cnpj',
			'Data_Nota',
            'Meses_Nota',
            'Situação'

	
		]]);
        foreach($notaVendaNasajon as $nota){

            $cnpj = preg_replace('/[^0-9]/', '', (string) $nota->documento_cliente);
         
			$netrinApiRetorno = NetrinApiRetorno::select('json_retorno','created_at')->whereHas('netrinApi', function($query) use ($cnpj){
				
				$inicio_periodo = Carbon::now()->subMonths(6);

                $query->where('cpf_cnpj', $cnpj);
                $query->where('alerta_erro', false);
                
				
            })->first();
	
			$meses_cli=	Carbon::now()->diffInMonths($nota->emissao); 
            if(empty($netrinApiRetorno)){ 
               
              
				$dados->push([
                    $nota->documento_cliente,
                    parserData($nota->emissao),
                    $meses_cli,
                   'NAO_TEM_NETRIN ',
                   0
				]);
			
		
			}
		}
       
		return $dados;
    }

    public function bindValue(Cell $cell, $value){
        if(!in_array($cell->getColumn(), ['H']) || $cell->getRow() == 1){
        	$cell->setValueExplicit($value, DataType::TYPE_STRING);
		}else{
        	$cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
		}

        return true;
    }
}
