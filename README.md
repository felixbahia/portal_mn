# README #


# Criação de Features
* Usar padrão git flow [https://danielkummer.github.io/git-flow-cheatsheet/index.pt_BR.html](https://danielkummer.github.io/git-flow-cheatsheet/index.pt_BR.html)
* Todos as features devem ser CamelCase
* Criar sempre uma feature nova para os programas
* Todo merge tem que ter o pull do develop

# Sempre que for criar programa novo deve ser criado uma Controller novo
|

# Padrão de resposta padrão para o cliente (Navegador)
```
[
	status = '', /// success, error
	message = '', /// mensagem
	error = [
		campo = [ ]
	],
	response: [ ]
]
```
* Respostas deven ser sempre feito usando o response logo em seguida. Ex:
```php
	return response()->json([
		'status' => '', /// success, error
		'message' => '', /// mensagem
		'error' => [
			'campo' => []
		],
		'response' => []
	]);
```
* Codigo HTTP de resposta devem ser sempre:
	* 200 para success
	* 422 para error



# Padrões de criação de tabelas e seus campos
* ### Tipo de campo:
	* numérico: *Float*
	* texto: *String*
	* verdadeiro ou falso: *Boolean*
	* exceção: Procurar o responsalve para definir
* ### Nome de campo:
	* Sempre separar por "_" nome
	* Relacionamento com outras tabelas:
		* nome_tabela + "_" + chave_primaria
		* exeções:
			* Produto: produto_codigo (String)
			* Estabelecimento: estabelecimento_codigo (String tamanho 2)
			* Tabelas/Views Nasajon: Procurar o responsalve para definir
* ### Nome de tabela
	* Nome da tabela separada por "_" e terminar por "s"
	* Tabela filha deve ser nome_tabela_pai_sem_s + "_" + nome_filha
* ### Padrão de nome de coluna
	* Sempre ter campos usuário criação, edição e exclusão:
		* created_by = Criação
        * updated_by = Edição
        * deleted_by = Exclusão


# Criação de Views
* Devem ser criada pasta para cada programa em /resources/views/programs
* Programas que são aberto pela modal devem ser criado a pasta /modal dentro da pasta do programa
* Ultilizar layouts já criados
* Não é para criar layout novo sem a definição com o responsalvel
* Nos arquivos que não são de aberturas por modal não devem ter a tag *script*
* Não devem ter console ou comentario deixando lixo nos codigos
* DataTable só deve ser criado uma unica vez quando a pagina é carregada 
* Datatable o target de coluna apenas por classe
* Id da modal de abertura tem que ter corencia com o que é
* Qualquer função tem que usar padrão CamelCase o lowerCamelCase
* Padrão de retorno de cliente sempre "Nome - cpf/cnpj"
* Padrão de retorno de produto se não for apenas uma coluna "Código - Descrição"
* Sempre olhar se a ordenação do datatable está certa
* Função de mensagem sempre tem que ter titulo
* Nenhuma função em javascript pode já existir em outro programa
* Todos os campos devem ter maxlength com o maximo do banco
* Todos os campos devem usar forms para criação
* Funções que não tem retorno ou ação não deve existir
* Rotas tem que estar na função não no input (Resalva para rotas criadas com parametro)

# Criação de rotas
* Criar um grupo para todos os programas
* Programas que são a abertura da modal deve estar em um grupo 'modal'
* Nomes das rotas devem ser separadas por '_'
* Alias da rota deve ser claro e separado por '_'
* Adicionar rotas sempre no final do arquivo
* Deixa uma linha em branco no final
* Rotas se não são aberta pelo navegador tem que ser post
* Todas as rotas tem que ser autenticadas
* Agrupar rotas sempre que se tem grupos de ações
* Padrão para ações:
	* adicionar tem que conter alias e nome 'salvar'
	* editar tem que conter alias e nome 'editar'
	* excluir tem que conter alias e nome 'excluir'
	* não devem conter get, set, carregar no alias e nome

# Criação de Model
* Nomes devem ser CamelCase
* Qualquer função tem que usar padrão CamelCase o lowerCamelCase
* Quando for uma view da nasajon sempre adicionar `Nasajon` no final
* Nome deve ser o nome da tabela sem o 's' no final
* Devem ter o relacionamento HasOne ou HasMany com suas respectivas relações

# Criação de Controller
* Nomes devem ser CamelCase
* Criar sempre com o nome do programa e no final Controller
* Uso do whereRaw é em ultimo caso
* Uso do selectRaw é em ultimo caso
* Nunca usar $request->all()
* Qualquer função tem que usar padrão CamelCase o lowerCamelCase
* Biblioteca do carbon sempre é use Carbon\Carbon
* Biblioteca de auth é sempre use Auth
* Indice de array tem que ser padrão Snake case
* Padrão de retorno de cliente sempre "Nome - cpf/cnpj"
* Padrão de retorno de produto se não for apenas uma coluna "Código - Descrição"
* Usar sempre with na busca se estiver usando relação
* Não colocar with que não está usando
* Uma busca não deve ter chamadas de diversas vezes a função with
* Data que vem do banco ou imputadas no codigo devem usar Carbon::parse() e sempre estar com formato internacional
* Formato de data na busca deve ser usado o formato internacional
* Varialvel e indice de array devem ser claro o valor que tem
* Não usar if else sem chave
* Sempre ter uma linha separando as chaves de funções e if else
* Não pode conter log
* Não usar as funções do laravel: insert, update, create
* Chamadas de API de banco devem sempre ter tratamento


# Criação de Request
* Nomes devem ser CamelCase
* Todos os programas devem ter Request para validação dos dados
* Criar sempre com o nome do programa + ação do programa + Request
* As mensagem tem que usar o padrão de retorno do laravel. Todos os padrões estão em resources/lang/pt-BR/validation.php. Qualquer mensagem que seja fora do padrão deve ser discutida. Exemplo de uso:
* ```php
	__(\'validation.required\', [\'attribute\' => \'campo\'])
	```
*

* Usar padrão de resposta:
* ```php
	use Illuminate\Http\JsonResponse;
	use Illuminate\Validation\ValidationException;
	use Illuminate\Contracts\Validation\Validator;
	use Illuminate\Http\Exceptions\HttpResponseException;
	use Illuminate\Validation\Rule;
	
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
        throw new HttpResponseException(response()->json($error, 422));
    }
	```

# Criação de Export
* Nomes devem ser CamelCase
* Criar sempre com o nome do programa + Export

# Criação de Funções
* Nomes devem usar padrão CamelCase o lowerCamelCase
* Nomes devem ser claros
* Nomes devem ser em portugues
* Toda função deve ser claras quanto o resultado
* Toda função deve ter presente qual a nivel de acesso (private, protected ou public)
* Toda função nova tem que estar no final do arquivo


# Criação de comando
* Nomes devem usar padrão CamelCase
* Comando deve ser discutido com o responsavel o nome
* Descrição deve ser clara
* Não devem existir Log e echo
* Todo comando precisa de um registro no AtualizaCron e ser usado na chamada
* Exemplo de uso
```php
use App\AtualizacaoCron;
use Carbon\Carbon;

$AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'comando_atualizar')->first();

$inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
$final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

if($inicio_atualizacao->lte($final_atualizacao)){
	$AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
	$AtualizacaoCronObj->save();

	try{
		$objeto = $this->funcaoRodada();
		$AtualizacaoCronObj->atualizacao = Carbon::now();
		$AtualizacaoCronObj->erro = '';
		$AtualizacaoCronObj->alerta_erro = false;
		$AtualizacaoCronObj->save();
	}catch (Exception $e) {
		$AtualizacaoCronObj->erro = $e->getMessage();
		$AtualizacaoCronObj->alerta_erro = true;
		$AtualizacaoCronObj->atualizacao = Carbon::now();
		$AtualizacaoCronObj->save();
	}
}
```

# Less
* Sempre deve ser adicionado por ultimo no arquivo
* Margin ou padding não se deve usar porcentagem 
* Icones devem ser usados do Font Awesome [https://origin.fontawesome.com/icons?d=gallery&m=free](https://origin.fontawesome.com/icons?d=gallery&m=free)
* Seguir o padrão Less [http://lesscss.org/](http://lesscss.org/) para os estilos

# Todos os codigos estão sendo revisados ao fundo se não tiverem nos padrões acima não serão aceitos


# Qualquer questão que não esteja acordado neste arquivo devem ser passadas para o reponsavel para ser ajustado
