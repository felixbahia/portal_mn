<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use App\TitulosEmAbertoNasajon;
use Maatwebsite\Excel\Facades\Excel;

use App\TitulosEmAbertoNasajonPortal;
use App\Exports\TitulosVencidosExport;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\EmailController;

class EmailTitulosController extends Controller
{
	public static function enviarEmailDocumentos(){

		$hoje = Carbon::today();
		
		$titulos = TitulosEmAbertoNasajon::with('estabelecimento_detalhes', 'cliente')
			->select(
				'vencimento',
				'nota_numero',
				'valor',
				'numero',
				'banco_codigo',
				'conta_agencia',
				'conta_numero',
				'conta_digito',
				'nossonumero',
				'id_estabelecimento',
				'cod_cliente'			
			)
			->where('vencimento', $hoje->copy()->addDays(5))
			->whereHas('notaDetalhes')
			->distinct()
            ->get();

		$boletos = [];

		$titulos->each(function($titulo) use(&$boletos){
			$vencimentoCarbon = Carbon::parse($titulo->vencimento);
			$boletos[] = [
				'nome_cliente' =>  $titulo->cliente->nome,
				'nota_numero' => $titulo->nota_numero,
				'email' => $titulo->cliente->email,
				'valor' => $titulo->valor,
				'vencimento_dias' => Carbon::now()->diffInDays($vencimentoCarbon),
				'link' => '<center><div><!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="http://portal.mntecidos.com.br/login" style="height:40px;v-text-anchor:middle;width:250px;" arcsize="10%" strokecolor="#0c299d" fillcolor="#003554"><w:anchorlock/><center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;">Confirmar</center></v:roundrect><![endif]--><a href="http://portal.mntecidos.com.br/login" style="background-color:#003554;border:1px solid #0c299d;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:250px;-webkit-text-size-adjust:none;mso-hide:all;">http://portal.mntecidos.com.br/login</a></div></center>'
			];
		});

		$emailControllerObj = new EmailController;

		foreach($boletos as $boleto){
			$variaveis = [
				'nome_cliente' => $boleto['nome_cliente'],
				'valor' => parserValor($boleto['valor']),
				'nota_numero' => $boleto['nota_numero'],
				'link' => $boleto['link'],
				'manual' => '<a href="'.route('manual.cliente').'" style="color: red">Manual Cadastro Portal MN</a>',
				'vencimento_dias' => $boleto['vencimento_dias']
			];
			$mail_result = $emailControllerObj->sendEmailToken('00', 'email_boleto_renegociado', [$boleto['email']], $variaveis, [], 'Link para acesso ao boleto da NF '.$boleto['nota_numero']);
			if($mail_result['status'] === 'error'){
				throw new \Exception('Não foi possível enviar o Email!');
			}
		}
	}

	public static function enviarEmailDocumentosRenegociados($fields){

		$data_inicio = Carbon::parse($fields['data_inicio']);
		$data_fim = Carbon::parse($fields['data_fim']);
        
		$titulos = TitulosEmAbertoNasajonPortal::with('cliente', 'estabelecimento_detalhes')
			->whereNotNull('nossonumero')
			->whereRaw('CHAR_LENGTH(TRIM(LEADING \'0\' FROM nossonumero)) > 0')
			->whereBetween("titulo_emissao", [$data_inicio->format('Y-m-d'), $data_fim->format('Y-m-d')])
			->where('origem_texto', 'Renegociação')
			->get();
		
		$boletos = [];

		
		$titulos->each(function($titulo) use(&$boletos){
			$vencimentoCarbon = Carbon::parse($titulo->vencimento);
			$boletos[] = [
				'nome_cliente' =>  $titulo->cliente->nome,
				'nota_numero' => $titulo->nota_numero,
				'email' => $titulo->cliente->email,
				'valor' => $titulo->valor,
				'vencimento_dias' => Carbon::now()->diffInDays($vencimentoCarbon),
				'link' => '<center><div><!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="http://portal.mntecidos.com.br/login" style="height:40px;v-text-anchor:middle;width:250px;" arcsize="10%" strokecolor="#0c299d" fillcolor="#003554"><w:anchorlock/><center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;">Confirmar</center></v:roundrect><![endif]--><a href="http://portal.mntecidos.com.br/login" style="background-color:#003554;border:1px solid #0c299d;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:250px;-webkit-text-size-adjust:none;mso-hide:all;">http://portal.mntecidos.com.br/login</a></div></center>'
			];
		});

		$emailControllerObj = new EmailController;
		foreach($boletos as $boleto){
			$variaveis = [
				'nome_cliente' => $boleto['nome_cliente'],
				'valor' => parserValor($boleto['valor']),
				'nota_numero' => $boleto['nota_numero'],
				'link' => $boleto['link'],
				'manual' => '<a href="'.route('manual.cliente').'" style="color: red">Manual Cadastro Portal MN</a>',
				'vencimento_dias' => $boleto['vencimento_dias']
			];
			$mail_result = $emailControllerObj->sendEmailToken('00', 'email_boleto_renegociado', [$boleto['email']], $variaveis, [], 'Link para acesso ao boleto da NF '.$boleto['nota_numero']);
			if($mail_result['status'] === 'error'){
				throw new \Exception('Não foi possível enviar o Email!');
			}
		}
	}

	public function vencidosCarteira(){
   		Excel::store(new TitulosVencidosExport, 'titulos_vencido_carteira.xls');
		$EmailObj = new EmailController();
	 	$returnEmail = $EmailObj->sendEmailToken('00', "titulos_vencidos_carteira", [], [], ['titulos_vencido_carteira.xls' => ['as' => 'titulos_vencido_carteira.xls', 'mime' => 'application/vnd.ms-excel']], []);
        Storage::delete('titulos_vencido_carteira.xls');
	}


