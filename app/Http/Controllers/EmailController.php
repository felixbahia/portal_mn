<?php

namespace App\Http\Controllers;

use Auth;
use App\Email;
use Illuminate\Http\Request;
use App\Mail\EmailPadrao;
use App\Http\Requests\EmailRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;

class EmailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ConfigurcaoEmail") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ConfigurcaoEmail');
        return view('programs.emails.index');
    }

    public function filter(Request $request){
        $fields = $request->only(['estabelecimento', 'titulo', 'enviado_por']);
        $EmailObj = Email::select('*');
        if(!empty($fields['estabelecimento'])){
            $EmailObj->where('estabelecimento', $fields['estabelecimento']);
        }
        if(!empty($fields['titulo'])){
            $EmailObj->where('title_view', 'ilike', '%'.$fields['titulo'].'%');
        }
        if(!empty($fields['enviado_por'])){
            $EmailObj->where('email_sender', $fields['enviado_por']);
        }
        $emails = $EmailObj->get();
        $return = [];
        $estabelecimentos = returnEmpresasNasajonView();
        foreach($emails as $email){
            $return[] = [
                'estabelecimento' => $estabelecimentos[intval($email->estabelecimento)],
                'titulo' => $email->title_view,
                'enviado_por' => $email->email_sender,
                'assunto' => $email->subject,
                'conteudo' => str_replace("<br />","",$email->body),
                'id' => Crypt::encrypt($email->id)
            ];
        }
        return response()->json([
            'status' => 'success',
            'message' => '',
            'response' => $return,
        ]);
    }

    public function edita(Request $request){
        $id = 0;
        try {
            $id = (int) Crypt::decrypt($request['id']);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'E-mail não encontrado!'
            ]);
        }
        $EmailObj = Email::find($id);
        if(is_null($EmailObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'E-mail não encontrado!'
            ]);
        }
        $dados = [];
        $dados['id'] = $request['id'];
        $dados["enviado_por"] = $EmailObj->email_sender;
        $dados["emails_enviados"] = explode(";", trim($EmailObj->emails_send));
        $dados["emails_copias"] = explode(";", trim($EmailObj->emails_cc));
        $dados["emails_copias_oculta"] = explode(";", trim($EmailObj->emails_bcc));
        $dados["assunto"] = $EmailObj->subject;
        $dados["conteudo"] = $EmailObj->body;
        $dados["template_variavies"] = $EmailObj->variaveis_template;
        return view('programs.emails.modal')->with('dados', $dados);
    }

    public function editar(EmailRequest $request){
        $id = 0;
        try {
            $id = (int) Crypt::decrypt($request['id']);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'E-mail não encontrado!'
            ]);
        }
        $emailObj = Email::find($id);
        if(is_null($emailObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'E-mail não encontrado!'
            ], 422);
        }

        $fields = $request->only(['enviado_por', 'emails_enviados', 'edicao_email', 'emails_copias', 'emails_copias_oculta', 'assunto', 'conteudo']);
         
        $body = $fields["conteudo"];

        $body = str_replace(["\n", "\r", "<p>", "</p>"], "", $body);

        $emailObj->email_sender = strtolower(trim($fields["enviado_por"]));

        $emails_send = $fields["emails_enviados"];
        foreach ($emails_send as $key => $email) {
            if(!empty(trim($email))){
                $emails_send[$key] = strtolower(trim($email));
            } else {
                unset($emails_send[$key]);
            }
        }
        $emailObj->emails_send = implode(";", $emails_send);

        $emails_cc = $fields["emails_copias"];
        foreach ($emails_cc as $key => $email) {
            if(!empty(trim($email))){
                $emails_cc[$key] = strtolower(trim($email));
            } else {
                unset($emails_cc[$key]);
            }
        }
        $emailObj->emails_cc = implode(";", $emails_cc);

        $emails_bcc = $fields["emails_copias_oculta"];
        foreach ($emails_bcc as $key => $email) {
            if(!empty(trim($email))){
                $emails_bcc[$key] = strtolower(trim($email));
            } else {
                unset($emails_bcc[$key]);
            }
        }
        $emailObj->emails_bcc = implode(";", $emails_bcc);
        $emailObj->subject = $fields["assunto"];
        $emailObj->body = $body;
        $emailObj->updated_at = date("Y-m-d H:i:s");
        $emailObj->updated_by = Auth::id();

        if($emailObj->save()){
            return response()->json([
                "status" => "success",
                "message" => ""
            ]);
        }else{
            return response()->json([
                "status" => "errror",
                "message" => "Não foi possivel atualizar este e-mail!<br>Tente Novamente mais tarde!"
            ], 422);
        }

    }
    /**
     * Undocumented function
     *
     * @param string $estabelecimento
     * @param string $token_email
     * @param array $email_to
     * @param array $variaveis_replace ['body' => [], 'subject' => []]
     * @param array $attachments_send
     * @return void
     */
    public function sendEmailToken($estabelecimento, $token_email, $email_to = [], $variaveis_replace = [], $attachments_send = [],$titulo = null){
        $EmailObj = Email::where('estabelecimento', str_pad($estabelecimento, 2, '0',STR_PAD_LEFT))->where('token_email', $token_email)->first();
        if(is_null($EmailObj)){
            return [
                "status" => "error",
                "message" => "E-mail não encontrado!"
            ];
        }
        $empresas = returnEmpresasNasajonView();
        $variaveis_replace["nome_estabelecimento"] = $empresas[intval($estabelecimento)];
        $EmailObj = $this->parserBodyVarival($EmailObj, $variaveis_replace);

        $OjbEmailSend = new \stdClass();
        $OjbEmailSend->from = ["address" => $EmailObj->email_sender];
        $OjbEmailSend->subject = (empty($titulo)) ? $EmailObj->subject : $titulo;
        $OjbEmailSend->body = $EmailObj->body;
        $OjbEmailSend->attachments_send = $attachments_send;
        $OjbEmailSend->template_email = $EmailObj->template_email;
        if(!empty($EmailObj->emails_send)){
            $email_to_temp = explode(";", $EmailObj->emails_send);
            $email_to = array_merge($email_to, $email_to_temp);
            unset($email_to_temp);
        }
        $emails_cc = [];
        if(!empty($EmailObj->emails_cc)){
            $emails_cc = explode(";", $EmailObj->emails_cc);
        }
        $emails_bcc = [];
        if(!empty($EmailObj->emails_bcc)){
            $emails_bcc = explode(";", $EmailObj->emails_bcc);
        }
        if(env('APP_DEBUG') === true){
            $email_to = [env('EMAIL_SEND_DEBUG')];
            $emails_cc = [];
            $emails_bcc = [];
        }
        try{
            Mail::to($email_to)
                ->cc($emails_cc)
                ->bcc($emails_bcc)
                ->send(new EmailPadrao($OjbEmailSend));
            return [
                'status' => 'success'
            ];
        } catch (\Exception $e){
            return [
                'status' => 'error',
                'message' => 'Não foi possivel enviar o e-mail',
                'error' => $e
            ];
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $EmailObj
     * @param [type] $variaveis_replace
     * @return void
     */
    private function parserBodyVarival($EmailObj, $variaveis_replace){
        if(count($variaveis_replace) > 0){
            $body = $EmailObj->body;
            foreach ($variaveis_replace as $key => $value) {
                $body = str_replace("[[{$key}]]", $value, $body);
            }
            $EmailObj->body = nl2br($body);
            $subject = $EmailObj->subject;
            foreach ($variaveis_replace as $key => $value) {
                $subject = str_replace("[[{$key}]]", $value, $subject);
            }
            $EmailObj->subject = $subject;
        }
        return $EmailObj;
    }
}
