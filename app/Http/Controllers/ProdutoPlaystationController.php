<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use App\Movimentacao;
use App\ClienteNasajon;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ProdutoPlaystationBuscaRequest;


use Illuminate\Http\Request;

class ProdutoPlaystationController extends Controller
{
    
    private $cfop_vendas_e_devolucao = ['1201', '1202', '2201', '2202','5922', '5949', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '5104', '6104'];
    private $cfop_devolucao = ['1201', '1202', '2201', '2202'];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\VendaPlaystation") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\VendaPlaystation');

        return view('programs.venda_playstation.index');
    }


    public function filtro(ProdutoPlaystationBuscaRequest $request){
        $fields = $request->only('mes_ano','dolar');
        $dolar = $fields['dolar'];

        $clientes_exluir = ClienteNasajon::select('codigo')
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
        ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        $query = Movimentacao::select('cfop','marca','descricao','produto_codigo', 'preco',DB::RAW('sum(quantidade) as quantidade')
        ,'valor_cofins' , 'valor_pis');
        if(!empty($fields['mes_ano'])){
            $data = $fields['mes_ano'];
          
             $query->where(DB::raw("TO_CHAR(data_movimentacao,'MM/YYYY')"), $data);

        }
        if(!empty($fields['dolar'])){
            $dolar = $fields['dolar'];
        }
        $query->whereNotIn('cliente_codigo', $clientes_exluir);
 
        $query->whereIn('cfop',$this->cfop_vendas_e_devolucao);
        $query->whereIn('linha', ['CAMPANHA LICENCIADO PS','CAMPANHA PLAYSTATION']);
      
        $query->groupBy('cfop','marca','descricao','produto_codigo', 'preco' ,'valor_cofins' , 'valor_pis');
      
        $result = $query->get();

        $movimentacao = [];
        $movimentacaoDevolucao = [];
        $total_quantidade = 0;
        $total_valor = 0;
        $total_royalt = 0;
        $total_deducao = 0;
        $total_liquido = 0;
        $total_usd = 0;
        $total_devolucao = 0;
        $total_quantidade_dev = 0;
        $total_valor_dev = 0;
        $total_royalt_dev = 0;
        $total_deducao_dev = 0;
        $total_liquido_dev = 0;
        $total_usd_dev = 0;
        $total_devolucao_dev = 0;

      
        foreach($result as $movimento) {
           
            
           
            if(in_array($movimento->cfop, $this->cfop_devolucao)){
                $ganho_dev = empty($movimento->quantidade) &&  empty($movimento->preco)? '' :(($movimento->quantidade * $movimento->preco) - ($movimento->valor_cofins + $movimento->valor_pis))* 12 / 100;
                $movimentacaoDevolucao[] = [
                    'personagem' => $movimento->marca,
                    'produto' => $movimento->descricao,
                    'codigo' => $movimento->produto_codigo,
                    'plataforma' => 'PS3.PS4',
                    'territorio' => 'Brasil',
                    'preco' =>empty($movimento->preco)? '' :  parserValor($movimento->preco),
                    'retorno_unitario' => empty($movimento->quantidade)? '' :parserValor($movimento->quantidade),
                    'retorno_subtotal' =>empty($movimento->quantidade)? '' : parserValor($movimento->quantidade * $movimento->preco),
                     'deducao' => empty($movimento->valor_cofins) &&  empty($movimento->valor_pis) ? '' :parserValor($movimento->valor_cofins + $movimento->valor_pis),
                    'liquida_receita' => empty($movimento->quantidade)? '' : parserValor(($movimento->quantidade * $movimento->preco) - ($movimento->valor_cofins + $movimento->valor_pis)),
                    'royalts' => '-12,00%',
                    'royalts_ganhos' => empty($movimento->quantidade) &&  empty($movimento->preco)? '' :parserValor((($movimento->quantidade * $movimento->preco) - ($movimento->valor_cofins + $movimento->valor_pis))* 12 / 100),
                    'ganhos_usd' => empty($dolar) && empty($ganho_dev)? '' :parserValor($ganho_dev / parserNumber($dolar)),
                    'devolucao' => empty($movimento->quantidade)? '' :parserValor($movimento->quantidade),
                   
                ];
            
                     
                $total_quantidade_dev += $movimento->quantidade;
                $total_valor_dev  += ($movimento->quantidade * $movimento->preco);
                $total_royalt_dev  += (($movimento->quantidade * $movimento->preco) - $movimento->deducao)* 12 / 100;
                $total_deducao_dev +=$movimento->valor_cofins + $movimento->valor_pis;
                $total_liquido_dev +=($movimento->quantidade * $movimento->preco) - ($movimento->valor_cofins + $movimento->valor_pis);
                $total_usd_dev += ($ganho_dev /parserNumber($dolar));
                $total_devolucao_dev +=$movimento->quantidade;

            }else{
                $ganho = empty($movimento->quantidade) &&  empty($movimento->preco)? '' :(($movimento->quantidade * $movimento->preco) - ($movimento->valor_cofins + $movimento->valor_pis))* 12 / 100;
                $movimentacao[] = [
                    'personagem' => $movimento->marca,
                    'produto' => $movimento->descricao,
                    'codigo' => $movimento->produto_codigo,
                    'plataforma' => 'PS3.PS4',
                    'territorio' => 'Brasil',
                    'preco' =>empty($movimento->preco)? '' :  parserValor($movimento->preco),
                    'retorno_unitario' => empty($movimento->quantidade)? '' :parserValor($movimento->quantidade),
                    'retorno_subtotal' =>empty($movimento->quantidade)? '' : parserValor($movimento->quantidade * $movimento->preco),
                    'deducao' => empty($movimento->valor_cofins) &&  empty($movimento->valor_pis) ? '' :parserValor($movimento->valor_cofins + $movimento->valor_pis),
                    'liquida_receita' => empty($movimento->quantidade)? '' : parserValor(($movimento->quantidade * $movimento->preco) - ($movimento->valor_cofins + $movimento->valor_pis)),
                    'royalts' => '12,00%',
                    'royalts_ganhos' => empty($movimento->quantidade) &&  empty($movimento->preco)? '' :parserValor((($movimento->quantidade * $movimento->preco) - ($movimento->valor_cofins + $movimento->valor_pis))* 12 / 100),
                    'ganhos_usd' => empty($dolar) && empty($ganho)? '' :parserValor($ganho / parserNumber($dolar)),
                    'devolucao' => empty($movimento->quantidade)? '' :parserValor($movimento->quantidade),
                
                ];
            
                    
    
                $total_quantidade +=$movimento->quantidade;
                $total_valor  += ($movimento->quantidade * $movimento->preco);
                $total_royalt  += (($movimento->quantidade * $movimento->preco) - $movimento->deducao)* 12 / 100;
                $total_deducao +=$movimento->valor_cofins + $movimento->valor_pis;
                $total_liquido +=($movimento->quantidade * $movimento->preco) - ($movimento->valor_cofins + $movimento->valor_pis);
                $total_usd += ($ganho /parserNumber($dolar));
                $total_devolucao +=$movimento->quantidade;
       

        }
        }

        $total_quantidade =empty($total_quantidade)? '' : parserValor($total_quantidade);
        $total_valor = empty($total_valor)? '' : parserValor($total_valor);
        $total_royalt = empty($total_royalt)? '' : parserValor($total_royalt);
        $total_deducao =empty($total_deducao)? '' : parserValor($total_deducao);
        $total_liquido =empty($total_liquido)? '' : parserValor($total_liquido);
        $total_usd =empty($total_usd)? '' : parserValor($total_usd);
        
        $total_quantidade_dev =empty($total_quantidade_dev)? '' : parserValor($total_quantidade_dev);
        $total_valor_dev = empty($total_valor_dev)? '' : parserValor($total_valor_dev);
        $total_royalt_dev = empty($total_royalt_dev)? '' : parserValor($total_royalt_dev);
        $total_deducao_dev =empty($total_deducao_dev)? '' : parserValor($total_deducao_dev);
        $total_liquido_dev =empty($total_liquido_dev)? '' : parserValor($total_liquido_dev);
        $total_usd_dev =empty($total_usd_dev)? '' : parserValor($total_usd_dev);

           
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'venda_playstations' => $movimentacao,
                'devolucao_playstations' => $movimentacaoDevolucao,
               'total_quantidade'  =>$total_quantidade,
                'total_valor'  =>$total_valor,
                'total_royalt'  =>$total_royalt,
                'total_deducao' => $total_deducao,
                'total_liquido' => $total_liquido,
                'total_usd' =>  $total_usd,
                'total_quantidade_dev'  =>$total_quantidade_dev,
                'total_valor_dev'  =>$total_valor_dev,
                'total_royalt_dev'  =>$total_royalt_dev,
                'total_deducao_dev' => $total_deducao_dev,
                'total_liquido_dev' => $total_liquido_dev,
                'total_usd_dev' =>  $total_usd_dev,
            ],
        ]);
    }

}