	public function enviarEmailParaClienteInfomadoDoVencimento(){
		$hoje = Carbon::today();
		
		$titulos = TitulosEmAbertoNasajonPortal::with('notaDetalhes', 'cliente', 'revisao_vendedor_comissao.usuario')
			->select()
			->where('vencimento', $hoje->copy()->subDays(4))
			->whereHas('notaDetalhes')
			->distinct()
            ->get();

		$sem_banco = [];
		$boletos = [];

		$emailControllerObj = new EmailController;

		foreach($titulos as $titulo){
			$cliente_nome = $titulo->cliente->nome;
			$data_vencimento = parserData($titulo->vencimento);
			$nota_numero = $titulo->notaDetalhes->numero;
			$boleto_valor = parserValor($titulo->valor);
			$dados_boleto = 'Ainda não identificamos o pagamento do boleto vencido em '.$data_vencimento.', referente a NF-e '.$nota_numero.' no valor de R$ '.$boleto_valor.'.';
			$email_send = [];
			$titulo_email = 'Títulos Vencidos - '.$titulo->cliente->nome.' - Nota '.$nota_numero;
			$email_send[] = $titulo->cliente->email;
			if(!empty($titulo->revisao_vendedor_comissao->usuario)){
				$email_send[] = $titulo->revisao_vendedor_comissao->usuario->email;
				if(!empty($titulo->revisao_vendedor_comissao->usuario)){
					$email_send[] = $titulo->revisao_vendedor_comissao->usuario->supervisor->email;
				}
			}			
			$variaveis = [
				'cliente_nome' => $cliente_nome,
				'dados_boleto' => $dados_boleto,
			];
			
			$returnEmail = $emailControllerObj->sendEmailToken('00', "titulos_vencidos_informar_cliente", $email_send, $variaveis,[],$titulo_email);
		}

		$hoje = Carbon::today();
		
		$titulos = TitulosEmAbertoNasajon::with('notaDetalhes', 'cliente')
			->select()
			->where('vencimento', $hoje->copy()->subDays(8))
			->whereHas('notaDetalhes')
			->distinct()
            ->get();

		foreach($titulos as $titulo){
			$cliente_nome = $titulo->cliente->nome;
			$data_vencimento = parserData($titulo->vencimento);
			$nota_numero = $titulo->notaDetalhes->numero;
			$boleto_valor = parserValor($titulo->valor);
			$dados_boleto = 'O boleto da NF-e '.$nota_numero.' no valor de R$ '.$boleto_valor.' está vencido a <b>7 dias</b>.<p>Regularize o seu pagamento o quanto antes.</p><p>A partir do 11º dia do vencimento, além dos juros diário, <b>haverá cobrança de multa</b>.</p>';
			$email_cliente = $titulo->cliente->email;
			$titulo_email = 'Títulos Vencidos - '.$titulo->cliente->nome.' - Nota '.$nota_numero;
			$email_send = [];
			$email_send[] = $titulo->cliente->email;
			if(!empty($titulo->revisao_vendedor_comissao->usuario)){
				$email_send[] = $titulo->revisao_vendedor_comissao->usuario->email;
				if(!empty($titulo->revisao_vendedor_comissao->usuario)){
					$email_send[] = $titulo->revisao_vendedor_comissao->usuario->supervisor->email;
				}
			}
			$variaveis = [
				'cliente_nome' => $cliente_nome,
				'dados_boleto' => $dados_boleto,
			];
			$returnEmail = $emailControllerObj->sendEmailToken('00', "titulos_vencidos_informar_cliente", $email_send, $variaveis,[],$titulo_email);
		}

		$hoje = Carbon::today();
		
		$titulos = TitulosEmAbertoNasajon::with('notaDetalhes', 'cliente')
			->select()
			->where('vencimento', $hoje->copy()->subDays(23))
			->whereHas('notaDetalhes')
			->distinct()
            ->get();

		foreach($titulos as $titulo){
			$cliente_nome = $titulo->cliente->nome;
			$data_vencimento = parserData($titulo->vencimento);
			$nota_numero = $titulo->notaDetalhes->numero;
			$boleto_valor = parserValor($titulo->valor);
			$dados_boleto = 'O boleto da NF-e '.$nota_numero.' no valor de R$ '.$boleto_valor.' está vencido a <b>22 dias</b>.<p><b>A partir do 31º dia de vencimento, o título será enviado à Cobrança Judicial.</p><p>Regularize o pagamento e evite custos adicionais com honorários advocatícios</b>.</p>';
			$email_cliente = $titulo->cliente->email;
			$titulo_email = 'Títulos Vencidos - '.$titulo->cliente->nome.' - Nota '.$nota_numero;
			$email_send = [];
			$email_send[] = $titulo->cliente->email;
			if(!empty($titulo->revisao_vendedor_comissao->usuario)){
				$email_send[] = $titulo->revisao_vendedor_comissao->usuario->email;
				if(!empty($titulo->revisao_vendedor_comissao->usuario)){
					$email_send[] = $titulo->revisao_vendedor_comissao->usuario->supervisor->email;
				}
			}
			$variaveis = [
				'cliente_nome' => $cliente_nome,
				'dados_boleto' => $dados_boleto,
			];

			$returnEmail = $emailControllerObj->sendEmailToken('00', "titulos_vencidos_informar_cliente", $email_send, $variaveis,[],$titulo_email);
		}
 	}
}
