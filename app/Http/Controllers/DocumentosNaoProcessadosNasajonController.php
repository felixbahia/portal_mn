<?php

namespace App\Http\Controllers;

use App\DocumentosNaoProcessadosNasajon;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Auth;
use Illuminate\Support\Facades\DB;

class DocumentosNaoProcessadosNasajonController extends Controller
{

    public $tipo = [
        0 => 'Nota',
        5 => 'Pedido'
    ];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\DocumentosNaoProcessadosNasajon") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\DocumentosNaoProcessadosNasajon');

        $estabelecimentos = returnEmpresasNasajonView();

        return view('programs.documentos_nao_processados.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filter(Request $request, $retorno_array = false){

        $fields = $request->only('estabelecimento');

        $query = DocumentosNaoProcessadosNasajon::select(DB::Raw('count(*) as total, min(datahora_inclusao) as mais_antigo'), 'tipo')
            ->groupBy('tipo')
            ->where(DB::Raw("datahora_inclusao::date"), date('Y-m-d'))
            ->where('status', 0)
            ->whereIn('tipo', [0, 5]);

        if(isset($fields['estabelecimento']) && !is_null($fields['estabelecimento'])){
            $query->where(function($query) use($fields){
                $query->whereHas('pedidoVendaNasajon', function($query) use($fields){
                        $query->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
                    })
                    ->orWhereHas('notaNasajon', function($query) use($fields){
                        $query->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
                    });
                });
        }

        $documentosNaoProcessadosNasajonObj = $query->get();

        $retorno = [];
        $agora = date('d/m H:i');

        $documentosNaoProcessadosNasajonObj->each(function ($documentos) use (&$retorno){

            $mais_antigo = Carbon::parse($documentos->mais_antigo);

            $retorno[] = [
                'tipo' => $this->tipo[$documentos->tipo],
                'quantidade' => $documentos->total,
                'mais_antigo' => $mais_antigo->format('d/m/Y H:i:s'),
                'mais_antigo_diferenca' => $mais_antigo->diffForHumans(),
                'data_verificacao' => $documentos->mais_antigo,
                'diferenca_inteiro' => $mais_antigo->diffInMinutes(Carbon::now()),
                'mais_antigo_hora' => $mais_antigo->format('H:i:s'),
                'mais_antigo_data' => $mais_antigo->format('d/m/Y'),
            ];
        });

        if($retorno_array){
            $return = [];
            $return['retorno'] =  $retorno;
            $return['agora'] =  $agora;

            return $return;
        }else{
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'retorno' => $retorno,
                    'agora' => $agora
                ]
            ];
    
            return response()->json($response);
        }
        
    }

    public function monitoramento(){
        $estabelecimentos = returnEmpresasNasajonView();

        $finalizar = false;

        foreach($estabelecimentos as $estabelecimento_id => $estabelecimento_descricao){
            $arr = [];
            $arr['estabelecimento'] = $estabelecimento_id;
    
            $request_filtro = new Request($arr);
    
            $return = $this->filter($request_filtro, true);

            foreach($return['retorno'] as $value){
                if(($value['diferenca_inteiro'] > 15 || $value['quantidade'] > 30)  && $finalizar == false){
                    $variaveis = [];
                    $variaveis['quantidade'] = $value['quantidade'];
                    $variaveis['mais_antigo_diferenca'] = $value['mais_antigo_diferenca'];
                    $variaveis['mais_antigo'] = $value['mais_antigo'];
                    $variaveis['estabelecimento'] = $estabelecimento_descricao;
                    $variaveis['hora_ultimo_processamento'] = $value['mais_antigo_hora'];
  
                    $emailControllerObj = new EmailController;
                    $emailControllerObj->sendEmailToken('00', 'documento_nao_processado', [], $variaveis);

                    $finalizar = true;
                }   
                
                if($finalizar){
                    break;
                }
            }
        }
        
        return true;
    }
}
