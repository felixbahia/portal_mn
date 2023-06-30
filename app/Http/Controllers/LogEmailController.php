<?php

namespace App\Http\Controllers;

use App\Email;
use App\LogEmail;
use Carbon\Carbon;
use App\EstadoGnre;
use App\NotaVendaNasajon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogEmailController extends Controller
{
    private $caminho='public/estado_gnre/liminar/';

    public function notasClienteIsento(){
        ini_set('memory_limit','1024M');

        $inicio_periodo = Carbon::now();
        $notaVendaNasajon = NotaVendaNasajon::select('uf','cpf_cnpj', 'emissao','cod_estabelecimento','nome_estabelecimento','vw_notas_de_venda.numero','vw_notas_de_venda.nome_cliente','vw_notas_de_venda.valor')
            ->join('integracoes.vw_dados_clientes', 'vw_dados_clientes.cpf_cnpj', '=', 'vw_notas_de_venda.documento_cliente')
            ->with('estabelecimentoDetalhes')
            ->where('bloqueado',false)
            ->whereIn('indicadorinscricaoestadual',[2,9])
            ->where('emissao',$inicio_periodo->format('Y-m-d')); 
            
         $notaVendaNasajon = $notaVendaNasajon->get();
    
        $email =  Email::select()->where('token_email','notas_cliente_isento')->first();
        $estadoGnre = EstadoGnre::select()->get();
       
        $notas = [];  
        $arquivo='';
        $nome_arquivo='';
      
        foreach($notaVendaNasajon as $nota){
      
            if($nota->uf !== $nota->estabelecimentoDetalhes->cidadeDetalhes->uf){
     
                        $estado = $estadoGnre->firstWhere('estado',$nota->uf);
                        $arquivo = '';
                        $nome_arquivo = '';
                        if (!empty($estado)) {      
                            $arquivo= $estado->liminar;
                            $nome_arquivo = strtolower($nota->uf) .'_liminar.pdf';
                        }

                        $logEmail = LogEmail::where('tipo_doc','NotaVendaNasajon')->where('numero_doc',$nota->numero)->first();
                        
                        if(is_null($logEmail)){           
                            $logEmailObj = new LogEmail();
                            $logEmailObj->tipo_doc ='NotaVendaNasajon';
                            $logEmailObj->nome_pessoa = $nota->nome_cliente;
                            $logEmailObj->numero_doc = $nota->numero;
                            $logEmailObj->valor = $nota->valor;
                            if(!empty($estado)) {
                                $logEmailObj->observacao = 'Liminar não Recolher Imposto - Nota Emitida para cliente Isento / GNRE_ANEXO_' . $nota->uf .'_'.$nota->cod_estabelecimento;
                            }else{
                                $logEmailObj->observacao ='Nota Emitida para cliente Isento / GNRE - Recolher Imposto_' . $nota->uf .'_'.$nota->cod_estabelecimento;
                            }      
                            $logEmailObj->enviado = true;
                            $logEmailObj->created_at =Carbon::now();
                            $logEmailObj->updated_at =Carbon::now();
                            $logEmailObj->emails_id = $email->id;

                            $logEmailObj->save();
                
                            $notas[] = [
                                'nome_cliente' => $nota->nome_cliente,
                                'numero_nf' => $nota->numero,
                                'valor_nota' => parserValor($nota->valor),
                                'cpf_cnpj' => $nota->cpf_cnpj,
                                'estado' =>  $nota->uf ,
                                'emissao' => parserData($nota->emissao) ,
                                'cod_estabelecimento' =>  $nota->cod_estabelecimento ,
                                'nome_estabelecimento' =>  $nota->nome_estabelecimento ,
                            ];
                                            
                            if(!empty($notas)) {
                                $mailBody = '<table  border="1" cellpadding="0" cellspacing="0">'.
                                    '<thead>'.
                                    '<th>Estabelecimento </th>'.
                                    '<th> Número da  Nota </th>'.
                                    '<th>Emissão </th>'.
                                    '<th> Cpf_Cnpj </th>'.
                                        '<th> Cliente </th>'.
                                        '<th> Estado </th>'.
                                        '<th> Valor </th>'.
                
                                        '</thead>'.
                                '<tbody>';
                                                                
                                foreach($notas as $nota){
                                    $mailBody.='<tr>'.
                                        '<td>'.$nota['cod_estabelecimento'].'-' .$nota['nome_estabelecimento'].'</td>'.
                                        '<td>'.$nota['numero_nf'].'</td>'.
                                        '<td>'.$nota['emissao'].'</td>'.
                                        '<td>'.$nota['cpf_cnpj'].'</td>'.
                                        '<td>'.$nota['nome_cliente'].'</td>'.
                                        '<td>'.$nota['estado'].'</td>'.
                                    '<td>'.$nota['valor_nota'].'</td>'.
                                    '</tr>';
                                }
                                                                    
                                $mailBody .= '</tbody>'.
                                    '</table>';
                                                            
                                if(!empty($estado)) {
                                    $this->enviarComunicadoAnexo($mailBody,$arquivo,$nome_arquivo);
                                }else{
                                    $this->enviarComunicado($mailBody);
                                }

                                unset($notas);
                            }
                        }
                }
         }
    }
    
    private function enviarComunicado($mailBody){
        $emailControllerObj = new EmailController;
        $emailControllerObj->sendEmailToken('00', 'notas_cliente_isento', [], ['corpo' => $mailBody,'assunto' =>'Nota Emitida para cliente Isento / GNRE - Recolher Imposto']);
    }

    private function enviarComunicadoAnexo($mailBody,$arquivo,$nome_arquivo){
        $emailControllerObj = new EmailController;
        $anexo = [$arquivo => ['as' => $nome_arquivo]];

        $emailControllerObj->sendEmailToken('00', 'notas_cliente_isento',[], ['corpo' => $mailBody,'assunto' =>'Liminar não Recolher Imposto - Nota Emitida para cliente Isento / GNRE'], $anexo);
    }
}
