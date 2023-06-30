<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

use App\ClienteNasajon;
use App\TransportadorNasajon;

class RemessaConsignacaoSalvarRequest extends FormRequest
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
		return [
			'cliente_nome' => [
				'required',
				function($attribute, $value, $fail) {
					if(!empty($value)){
						$ClienteNasajonObj = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ILIKE', trim($this->cliente_nome))->where('bloqueado', 'false')->first();
						if(empty($ClienteNasajonObj)){
							return $fail(__('validation.exists', ['attribute' => 'Cliente']));
						}

					}
				}
			],
			'transportadora_nome' => [
				'required',
				function($attribute, $value, $fail) {
					if(!empty($value)){
						$TransportadorNasajonObj = TransportadorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'), 'ILIKE', trim($this->transportadora_nome))->where('bloqueado', 'false')->first();
						if(empty($TransportadorNasajonObj)){
							return $fail(__('validation.exists', ['attribute' => 'Transportadora']));
						}

					}
				}
			],
			'transportadora_tipo_frete' => [
				'required',
			]
		];
	}
	public function messages(){
		return [
			'cliente_nome.required' => __('validation.required', ['attribute' => 'Cliente']),
			'transportadora_nome.required' => __('validation.required', ['attribute' => 'Transportadora']),
			'transportadora_tipo_frete.required' => __('validation.required', ['attribute' => 'Tipo frete']),
		];
	}

	protected function failedValidation(Validator $validator) {
		$errors = (new ValidationException($validator))->errors();
		foreach ($errors as $key => $value) {
			$errors[$key] = implode("<br>", $value);
		}
		$error = [
			'status' => 'error', /// success, error
			'message' => 'Campos inválidos', /// mensagem
			'error' => $errors,
			'response' => []
		];
		throw new HttpResponseException(response()->json($error, 422));
	}
}
