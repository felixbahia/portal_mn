<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use App\NotaVendaNasajon;
use App\DevolucaoNota;

use Auth;
use Carbon\Carbon;

class DevolucaoNotaEditarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $erro_id = false;

        try {
            $id = Crypt::decrypt($this->id);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return [
                'nota_fiscal' => [
                    function ($attribute, $value, $fail){
                       return $fail('Ocorreu um erro ao processar sua requisição, por favor atualize a tela');
                    }
                ]
            ];
        }

        $estaDevolucaoNotaObj = DevolucaoNota::find($id); 

        if($estaDevolucaoNotaObj->devolucao_nota_status_id != 8){
            return [
                'nota_fiscal' => [
                    function ($attribute, $value, $fail){
                       return $fail('Esta requisição já foi enviada e não está mais em digitação');
                    }
                ]
            ];
        }

        $notaNasajon = NotaVendaNasajon::with('faturamento_nota_devolucao', 'item')
            ->where('cod_estabelecimento', str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT))
            ->where(DB::Raw('trim(leading \'0\' from numero)'), ltrim($this->nota_fiscal, 0))
            ->first();

        $devolucaoNotaObj = DevolucaoNota::with('produtos')
            ->where('estabelecimento', str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT))
            ->where('nota_fiscal', $this->nota_fiscal)
            ->where('id', '!=', $id)
            ->where('devolucao_nota_status_id', "!=", 11)
            ->get();

        $devolucaoNotaNasajonObj = $notaNasajon->faturamento_nota_devolucao->where('TIPO', 'DEVOLUÇÃO');

        $itens_nao_devolvidos = collect([]);

        if($devolucaoNotaObj->whereIn('devolucao_nota_status_id', [1,2,8])->isNotEmpty()){
            $digitacao_aberta = true;
        }
        else{
            $digitacao_aberta = false;
        }

        if($devolucaoNotaObj->containsStrict('valor_parcial', false)){
            $devolucao_completa = true;
        }
        else{
            $devolucao_completa = false;

            $notaNasajon->item->each(function($item) use (&$itens_nao_devolvidos, $devolucaoNotaNasajonObj, $devolucaoNotaObj){
    
                $devolvidos = 0;

                $devolucaoNotaNasajonItemsObj = $devolucaoNotaNasajonObj->pluck('itens_faturamento')->where('Item - Código', $item->produto_detalhes->codigo_produto);

                if($devolucaoNotaNasajonItemsObj->isNotEmpty()){
                    $devolvidos += $devolucaoNotaNasajonItemsObj->sum('Item - Quantidade');
                }
    
                $requisicaoDevolucaoItemObj = $devolucaoNotaObj->pluck('produtos')->flatten()->where('produto_id', $item->id_item_nota);

                if($requisicaoDevolucaoItemObj->isNotEmpty()){
                    $devolvidos += $requisicaoDevolucaoItemObj->flatten()->sum('quantidade');
                }

                if($item->quantidade - $devolvidos > 0){
                    $itens_nao_devolvidos->push([
                        'id' => $item->id_item_nota,
                        'quantidade' => $item->quantidade - $devolvidos
                    ]);
                }
            });
        }

        $raiz_cnpj = str_replace('.', '', explode('/', $notaNasajon->documento_cliente)[0]);

        if(in_array($raiz_cnpj, ['06311274', '05075884'])){
            $intercompany = true;
        }
        else{
            $intercompany = false;
        }

        if((!in_array(Auth::user()->id, [55, 46,105]) && Auth::user()->tipo_usuario_id != 1) && $notaNasajon->item->pluck('produto_detalhes')->flatten()->contains('linha', 'OUTLET')){
            $outlet = true;
        }
        else{
            $outlet = false;
        }

        $rules = [
            'descricao_documento' => 'array',
            'descricao_documento.*' => ['required_with:documento'],
            'documento.*' => [
                'mimetypes:application/pdf,application/msword,image/jpeg,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'max:2048',
                'required_with:descricao_documento'
            ],
            'estabelecimento' => [
                'required',
            ],
            'nota_fiscal' => [
                'required',
                function ($attribute, $value, $fail) use($notaNasajon, $itens_nao_devolvidos, $devolucao_completa, $digitacao_aberta, $intercompany, $outlet){

                    if(is_null($notaNasajon)){
                        return $fail('Nota inválida');
                    }

                    if($digitacao_aberta){
                        return $fail('Há uma requisição em digitação para esta nota, favor verificar');
                    }

                    if($devolucao_completa){
                        return $fail('Esta nota já tem um processo de devolução completa em aberto');
                    }
                    else if($itens_nao_devolvidos->isEmpty()){
                        return $fail('Todos os itens desta nota já foram devolvidos ou estão em processo de devolução');
                    }
                    
                    if($intercompany){
                        return $fail('Não é permitida devolução de notas intercompany');
                    }

                    /* retirada temporária da regra 

                    if(Carbon::now()->diffInDays($notaNasajon->emissao) > 31 && !in_array(Auth::user()->tipo_usuario_id, [1, 15, 18])){
                        return $fail('Não é permitida a devolução de notas emitidas há 32 dias ou mais');
                    }
                    */

                }
            ],
            'observacao' => [
                'required_if:motivo,1',
                'max:254'
            ],
            'arquivo' => [
                ($this->motivo==1&&empty($estaDevolucaoNotaObj->arquivo))?'required':'',
                'mimes:pdf,png,jpg,gif'
            ],
            'valor_parcial' => [
                'required',
                function ($attribute, $value, $fail) use($notaNasajon, $devolucaoNotaNasajonObj, $devolucaoNotaObj){
                    if($value == true && !isset($this->produtos)){
                        return $fail('Selecione os produtos');
                    }
                    else if(($devolucaoNotaNasajonObj->isNotEmpty() || $devolucaoNotaObj->isNotEmpty())  && $value == 0){
                        return $fail('Já foi realizada uma devolução parcial para esta nota, não é mais possível fazer uma devolução completa.');
                    }
                }
            ],
            'produtos.*.produto' => [
                'exists:nasajon.integracoes.vw_notas_de_venda_itens,id_item_nota'
            ],
            'produtos.*.quantidade_devolvida' => [
                'required',
                function ($attribute, $value, $fail) use($itens_nao_devolvidos){
                    $item = $itens_nao_devolvidos->firstWhere('id', $this->produtos[explode('.', $attribute)[1]]['produto']);

                    if(!empty($item) && (parserNumber($value) > round($item['quantidade'], 2))){
                        return $fail('Quantidade inválida ');
                    }
                }
            ],
            'motivo' => [
                'required',
                'exists:devolucao_nota_motivos,id'
            ],
            'nome_contato' => 'required',
            'telefone_contato' => 'required',
            'email_contato' => [
                'required',
                'email'
            ]
        ];

        if(!empty($this->request->get('descricao_documento'))){
            foreach($this->request->get('descricao_documento') as $key => $val){
                $rules['descricao_documento.'.$key] = [
                    'required_with:documento.'.$key,
                ];
                $rules['documento.'.$key] = [
                    'mimetypes:application/pdf,application/msword,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'max:2048',
                    'required_with:descricao_documento.'.$key,
                ];
            }
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [];

        if(!empty($this->request->get('descricao_documento'))){
            foreach($this->request->get('descricao_documento') as $key => $val){
                $messages['descricao_documento.'.$key.'.required_with'] = __('validation.required_with', ['attribute' => 'Descrição do Documento', 'values' => 'Anexo']);
                
                $messages['documento.'.$key.'.max'] = __('validation.max.file', ['attribute' => 'Documento']);
                $messages['documento.'.$key.'.mimetypes'] = __('validation.mimes', ['attribute' => 'Documento', 'values' => 'PDF, DOC, PNG e JPG']);
                $messages['documento.'.$key.'.required_with'] = __('validation.required_with', ['attribute' => 'Anexo', 'values' => 'Descrição Documento']);
            }
        }

        $messages ['estabelecimento.required'] = __('validation.required', ['attribute' => 'Estabelecimento']);
        $messages ['nota_fiscal.required'] =  __('validation.required', ['attribute' => 'Número da nota']);
        $messages ['valor_devolvido.required_if'] =  'Digite um valor';
        $messages ['motivo.required'] =  'Selecione um motivo';
        $messages ['motivo.exists'] = 'Motivo inválido';
        $messages ['produtos.*.quantidade_devolvida.required'] = 'Digite uma quantidade para o produto selecionado';
        $messages ['valor_parcial.required'] = "Escolha o tipo de devolução";
        $messages ['nome_contato.required'] =  "Digite o nome do contato";
        $messages ['telefone_contato.required'] =  "Digite o telefone do contato";
        $messages ['email_contato.required'] =  "Digite o e-mail do contato";
        $messages ['email_contato.email'] =  "Digite um e-mail válido";
        $messages ['email_contato.email'] =  "Digite um e-mail válido";
        $messages ['observacao.required_if'] =  'É necessário descrever o defeito apresentado.';
        $messages ['observacao.max'] =  'A observação está longa demais, tamanho máximo de 254 caracteres';
        $messages ['arquivo.required_if'] =  'É necessário o envio de uma imagem que mostre o defeito apresentado';
        $messages ['arquivo.mimes'] =  'Os formatos aceitos para o arquivo são: pdf, png, jpg, e gif';

        return $messages;
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => '', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }
}