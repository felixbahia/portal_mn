<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
})->name('teste');*/

Route::post('auth/login', 'Api\UserController@login')->name("api.login");

Route::group(['middleware' => 'auth:api'], function(){
	Route::post('auth/logout', 'Api\UserController@logout')->name("api.logout");

	Route::get('estabelecimentos', 'Api\EstabelecimentosController@getDados')->name("api.estabelecimentos");

	Route::prefix('produtos')->group(function () {
		Route::get('buscar', 'Api\ProdutoController@busca')->name("api.produtos.buscar");
	});

	Route::prefix('produto')->group(function () {
		Route::post('/informacoes_preco', 'Api\ProdutoController@retornaInformacoesPreco')->name('api.produto.informacoes_preco');
		Route::post('/filtro/pedido', 'Api\ProdutoController@buscaPedido')->name('api.produto.pedido_buscar');
	});

	Route::prefix('tabela-preco')->group(function(){
		Route::get('/', 'Api\ListaDePrecosController@popularLocalizacao')->name('api.preco.localizacoes');
		Route::get('/buscar', 'Api\ListaDePrecosController@listaDePrecos')->name('api.preco.lista');
	});

	Route::prefix('cep')->group(function(){
		Route::get('/', 'Api\CepController@busca')->name('api.cep.busca');
		Route::get('/cidades', 'Api\CepController@buscaCidadePorEstado')->name('api.cep.cidade');
	});

	Route::prefix('transportadores')->group(function () {
		Route::get('/', 'Api\TransportadorController@busca')->name("api.transportador.buscar");
	});

	Route::prefix('vendedores')->group(function () {
		Route::get('/', 'Api\VendedorController@busca')->name("api.vendedores.buscar");
	});

	Route::prefix('user')->group(function(){
		Route::get('/session', 'Api\UserController@getSession')->name('api.user.session');
		Route::put('/info', 'Api\UserController@updadateInfo')->name('api.user.info');
	});

	Route::prefix('clientes')->group(function(){
		Route::get('/', 'Api\ClienteNovoController@busca')->name('api.cliente_novo.busca');
		Route::put('/cadastrar', 'Api\ClienteNovoController@cadastro')->name('api.cliente_novo.cadastro');

		Route::prefix('comissao')->group(function(){
			Route::get('/', 'Api\ComissaoApiController@comissao')->name('api.comissao');
			Route::get('/detalhes/{id}', 'Api\ComissaoApiController@comissaoDetalhes')->name('api.comissao.detalhes');
		});
		Route::post('/condicao_pagamento', 'Api\ClienteNovoController@buscaCondicoesPagamento')->name('api.cliente.condicao_pagamento');
		Route::post('/verifica_credito', 'Api\ClienteNovoController@verificaCredito')->name('api.cliente.verifica_credito');
	});

	Route::prefix('titulos')->group(function(){
		Route::get('/buscar', 'Api\TitulosController@buscar')->name('api.titulos.buscar');
		Route::get('/{estabelecimento}/{numero_nota}/{serie_nota}', 'Api\TitulosController@exibeNota')->name('api.titulos.detalhes');

	});

	Route::prefix('pedidos')->group(function(){
		Route::get('/filtros/', 'Api\PedidoController@filtros')->name('api.pedidos.filtros');
		Route::get('/buscar/', 'Api\PedidoController@buscar')->name('api.pedidos.buscar');
		
		Route::post('/', 'Api\PedidoController@returnDadosAbertura')->name('api.pedidos.salvar');
		
		Route::post('/salvar', 'Api\PedidoController@salvarPedido')->name('api.pedidos.salvar');
		Route::post('/ultimos_dados', 'Api\PedidoController@recuperaUltimosDados')->name('api.pedidos.ultimos_dados');

		Route::prefix('{pedido}/')->group(function(){
			Route::post('/totalizadores', 'Api\PedidoController@totalizadores')->name('api.pedidos.totalizadores');
			Route::post('/apagar_todos_itens', 'Api\PedidoController@apagarTodosItemsPedido')->name('api.pedidos.apagar_todos_itens');
			Route::prefix('item')->group(function(){
				Route::post('/adicionarProduto', 'Api\PedidoController@adicionarProduto')->name('api.pedidos.itens.adicionar'); 
				Route::post('/editarProduto', 'Api\PedidoController@editarProduto')->name('api.pedidos.itens.editar');
				Route::post('/excluirProduto', 'Api\PedidoController@excluirProduto')->name('api.pedidos.itens.excluir');
			});
		});
	});

});

Route::prefix('pedido_online')->group(function(){
	Route::post('/integracao','PedidoCieloIntegracaoController@integracao')->name('pedido_online.integracao');
	Route::any('/pagamento_pedido_debito/{token}','PedidoCieloIntegracaoController@pagamentoOnlineDebito')->name('pedido_online.pagamento_debito');
	Route::any('/retorno/{token}','PedidoCieloIntegracaoController@retornoPagamento')->name('pedido_online.retorno_pagamento');
});

Route::prefix('retorno_pagarme_presencial')->group(function(){
	Route::any('/','StoneTransacaoController@retornoApiConnect')->name('api.stone.retorno_api_connect');
});