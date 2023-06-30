<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Authentication Routes...
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');
Route::get('home_coletor', 'Auth\LoginController@logout')->name('home_coletor');
Route::get('/home', 'ModuloController@indexMensagem')->name('home_message')->middleware('auth');
Route::get('/auth20', 'ModuloController@indexMensagem')->name('auth20')->middleware('MsGraphAuthenticated');
Route::get('/', 'ModuloController@index')->name('home')->middleware('auth');

Route::prefix('modulo')->middleware(['auth'])->group(function () {
	Route::get('/{modulo}', 'ModuloController@indexModulo')->name('modulo');
	Route::get('/{modulo}/{submodulo}', 'ModuloController@indexSubModulo')->name('sub_modulo');
});

Route::prefix('modulos')->middleware(['auth'])->group(function () {
	Route::get('/', 'ModuloController@indexCadastro')->name('modulos.index');
	Route::get('/cadastrar', 'ModuloController@create')->name('modulos.create');
	Route::post('/cadastrar', 'ModuloController@store')->name('modulos.store');
	Route::post('/filtro', 'ModuloController@filter')->name('modulos.filter');
	Route::post('/remover/{id}', 'ModuloController@destroy')->name('modulos.destroy');
	Route::get('/editar/{id}', 'ModuloController@edit')->name('modulos.edit');
	Route::post('/editar/{id}','ModuloController@update')->name('modulos.update');
});

Route::prefix('sub_modulos')->middleware(['auth'])->group(function () {
	Route::get('/', 'SubModuloController@indexCadastro')->name('submodulos.index');
	Route::get('/cadastrar', 'SubModuloController@create')->name('submodulos.create');
	Route::post('/cadastrar', 'SubModuloController@store')->name('submodulos.store');
	Route::post('/filtro', 'SubModuloController@filter')->name('submodulos.filter');
	Route::post('/remover/{id}', 'SubModuloController@destroy')->name('submodulos.destroy');
	Route::get('/editar/{id}', 'SubModuloController@edit')->name('submodulos.edit');
	Route::post('/editar/{id}','SubModuloController@update')->name('submodulos.update');
	Route::post('/modulos','SubModuloController@returnToModulos')->name('submodulos.modulo_id');
});

Route::prefix('programas')->middleware(['auth'])->group(function () {
	Route::get('/', 'ProgramaController@indexCadastro')->name('programas.index');
	Route::post('/filtro', 'ProgramaController@filter')->name('programas.filter');
	Route::get('/editar/{id}', 'ProgramaController@edit')->name('programas.edit');
	Route::post('/editar/{id}','ProgramaController@update')->name('programas.update');
});

Route::prefix('usuario_auth')->middleware(['auth'])->group(function () {
	Route::post('/empresa_padrao', 'UserController@empresaPadraoUpdate')->name('usuario.update-empresa');
});

// Usuários Routes
Route::prefix('usuario')->middleware(['auth'])->group(function () {
	Route::get('/', 'UserController@index')->name('usuario.index');
	Route::post('/cadastrarForm', 'UserController@create')->name('usuario.create');
	Route::post('/cadastrar', 'UserController@store')->name('usuario.store');
	Route::post('/filtro', 'UserController@filter')->name('usuario.filter');
	Route::post('/excluir', 'UserController@formExcluir')->name('usuario.formExcluir');
	Route::post('/remover', 'UserController@destroy')->name('usuario.destroy');
	Route::post('/editar', 'UserController@edit')->name('usuario.edit');
	Route::get('/visualizar/{id}', 'UserController@show')->name('usuario.show');
	Route::post('/editar/{id}','UserController@update')->name('usuario.update');
	Route::post('/logar','UserController@logarId')->name('usuario.logar');
	Route::get('/filtro_ajax','UserController@filterAjax')->name('usuario.filter-ajax');
	Route::post('/filtro_ajax', 'UserController@filterRole')->name('usuario.filter-ajax-role');
	Route::post('/tipo_usuario_responsavel', 'UserController@filterUserResponsavel')->name('usuario.filter.responsavel');
	Route::post('/dados_subordinados', 'UserController@getDadosSubordinados')->name('usuario.dados_subordinados');
	Route::post('/dados_subordinados_outros', 'UserController@getDadosSubordinadosOutros')->name('usuario.dados_subordinados_outros');
	Route::post('/gerentes_vendedores', 'UserController@retornaGerentesEVendedores')->name('usuario.gerentes_vendedores');
	Route::post('/vendedores', 'UserController@retornaVendedores')->name('usuario.vendedores');
	Route::post('/autocomplete', 'UserController@autoComplete')->name('usuario.autocomplete');
	Route::post('/autocomplete_codigo_representante', 'UserController@autoCompleteCodigoRepresentante')->name('usuario.autocomplete_codigo_representante');
	Route::post('/filtro_buscar', 'UserController@filtroBuscar')->name('usuario.filtro_buscar');
	Route::prefix('modal')->group(function(){
		Route::get('/buscar', 'UserController@modalBuscar')->name('usuario.modal.buscar');
	});

	Route::get('/editar_dados','UserController@editarDados')->name('usuario.editar_dados');
	Route::post('/editar_dados','UserController@salvarEditarDados')->name('usuario.salvar_editar_dados');

});

Route::prefix('usuario')->group(function () {
	Route::get('/photo/{id}','UserController@getPhoto')->name('usuario.photo');
});

// Fornecedor Contabil Routes
Route::prefix('fornecedor_contabil')->middleware(['auth'])->group(function () {
	Route::get('/', 'FornecedorContabilController@index')->name('fornecedor_contabil.index');
	Route::get('/cadastrar', 'FornecedorContabilController@create')->name('fornecedor_contabil.create');
	Route::post('/cadastrar', 'FornecedorContabilController@store')->name('fornecedor_contabil.store');
	Route::post('/filtro', 'FornecedorContabilController@filter')->name('fornecedor_contabil.filter');
	Route::post('/filtro_codigo', 'FornecedorContabilController@filterCodcad')->name('fornecedor_contabil.filter_codcad');
	Route::post('/remover/{codcad}/{estabel}', 'FornecedorContabilController@destroy')->name('fornecedor_contabil.destroy');
	Route::get('/editar/{codcad}/{estabel}', 'FornecedorContabilController@edit')->name('fornecedor_contabil.edit');
	Route::post('/editar/{codcad}/{estabel}','FornecedorContabilController@update')->name('fornecedor_contabil.update');
});

// Banco Contabil Routes
Route::prefix('banco_contabil')->middleware(['auth'])->group(function () {
	Route::get('/', 'BancoContabilController@index')->name('banco_contabil.index');
	Route::get('/cadastrar', 'BancoContabilController@create')->name('banco_contabil.create');
	Route::post('/cadastrar', 'BancoContabilController@store')->name('banco_contabil.store');
	Route::post('/filtro', 'BancoContabilController@filter')->name('banco_contabil.filter');
	Route::post('/remover/{estabel}/{codbco}', 'BancoContabilController@destroy')->name('banco_contabil.destroy');
	Route::get('/editar/{estabel}/{codbco}', 'BancoContabilController@edit')->name('banco_contabil.edit');
	Route::post('/editar/{estabel}/{codbco}','BancoContabilController@update')->name('banco_contabil.update');
});

// Banco Contabil Routes
Route::prefix('lotes_lancamentos')->group(function () {
	Route::get('/', 'LotesLancamentosController@index')->name('lotes_lancamentos.index')->middleware(['auth']);
	Route::post('/filtro', 'LotesLancamentosController@filter')->name('lotes_lancamentos.filter')->middleware(['auth']);
	Route::post('/update_lancamento', 'LotesLancamentosController@alterLancamentos')->name('lotes_lancamentos.alter_lancamentos')->middleware(['auth']);
	Route::get('/show/{status}/{id}', 'LotesLancamentosController@showTables')->name('lotes_lancamentos.show_tables')->middleware(['auth']);
	Route::post('/get_table/{lote}/{status}', 'LotesLancamentosController@getTable')->name("lotes_lancamentos.get_table")->middleware(['auth']);
	Route::get('/download/{id}', 'LotesLancamentosController@downloadLote')->name("lotes_lancamentos.download_lote")->middleware(['auth']);
	Route::post('/regerar/{id}', 'LotesLancamentosController@regerarLote')->name("lotes_lancamentos.regerar_file")->middleware(['auth']);
	Route::get('/insertTables', 'LancamentosExportController@insertTables')->name('lotes_lancamentos.create-inserts');
});

Route::prefix('perfis_acesso')->middleware(['auth'])->group(function () {
	Route::get("/", 'PerfilController@index')->name("perfil.index");
	Route::get('/cadastrar', 'PerfilController@create')->name('perfil.create');
	Route::post('/cadastrar', 'PerfilController@store')->name('perfil.store');
	Route::post('/filtro', 'PerfilController@filter')->name('perfil.filter');
	Route::post('/remover/{id}', 'PerfilController@destroy')->name('perfil.destroy');
	Route::get('/editar/{id}', 'PerfilController@edit')->name('perfil.edit');
	Route::post('/editar/{id}','PerfilController@update')->name('perfil.update');
	Route::get('/listar/usuarios/{id}','PerfilController@listUsers')->name('perfil.lista_usuarios');
	Route::post('/adicionar/usuario','PerfilController@addUser')->name('perfil.add_usuarios');
	Route::post('/usuarios', 'PerfilController@getTableUser')->name('perfil.get_table_user');

	Route::post('/filtro_modal','PerfilController@filtroModal')->name('perfil.filter_modal');

	Route::prefix('modal')->group(function(){
		Route::get('/busca','PerfilController@modalBusca')->name('perfil.modal.busca');
	});

	
});

// Cliente Route
Route::prefix('cliente')->middleware(['auth'])->group(function () {
	Route::get('/', 'ClienteController@index')->name('cliente.index');
	Route::get('/dialog', 'ClienteController@indexDialog')->name('cliente.index.dialog');
	Route::post('/filtro', 'ClienteController@filter')->name('cliente.filter');	
	Route::post('/dialog_cadastro', 'ClienteController@indexDialogCadastro')->name('cliente.index.dialogCadastro');
	Route::post('/dialog_cadastro_venda', 'ClienteController@indexDialogCadastroVenda')->name('cliente.index.dialogCadastroVenda');
	Route::post('/filtro_cadastro', 'ClienteController@filterCadastro')->name('cliente.filterCadastro');
	Route::post('/filtro_cadastro_venda', 'ClienteController@filterCadastroVenda')->name('cliente.filterCadastroVenda');
	Route::post('/view', 'ClienteController@show')->name('cliente.view');
	Route::prefix('posicao_sintetica')->middleware(['auth'])->group(function(){
		Route::get('/', 'ClienteAnaliseSinteticaController@index')->name('cliente.posicao_sintetica.index');		
		Route::post('/return_dados', 'ClienteAnaliseSinteticaController@returnDados')->name('cliente.posicao_sintetica.return');
		Route::post('/filter_titulos_faturados', 'ClienteAnaliseSinteticaController@filterTitulosFaturados')->name('cliente.posicao_sintetica.filter_titulos_faturados');
		Route::prefix('modal')->group(function(){
			Route::post('/', 'ClienteAnaliseSinteticaController@modal')->name('cliente.posicao_sintetica.modal');
			Route::post('/cheques', 'ClienteAnaliseSinteticaController@returnCheques')->name('cliente.posicao_sintetica.modal.cheques');
			Route::post('/titulo', 'ClienteAnaliseSinteticaController@detalhesTituloModal')->name('cliente.posicao_sintetica.modal.titulo');
			Route::post('/titulo_cheques_vinculados', 'ClienteAnaliseSinteticaController@tituloChequesVinculados')->name('cliente.posicao_sintetica.modal.titulo_cheques_vinculados');
		});
		Route::post('/titulos_faturados/{coluna}', 'ClienteAnaliseSinteticaController@returnTitulosFaturados')->name('cliente.posicao_sintetica.titulos_faturados');
		Route::post('/liberar', 'ClienteAnaliseSinteticaController@liberarTituloRenegociado')->name('cliente.posicao_sintetica.liberar_titulos');
		Route::post('/titutos_terceiros/{coluna}', 'ClienteAnaliseSinteticaController@returnTitulosTerceiros')->name('cliente.posicao_sintetica.titulos_terceiros');
		Route::post('/notas_credito/{coluna}', 'ClienteAnaliseSinteticaController@returnNotasCredito')->name('cliente.posicao_sintetica.notas_credito');
		Route::post('/notas_debito/{coluna}', 'ClienteAnaliseSinteticaController@returnNotasDebito')->name('cliente.posicao_sintetica.notas_debito');
		Route::post('/cheques_a_receber/{coluna}', 'ClienteAnaliseSinteticaController@returnChequesRaceber')->name('cliente.posicao_sintetica.cheques_a_receber');
		Route::post('/dados_clinete', 'ClienteAnaliseSinteticaController@dadosCliente')->name('cliente.posicao_sintetica.dados_clientes');
		Route::post('/cobranca_ragazzi', 'ClienteAnaliseSinteticaController@cobrancaRagazzi')->name('cliente.posicao_sintetica.cobranca_ragazzi');
		Route::post('/alterar_titulo_judicial', 'ClienteAnaliseSinteticaController@alterarTituloJudicial')->name('cliente.posicao_sintetica.alterar_titulo_judicial');
		Route::post('/titulos_pagos', 'ClienteAnaliseSinteticaController@titulosPagos')->name('cliente.posicao_sintetica.titulos_pagos');
		Route::post('/titulos_renegociados', 'ClienteAnaliseSinteticaController@titulosRenegociados')->name('cliente.posicao_sintetica.titulos_renegociados');
		Route::post('/titulos_forma_pagamento', 'ClienteAnaliseSinteticaController@titulosFormaPagamento')->name('cliente.posicao_sintetica.titulos_forma_pagamento');
		Route::post('/devolucao_dados', 'ClienteAnaliseSinteticaController@detalhesDevolucao')->name('cliente.posicao_sintetica.devolucao_dados');
	});
	Route::post('/autocomplete','ClienteController@autoComplete')->name('cliente.autocomplete');
	Route::post('/autoCompleteId','ClienteController@autoCompleteId')->name('cliente.autocompleteid');
	Route::post('/codParaNome','ClienteController@codParaNome')->name('cliente.codparanome');
	Route::post('/isencao','ClienteController@verificaClienteIsencaoEstadual')->name('cliente.isencao');
	Route::post('/nome_para_codigo','ClienteController@nameToCod')->name('cliente.name_to_cod');
	Route::post('/autocomplete_nome','ClienteController@autoCompleteNome')->name('clientes.autocomplete');
	Route::post('/autocomplete_nome_venda','ClienteController@autoCompleteNomeVenda')->name('clientes.autocomplete_venda');
	Route::prefix('cliente_padrao')->middleware(['auth'])->group(function(){
		Route::post('/apaga_cliente_padrao', 'ClienteController@apagaClientePadrao')->name('cliente.apagaClientePadrao');
		Route::post('/salva_cliente_padrao', 'ClienteController@salvaClientePadrao')->name('cliente.salvaClientePadrao');
	});
	Route::prefix('limite')->middleware(['auth'])->group(function(){
		Route::get('/', 'ClienteCreditoController@index')->name('cliente.limite.index');
		Route::post('/filtrar', 'ClienteCreditoController@filter')->name('cliente.limite.filter');
		Route::post('/adicionar', 'ClienteCreditoController@adicionar')->name('cliente.limite.adicionar');
		Route::post('/adicionar_aprovacao', 'ClienteCreditoController@adicionar')->name('cliente.limite.adicionar_aprovacao');
		Route::post('/editar', 'ClienteCreditoController@editar')->name('cliente.limite.editar');
		Route::post('/excluir', 'ClienteCreditoController@excluir')->name('cliente.limite.excluir');
		Route::prefix('modal')->group(function(){
			Route::get('/adicionar', 'ClienteCreditoController@modalAdicionar')->name('cliente.limite.modal.adicionar');
			Route::post('/editar', 'ClienteCreditoController@modalEditar')->name('cliente.limite.modal.editar');
			Route::post('/deletar', 'ClienteCreditoController@modalDeletar')->name('cliente.limite.modal.deletar');
			Route::post('/adicionar_aprovacao', 'ClienteCreditoController@modalAdicionarAprovacao')->name('cliente.limite.modal.adicionar_aprovacao');
		});
	});
	Route::prefix('edicao')->middleware(['auth'])->group(function(){
		Route::get('/', 'ClienteEdicaoController@index')->name('cliente.edicao.index');
		Route::post('/info/cliente', 'ClienteEdicaoController@infoCliente')->name('cliente.edicao.infoCliente');
		Route::post('/salvar', 'ClienteEdicaoController@salvarEdicao')->name('cliente.edicao.salvar');
		Route::post('/excluir_documento', 'ClienteEdicaoController@excluirDocumento')->name('cliente.edicao.documento.excluir');
	});

	Route::post('filtro_consulta', 'ClienteController@filterConsulta')->name('cliente.filter_consulta');
	Route::post('cidades', 'ClienteController@cidades')->name('cliente.cidades');
});

// Cliente Route
Route::prefix('produto')->middleware(['auth'])->group(function () { 
	Route::get('/', 'ProdutoController@index')->name('produto.index');
	Route::post('/filtro', 'ProdutoController@filter')->name('produto.filter');
	Route::post('/filtrosimples', 'ProdutoController@filterSimples')->name('produto.filtersimples');
	Route::post('/filtersimples_limitado', 'ProdutoController@filterSimplesLimitado')->name('produto.filtersimples_limitado');
	Route::post('/pesquisacodigo', 'ProdutoController@pesquisaProdutoPorCodigo')->name('produto.pesquisaprodutocodigo');
	Route::post('/pesquisadescricao', 'ProdutoController@pesquisaProdutoPorDescricao')->name('produto.pesquisaprodutodescricao');
	Route::post('/pesquisacodigoesp', 'ProdutoController@pesquisaProdutoPorCodigoEspecificacao')->name('produto.pesquisaprodutocodigoespecificacao');
	Route::post('/pesquisadescricaoesp', 'ProdutoController@pesquisaProdutoPorDescricaoEspecificacao')->name('produto.pesquisaprodutodescricaoespecificacao');
	Route::post('/pesquisaoprecofob', 'ProdutoController@pesquisaPrecoFobPorGrupo')->name('produto.pesquisaprecofob');
	Route::post('/view', 'ProdutoController@show')->name('produto.view');
	Route::post('/autocomplete', 'ProdutoController@autoComplete')->name('produto.autocomplete');
	Route::post('/autocompletelimitacao', 'ProdutoController@autoCompleteLimitado')->name('produto.autocompletelimitacao');
	Route::post('/autocompletepedido', 'ProdutoController@autoCompletePedido')->name('produto.autocompletepedido');
	Route::post('/exportar', 'ProdutoController@exportarExcel')->name('produto.exportar');
	Route::prefix('analise')->middleware(['auth'])->group(function(){
		Route::get('/', 'ProdutoController@indexAnalise')->name('analise.produto.index');
		Route::post('/filtro', 'ProdutoController@filterAnaliseTela')->name('analise.produto.filter');
		Route::post('/export', 'ProdutoController@exportAnalisePedido')->name('analise.produto.export');
	});

	Route::prefix('modal')->group(function () {
		Route::post('/estoque', 'ProdutoController@indexEstoque')->name('produto.estoque');
		Route::post('/reserva', 'ProdutoController@indexReserva')->name('produto.reserva');
		Route::post('/pecas_reserva', 'ProdutoController@indexPecasReserva')->name('produto.pecas_reserva');
		Route::post('/compras', 'ProdutoController@indexCompras')->name('produto.compras');
		
		Route::post('/pesquisa', 'ProdutoController@modalPesquisa')->name('produto.modal_pesquisa');
		Route::post('/pesquisa_limitadao', 'ProdutoController@modalPesquisaLimitado')->name('produto.modal_pesquisa_limitado');
		Route::post('/pedido', 'ProdutoController@modalPesquisaPedido')->name('produto.modal_pesquisa_pedido');

		Route::prefix('movimento_estoque')->middleware(['auth'])->group(function(){
			Route::post('/dialog', 'MovimentoEstoqueController@dialog')->name('produto.movimento_estoque.dialog');
			Route::post('/movimento_portal', 'MovimentoEstoqueController@movimentacaoPortal')->name('produto.movimento_estoque.movimento_portal');
			Route::post('/movimento_grupo_portal', 'MovimentoEstoqueController@movimentacaoPortalGrupo')->name('produto.movimento_estoque.movimento_portal_grupo');
			Route::post('/movimento_estabelecimentos_portal', 'MovimentoEstoqueController@movimentacaoPortalEstabelecimentos')->name('produto.movimento_estoque.movimento_portal_estabelecimentos');
			Route::post('/movimento_portal_terceiro', 'MovimentoEstoqueController@movimentoEstoqueTerceiro')->name('produto.movimento_estoque.movimento_portal_terceiro');
			Route::post('/movimento_portal_terceiro_fornecedor', 'MovimentoEstoqueController@movimentoEstoqueTerceiroFornecedor')->name('produto.movimento_estoque.movimento_portal_terceiro_fornecedor');
		});
	});

	Route::prefix('especificacoes')->middleware(['auth'])->group(function(){
		Route::get('/', 'InformacaoAdicionalProdutoController@index')->name('analise.produto.infoadicional.index');
		Route::post('/filtro', 'InformacaoAdicionalProdutoController@filter')->name('analise.produto.infoadicional.filter');
		Route::post('/editar', 'InformacaoAdicionalProdutoController@telaEdicao')->name('analise.produto.infoadicional.telaEdicao');
		Route::post('/editar/salvar', 'InformacaoAdicionalProdutoController@salvar_edicao')->name('analise.produto.infoadicional.salvar_edicao');
		Route::post('/adicionar', 'InformacaoAdicionalProdutoController@telaAdicao')->name('analise.produto.infoadicional.telaAdicao');
		Route::post('/adicionar/salvar', 'InformacaoAdicionalProdutoController@salvar_adicao')->name('analise.produto.infoadicional.salvar_adicao');
		Route::post('/novo_produto/detalhes', 'InformacaoAdicionalProdutoController@retornaDetalhesProduto')->name('analise.produto.infoadicional.retornaDetalhesProduto');
		Route::post('/novo_produto/excluir', 'InformacaoAdicionalProdutoController@excluir')->name('analise.produto.infoadicional.excluir');
		Route::post('/busca/produtos', 'InformacaoAdicionalProdutoController@modalPesquisaProdutos')->name('analise.produto.infoadicional.produtosSemDetalhes');
		Route::post('/busca/produtos/filtro', 'InformacaoAdicionalProdutoController@retornaProdutosSemDetalhes')->name('analise.produto.infoadicional.retornaProdutosSemDetalhes');
		Route::post('/busca/grupo', 'InformacaoAdicionalProdutoController@buscaGrupo')->name('analise.produto.infoadicional.busca_grupo');
	});
	Route::post('/retorna_informacoes_preco', 'ProdutoController@retornaInformacoesPreco')->name('produto.retorna_informacoes_preco');
	Route::post('/retorna_informacoes_produto', 'ProdutoController@retornaInformacoesProduto')->name('produto.retorna_informacoes_produto');
	Route::post('/filtro/estoque/porestabelecimento', 'ProdutoController@filterEstoqueEstabelecimento')->name('produto.filter_estoque_estabelecimento');
	Route::prefix('sem_estoque')->middleware(['auth'])->group(function(){
		Route::get('/', 'ProdutosSemEstoqueController@index')->name('produtos_sem_estoque.index');
		Route::post('/filter', 'ProdutosSemEstoqueController@filter')->name('produtos_sem_estoque.filtro');
	});
	Route::post('/retorna_estoque_pedido', 'ProdutoController@getEstoqueProdutoDisponivel')->name('produto.retorna_estoque_disponivel');
	Route::prefix('grupo')->middleware(['auth'])->group(function(){	
		Route::get('/', 'ProdutoGrupoController@index')->name('produto.grupo.index');
		Route::post('/adicionar', 'ProdutoGrupoController@adicionar')->name('produto.grupo.adicionar');
		Route::post('/editar', 'ProdutoGrupoController@editar')->name('produto.grupo.editar');
		Route::post('/deletar', 'ProdutoGrupoController@deletar')->name('produto.grupo.deletar');
		Route::post('/filter', 'ProdutoGrupoController@filter')->name('produto.grupo.filter');
		Route::post('/autocomplete', 'ProdutoGrupoController@autoComplete')->name('produto.grupo.autocomplete');
		Route::prefix('modal')->group(function(){
			Route::get('/adicionar', 'ProdutoGrupoController@modalAdicionar')->name('produto.grupo.modal.adicionar');
			Route::post('/editar', 'ProdutoGrupoController@modalEditar')->name('produto.grupo.modal.editar');
			Route::post('/deletar', 'ProdutoGrupoController@modalDeletar')->name('produto.grupo.modal.deletar');
		});	
	});
	Route::post('/filtro/pedido', 'ProdutoController@filterModalPedido')->name('produto.filter_pesquisa_pedido');
	Route::prefix('marca')->middleware(['auth'])->group(function(){	
		Route::get('/', 'ProdutoMarcaController@index')->name('produto.marca.index');
		Route::post('/adicionar', 'ProdutoMarcaController@adicionar')->name('produto.marca.adicionar');
		Route::post('/editar', 'ProdutoMarcaController@editar')->name('produto.marca.editar');
		Route::post('/deletar', 'ProdutoMarcaController@deletar')->name('produto.marca.deletar');
		Route::post('/filter', 'ProdutoMarcaController@filter')->name('produto.marca.filter');
		Route::post('/autocomplete', 'ProdutoMarcaController@autoComplete')->name('produto.marca.autocomplete');
		Route::prefix('modal')->group(function(){
			Route::get('/adicionar', 'ProdutoMarcaController@modalAdicionar')->name('produto.marca.modal.adicionar');
			Route::post('/editar', 'ProdutoMarcaController@modalEditar')->name('produto.marca.modal.editar');
			Route::post('/deletar', 'ProdutoMarcaController@modalDeletar')->name('produto.marca.modal.deletar');
		});	
	});
	Route::prefix('linha')->middleware(['auth'])->group(function(){	
		Route::get('/', 'ProdutoLinhaController@index')->name('produto.linha.index');
		Route::post('/adicionar', 'ProdutoLinhaController@adicionar')->name('produto.linha.adicionar');
		Route::post('/editar', 'ProdutoLinhaController@editar')->name('produto.linha.editar');
		Route::post('/deletar', 'ProdutoLinhaController@deletar')->name('produto.linha.deletar');
		Route::post('/filter', 'ProdutoLinhaController@filter')->name('produto.linha.filter');
		Route::post('/autocomplete', 'ProdutoLinhaController@autoComplete')->name('produto.linha.autocomplete');
		Route::prefix('modal')->group(function(){
			Route::get('/adicionar', 'ProdutoLinhaController@modalAdicionar')->name('produto.linha.modal.adicionar');
			Route::post('/editar', 'ProdutoLinhaController@modalEditar')->name('produto.linha.modal.editar');
			Route::post('/deletar', 'ProdutoLinhaController@modalDeletar')->name('produto.linha.modal.deletar');
		});	
	});
	Route::prefix('subgrupo')->middleware(['auth'])->group(function(){	
		Route::get('/', 'ProdutoSubgrupoController@index')->name('produto.subgrupo.index');
		Route::post('/adicionar', 'ProdutoSubgrupoController@adicionar')->name('produto.subgrupo.adicionar');
		Route::post('/editar', 'ProdutoSubgrupoController@editar')->name('produto.subgrupo.editar');
		Route::post('/deletar', 'ProdutoSubgrupoController@deletar')->name('produto.subgrupo.deletar');
		Route::post('/filter', 'ProdutoSubgrupoController@filter')->name('produto.subgrupo.filter');
		Route::post('/autocomplete', 'ProdutoSubgrupoController@autoComplete')->name('produto.subgrupo.autocomplete');
		Route::prefix('modal')->group(function(){
			Route::get('/adicionar', 'ProdutoSubgrupoController@modalAdicionar')->name('produto.subgrupo.modal.adicionar');
			Route::post('/editar', 'ProdutoSubgrupoController@modalEditar')->name('produto.subgrupo.modal.editar');
			Route::post('/deletar', 'ProdutoSubgrupoController@modalDeletar')->name('produto.subgrupo.modal.deletar');
		});	
	});
	Route::prefix('insumo')->middleware(['auth'])->group(function(){
		Route::post('/insumo_descricao', 'ProdutoInsumoController@retornaInsumoDescricao')->name('produto.insumo.insumo_descricao');
		Route::post('/servico_descricao', 'ProdutoInsumoController@retornaServicoDescricao')->name('produto.insumo.servico_descricao');
		Route::post('/auto_complete', 'ProdutoInsumoController@autoComplete')->name('produto.insumo.autocomplete');
		Route::post('/calculo', 'ProdutoInsumoController@calculoInsumo')->name('produto.insumo.calculo');
		Route::prefix('modal')->group(function(){
			Route::get('/buscar', 'ProdutoInsumoController@modalBuscarInsumo')->name('produto.insumo.modal.buscar');
		});
	});
	Route::prefix('tecido')->middleware(['auth'])->group(function(){
		Route::post('/tecido_descricao', 'ProdutoTecidosController@retornaTecidoDescricao')->name('produto.tecido.tecido_descricao');
		Route::post('/auto_complete', 'ProdutoTecidosController@autoComplete')->name('produto.tecido.autocomplete');
		Route::post('/calculo', 'ProdutoTecidosController@calculoTecidos')->name('produto.tecido.calculo');
		Route::post('/validar_descricao', 'ProdutoTecidosController@validarDescricaoTecido')->name('produto.tecido.validar_descricao');
		Route::prefix('modal')->group(function(){
			Route::get('/buscar', 'ProdutoTecidosController@modalBuscarTecido')->name('produto.tecido.modal.buscar');
		});
	});
	Route::prefix('produto_novo')->middleware(['auth'])->group(function(){
		Route::get('/', 'ProdutoNovoController@index')->name('produto.novo.index');
		Route::post('/adicionar','ProdutoNovoController@adicionar')->name('produto.novo.adicionar');
		Route::post('/editar','ProdutoNovoController@editar')->name('produto.novo.editar');
		Route::post('/filter','ProdutoNovoController@filter')->name('produto.novo.filter');
		Route::prefix('modal')->group(function(){
			Route::get('/adicionar', 'ProdutoNovoController@modalAdicionar')->name('produto.novo.modal.adicionar');
			Route::post('/editar', 'ProdutoNovoController@modalEditar')->name('produto.novo.modal.editar');
		});	
	});
	Route::prefix('tecido_base')->middleware(['auth'])->group(function(){
		Route::get('/','ProdutoTecidoBaseController@index')->name('produto.tecido_base.index');
		Route::post('/adicionar','ProdutoTecidoBaseController@adicionar')->name('produto.tecido_base.adicionar');
		Route::post('/filter', 'ProdutoTecidoBaseController@filter')->name('produto.tecido_base.filter');
		Route::post('/filtro_buscar', 'ProdutoTecidoBaseController@filtroBuscar')->name('produto.tecido_base.filtro_buscar');
		Route::post('/editar', 'ProdutoTecidoBaseController@editar')->name('produto.tecido_base.editar');
		Route::post('/deletar', 'ProdutoTecidoBaseController@deletar')->name('produto.tecido_base.deletar');
		Route::post('/autocomplete', 'ProdutoTecidoBaseController@autoComplete')->name('produto.tecido_base.autocomplete');
		Route::post('/get_tecido_base', 'ProdutoTecidoBaseController@getTecidoBase')->name('produto.tecido_base.get_tecido_base');
		Route::prefix('modal')->group(function(){
			Route::get('/adicionar', 'ProdutoTecidoBaseController@modalAdicionar')->name('produto.tecido_base.modal.adicionar');
			Route::post('/editar', 'ProdutoTecidoBaseController@modalEditar')->name('produto.tecido_base.modal.editar');
			Route::post('/deletar', 'ProdutoTecidoBaseController@modalDeletar')->name('produto.tecido_base.modal.deletar');
			Route::post('/buscar', 'ProdutoTecidoBaseController@modalBuscar')->name('produto.tecido_base.modal.buscar');
		});
	});
	Route::prefix('tecido_estampado')->middleware(['auth'])->group(function(){
		Route::get('/', 'ProdutoTecidoEstampadoController@index')->name('produto.tecido_estampado.index');
		Route::post('/adicionar', 'ProdutoTecidoEstampadoController@adicionar')->name('produto.tecido_estampado.adicionar');
		Route::post('/filter', 'ProdutoTecidoEstampadoController@filter')->name('produto.tecido_estampado.filter');
		Route::post('/editar', 'ProdutoTecidoEstampadoController@editar')->name('produto.tecido_estampado.editar');
		Route::post('/deletar', 'ProdutoTecidoEstampadoController@deletar')->name('produto.tecido_estampado.deletar');
		Route::prefix('modal')->group(function(){
			Route::get('/adicionar', 'ProdutoTecidoEstampadoController@modalAdicionar')->name('produto.tecido_estampado.modal.adicionar');
			Route::post('/editar', 'ProdutoTecidoEstampadoController@modalEditar')->name('produto.tecido_estampado.modal.editar');
			Route::post('/deletar', 'ProdutoTecidoEstampadoController@modalDeletar')->name('produto.tecido_estampado.modal.deletar');
		});
	});

	Route::prefix('ajuste_estoque')->middleware(['auth'])->group(function(){
		Route::get('/', 'AjusteEstoqueController@index')->name('produto.estoque.ajuste_estoque.index');
		Route::post('/filter', 'AjusteEstoqueController@filter')->name('produto.estoque.ajuste_estoque.filter');
		Route::prefix('modal')->group(function(){
			Route::post('/dialog', 'AjusteEstoqueController@dialog')->name('produto.estoque.ajuste_estoque.dialog');
			Route::post('/dialog_valor', 'AjusteEstoqueController@dialogValor')->name('produto.estoque.ajuste_estoque.dialog_valor');
			Route::post('/motivo_quantidade', 'AjusteEstoqueController@dialogMotivoQuantidade')->name('produto.estoque.ajuste_estoque.motivo_quantidade');
			Route::post('/motivo_valor', 'AjusteEstoqueController@dialogMotivoValor')->name('produto.estoque.ajuste_estoque.motivo_valor');
			Route::post('/motivo_rastreabilidade', 'AjusteEstoqueController@modalRastreabilidade')->name('produto.estoque.ajuste_estoque.motivo_rastreabilidade');
		});
		Route::prefix('motivo')->middleware(['auth'])->group(function(){
			Route::get('/','MotivoAjusteEstoqueController@index')->name('produto.ajuste_estoque.motivo.index');
			Route::post('/adicionar','MotivoAjusteEstoqueController@adicionar')->name('produto.ajuste_estoque.motivo.adicionar');
			Route::post('/filter','MotivoAjusteEstoqueController@filter')->name('produto.ajuste_estoque.motivo.filter');
			Route::post('/editar','MotivoAjusteEstoqueController@editar')->name('produto.ajuste_estoque.motivo.editar');
			Route::post('/deletar','MotivoAjusteEstoqueController@deletar')->name('produto.ajuste_estoque.motivo.deletar');
			Route::prefix('modal')->group(function(){
				Route::post('/adicionar', 'MotivoAjusteEstoqueController@modalAdicionar')->name('produto.ajuste_estoque.motivo.modal.adicionar');
				Route::post('/editar', 'MotivoAjusteEstoqueController@modalEditar')->name('produto.ajuste_estoque.motivo.modal.editar');
				Route::post('/deletar', 'MotivoAjusteEstoqueController@modalDeletar')->name('produto.ajuste_estoque.motivo.modal.deletar');
			});
		});
		Route::prefix('digitacao')->middleware(['auth'])->group(function(){
			Route::get('/', 'AjusteEstoqueController@indexDigitacao')->name('produto.ajuste_estoque.digitacao.index');
			Route::post('/filter', 'AjusteEstoqueController@filterDigitacao')->name('produto.ajuste_estoque.digitacao.filter');
			Route::post('/liberar_ajuste', 'AjusteEstoqueController@liberarAjuste')->name('produto.ajuste_estoque.digitacao.liberar_ajuste');
			Route::post('/filter_pecas', 'AjusteEstoqueController@filterPecas')->name('produto.ajuste_estoque.digitacao.filter_pecas');
			Route::post('/filter_produtos', 'AjusteEstoqueController@filterProdutos')->name('produto.ajuste_estoque.digitacao.filter_produtos');
			Route::post('/adicionar', 'AjusteEstoqueController@adicionar')->name('produto.ajuste_estoque.digitacao.adicionar');
			Route::prefix('modal')->middleware(['auth'])->group(function(){
				Route::get('/adicionar', 'AjusteEstoqueController@modalAdicionarDigitacao')->name('produto.ajuste_estoque.digitacao.modal.adicionar');
				Route::post('/pecas', 'AjusteEstoqueController@modalPecas')->name('produto.ajuste_estoque.digitacao.modal.pecas');
			});
		});
	});

	Route::post('/retorno_informacao_preco_producao', 'ProdutoController@retornaInformacaoPrecoProducao')->name('produto.retorna_informacoes_preco_producao');

	Route::post('/pecas_pedido', 'ProdutoController@pecasPedido')->name('produto.pecas_pedidos');

});

// Cliente Route
Route::prefix('ramais')->middleware(['auth'])->group(function () {
	Route::get('/', 'DiversosController@indexRamais')->name('ramais.index');
});

Route::prefix('upload_ramais')->middleware(['auth'])->group(function () {
	Route::get('/', 'UploadRamaisController@indexRamais')->name('upload_ramais.index');
	Route::post('/filtro', 'UploadRamaisController@filterUpload')->name('upload_ramais.filter');
});

Route::prefix('margem')->middleware(['auth'])->group(function () {
	Route::get('/', 'MargemController@index')->name('margem.index');
	Route::post("/formCadastro", 'MargemController@formCadastro')->name('margem.formCadastro');
	Route::post("/formEdit", 'MargemController@formEdit')->name('margem.formEdit');
	Route::post("/formDelete", 'MargemController@formDelete')->name('margem.formDelete');
	Route::post("/formCadastroAdd", 'MargemController@cadastro')->name('margem.cadastro');
	Route::post("/formCadastroEdit", 'MargemController@editar')->name('margem.editar');
	Route::post("/formCadastroDelete", 'MargemController@excluir')->name('margem.excluir');
	Route::post("/formCadastroFilter", 'MargemController@filter')->name('margem.filter');
});

Route::prefix('margem_prazo')->middleware(['auth'])->group(function (){
	Route::get('/', 'MargemPrazoController@index')->name('margem_prazo.index');
	Route::post("/formCadastro", 'MargemPrazoController@formCadastro')->name('margem_prazo.formCadastro');
	Route::post("/formEdit", 'MargemPrazoController@formEdit')->name('margem_prazo.formEdit');
	Route::post("/formDelete", 'MargemPrazoController@formDelete')->name('margem_prazo.formDelete');
	Route::post("/formCadastroAdd", 'MargemPrazoController@cadastro')->name('margem_prazo.cadastro');
	Route::post("/formCadastroEdit", 'MargemPrazoController@editar')->name('margem_prazo.editar');
	Route::post("/formCadastroDelete", 'MargemPrazoController@excluir')->name('margem_prazo.excluir');
	Route::post('/formCadastroFilter', 'MargemPrazoController@filter')->name('margem_prazo.filter');
});

Route::prefix('aliquota_preco')->middleware(['auth'])->group(function (){
	Route::get('/', 'AliquotaPrecoController@index')->name('aliquota_preco.index');
	Route::post('/aliquotaFilter', 'AliquotaPrecoController@filter')->name('aliquota_preco.filter');
	Route::post("/formCadastro", 'AliquotaPrecoController@formCadastro')->name('aliquota_preco.formCadastro');
	Route::post("/formEdit", 'AliquotaPrecoController@formEdit')->name('aliquota_preco.formEdit');
	Route::post("/formCadastroAdd", 'AliquotaPrecoController@cadastro')->name('aliquota_preco.cadastro');
	Route::post("/formCadastroEdit", 'AliquotaPrecoController@editar')->name('aliquota_preco.editar');
});

Route::prefix('listagem_precos')->middleware(['auth'])->group(function(){
	Route::get('/', 'ListagemDePrecosController@index')->name('listagem_precos.index');
	Route::post('/filtro', 'ListagemDePrecosController@filter')->name('listagem_precos.filter');
	Route::post('/estado', 'ListagemDePrecosController@selectAliquotas')->name('listagem_precos.aliquotas');
	Route::post('/export', 'ListagemDePrecosController@export')->name('listagem_precos.export');
	Route::post('/download', 'ListagemDePrecosController@linkLista')->name('listagem_precos.download');
	Route::prefix('antiga')->group(function(){
		Route::get('/', 'ListagemDePrecosAntigaController@index')->name('lista_preco.antiga.index');
	});

});

Route::prefix('nova_lista_precos')->middleware(['auth'])->group(function(){
	Route::get('/', 'NovaListaPrecosController@index')->name('nova_lista_precos.index');
	Route::post('/filtro', 'NovaListaPrecosController@filter')->name('nova_lista_precos.filter');
	Route::post('/estado', 'NovaListaPrecosController@selectAliquotas')->name('nova_lista_precos.aliquotas');
	Route::post('/export', 'NovaListaPrecosController@export')->name('nova_lista_precos.export');
	Route::post('/download', 'NovaListaPrecosController@exportarListas')->name('nova_lista_precos.download');
	Route::prefix('antiga')->group(function(){
		Route::get('/', 'ListagemDePrecosAntigaController@index')->name('lista_preco.antiga.index');
	});

});

Route::prefix('carteira_pedidos')->middleware(['auth'])->group(function(){
	Route::get('/', 'CarteiraPedidosController@index')->name('carteira_pedidos.index');
	Route::post('/filtro', 'CarteiraPedidosController@filter')->name('carteira_pedidos.filter');
});

Route::prefix('pedidos_orcamentos')->middleware(['auth'])->group(function(){
	Route::get('/relacao','PedidoVendaController@checkaRelacionamento')->name('pedido_orcamentos.relacionamento');
	Route::get('/', 'PedidoVendaController@index')->name('pedidos_orcamentos.index');
	Route::post('/filtro', 'PedidoVendaController@filtro')->name('pedidos_orcamentos.filtro');
	Route::post('/itens', 'PedidoVendaController@itensPedidos')->name('pedidos_orcamentos.itens');
	Route::post('/pedidos_abertos', 'PedidoVendaController@pedidosAbertos')->name('pedidos_orcamentos.pedidos_abertos');
	Route::post('/mostrar', 'PedidoVendaController@showPedidoCompleto')->name('pedidos_orcamentos.show');
	Route::post('/pedidos', 'PedidoVendaController@pedidos')->name('pedidos_orcamentos.pedidos');
	Route::post('/cancelar', 'PedidosVendaNasajonController@cancelaPedidoNasajon')->name('pedidos_orcamentos.cancelar');
	Route::post('/alterar', 'PedidosVendaNasajonController@editarTransportadora')->name('pedidos_orcamentos.alterar_transportadora');
	
	Route::prefix('modal')->group(function(){
		Route::post('/', 'PedidosVendaNasajonController@detalhesModal')->name('pedidos_orcamentos.modal');
		Route::post('/editar', 'PedidosVendaNasajonController@modalEditarTransportadora')->name('pedidos_orcamentos.modal.editar_transportadora');
	});
});

Route::prefix('parametros_aprovacao')->middleware(['auth'])->group(function(){
	Route::get('/', 'ParametrosAprovacaoController@index')->name('parametros_aprovacao.index');
	Route::post('/filtro', 'ParametrosAprovacaoController@filter')->name('parametros_aprovacao.filter');
	Route::post('/formAdd', 'ParametrosAprovacaoController@formCreate')->name('parametros_aprovacao.formCreate');
	Route::post('/formEdit', 'ParametrosAprovacaoController@formEdit')->name('parametros_aprovacao.formEdit');
	Route::post('/formDelete', 'ParametrosAprovacaoController@formDelete')->name('parametros_aprovacao.formDelete');
	Route::post('/add', 'ParametrosAprovacaoController@create')->name('parametros_aprovacao.create');
	Route::post('/edit', 'ParametrosAprovacaoController@edit')->name('parametros_aprovacao.edit');
	Route::post('/delete', 'ParametrosAprovacaoController@delete')->name('parametros_aprovacao.delete');
	
});

Route::prefix('aprovacao_pedido')->middleware(['auth'])->group(function(){
	Route::get('/', 'AprovacaoDePedidoController@index')->name('aprovacao_pedido.index');
	Route::post('/filtro', 'AprovacaoDePedidoController@filter')->name('aprovacao_pedido.filtro');
	Route::post('/aprova_pedido', 'AprovacaoDePedidoController@aprovaPedido')->name('aprovacao_pedido.aprova_pedido');
	Route::post('/recusa', 'AprovacaoDePedidoController@recusarPedido')->name('aprovacao_pedido.recusa_pedido');
	Route::post('/retaguarda', 'AprovacaoDePedidoController@retaguardaPedido')->name('aprovacao_pedido.retaguarda');
	Route::prefix('modal')->group(function(){
		Route::post('/', 'AprovacaoDePedidoController@modal')->name('parametros_aprovacao.modal');
		Route::post('/recusa_pedido', 'AprovacaoDePedidoController@recusarPedidoModal')->name('aprovacao_pedido.modal.recusa_pedido');
	});
});

Route::prefix('aprovacao_pedido_futuro')->middleware(['auth'])->group(function(){
	Route::get('/', 'AprovacaoPedidoFuturoController@index')->name('aprovacao_pedido_futuro.index');
	Route::post('/filtro', 'AprovacaoPedidoFuturoController@filter')->name('aprovacao_pedido_futuro.filtro');
	Route::post('/deletar', 'AprovacaoPedidoFuturoController@deletar')->name('aprovacao_pedido_futuro.deletar');
	Route::post('/aprovar', 'AprovacaoPedidoFuturoController@aprovaPedidoFuturo')->name('aprovacao_pedido_futuro.aprovar');
	Route::post('/reprovar', 'AprovacaoPedidoFuturoController@reprovarPedidoFuturo')->name('aprovacao_pedido_futuro.reprovar');
	Route::prefix('modal')->group(function(){
		Route::post('/deletar', 'AprovacaoPedidoFuturoController@modalDeletar')->name('aprovacao_pedido_futuro.modal.deletar');
		Route::post('/recusar', 'AprovacaoPedidoFuturoController@modalRecusa')->name('aprovacao_pedido_futuro.modal.recusa_pedido');
	});
});

Route::get('/pedido/{hash}', 'PedidoPortalController@detalhesDeslogado')->name('pedido_portal.detalhesDeslogado');

Route::prefix('pedido_portal')->middleware(['auth'])->group(function(){
	Route::get('/', 'PedidoPortalController@index')->name('pedido_portal.index');
	Route::post('/filter', 'PedidoPortalController@filter')->name('pedido_portal.filter');

	Route::post('/excluir', 'PedidoPortalController@excluir')->name('pedido_portal.excluir');
	Route::post('/ultimos_dados', 'PedidoPortalController@recuperaUltimosDadosCliente')->name('pedido_portal.ultimos_dados');
	Route::post('/desconto', 'PedidoPortalController@mudarDesconto')->name('pedido_portal.mudar_desconto');
	Route::post('/totalizadores', 'PedidoPortalController@totalizadores')->name('pedido_portal.totalizadores');
	Route::post('/corrigir_status_pedido_pago', 'PedidoPortalController@corrigirStatusPedidoPago')->name('pedido_portal.corrigir_status_pedido_pago');
	
	Route::prefix('modal')->group(function(){
		Route::post('/digitacao', 'PedidoPortalController@formAdd')->name('pedido_portal.formAdd');
		Route::post('/excluir', 'PedidoPortalController@modalExcluir')->name('pedido_portal.modal_excluir');
		Route::post('/desconto', 'PedidoPortalController@modalDesconto')->name('pedido_portal.modal_desconto');

		Route::post('/edicao_futuro', 'PedidoPortalController@modalEdicaoFuturo')->name('pedido_portal.modal.edicao_futuro');
	});
	Route::prefix('salvar')->group(function(){
		Route::post('/', 'PedidoPortalController@salvar')->name('pedido_portal.salvar');
		Route::post('/salvar_campos', 'PedidoPortalController@salvarCampos')->name('pedido_portal.salvar_campos');
		Route::post('/processar', 'PedidoPortalController@processarPedido')->name('pedido_portal.salvarPedido');

		Route::post('/salvar_edicao_futuro', 'PedidoPortalController@salvarFuturo')->name('pedido_portal.salvar.edicao_futuro');
	});

	Route::post('/recalcular_todos_itens', 'PedidoPortalController@recalcularTodosItensPedido')->name('pedido_portal.recalcular_todos_itens');
	Route::post('/apagar_todos_itens', 'PedidoPortalController@apagarTodosItensPedido')->name('pedido_portal.apagar_todos_itens');
	Route::prefix('item')->group(function(){
		Route::post('/adicionarProduto', 'PedidoItemPortalController@adicionarProduto')->name('pedido_portal.item.adicionar');
		Route::post('/editarProduto', 'PedidoItemPortalController@editaProduto')->name('pedido_portal.item.editar');
		Route::post('/excluirProduto', 'PedidoItemPortalController@excluiProduto')->name('pedido_portal.item.excluir');
		Route::post('/retornar_dados', 'PedidoItemPortalController@returnDados')->name('pedido_portal.item.returnDados');
	});

	Route::prefix('motivo_recusa')->group(function () {
		Route::get('/', 'MotivoRecusaPedidoController@index')->name('pedido.motivo_recusa.index');
		Route::post('/filtrar', 'MotivoRecusaPedidoController@filtrar')->name('pedido.motivo_recusa.filtrar');
		Route::prefix('modal')->group(function () {
			Route::post('/cadastro', 'MotivoRecusaPedidoController@cadastro')->name('pedido.motivo_recusa.cadastro');
			Route::post('/editcao', 'MotivoRecusaPedidoController@editcao')->name('pedido.motivo_recusa.editcao');
			Route::post('/delecao', 'MotivoRecusaPedidoController@delecao')->name('pedido.motivo_recusa.delecao');
		});
		Route::prefix('salvar')->group(function () {
			Route::post('/cadastrar', 'MotivoRecusaPedidoController@cadastrar')->name('pedido.motivo_recusa.cadastrar');
			Route::post('/editar', 'MotivoRecusaPedidoController@editar')->name('pedido.motivo_recusa.editar');
			Route::post('/deletar', 'MotivoRecusaPedidoController@deletar')->name('pedido.motivo_recusa.deletar');
		});
	});

	Route::prefix('detalhes')->group(function () {
		Route::post('/', 'PedidoPortalController@detalhes')->name('pedido_portal.detalhes');
		Route::post('/futuro', 'PedidoPortalController@detalhesProgramado')->name('pedido_portal.detalhes_futuro');
		Route::post('/imprimir', 'PedidoPortalController@imprimir')->name('pedido_portal.detalhes.imprimir');
	});

	Route::prefix('duplicar')->group(function () {
		Route::post('/', 'PedidoPortalController@duplicar')->name('pedido_portal.duplicar');
		Route::post('/salvar', 'PedidoPortalController@duplicarSalvar')->name('pedido_portal.duplicar.salvar');
	});
	
	Route::get('/gerar_proposta_link', 'PedidoPortalController@geracaoPropostaLink')->name('pedido_portal.gerar_proposta_link');
});

Route::prefix('vencimentos')->middleware(['auth'])->group(function(){
	Route::prefix('modal')->group(function () {
		Route::post('/', 'VencimentosController@modalPrazo')->name('vencimentos.modal_prazo');
	});
	Route::post('/filtro', 'VencimentosController@filtro')->name('vencimentos.filtro');
	Route::post('/codvctParaDescricao', 'VencimentosController@codvctParaDescricao')->name('vencimentos.cod_para_descr');
});

Route::prefix('clientes_novos')->middleware(['auth'])->group(function(){
	Route::get('/', 'ClienteNovoController@index')->name('cliente_novo.index');
	Route::post('/validarCPFCNPJ','ClienteNovoController@validarCPF_CNPJ')->name('cliente_novo.validarCPFCNPJ');
	Route::get('/cadastrar', 'ClienteNovoController@create')->name('cliente_novo.create');
	Route::post('/cadastrar', 'ClienteNovoController@store')->name('cliente_novo.store');
	Route::post('/view', 'ClienteNovoController@show')->name('cliente_novo.view');
	Route::post('/filtro', 'ClienteNovoController@filter')->name('cliente_novo.filter');
	Route::post('/excluir', 'ClienteNovoController@delete')->name('cliente_novo.delete');
	Route::post('/remover', 'ClienteNovoController@destroy')->name('cliente_novo.destroy');
	Route::post('/editar', 'ClienteNovoController@edit')->name('cliente_novo.edit');
	Route::post('/atualizar','ClienteNovoController@update')->name('cliente_novo.update');
	Route::post('/check_cliente', 'ClienteNovoController@checkClienteExiste')->name('cliente_novo.check_exist');
	Route::prefix('aprovacao')->group(function(){
		Route::get('/', 'ClienteNovoController@indexAprovacao')->name('cliente_novo.aprovacao');
		Route::post('/filtro', 'ClienteNovoController@filterAprovacao')->name('cliente_novo.aprovacao.filter');
		Route::post('/serasa', 'ClienteNovoController@indexserasa')->name('cliente_novo.aprovacao.serasa');
		Route::post('/confirmacao/aprovado', 'ClienteNovoController@confirmacaoAprovar')->name('cliente_novo.aprovacao.confirmacao.aprovado');
		Route::post('/confirmacao/reprovado', 'ClienteNovoController@confirmacaoReprovar')->name('cliente_novo.aprovacao.confirmacao.reprovado');
		Route::post('/aprovado', 'ClienteNovoController@aprovarCadastro')->name('cliente_novo.aprovacao.aprovado');
		Route::post('/reprovado', 'ClienteNovoController@reprovarCadastro')->name('cliente_novo.aprovacao.reprovado');
	});
});

Route::prefix('transportador')->middleware(['auth'])->group(function(){
	Route::get('/', 'TransportadorController@index')->name('transportador.index');
	Route::post('/view', 'TransportadorController@view')->name('transportador.view');
	Route::post('/dialog', 'TransportadorController@indexDialog')->name('transportador.index.dialog');
	Route::post('/filter', 'TransportadorController@filter')->name('transportador.filtro');
	Route::post('/codigoParaNome', 'TransportadorController@codigoParaNome')->name('transportador.codigo_para_nome');
	Route::post('/nomeParaCodigo', 'TransportadorController@nomeParaCodigo')->name('transportador.nome_para_codigo');
	Route::post('/autocomplete', 'TransportadorController@autocomplete')->name('transportador.autocomplete');
	Route::post('/dados_transportador', 'TransportadorController@dadosTransportador')->name('transportador.dados_transportador');
});

Route::prefix('busca_cep')->middleware(['auth'])->group(function(){
	Route::post("/", 'BuscaDeCepController@busca')->name("busca_cep");
	Route::post("/cidade", 'BuscaDeCepController@buscaCidadePorEstado')->name("busca_cep.cidade");
});

Route::prefix('condicoes_pagamento_web')->middleware(['auth'])->group(function(){
	Route::get('/', 'CondicoesPagamentoWebController@index')->name('condicoes_pagamento_web.index');
	Route::post('/dialog', 'CondicoesPagamentoWebController@dialog')->name('condicoes_pagamento_web.dialog');
	Route::post('/filter', 'CondicoesPagamentoWebController@filtro')->name('condicoes_pagamento_web.filtro');
	Route::prefix('modal')->group(function () {
		Route::post('/criar', 'CondicoesPagamentoWebController@formNovaCondicao')->name('condicoes_pagamento_web.form_nova_condicao');
		Route::post('/editar','CondicoesPagamentoWebController@formEditaCondicao')->name('condicoes_pagamento_web.editar_condicao');
		Route::post('/excluir','CondicoesPagamentoWebController@formDeletaCondicao')->name('condicoes_pagamento_web.excluir_condicao');
	});
	Route::post('/autocompleteParcelas','CondicoesPagamentoWebController@autoCompleteParcelas')->name('condicoes_pagamento_web.retorna_vencimentos.autocompleteParcelas');
	Route::post('/retornaVencimentos','CondicoesPagamentoWebController@retornaVencimentos')->name('condicoes_pagamento_web.retorna_vencimentos');
	Route::post('/salvar','CondicoesPagamentoWebController@novaCondicao')->name('condicoes_pagamento_web.nova_condicao');
	Route::post('/editar','CondicoesPagamentoWebController@editaCondicao')->name('condicoes_pagamento_web.edita');
	Route::post('/excluir','CondicoesPagamentoWebController@excluiCondicao')->name('condicoes_pagamento_web.excluir');
	Route::post('/autocomplete', 'CondicoesPagamentoWebController@autoComplete')->name('condicoes_pagamento_web.autocomplete');
	Route::post('/descrparaid', 'CondicoesPagamentoWebController@descrParaId')->name('condicoes_pagamento_web.descr_para_id');

});

Route::prefix('vendedor')->middleware(['auth'])->group(function(){
	Route::post('/dialog', 'VendedorController@indexDialog')->name('vendedor.index.dialog');
	Route::post('/filter', 'VendedorController@filter')->name('vendedor.filtro');
	Route::post('/codigoParaNome', 'VendedorController@codigoParaNome')->name('vendedor.codigo_para_nome');
});

Route::prefix('historico_vendas')->middleware(['auth'])->group(function(){
	Route::get('/', 'HistoricoDeVendasController@index')->name('historico_vendas.index');
	Route::post('/filter', 'HistoricoDeVendasController@filter')->name('historico_vendas.filtro');
	Route::post('/dialog', 'HistoricoDeVendasController@dialog')->name('historico_vendas.dialog');
});

Route::prefix('titulos_abertos')->middleware(['auth'])->group(function(){
	Route::get('/', 'TitulosAbertosController@index')->name('titulos.index');
	Route::post('/filter', 'TitulosAbertosController@filter')->name('titulos.filtro');
});


Route::prefix('comissao')->middleware(['auth'])->group(function(){
	Route::get('/', 'ComissaoController@index')->name('comissao.index');
	Route::post('/filter', 'ComissaoController@filter')->name('comissao.filtro');
	Route::post('/dialog', 'ComissaoController@dialog')->name('comissao.dialog');
});

Route::prefix('faturamento')->middleware(['auth'])->group(function(){
	Route::get('/', 'FaturamentoController@index')->name('faturamento.index');
	Route::post('/filter', 'FaturamentoController@filter')->name('faturamento.filtro');
	Route::prefix('/dialog')->group(function(){
		Route::post('/dia', 'FaturamentoController@dialogDia')->name('faturamento.dialog.dia');
		Route::post('/mes', 'FaturamentoController@dialogMes')->name('faturamento.dialog.mes');
		Route::post('/ano', 'FaturamentoController@dialogAno')->name('faturamento.dialog.ano');
		Route::post('/ano', 'FaturamentoController@dialogAnoAnalise')->name('faturamento.dialog.ano_analise');
		Route::post('/dia_fechamento_caixa', 'FaturamentoController@dialogDiaFechamentoCaixa')->name('faturamento.dialog.dia_fechamento_caixa');
		Route::post('/mes_fechamento_caixa', 'FaturamentoController@dialogMesFechamentoCaixa')->name('faturamento.dialog.mes_fechamento_caixa');
		Route::post('/ano_fechamento_caixa', 'FaturamentoController@dialogAnoFechamentoCaixa')->name('faturamento.dialog.ano_fechamento_caixa');
	});
});

Route::prefix('natureza_operacao')->middleware(['auth'])->group(function(){
	Route::get('/', 'NaturezaDeOperacaoController@index')->name('natureza_operacao.index');
	Route::post('/filtro', 'NaturezaDeOperacaoController@filter')->name('natureza_operacao.filtro');
	Route::post('/adicionar', 'NaturezaDeOperacaoController@adicionarNatureza')->name('natureza_operacao.add');
	Route::post('/editar', 'NaturezaDeOperacaoController@editarNatureza')->name('natureza_operacao.edit');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'NaturezaDeOperacaoController@adicionarNaturezaModal')->name('natureza_operacao.modal.add');
		Route::post('/editar', 'NaturezaDeOperacaoController@editarNaturezaModal')->name('natureza_operacao.modal.edit');
	});
	
});

Route::prefix('ficha_tecnica_produto')->middleware(['auth'])->group(function(){
	Route::get('/', 'FichaTecnicaProdutoController@index')->name('ficha_tecnica.index');
	Route::post('/filtro', 'FichaTecnicaProdutoController@filter')->name('ficha_tecnica.filtro');
	Route::post('/cadastrar', 'FichaTecnicaProdutoController@cadastraProduto')->name('ficha_tecnica.cadastrar');
	Route::post('/editar', 'FichaTecnicaProdutoController@editaProduto')->name('ficha_tecnica.editar');
	Route::post('/excluir', 'FichaTecnicaProdutoController@excluiProduto')->name('ficha_tecnica.excluir');
	Route::prefix('modal')->group(function(){
		Route::post('/cadastra', 'FichaTecnicaProdutoController@cadastraProdutoModal')->name('ficha_tecnica.modal.cadastra');
		Route::post('/edita', 'FichaTecnicaProdutoController@editaProdutoModal')->name('ficha_tecnica.modal.edita');
		Route::post('/exclui', 'FichaTecnicaProdutoController@excluiProdutoModal')->name('ficha_tecnica.modal.exclui');
		Route::post('/view', 'FichaTecnicaProdutoController@viewModal')->name('ficha_tecnica.modal.view');
		Route::post('/view_sem_codigo', 'FichaTecnicaProdutoController@viewSemCodigoModal')->name('ficha_tecnica.modal.view_sem_codigo');
		Route::post('/view_sem_codigo_tecido', 'FichaTecnicaProdutoController@viewSemCodigoTecidoModal')->name('ficha_tecnica.modal.view_sem_codigo_tecido');
	});

});

Route::prefix('produtos_nasajon')->middleware(['auth'])->group(function(){
	Route::post('/autocomplete', 'ProdutoNasajonController@autocomplete')->name('produtos_nasajon.autocomplete');
	Route::post('/filter', 'ProdutoNasajonController@filter')->name('produtos_nasajon.modal.filter');
	Route::prefix('modal')->group(function(){
		Route::post('/busca', 'ProdutoNasajonController@modalBusca')->name('produtos_nasajon.modal.busca');
	});
});

Route::prefix('parametros_pedido')->middleware(['auth'])->group(function(){
	Route::get('/', 'ParametrosPedidoController@index')->name('parametros_pedido.index');
	Route::post('/filtro', 'ParametrosPedidoController@filter')->name('parametros_pedido.filter');
	Route::prefix('modal')->group(function () {
		Route::post('/edita', 'ParametrosPedidoController@modal')->name('parametros_pedido.modal');
	});
	Route::post('/salvar', 'ParametrosPedidoController@salvar')->name('parametros_pedido.salvar');
});

Route::prefix('compras')->middleware(['auth'])->group(function(){
	Route::get('/', 'AnaliseDeComprasController@index')->name('compras_analise.index');
	Route::post('/filtro', 'AnaliseDeComprasController@filter')->name('compras_analise.filter');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/artigos', 'AnaliseDeComprasController@modalArtigos')->name('compras_analise.modal.artigo');
		Route::post('/comprado', 'AnaliseDeComprasController@modalComprados')->name('compras_analise.modal.comprado');
		Route::post('/vendido', 'AnaliseDeComprasController@modalVendido')->name('compras_analise.modal.vendido');
		Route::post('/receber', 'AnaliseDeComprasController@modalReceber')->name('compras_analise.modal.receber');
		Route::post('/compra_venda', 'AnaliseDeComprasController@filter')->name('compras_analise.modal.compra_venda');
		Route::post('/a_receber', 'AnaliseDeComprasController@filter')->name('compras_analise.modal.areceber');
		Route::post('/analise_mes', 'AnaliseDeComprasController@modalAnaliseMes')->name('compras_analise.modal.analise.mes');
		Route::post('/analise_compras', 'AnaliseDeComprasController@modalAnaliseMesAberturaCompras')->name('compras_analise.modal.analise.compras');
		Route::post('/analise_vendas', 'AnaliseDeComprasController@modalAnaliseMesAberturaVendas')->name('compras_analise.modal.analise.vendas');
		Route::post('/analise_mes_mes_remessas', 'AnaliseDeComprasController@modalAnaliseMesAberturaRemessas')->name('compras_analise.modal.analise.analise_mes_mes_remessas');
		Route::post('/analise_produto', 'AnaliseDeComprasController@modalAnaliseMesAberturaProdutos')->name('compras_analise.modal.analise.produto');
        Route::post('/analise_remessas', 'AnaliseDeComprasController@modalAnaliseComprasRemessas')->name('compras_analise.modal.analise.remessas');
	});
});

Route::prefix('entrada_pedido')->middleware(['auth'])->group(function(){
	Route::get('/', 'EntradaPedidoController@index')->name('entrada_pedido.index');
	Route::post('/filtro', 'EntradaPedidoController@filtro')->name('entrada_pedido.filtro');
	Route::post('/lista', 'EntradaPedidoController@todosPedidosAcumulados')->name('entrada_pedido.lista');
	Route::post('/resetaBusca', 'EntradaPedidoController@resetaBusca')->name('entrada_pedido.reseta_busca');
});

Route::prefix('analise_pedidos')->middleware(['auth'])->group(function () {
	Route::get('/', 'AnalisePeidosController@index')->name('analise_pedidos.index');
	Route::post('/filtro', 'AnalisePeidosController@filtro')->name('analise_pedidos.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/abertura', 'AnalisePeidosController@modalAbertura')->name('analise_pedidos.modal.abertura');
	});
});

Route::prefix('inventario')->middleware(['auth'])->group(function () {
	Route::get('/', 'InventarioProdutoController@index')->name('inventario.index');
	Route::post('/filtro', 'InventarioProdutoController@filtro')->name('inventario.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/contagem', 'InventarioProdutoController@modalContagem')->name('inventario.modal.contagem');
		Route::post('/produto', 'InventarioProdutoController@modalProduto')->name('inventario.modal.produto');
		Route::post('/diferenca', 'InventarioProdutoController@modalDiferenca')->name('inventario.modal.diferenca');

		Route::post('/nao_inventariado', 'InventarioProdutoController@modalNaoInventariado')->name('inventario.modal.nao_inventariado');
	});
	Route::post('excluir_produto', 'InventarioProdutoController@excluirPecaProduto')->name('inventario.excluir_produto');
	Route::post('excluirproduto', 'InventarioProdutoController@excluirProduto')->name('inventario.excluirproduto');
	Route::post('/aplicar_estoque', 'InventarioProdutoController@aplicarEstoque')->name('inventario.aplicar_estoque');
});

Route::prefix('configuracao_email')->middleware(['auth'])->group(function(){
	Route::get('/', 'EmailController@index')->name('email.index');
	Route::post('/filtro', 'EmailController@filter')->name('email.filtro');
	Route::prefix('modal')->group(function(){
		Route::post('/editar', 'EmailController@edita')->name('email.edita');
	});
	Route::prefix('salvar')->group(function(){
		Route::post('/editar', 'EmailController@editar')->name('email.editar');
	});
});

Route::prefix('regras_separacao')->middleware(['auth'])->group(function(){
	Route::get('/', 'RegrasSeparacaoController@index')->name('regras_separacao.index');
	Route::post('/filtro', 'RegrasSeparacaoController@filter')->name('regras_separacao.filtro');
	Route::post('/adicionar', 'RegrasSeparacaoController@adicionar')->name('regras_separacao.adicionar');
	Route::post('/editar', 'RegrasSeparacaoController@editar')->name('regras_separacao.editar');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'RegrasSeparacaoController@modal_adicionar')->name('regras_separacao.modal.adicionar');
		Route::post('/editar', 'RegrasSeparacaoController@modal_editar')->name('regras_separacao.modal.editar');
	});
});


Route::prefix('monitoracao/separacao')->middleware(['auth'])->group(function(){
	Route::get('/', 'PedidosMonitoradoController@indexSeparacao')->name('monitoracao.index');
	Route::post('/filtro', 'PedidosMonitoradoController@filterSeparacao')->name('monitoracao.filtro');
	Route::prefix('modal')->group(function(){
		Route::post('/abertura', 'PedidosMonitoradoController@modalAbetrura')->name('monitoracao.modal.abertura');
		Route::post('/abertura_separacao', 'PedidosMonitoradoController@modalAbetruraSeparacao')->name('monitoracao.modal.abertura_separacao');
	});
});

Route::prefix('cadastro_produtos_precos')->middleware(['auth'])->group(function(){
	Route::get('/', 'CadastroProdutosPrecosController@index')->name('cadastro_produtos_preco.index');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'CadastroProdutosPrecosController@modal_adicionar')->name('cadastro_produtos_preco.modal.adicionar');
		Route::post('/editar', 'CadastroProdutosPrecosController@modal_editar')->name('cadastro_produtos_preco.modal.editar');
	});

});

Route::prefix('estabelecimento_cidade_fob')->middleware(['auth'])->group(function(){
	Route::get('/', 'EstabelecimentoCidadeFobController@index')->name('estabelecimento_cidade_fob.index');
	Route::post('/filtrar', 'EstabelecimentoCidadeFobController@filter')->name('estabelecimento_cidade_fob.filter');
	Route::post('/adicionar', 'EstabelecimentoCidadeFobController@adicionar')->name('estabelecimento_cidade_fob.adicionar');
	Route::post('/editar', 'EstabelecimentoCidadeFobController@editar')->name('estabelecimento_cidade_fob.editar');
	Route::post('/excluir', 'EstabelecimentoCidadeFobController@excluir')->name('estabelecimento_cidade_fob.excluir');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'EstabelecimentoCidadeFobController@modalAdicionar')->name('estabelecimento_cidade_fob.modal.adicionar');
		Route::post('/editar', 'EstabelecimentoCidadeFobController@modalEditar')->name('estabelecimento_cidade_fob.modal.editar');
		Route::post('/deletar', 'EstabelecimentoCidadeFobController@modalDeletar')->name('estabelecimento_cidade_fob.modal.deletar');
	});

});

Route::prefix('entrada_de_caixa')->middleware(['auth'])->group(function(){
	Route::get('/', 'EntradaDeCaixaController@index')->name('entrada_de_caixa.index');
	Route::post('/filtrar', 'EntradaDeCaixaController@filter')->name('entrada_de_caixa.filter');
	Route::post('/adicionar', 'EntradaDeCaixaController@adicionar')->name('entrada_de_caixa.adicionar');
	Route::post('/editar', 'EntradaDeCaixaController@editar')->name('entrada_de_caixa.editar');
	Route::post('/excluir', 'EntradaDeCaixaController@excluir')->name('entrada_de_caixa.excluir');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'EntradaDeCaixaController@modalAdicionar')->name('entrada_de_caixa.modal.adicionar');
		Route::post('/editar', 'EntradaDeCaixaController@modalEditar')->name('entrada_de_caixa.modal.editar');
		Route::post('/deletar', 'EntradaDeCaixaController@modalDeletar')->name('entrada_de_caixa.modal.deletar');
	});

});

Route::prefix('fluxo_caixa')->middleware(['auth'])->group(function(){
	Route::get('/', 'FluxoDeCaixaController@index')->name('fluxo_caixa.index');
	Route::post('/filtrar', 'FluxoDeCaixaController@filter')->name('fluxo_caixa.filter');
	Route::post('/exportar', 'FluxoDeCaixaController@exportarExcel')->name('fluxo_caixa.exportar');
	Route::prefix('modal')->group(function(){
		Route::post('/dados', 'FluxoDeCaixaController@modalDados')->name('fluxo_caixa.modal.dados');
		Route::post('/previsao', 'FluxoDeCaixaController@modalPrevisao')->name('fluxo_caixa.modal.previsao');
		Route::post('/previsao_fornecedor', 'FluxoDeCaixaController@modalPrevisaoTituloFornecedor')->name('fluxo_caixa.modal.previsao_fornecedor');
	});
});

Route::prefix('analise_de_entrada')->middleware(['auth'])->group(function(){
	Route::get('/', 'AnaliseDeParcelasMesController@index')->name('analise_de_entrada.index');
	Route::post('/filtrar', 'AnaliseDeParcelasMesController@filter')->name('analise_de_entrada.filter');
});

Route::prefix('grupo_empresarial')->middleware(['auth'])->group(function(){
	Route::get('/', 'GrupoEmpresarialController@index')->name('grupo_empresarial.index');
	Route::post('/filtrar', 'GrupoEmpresarialController@filter')->name('grupo_empresarial.filter');
	Route::post('/adicionar', 'GrupoEmpresarialController@adicionar')->name('grupo_empresarial.adicionar');
	Route::post('/editar', 'GrupoEmpresarialController@editar')->name('grupo_empresarial.editar');
	Route::post('/excluir', 'GrupoEmpresarialController@excluir')->name('grupo_empresarial.excluir');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'GrupoEmpresarialController@modalAdicionar')->name('grupo_empresarial.modal.adicionar');
		Route::post('/editar', 'GrupoEmpresarialController@modalEditar')->name('grupo_empresarial.modal.editar');
		Route::post('/deletar', 'GrupoEmpresarialController@modalDeletar')->name('grupo_empresarial.modal.deletar');
	});
});

Route::prefix('revisao_comissao')->middleware(['auth'])->group(function(){
	Route::get('/', 'RevisaoComissaoController@index')->name('revisao_comissao.index');
	Route::post('/filtrar', 'RevisaoComissaoController@filter')->name('revisao_comissao.pesquisa');
	Route::post('/aplicar', 'RevisaoComissaoController@aplicarNovaComissao')->name('revisao_comissao.aplicar');
	Route::post('/aplicar/vendedor', 'RevisaoComissaoController@aplicarNovoVendedor')->name('revisao_comissao.vendedor.aplicar');
	Route::post('/aplicar/nasajon', 'RevisaoComissaoController@modificarComissaoNotaNasajon')->name('revisao_comissao.nasajon.aplicar');
	Route::prefix('modal')->group(function(){
		Route::post('/pedido', 'RevisaoComissaoController@retornarPedido')->name('revisao_comissao.retorna_pesquisa');
		Route::post('/comissao', 'RevisaoComissaoController@retornaComissao')->name('revisao_comissao.retorna_comissao');
	});
});

Route::prefix('exportacao_romaneio')->middleware(['auth'])->group(function(){
	Route::get('/', 'ExportacaoRomaneioController@index')->name('exportacao_romaneio.index');
	Route::post('/exportar', 'ExportacaoRomaneioController@exportar')->name('exportacao_romaneio.exportar');
	Route::post('/exportar_etq', 'ExportacaoRomaneioController@exportarEtq')->name('exportacao_romaneio.exportar_etq');
	Route::post('/validar', 'ExportacaoRomaneioController@validar')->name('exportacao_romaneio.validar');
});

Route::get('/pcp', function(){
	if(env('APP_HOST') !== 'producao' && !empty(env('APP_HOST'))){
		return redirect('http://portal.mntecidos.com.br:84/');
	}else{
		return redirect('http://portal.mntecidos.com.br:8080/');
	}
})->middleware(['auth'])->name('pcp.link');

Route::prefix('listaprecosprodutospromocionais')->middleware(['auth'])->group(function(){
	Route::get('/', 'ProdutoPromocionalController@index')->name('listaprecosprodutospromocionais.index');
	Route::post('/filtrar', 'ProdutoPromocionalController@filter')->name('listaprecosprodutospromocionais.filter');
	Route::post('/adicionar', 'ProdutoPromocionalController@adicionar')->name('listaprecosprodutospromocionais.adicionar');
	Route::post('/editar', 'ProdutoPromocionalController@editar')->name('listaprecosprodutospromocionais.editar');
	Route::post('/excluir', 'ProdutoPromocionalController@excluir')->name('listaprecosprodutospromocionais.excluir');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'ProdutoPromocionalController@modalAdicionar')->name('listaprecosprodutospromocionais.modal.adicionar');
		Route::post('/editar', 'ProdutoPromocionalController@modalEditar')->name('listaprecosprodutospromocionais.modal.editar');
		Route::post('/deletar', 'ProdutoPromocionalController@modalDeletar')->name('listaprecosprodutospromocionais.modal.deletar');
		Route::post('/validar', 'ProdutoPromocionalController@modalValidar')->name('listaprecosprodutospromocionais.modal.validar');
	});
});

Route::prefix('motivo_financeiro')->middleware(['auth'])->group(function(){
	Route::get('/', 'MotivoFinanceiroController@index')->name('motivo_financeiro.index');
	Route::post('/filtrar', 'MotivoFinanceiroController@filter')->name('motivo_financeiro.filter');
	Route::post('/adicionar', 'MotivoFinanceiroController@adicionar')->name('motivo_financeiro.adicionar');
	Route::post('/editar', 'MotivoFinanceiroController@editar')->name('motivo_financeiro.editar');
	Route::post('/deletar', 'MotivoFinanceiroController@deletar')->name('motivo_financeiro.deletar');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'MotivoFinanceiroController@modalAdicionar')->name('motivo_financeiro.modal.adicionar');
		Route::post('/editar', 'MotivoFinanceiroController@modalEditar')->name('motivo_financeiro.modal.editar');
		Route::post('/deletar', 'MotivoFinanceiroController@modalDeletar')->name('motivo_financeiro.modal.deletar');
	});
});

Route::prefix('atualizacao_precos')->middleware(['auth'])->group(function(){
	Route::get('/', 'ImportacaoPrecoController@index')->name('atualizacao_preco.index');
	Route::post('/filtro', 'ImportacaoPrecoController@filtro')->name('atualizacao_preco.filtro');
	Route::post('/atualiza', 'ImportacaoPrecoController@atualizaPreco')->name('atualizacao_preco.atualiza');
	Route::post('/atualiza_especificacoes', 'ImportacaoPrecoController@atualizaEspecificacoes')->name('atualizacao_preco.atualiza_especificacoes');

	Route::post('/busca_atualizacao_massa', 'ImportacaoPrecoController@buscaAtualizacaoEmMassa')->name('atualizacao_preco.busca_atualizacao_massa');
	Route::post('/salvar_atualizacao_massa', 'ImportacaoPrecoController@salvarAtualizacaoEmMassa')->name('atualizacao_preco.salvar_atualizacao_massa');

	Route::prefix('modal')->group(function(){
		Route::post('/editar', 'ImportacaoPrecoController@modal')->name('atualizacao_preco.modal.editar');
		Route::post('/produtos', 'ImportacaoPrecoController@modalProdutos')->name('atualizacao_preco.modal.produtos');
		Route::post('/ultimas_compras', 'ImportacaoPrecoController@modalUltimasCompras')->name('atualizacao_preco.modal.ultimas_compras');
		Route::post('/edicao_especificacoes', 'ImportacaoPrecoController@edicaoEspecificacaoMassa')->name('atualizacao_preco.modal.edicao_especificacoes');
		Route::post('/historico_ateracao', 'ImportacaoPrecoController@modalHistoricoAltecoes')->name('atualizacao_preco.modal.historico_alteracoes');

		Route::post('/atualizacao_massa', 'ImportacaoPrecoController@modalAtualizacaoEmMassa')->name('atualizacao_preco.modal.atualizacao_massa');
	});
});

Route::prefix('lancamento_deb_cred_vendedor')->middleware(['auth'])->group(function(){
	Route::get('/', 'LancamentoDebCredVendedorController@index')->name('lancamento_deb_cred_vendedor.index');
	Route::post('/filtrar', 'LancamentoDebCredVendedorController@filter')->name('lancamento_deb_cred_vendedor.filter');
	Route::post('/adicionar', 'LancamentoDebCredVendedorController@adicionar')->name('lancamento_deb_cred_vendedor.adicionar');
	Route::post('/editar', 'LancamentoDebCredVendedorController@editar')->name('lancamento_deb_cred_vendedor.editar');
	Route::post('/deletar', 'LancamentoDebCredVendedorController@deletar')->name('lancamento_deb_cred_vendedor.deletar');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'LancamentoDebCredVendedorController@modalAdicionar')->name('lancamento_deb_cred_vendedor.modal.adicionar');
		Route::post('/editar', 'LancamentoDebCredVendedorController@modalEditar')->name('lancamento_deb_cred_vendedor.modal.editar');
		Route::post('/deletar', 'LancamentoDebCredVendedorController@modalDeletar')->name('lancamento_deb_cred_vendedor.modal.deletar');
	});
});

Route::prefix('book_virtual')->middleware(['auth'])->group(function(){
	// Route::get('/', 'BookVirtualController@index')->name('book_virtual.index');
	Route::prefix('cadastrar')->group(function(){
		Route::get('/', 'BookVirtualController@cadastroIndex')->name('book_virtual.cadastro.index');
		Route::post('/filtrar', 'BookVirtualController@cadastroFilter')->name('book_virtual.cadastro.filter');
		Route::post('/filtrarchild', 'BookVirtualController@cadastroFilterChild')->name('book_virtual.cadastro.filter_child');
		Route::post('/adicionar', 'BookVirtualController@cadastroAdicionar')->name('book_virtual.cadastro.adicionar');
		Route::post('/editar', 'BookVirtualController@cadastroEditar')->name('book_virtual.cadastro.editar');
		Route::post('/deletar', 'BookVirtualController@cadastroDeletar')->name('book_virtual.cadastro.deletar');
		Route::prefix('modal')->group(function(){
			Route::get('/adicionar', 'BookVirtualController@cadastroModalAdicionar')->name('book_virtual.cadastro.modal.adicionar');
			Route::post('/editar', 'BookVirtualController@cadastroModalEditar')->name('book_virtual.cadastro.modal.editar');
			Route::post('/deletar', 'BookVirtualController@cadastroModalDeletar')->name('book_virtual.cadastro.modal.deletar');
		});
		Route::prefix('item')->group(function(){
			Route::post('/adicionar', 'BookVirtualController@adicionarProduto')->name('book_virtual.item.adicionar');
			Route::post('/deletar', 'BookVirtualController@deletarProduto')->name('book_virtual.item.deletar');
		});

		Route::prefix('desenho')->group(function(){
			Route::post('/salvar', 'BookVirtualController@salvarDesenho')->name('book_virtual.desenho.salvar');
			Route::post('/editar', 'BookVirtualController@editarDesenho')->name('book_virtual.desenho.editar');
			Route::post('/excluir', 'BookVirtualController@excluirDesenho')->name('book_virtual.desenho.excluir');

			Route::post('/lisos', 'BookVirtualController@salvarImagemTamanhoReal')->name('book_virtual.desenho.lisos');

			Route::prefix('modal')->group(function(){
				Route::post('/adicionar', 'BookVirtualController@novoDesenhoModal')->name('book_virtual.desenho.modal.adicionar');
				Route::post('/editar', 'BookVirtualController@editarDesenhoModal')->name('book_virtual.desenho.modal.editar');
			});
		});
		
		Route::prefix('instrucoes_lavagem')->group(function(){
			Route::post('/salvar', 'BookVirtualController@salvarInstrucoesLavagem')->name('book_virtual.instrucoes_lavagem.salvar');
		});
	});
});

Route::prefix('bookvirtual')->middleware(['auth'])->group(function(){
	Route::get('/{book_virtual}', 'ConsultaEstoquePrecoController@index')->name('consulta_preco_estoque.index');
	Route::get('/book/{book_virtual}', 'ConsultaEstoquePrecoController@book')->name('consulta_preco_estoque.book');
});

Route::prefix('consulta_ultimas_vendas_produto_cliente')->middleware(['auth'])->group(function(){
	Route::get('/', 'ConsultaUltimasVendasProdutoClienteController@index')->name('consulta_ultimas_vendas_produto_cliente.index');
	Route::post('/filter', 'ConsultaUltimasVendasProdutoClienteController@filter')->name('consulta_ultimas_vendas_produto_cliente.filter');
	Route::post('/dialog', 'ConsultaUltimasVendasProdutoClienteController@dialog')->name('consulta_ultimas_vendas_produto_cliente.dialog');
});
Route::prefix('pedidos_compras')->middleware(['auth'])->group(function(){
	Route::get('/', 'PedidosComprasNasajonController@pedidoAberto')->name('pedidos_compras.pedidos_abertos.index');
	Route::post('/filter', 'PedidosComprasNasajonController@filterPedidoAberto')->name('pedidos_compras.pedidos_abertos.filter');
	Route::post('/dialog', 'PedidosComprasNasajonController@dialogPedidoAberto')->name('pedidos_compras.pedidos_abertos.dialog');
	Route::post('/exportar_excel', 'PedidosComprasNasajonController@exportarExcel')->name('pedidos_compras.pedidos_abertos.exportar_excel');
	Route::post('/exportar_excel_dialog', 'PedidosComprasNasajonController@exportarExcelDialog')->name('pedidos_compras.pedidos_abertos.exportar_excel_dialog');
	Route::post('/alterar', 'PedidosComprasNasajonController@alterarPrevisaoEntrega')->name('pedidos_compras.pedidos_abertos.alterar');
	Route::prefix('modal')->group(function(){
		Route::post('/', 'PedidosComprasNasajonController@alterarPrevisaoEntregaModal')->name('pedidos_compras.pedidos_abertos.modal');
		Route::post('/geracao_pedido_necessidade_compras', 'PedidosComprasNasajonController@modalGeracaoPedidoComprasNecessidades')->name('pedidos_compras.modal.geracao_pedido_necessidade_compras');
		Route::post('/detalhes_por_projeto', 'PedidosComprasNasajonController@viewDetalhesPorProjeto')->name('pedidos_compras.modal.detalhes_por_projeto');
        Route::post('/exibir_pedidos', 'PedidosComprasNasajonController@exibirPedidos')->name('pedidos_compras.modal.exibirpedidos');
	});
});

Route::prefix('fornecedor')->middleware(['auth'])->group(function(){
	Route::post('/autocomplete', 'FornecedorNasajonController@autocomplete')->name('fornecedor.autocomplete');	
	Route::prefix('buscar')->group(function(){
		Route::get('/', 'FornecedorNasajonController@indexBusca')->name('fornecedor.busca.index');
		Route::post('/filter', 'FornecedorNasajonController@filterBuscar')->name('fornecedor.busca.filter');
	});	
});

Route::prefix('ordem_faturamento_servico_armazenagem')->middleware(['auth'])->group(function(){	
	Route::get('/', 'OrdemFaturamentoServicoArmazenagemController@index')->name('ordem_faturamento_servico_armazenagem.index');
	Route::post('/gerar', 'OrdemFaturamentoServicoArmazenagemController@gerar')->name('ordem_faturamento_servico_armazenagem.gerar');
	Route::post('/validar', 'OrdemFaturamentoServicoArmazenagemController@validar')->name('ordem_faturamento_servico_armazenagem.validar');
});

Route::prefix('notas_nasajon')->middleware(['auth'])->group(function(){
	Route::prefix('modal')->group(function(){
		Route::post('/exibir', 'NotasNasajonController@exibirNota')->name('notas_nasajon.modal.exibir');
		Route::post('/exibir_nota', 'NotasNasajonController@exibirNotaBusca')->name('notas_nasajon.modal.exibir_busca');
		Route::post('/em_aberto', 'NotasEmAbertoNasajonController@exibirNota')->name('notas_aberto_nasajon.modal');
		Route::prefix('documentos')->group(function(){
			Route::post('/', 'HistoricoDeVendasController@exibirDocumentos')->name('notas_nasajon.modal.documentos');
			Route::post('/pdf', 'HistoricoDeVendasController@downloadPdf')->name('notas_nasajon.modal.documentos.pdf');
			Route::post('/xml', 'HistoricoDeVendasController@downloadXml')->name('notas_nasajon.modal.documentos.xml');
			Route::post('/boleto', 'HistoricoDeVendasController@downloadBoleto')->name('notas_nasajon.modal.documentos.boleto');

			Route::prefix('testar')->group(function(){
				Route::post('/danfe', 'HistoricoDeVendasController@testarDanfe')->name('notas_nasajon.testar_danfe');
				Route::post('/xml', 'HistoricoDeVendasController@testarXml')->name('notas_nasajon.testar_xml');
			});
		});
	});
});

Route::prefix('faccao')->middleware(['auth'])->group(function(){	
	Route::get('/', 'FaccaoController@index')->name('faccao.index');
	Route::post('/adicionar', 'FaccaoController@adicionar')->name('faccao.adicionar');
	Route::post('/editar', 'FaccaoController@editar')->name('faccao.editar');
	Route::post('/deletar', 'FaccaoController@deletar')->name('faccao.deletar');
	Route::post('/filter', 'FaccaoController@filter')->name('faccao.filter');
	Route::post('/autocomplete', 'FaccaoController@autoComplete')->name('faccao.autocomplete');
	Route::post('/autocomplete_servico', 'FaccaoController@autoCompleteServico')->name('faccao.autocomplete_servico');
	Route::post('/buscar', 'FaccaoController@filterBuscar')->name('faccao.busca.filter');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'FaccaoController@modalAdicionar')->name('faccao.modal.adicionar');
		Route::post('/editar', 'FaccaoController@modalEditar')->name('faccao.modal.editar');
		Route::post('/deletar', 'FaccaoController@modalDeletar')->name('faccao.modal.deletar');
		Route::get('/buscar', 'FaccaoController@modalBuscar')->name('faccao.modal.buscar');
	});	
});

Route::prefix('tipo_de_servico')->middleware(['auth'])->group(function(){	
	Route::get('/', 'TipoDeServicoController@index')->name('tipo_de_servico.index');
	Route::post('/adicionar', 'TipoDeServicoController@adicionar')->name('tipo_de_servico.adicionar');
	Route::post('/editar', 'TipoDeServicoController@editar')->name('tipo_de_servico.editar');
	Route::post('/deletar', 'TipoDeServicoController@deletar')->name('tipo_de_servico.deletar');
	Route::post('/filter', 'TipoDeServicoController@filter')->name('tipo_de_servico.filter');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'TipoDeServicoController@modalAdicionar')->name('tipo_de_servico.modal.adicionar');
		Route::post('/editar', 'TipoDeServicoController@modalEditar')->name('tipo_de_servico.modal.editar');
		Route::post('/deletar', 'TipoDeServicoController@modalDeletar')->name('tipo_de_servico.modal.deletar');
	});	
});

Route::prefix('faccao_x_tipo_de_servico')->middleware(['auth'])->group(function(){	
	Route::get('/', 'FaccaoTipoDeServicoController@index')->name('faccao_tipo_de_servico.index');
	Route::post('/adicionar', 'FaccaoTipoDeServicoController@adicionar')->name('faccao_tipo_de_servico.adicionar');
	Route::post('/editar', 'FaccaoTipoDeServicoController@editar')->name('faccao_tipo_de_servico.editar');
	Route::post('/deletar', 'FaccaoTipoDeServicoController@deletar')->name('faccao_tipo_de_servico.deletar');
	Route::post('/filter', 'FaccaoTipoDeServicoController@filter')->name('faccao_tipo_de_servico.filter');
	Route::post('/autocomplete', 'FaccaoTipoDeServicoController@autoComplete')->name('faccao_tipo_de_servico.autocomplete');
	Route::post('/get_tipo_servico', 'FaccaoTipoDeServicoController@getTipoDeServico')->name('faccao_tipo_de_servico.get_tipo_servico');
	Route::post('/get_preco', 'FaccaoTipoDeServicoController@getPreco')->name('faccao_tipo_de_servico.get_preco');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'FaccaoTipoDeServicoController@modalAdicionar')->name('faccao_tipo_de_servico.modal.adicionar');
		Route::post('/editar', 'FaccaoTipoDeServicoController@modalEditar')->name('faccao_tipo_de_servico.modal.editar');
		Route::post('/deletar', 'FaccaoTipoDeServicoController@modalDeletar')->name('faccao_tipo_de_servico.modal.deletar');
	});	
});

Route::prefix('confirmacao_saida_nota')->middleware(['auth'])->group(function(){	
	Route::get('/', 'ConfirmacaoNotaSaidaController@index')->name('confirmacao_saida_nota.index');
	Route::post('/adicionar', 'ConfirmacaoNotaSaidaController@adicionar')->name('confirmacao_saida_nota.adicionar');
	Route::post('/editar', 'ConfirmacaoNotaSaidaController@editar')->name('confirmacao_saida_nota.editar');
	Route::post('/deletar', 'ConfirmacaoNotaSaidaController@deletar')->name('confirmacao_saida_nota.deletar');
	Route::post('/filter', 'ConfirmacaoNotaSaidaController@filter')->name('confirmacao_saida_nota.filter');
	Route::post('/getclientedataemissao', 'ConfirmacaoNotaSaidaController@getClienteDataEmissao')->name('confirmacao_saida_nota.get_cliente_data_emissao');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'ConfirmacaoNotaSaidaController@modalAdicionar')->name('confirmacao_saida_nota.modal.adicionar');
		Route::post('/editar', 'ConfirmacaoNotaSaidaController@modalEditar')->name('confirmacao_saida_nota.modal.editar');
		Route::post('/deletar', 'ConfirmacaoNotaSaidaController@modalDeletar')->name('confirmacao_saida_nota.modal.deletar');
	});	
});

Route::prefix('lancamento_projeto')->middleware(['auth'])->group(function(){	
	Route::get('/', 'LancamentoProjetoController@index')->name('lancamento_projeto.index');
	Route::post('/adicionar', 'LancamentoProjetoController@adicionar')->name('lancamento_projeto.adicionar');
	Route::post('/adicionar_on_change', 'LancamentoProjetoController@adicionarOnChange')->name('lancamento_projeto.adicionar_on_change');
	Route::post('/revisao_ok', 'LancamentoProjetoController@revisaoOkProjeto')->name('lancamento_projeto.revisao_ok');
	Route::post('/aprovacao', 'LancamentoProjetoController@aprovaProjeto')->name('lancamento_projeto.aprovacao');
	Route::post('/editar', 'LancamentoProjetoController@editar')->name('lancamento_projeto.editar');
	Route::post('/deletar', 'LancamentoProjetoController@deletar')->name('lancamento_projeto.deletar');
	Route::post('/duplicar', 'LancamentoProjetoController@duplicar')->name('lancamento_projeto.duplicar');
	Route::post('/salvar_duplicada', 'LancamentoProjetoController@salvarDuplicada')->name('lancamento_projeto.salvar_duplicada');
	Route::post('/filter', 'LancamentoProjetoController@filter')->name('lancamento_projeto.filter');
	Route::post('/recusar', 'LancamentoProjetoController@recusarProjeto')->name('lancamento_projeto.recusa_projeto');
	Route::post('/reajuste', 'LancamentoProjetoController@reajusteQuantidade')->name('lancamento_projeto.reajuste');
	Route::post('/resultado', 'LancamentoProjetoController@resultado')->name('lancamento_projeto.resultado');
	Route::post('/get_informacao_preco', 'LancamentoProjetoController@getInformacaoPreco')->name('lancamento_projeto.get_informacao_preco');
	Route::post('/duplicar_produto', 'LancamentoProjetoController@duplicarProduto')->name('lancamento_projeto.duplicar_produto');
	Route::post('/alterar_status', 'LancamentoProjetoController@alteracaoStatus')->name('lancamento_projeto.alterar_status');
	Route::post('/liberar_tabs', 'LancamentoProjetoController@liberarTabs')->name('lancamento_projeto.liberar_tabs');
	Route::post('/imprimir', 'LancamentoProjetoController@imprimir')->name('lancamento_projeto.imprimir');
	Route::post('/imprimir_resumo_faccao', 'LancamentoProjetoController@imprimirResumoFaccao')->name('lancamento_projeto.imprimir_resumo_faccao');
	Route::post('/carregar_documentos', 'LancamentoProjetoController@carregarDocumentos')->name('lancamento_projeto.carregar_documentos');
	Route::prefix('modal')->group(function(){
		Route::post('/', 'LancamentoProjetoController@modal')->name('lancamento_projeto.modal');
		Route::get('/adicionar', 'LancamentoProjetoController@modalAdicionar')->name('lancamento_projeto.modal.adicionar');
		Route::post('/editar', 'LancamentoProjetoController@modalEditar')->name('lancamento_projeto.modal.editar');
		Route::post('/deletar', 'LancamentoProjetoController@modalDeletar')->name('lancamento_projeto.modal.deletar');
		Route::post('/recusar', 'LancamentoProjetoController@recusarProjetoModal')->name('lancamento_projeto.modal.recusar');
		Route::post('/view', 'LancamentoProjetoController@modalView')->name('lancamento_projeto.modal.view');
		Route::post('/desconto', 'LancamentoProjetoController@modalDesconto')->name('lancamento_projeto.modal_desconto');
		Route::post('/buscar_projeto_produto', 'LancamentoProjetoController@modalBuscarProjetoProduto')->name('lancamento_projeto.modal.buscar_projeto_produto');
		Route::post('/editar_status', 'LancamentoProjetoController@modalEditarStatus')->name('lancamento_projeto.modal.editar_status');
		Route::post('/composicao_produto_final', 'LancamentoProjetoController@modalComposicao')->name('lancamento_projeto.modal.composicao');
		Route::post('/duplicar_produto', 'LancamentoProjetoController@modalDuplicarProduto')->name('lancamento_projeto.modal.duplicar_produto');
	});
	Route::prefix('servico')->group(function(){
		Route::post('/adicionar', 'LancamentoProjetoController@adicionarServico')->name('lancamento_projeto.servico.adicionar');
		Route::post('/get_editar', 'LancamentoProjetoController@getEditarServico')->name('lancamento_projeto.servico.get_editar');
		Route::post('/editar', 'LancamentoProjetoController@editarServico')->name('lancamento_projeto.servico.editar');
		Route::post('/deletar', 'LancamentoProjetoController@deletarServico')->name('lancamento_projeto.servico.deletar');
		Route::post('/carregar_servico', 'LancamentoProjetoController@carregarServicos')->name('lancamento_projeto.servico.carregar_servico');
		Route::post('/aplicar_alteracao', 'LancamentoProjetoController@aplicarAlteracaoServico')->name('lancamento_projeto.servico.aplicar_alteracao');
		Route::post('/alteracao_em_massa', 'LancamentoProjetoController@alteracaoEmMassaServico')->name('lancamento_projeto.servico.alteracao_em_massa');
	});
	Route::prefix('faccao')->group(function(){
		Route::post('/salvar', 'LancamentoProjetoController@salvarFaccao')->name('lancamento_projeto.faccao.salvar');
		Route::post('/get_editar', 'LancamentoProjetoController@getEditarFaccao')->name('lancamento_projeto.faccao.get_editar');
		Route::post('/editar', 'LancamentoProjetoController@editarFaccao')->name('lancamento_projeto.faccao.editar');
		Route::post('/deletar', 'LancamentoProjetoController@deletarFaccao')->name('lancamento_projeto.faccao.deletar');
		Route::post('/calculo', 'LancamentoProjetoController@calculoFaccao')->name('lancamento_projeto.faccao.calculo');
		Route::post('/cancelar_edicao', 'LancamentoProjetoController@cancelarEdicaoFaccao')->name('lancamento_projeto.faccao.cancelar_edicao');
		Route::post('/alteracao_data', 'LancamentoProjetoController@alteracaoDataFaccao')->name('lancamento_projeto.faccao.alteracao_data');
		Route::prefix('modal')->group(function(){
			Route::post('/editar_data', 'LancamentoProjetoController@modalEditarData')->name('lancamento_projeto.faccao.modal.editar_data');
		});
	});
	Route::prefix('insumo')->group(function(){
		Route::post('/adicionar', 'LancamentoProjetoController@adicionarInsumo')->name('lancamento_projeto.insumo.adicionar');
		Route::post('/get_editar', 'LancamentoProjetoController@getEditarInsumo')->name('lancamento_projeto.insumo.get_editar');
		Route::post('/editar', 'LancamentoProjetoController@editarInsumo')->name('lancamento_projeto.insumo.editar');
		Route::post('/deletar', 'LancamentoProjetoController@deletarInsumo')->name('lancamento_projeto.insumo.deletar');
		Route::post('/cancelar_edicao', 'LancamentoProjetoController@cancelarEdicaoInsumo')->name('lancamento_projeto.insumo.cancelar_edicao');
		Route::post('/carregar_insumo', 'LancamentoProjetoController@carregarInsumos')->name('lancamento_projeto.insumo.carregar_insumo');
		Route::post('/aplicar_alteracao', 'LancamentoProjetoController@aplicarAlteracaoInsumo')->name('lancamento_projeto.insumo.aplicar_alteracao');
	});
	Route::prefix('produto')->group(function(){
		Route::post('/adicionar', 'LancamentoProjetoController@adicionarProduto')->name('lancamento_projeto.produto.adicionar');
		Route::post('/get_editar', 'LancamentoProjetoController@getEditarProduto')->name('lancamento_projeto.produto.get_editar');
		Route::post('/editar', 'LancamentoProjetoController@editarProduto')->name('lancamento_projeto.produto.editar');
		Route::post('/deletar', 'LancamentoProjetoController@deletarProduto')->name('lancamento_projeto.produto.deletar');
		Route::post('/cancelar_edicao', 'LancamentoProjetoController@cancelarEdicaoProduto')->name('lancamento_projeto.produto.cancelar_edicao');
		Route::post('/get_quantidade', 'LancamentoProjetoController@getQuantidadeProduto')->name('lancamento_projeto.produto.get_quantidade');
		Route::post('/get_produto', 'LancamentoProjetoController@getProduto')->name('lancamento_projeto.produto.get_produto');
		Route::post('/carregar_produto', 'LancamentoProjetoController@carregarProdutos')->name('lancamento_projeto.produto.carregar_produto');
		Route::post('/alteracao_em_massa', 'LancamentoProjetoController@alteracaoEmMassaProduto')->name('lancamento_projeto.produto.alteracao_em_massa');
		Route::post('/aplicar_alteracao', 'LancamentoProjetoController@aplicarAlteracaoProduto')->name('lancamento_projeto.produto.aplicar_alteracao');
		Route::prefix('arquivo')->group(function(){
			Route::post('/adicionar', 'LancamentoProjetoController@adicionarArquivoProduto')->name('lancamento_projeto.produto.arquivo.adicionar');
			Route::post('/deletar', 'LancamentoProjetoController@deletarArquivoProduto')->name('lancamento_projeto.produto.arquivo.deletar');
		});
	});
	Route::prefix('tecido')->group(function(){
		Route::post('/adicionar', 'LancamentoProjetoController@adicionarTecido')->name('lancamento_projeto.tecido.adicionar');
		Route::post('/get_editar', 'LancamentoProjetoController@getEditarTecido')->name('lancamento_projeto.tecido.get_editar');
		Route::post('/editar', 'LancamentoProjetoController@editarTecido')->name('lancamento_projeto.tecido.editar');
		Route::post('/deletar', 'LancamentoProjetoController@deletarTecido')->name('lancamento_projeto.tecido.deletar');
		Route::post('/cancelar_edicao', 'LancamentoProjetoController@cancelarEdicaoTecido')->name('lancamento_projeto.tecido.cancelar_edicao');
		Route::post('/get_quantidade', 'LancamentoProjetoController@getQuantidadeTecido')->name('lancamento_projeto.tecido.get_quantidade');
		Route::post('/get_tecido', 'LancamentoProjetoController@getTecido')->name('lancamento_projeto.tecido.get_tecido');
		Route::post('/carregar_tecido', 'LancamentoProjetoController@carregarTecidos')->name('lancamento_projeto.tecido.carregar_tecido');
		Route::post('/aplicar_alteracao', 'LancamentoProjetoController@aplicarAlteracaoTecido')->name('lancamento_projeto.tecido.aplicar_alteracao');
	});
	Route::prefix('produto')->group(function(){
		Route::post('/buscar_descricao', 'ProdutoProjetoController@retornaDescricao')->name('lancamento_projeto.produto.retorna_descricao');
		Route::post('/filter', 'ProdutoProjetoController@filter')->name('lancamento_projeto.produto.filter');
	});
	Route::prefix('revisao')->group(function(){
		Route::get('/', 'LancamentoProjetoController@indexRevisao')->name('lancamento_projeto.revisao.index');
		Route::post('/filter', 'LancamentoProjetoController@filterRevisao')->name('lancamento_projeto.revisao.filter');
		Route::prefix('modal')->group(function(){
			Route::post('/editar', 'LancamentoProjetoController@modalEditarRevisao')->name('lancamento_projeto.revisao.modal.editar');
		});
	});
	Route::prefix('consulta')->group(function(){
		Route::get('/', 'LancamentoProjetoController@indexConsulta')->name('projeto.consulta.index');
		Route::post('/filter', 'LancamentoProjetoController@filterConsulta')->name('projeto.consulta.filter');
	});
	Route::prefix('edicao_materia_prima')->group(function(){
		Route::get('/', 'LancamentoProjetoController@indexEdicaoMateriaPrima')->name('lancamento_projeto.edicao_materia_prima.index');
		Route::post('/filter', 'LancamentoProjetoController@filterEdicaoMateriaPrima')->name('lancamento_projeto.edicao_materia_prima.filter');
		Route::post('/salvar_edicao_materia_prima', 'LancamentoProjetoController@salvarEdicaoMateriaPrima')->name('lancamento_projeto.edicao_materia_prima.salvar');
		Route::prefix('modal')->group(function(){
			Route::post('/editar_materia_prima', 'LancamentoProjetoController@modalEditarMateriaPrima')->name('lancamento_projeto.edicao_materia_prima.modal.editar_materia_prima');
		});
	});
});

Route::prefix('consulta_estoque_geral')->middleware(['auth'])->group(function(){
	Route::get('/','ConsultaEstoqueGeralController@index')->name('consulta_estoque_geral.index');
	Route::post('/consulta','ConsultaEstoqueGeralController@consulta')->name('consulta_estoque_geral.consulta');
	Route::post('/detalhes','ConsultaEstoqueGeralController@detalhes')->name('consulta_estoque_geral.detalhes');
});

Route::prefix('pecas_fracionadas')->middleware(['auth'])->group(function(){
	Route::get('/','PecasFracionadasController@index')->name('pecas_fracionadas.index');
	Route::post('/consulta','PecasFracionadasController@consulta')->name('pecas_fracionadas.consulta');
	Route::post('/exibir_geral', 'PecasFracionadasController@exibirPecasGeral')->name('pecas_fracionadas.exibir.geral');
	Route::post('/exibir_fracionadas', 'PecasFracionadasController@exibirPecasFracionadas')->name('pecas_fracionadas.exibir.fracionadas');
});

Route::prefix('cliente_pre_pago')->middleware(['auth'])->group(function(){
	Route::get('/','ClientePrePagoController@index')->name('clientes_prepago.index');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar','ClientePrePagoController@formAdd')->name('cliente_prepago.formAdd');
		Route::post('/editar','ClientePrePagoController@formEdit')->name('cliente_prepago.formEdit');
		Route::post('/deletar','ClientePrePagoController@formDelete')->name('cliente_prepago.formDelete');
	});
	Route::post('/adicionar','ClientePrePagoController@store')->name('cliente_prepago.store');
	Route::post('/editar','ClientePrePagoController@update')->name('cliente_prepago.update');
	Route::post('/excluir','ClientePrePagoController@delete')->name('cliente_prepago.delete');
	Route::post('/filter','ClientePrePagoController@filter')->name('cliente_prepago.filter');

});

Route::prefix('parametro_hospitalar')->middleware(['auth'])->group(function(){
	Route::get('/', 'ParametroHospitalarController@index')->name('parametro_hospitalar.index');
	Route::post('/adicionar','ParametroHospitalarController@adicionar')->name('parametro_hospitalar.adicionar');
	Route::post('/editar','ParametroHospitalarController@editar')->name('parametro_hospitalar.editar');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'ParametroHospitalarController@modalAdicionar')->name('parametro_hospitalar.modal.adicionar');
		Route::get('/editar', 'ParametroHospitalarController@modalEditar')->name('parametro_hospitalar.modal.editar');
	});	
});

Route::prefix('produto_foto')->middleware(['auth'])->group(function(){
	Route::get('/', 'ProdutoFotoController@index')->name('produto_foto.index');
	Route::post('busca', 'ProdutoFotoController@filtro')->name('produto_foto.busca');
	Route::post('adicionar', 'ProdutoFotoController@salvar')->name('produto_foto.salvar');
	Route::post('editar', 'ProdutoFotoController@editar')->name('produto_foto.editar');
	Route::post('excluir', 'ProdutoFotoController@excluir')->name('produto_foto.excluir');
	Route::prefix('modal')->group(function(){
		Route::post('adicionar', 'ProdutoFotoController@adicionarModal')->name('produto_foto.modal.adicionar');
		Route::post('editar', 'ProdutoFotoController@editarModal')->name('produto_foto.modal.editar');
		Route::post('excluir', 'ProdutoFotoController@excluirModal')->name('produto_foto.modal.excluir');
	});
});

Route::prefix('codigo_barras')->middleware(['auth'])->group(function (){
	Route::prefix('produto')->group(function(){
		Route::post('pedido', 'ImportacaoCodigoBarrasController@importacaoProdutoPedido')->name('codigo_barras.produto.pedido');
	});
});

Route::prefix('favoritos')->middleware(['auth'])->group(function (){
	Route::get('/', 'ProgramasFavoritoController@index')->name('favoritos.index');
	Route::post('salvar', 'ProgramasFavoritoController@salvarProgramaFavorito')->name('favoritos.salvar');
});

Route::prefix('titulos_prepago')->middleware(['auth'])->group(function () {
	Route::get('/', 'PedidosPrePagoController@index')->name('titulos_prepago.index');
	Route::post('filtro', 'PedidosPrePagoController@filter')->name('titulos_prepago.filtro');
	Route::prefix('lancamentos')->group(function(){
		Route::post('salvar', 'PedidosPrePagoController@salvaLancamento')->name('titulos_prepago.lancamentos.salvar');
		Route::post('deletar', 'PedidosPrePagoController@apagarLancamento')->name('titulos_prepago.lancamentos.deletar');
	});
	Route::prefix('modal')->group(function(){
		Route::post('/', 'PedidosPrePagoController@modal')->name('titulos_prepago.modal');
		Route::post('filtro', 'PedidosPrePagoController@modal_filtro')->name('titulos_prepago.modal.filtro');
		Route::post('detalhes', 'PedidosPrePagoController@detalhesModal')->name('titulos_prepago.modal.detalhes');
		Route::post('abertos', 'PedidosPrePagoController@modalPrepagosAbertosRepresentante')->name('titulos_prepago.modal.abertos_representante');
	});

	Route::prefix('baixar')->group(function(){
		Route::get('/', 'BaixaTituloController@index')->name('titulos_prepago.baixar.index');
		Route::post('/titulos', 'BaixaTituloController@recuperaTitulos')->name('titulos_prepago.baixar.titulos');
		Route::post('/salvar', 'BaixaTituloController@salvaCheques')->name('titulos_prepago.baixar.salvar');
		Route::post('/baixar_titulo_na_nasajon', 'BaixaTituloController@baixarTituloNaNasajon')->name('baixar_titulo.baixar_titulo_na_nasajon');
		Route::post('/filtro_titulos_para_baixar', 'BaixaTituloController@filtroTitulosParaBaixar')->name('baixar_titulo.filtro_titulos_para_baixar');

		Route::prefix('modal')->group(function(){
			Route::post('/', 'BaixaTituloController@modalChequesBaixar')->name('titulos_prepago.baixar.modal');
			Route::post('/titulos_para_baixar', 'BaixaTituloController@modalTitulosParaBaixar')->name('baixar_titulo.modal.titulos_para_baixar');
			Route::post('/baixar_titulo', 'BaixaTituloController@modalBaixarTitulo')->name('baixar_titulo.modal.baixar_titulo');
		});
	});

});

Route::prefix('valor_custo_nota_produto')->middleware(['auth'])->group(function () {
	Route::get('/', 'ValorCustoNotaProdutoController@index')->name('valor_custo_nota_produto.index');
	Route::post('/filtro', 'ValorCustoNotaProdutoController@filter')->name('valor_custo_nota_produto.filter');
	Route::post('/novoCusto', 'ValorCustoNotaProdutoController@novoCusto')->name('valor_custo_nota_produto.novo_custo');
	Route::post('/editarCusto', 'ValorCustoNotaProdutoController@editarCusto')->name('valor_custo_nota_produto.editar_custo');
	Route::post('/excluirCusto', 'ValorCustoNotaProdutoController@excluirCusto')->name('valor_custo_nota_produto.excluir_custo');
	Route::prefix('modal')->group(function(){
		Route::post('/novo', 'ValorCustoNotaProdutoController@novosCustosModal')->name('valor_custo_nota_produto.modal.novo');
		Route::post('/editar', 'ValorCustoNotaProdutoController@editarCustosModal')->name('valor_custo_nota_produto.modal.editar');
		Route::post('/excluir', 'ValorCustoNotaProdutoController@excluirCustosModal')->name('valor_custo_nota_produto.modal.excluir');
		Route::post('/recupera_nota', 'ValorCustoNotaProdutoController@recuperaNota')->name('valor_custo_nota_produto.modal.recupera_nota');
	});
});

Route::prefix('necessidade_compras')->middleware(['auth'])->group(function () {
	Route::get('/', 'NecessidadeComprasController@index')->name('necessidade_compras.index');
	Route::post('/filter', 'NecessidadeComprasController@filter')->name('necessidade_compras.filter');
	Route::post('/dialog_projeto', 'NecessidadeComprasController@dialog')->name('necessidade_compras.dialog');
	Route::post('/editar_fornecedor', 'NecessidadeComprasController@editarFornecedor')->name('necessidade_compras.editar_fornecedor');
	Route::post('/get_produto_por_fornecedor', 'NecessidadeComprasController@getProdutoPorFornecedor')->name('necessidade_compras.get_produto_por_fornecedor');
	Route::post('/gerar_pedido_compra', 'NecessidadeComprasController@gerarPedidoCompra')->name('necessidade_compras.gerar_pedido_compra');
	Route::prefix('modal')->group(function(){
		Route::post('/editar_fornecedor', 'NecessidadeComprasController@modalEditarFornecedor')->name('necessidade_compras.modal.editar_fornecedor');
		Route::post('/detalhes_projeto', 'NecessidadeComprasController@viewDetalhesProjeto')->name('necessidade_compras.modal.detalhes_projeto');
		Route::post('/lista_compras', 'NecessidadeComprasController@modalListarCompras')->name('necessidade_compras.modal.lista_compras');
	}); 
});

Route::prefix('cheque')->middleware(['auth'])->group(function () {
	Route::get('/', 'ChequeController@index')->name('cheque.index');
	Route::post('/filter', 'ChequeController@filter')->name('cheque.filter');
	Route::post('/adicionar', 'ChequeController@adicionar')->name('cheque.adicionar');
	Route::post('/editar', 'ChequeController@editar')->name('cheque.editar');
	Route::post('/excluir', 'ChequeController@excluir')->name('cheque.excluir');
	Route::post('/recupera_titulos', 'ChequeController@recuperaTitulosParaCheques')->name('cheque.recupera_titulos');
	Route::prefix('modal')->group(function(){
		Route::post('/novo', 'ChequeController@modalNovo')->name('cheque.modal.novo');
		Route::post('/editar', 'ChequeController@modalEditar')->name('cheque.modal.editar');
		Route::post('/excluir', 'ChequeController@modalExcluir')->name('cheque.modal.excluir');
		Route::post('/cliente/{coluna}', 'ChequeController@modaChequesCliente')->name('cheque.modal.cliente');
	});
});

Route::prefix('analise_sintetica_projeto')->middleware(['auth'])->group(function () {
	Route::get('/', 'AnaliseSinteticaProjetoController@index')->name('analise_sintetica_projeto.index');
	Route::post('/filter', 'AnaliseSinteticaProjetoController@filter')->name('analise_sintetica_projeto.filter');
	Route::prefix('modal')->group(function(){
		Route::post('/dialog', 'AnaliseSinteticaProjetoController@dialog')->name('analise_sintetica_projeto.modal.dialog');
	});
});

Route::prefix('preco_base')->middleware(['auth'])->group(function () {
	Route::get('/', 'PrecoBaseController@index')->name('preco_base.index');
	Route::post('/', 'PrecoBaseController@calculo')->name('preco_base.calculo');
	Route::prefix('modal')->group(function(){
		Route::get('/dialog', 'PrecoBaseController@dialog')->name('preco_base.modal.dialog');
	});
});

Route::prefix('remessa_itens')->middleware(['auth'])->group(function () {
	Route::get('/', 'RemessaItensController@index')->name('remessa_itens.index');
	Route::post('/filter', 'RemessaItensController@filter')->name('remessa_itens.filter');
	Route::post('/gerar_remessa', 'RemessaItensController@gerarRemessa')->name('remessa_itens.gerar_remessa');
	Route::post('/produto_para_remessa', 'RemessaItensController@getProdutoParaRemessa')->name('remessa_itens.produto_para_remessa');
	Route::prefix('modal')->group(function(){
		Route::post('/geracao_remessa', 'RemessaItensController@dialogGeracaoRemessa')->name('remessa_itens.modal.geracao_remessa');
		Route::post('/detalhes_por_projeto', 'RemessaItensController@viewDetalhesPorProjeto')->name('remessa_itens.modal.detalhes_por_projeto');
		Route::post('/detalhes_itens_por_projeto', 'RemessaItensController@viewDetalhesItensPorProjeto')->name('remessa_itens.modal.detalhes_itens_por_projeto');
		Route::post('/detalhes_por_projeto_transferencia', 'RemessaItensController@viewDetalhesPorProjetoTransferencia')->name('remessa_itens.modal.detalhes_por_projeto_transferencia');
	});
});

Route::prefix('vendas_santista')->middleware(['auth'])->group(function () {
	Route::get('/', 'VendasSantistaController@index')->name('vendas_santista.index');
	Route::post('/filtro', 'VendasSantistaController@filtro')->name('vendas_santista.filter');

	Route::prefix('modal')->group(function(){
		Route::post('/', 'VendasSantistaController@modalAnalitica')->name('vendas_santista.modal');
	});

	Route::prefix('sintetica')->group(function(){
		Route::get('/', 'VendasSantistaController@indexSintetica')->name('vendas_santista.sintetica.index');
		Route::post('/filter', 'VendasSantistaController@filtroSintetica')->name('vendas_santista.sintetica.filter');
	});	
});

Route::prefix('ultimas_compras')->middleware(['auth'])->group(function (){
	Route::prefix('modal')->group(function(){
		Route::post('/dialog', 'UltimaComprasController@dialog')->name('ultimas_compras.modal.dialog');
	});
});

Route::prefix('usuario_cliente')->group(function () {
	Route::get('registrar', 'UsuarioClienteController@registrar')->name('usuario_cliente.registrar');
	Route::get('resetar', 'UsuarioClienteController@resetar')->name('usuario_cliente.resetar');	
	Route::prefix('nova_senha')->group(function () {
		Route::get('{hash}', 'UsuarioClienteController@novaSenhaIndex')->name('usuario_cliente.nova_senha.index');	
		Route::post('salvar_requisicao_nova_senha', 'UsuarioClienteController@salvarRequisicaoNovaSenha')->name('usuario_cliente.nova_senha.salvar_requisicao_nova_senha');	
		Route::post('salvar', 'UsuarioClienteController@salvarNovaSenha')->name('usuario_cliente.nova_senha.salvar');	
	});
	Route::post('cnpj_para_nome', 'UsuarioClienteController@cnpjParaNome')->name('usuario_cliente.cnpj_para_nome');
	Route::post('salvar', 'UsuarioClienteController@salvar')->name('usuario_cliente.salvar');
	Route::get('/', 'UsuarioClienteController@indexAprovacao')->middleware(['auth'])->name('usuario_cliente.index');
	Route::post('/filter', 'UsuarioClienteController@filter')->middleware(['auth'])->name('usuario_cliente.filter');
	Route::post('/aprovar', 'UsuarioClienteController@aprovarCliente')->middleware(['auth'])->name('usuario_cliente.aprovar');
	Route::post('/reprovar', 'UsuarioClienteController@reprovarCliente')->middleware(['auth'])->name('usuario_cliente.reprovar');
});

Route::prefix('analise_compras_mes')->middleware(['auth'])->group(function () {
	Route::get('/', 'AnaliseComprasMesController@index')->name('analise_compras_mes.index');
	Route::post('/filter', 'AnaliseComprasMesController@filter')->name('analise_compras_mes.filter');
	Route::prefix('modal')->group(function(){
		Route::post('/','AnaliseComprasMesController@show')->name('analise_compras_mes.modal.index');
		Route::post('/abertura','AnaliseComprasMesController@abertura')->name('analise_compras_mes.modal.abertura');
		Route::post('/modal_estoque','AnaliseComprasMesController@ShowEstoque')->name('analise_compras_mes.modal.estoque');
		Route::post('/modal_compras','AnaliseComprasMesController@ShowCompras')->name('analise_compras_mes.modal.compras');
		Route::post('/modal_vendas','AnaliseComprasMesController@ShowVendas')->name('analise_compras_mes.modal.vendas');
        Route::post('/modal_remessas','AnaliseComprasMesController@ShowRemessas')->name('analise_compras_mes.modal.remessas');
	});
});

Route::prefix('ficha_tecnica_visualizacao')->middleware(['auth'])->group(function(){
	Route::get('/', 'FichaTecnicaController@index')->name('ficha_tecnica.visualizacao.index');
	Route::post('/pesquisa', 'FichaTecnicaController@filter')->name('ficha_tecnica.visualizacao.filtro');

	Route::prefix('modal')->group(function (){
		Route::post('/', 'FichaTecnicaController@modal')->name('ficha_tecnica.visualizacao.modal');
	});
});

Route::prefix('ficha_tecnica_cadastro')->middleware(['auth'])->group(function(){
	Route::get('/', 'FichaTecnicaCadastroController@index')->name('ficha_tecnica.cadastro.index');
	Route::post('/pesquisa', 'FichaTecnicaCadastroController@filter')->name('ficha_tecnica.cadastro.filtro');
	Route::post('/duplicar_cadastro', 'FichaTecnicaCadastroController@salvarFichaTecnicaDuplicar')->name('ficha_tecnica.cadastro.duplicar');
	Route::post('/excluir', 'FichaTecnicaCadastroController@excluir')->name('ficha_tecnica.cadastro.excluir');

	Route::prefix('referecia_peca_lavagem')->group(function (){
		Route::post('/salvar', 'FichaTecnicaCadastroController@salvarInformacoesAdicionais')->name('ficha_tecnica.cadastro.referecia_peca_lavagem.salvar');
		Route::post('/apagar_imagem', 'FichaTecnicaCadastroController@apagarImagemProduto')->name('ficha_tecnica.cadastro.referecia_peca_lavagem.apagar_imagem');
	});

	Route::prefix('etiqueta')->group(function (){
		Route::post('/salvar', 'FichaTecnicaCadastroController@salvarEtiqueta')->name('ficha_tecnica.cadastro.etiqueta.salvar');
		Route::post('/excluir', 'FichaTecnicaCadastroController@apagarEtiqueta')->name('ficha_tecnica.cadastro.etiqueta.excluir');
	});

	Route::prefix('medidas')->group(function (){
		Route::post('/salvar', 'FichaTecnicaCadastroController@salvarMedidas')->name('ficha_tecnica.cadastro.medidas.salvar');
	});

	Route::prefix('sequencia')->group(function (){
		Route::post('/salvar', 'FichaTecnicaCadastroController@salvarSequencia')->name('ficha_tecnica.cadastro.sequencia.salvar');
	});

	Route::prefix('montagem')->group(function (){
		Route::post('/salvar', 'FichaTecnicaCadastroController@salvarMontagem')->name('ficha_tecnica.cadastro.montagem.salvar');
		Route::post('/excluir', 'FichaTecnicaCadastroController@apagarMontagem')->name('ficha_tecnica.cadastro.montagem.excluir');
	});

	Route::prefix('modal')->group(function (){
		Route::post('/editar', 'FichaTecnicaCadastroController@modalEditar')->name('ficha_tecnica.cadastro.modal.editar');
		Route::post('/novo', 'FichaTecnicaCadastroController@modalNovo')->name('ficha_tecnica.cadastro.modal.novo');
		Route::post('/salvar', 'FichaTecnicaCadastroController@salvarNovaFichaTecnica')->name('ficha_tecnica.cadastro.modal.salvar');
		Route::post('/duplicar', 'FichaTecnicaCadastroController@modalDuplicar')->name('ficha_tecnica.cadastro.modal.duplicar');
		Route::post('/excluir', 'FichaTecnicaCadastroController@modalExcluir')->name('ficha_tecnica.cadastro.modal.excluir');
	});

	Route::prefix('composicao')->group(function (){
		Route::post('/excluir', 'FichaTecnicaCadastroController@excluirComposicao')->name('ficha_tecnica.cadastro.composicao.excluir');
		Route::post('/editar', 'FichaTecnicaCadastroController@salvarComposicaoEdicao')->name('ficha_tecnica.cadastro.composicao.editar');
		Route::post('/salvar', 'FichaTecnicaCadastroController@salvarComposicaoNova')->name('ficha_tecnica.cadastro.composicao.salvar');

		Route::prefix('modal')->group(function (){
			Route::post('/novo', 'FichaTecnicaCadastroController@criarComposicaoModal')->name('ficha_tecnica.cadastro.composicao.modal.novo');
			Route::post('/editar', 'FichaTecnicaCadastroController@editarComposicaoModal')->name('ficha_tecnica.cadastro.composicao.modal.editar');
		});
	});
});

Route::prefix('contas_receber')->middleware(['auth'])->group(function () {
	Route::get('/', 'AnaliseContasReceberController@index')->name('analise_contas_receber.index');
	Route::post('/filter', 'AnaliseContasReceberController@filter')->name('analise_contas_receber.filter');
	Route::prefix('modal')->group(function(){
		Route::post('/','AnaliseContasReceberController@show')->name('analise_contas_receber.modal.index');
		Route::post('/desconto','AnaliseContasReceberController@showDesconto')->name('analise_contas_receber.modal.showDesconto');
		Route::post('/devolucao','AnaliseContasReceberController@showDevolucao')->name('analise_contas_receber.modal.showDevolucao');
		Route::post('/vencido','AnaliseContasReceberController@showVencido')->name('analise_contas_receber.modal.showVencido');
		Route::post('/dia','AnaliseContasReceberController@showDia')->name('analise_contas_receber.modal.showDia');
		Route::post('/antecipadas','AnaliseContasReceberController@showAntecipadas')->name('analise_contas_receber.modal.showAntecipadas');
		Route::post('/juros','AnaliseContasReceberController@showJuros')->name('analise_contas_receber.modal.showJuros');
		Route::post('/recebido','AnaliseContasReceberController@showRecebido')->name('analise_contas_receber.modal.showRecebido');
	});
});

Route::prefix('inadimplencia')->middleware(['auth'])->group(function (){
	Route::get('/','AnaliseInadimplenciaController@index')->name('analise_inadimplencia.index');
	Route::post('/filter','AnaliseInadimplenciaController@filter')->name('analise_inadimplencia.filter');
	Route::prefix('modal')->group(function(){
		Route::post('/emitidos','AnaliseInadimplenciaController@ModalEmitidos')->name('analise_inadimplencia.modal.emitidos');
		Route::post('/pagos','AnaliseInadimplenciaController@ModalPagos')->name('analise_inadimplencia.modal.pagos');
		Route::post('/cancelados','AnaliseInadimplenciaController@ModalCancelados')->name('analise_inadimplencia.modal.cancelados');
		Route::post('/atraso','AnaliseInadimplenciaController@ModalAtraso')->name('analise_inadimplencia.modal.atraso');
		Route::post('/adiantado','AnaliseInadimplenciaController@ModalAdiantado')->name('analise_inadimplencia.modal.adiantado');
		Route::post('/inadimplencia','AnaliseInadimplenciaController@ModalInadimplencia')->name('analise_inadimplencia.modal.inadimplencia');
		Route::post('/dia_a_dia','AnaliseInadimplenciaController@pagosDiaADiaValores')->name('analise_inadimplencia.modal.dia_a_dia');
		
		Route::prefix('abertura')->group(function(){
			Route::post('/titulos','AnaliseInadimplenciaController@TitulosAbertura')->name('analise_inadimplencia.modal.abertura.emitidos');
			Route::post('/pagos','AnaliseInadimplenciaController@TitulosPagosAbertura')->name('analise_inadimplencia.modal.abertura.pagos');
			Route::post('/cancelados','AnaliseInadimplenciaController@TitulosCanceladosAbertura')->name('analise_inadimplencia.modal.abertura.cancelados');
			Route::post('/adiantados','AnaliseInadimplenciaController@TitulosAdiantadoAbertura')->name('analise_inadimplencia.modal.abertura.adiantados');
			Route::post('/atrasados','AnaliseInadimplenciaController@TitulosAtrasadosAbertura')->name('analise_inadimplencia.modal.abertura.atrasados');
			Route::post('/inadimplencia','AnaliseInadimplenciaController@TitulosInadimplenciaAbertura')->name('analise_inadimplencia.modal.abertura.inadimplencia');
		});
	});
});

Route::prefix('laudos')->middleware(['auth'])->group(function(){
	Route::prefix('cadastrar')->group(function(){
		Route::get('/','LaudoController@cadastroIndex')->name('laudos.cadastro.index');
		Route::post('/filter', 'LaudoController@cadastroFilter')->name('laudo.filter');
		Route::post('/produtos', 'LaudoController@ProdutosGrupo')->name('laudo.produtos.grupo');
    	Route::post('/editar', 'LaudoController@cadastroEditar')->name('laudo.editar');
		Route::prefix('modal')->group(function(){
			Route::post('/editar', 'LaudoController@modalEditar')->name('laudo.modal.editar');
		});
		Route::post('/salvar', 'LaudoController@salvarInstrucoesLavagem')->name('laudo.cadastrar.salvar');
	
	});
});

Route::prefix('clientes_duvidosos')->middleware(['auth'])->group(function(){
	Route::get('/','ClienteDuvidosoController@index')->name('clientes_duvidosos.index');
	Route::post('/adicionar','ClienteDuvidosoController@adicionarClienteDuvidoso')->name('clientes_duvidosos.adicionar');
	Route::post('/deletar','ClienteDuvidosoController@deletarClienteDuvidoso')->name('clientes_duvidosos.deletar');
	Route::post('/filter','ClienteDuvidosoController@filterClienteDuvidoso')->name('clientes_duvidosos.filter');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'ClienteDuvidosoController@modalAdicionar')->name('clientes_duvidosos.modal.adicionar');
		Route::post('/deletar', 'ClienteDuvidosoController@modalDeletar')->name('clientes_duvidosos.modal.deletar');
	});
});

Route::prefix('atrasos_equipe')->middleware(['auth'])->group(function(){
	Route::get('/','AnaliseRelacaoAtrasosController@index')->name('analise_atrasos_equipe.index');
	Route::post('/filter','AnaliseRelacaoAtrasosController@filter')->name('analise_atrasos_equipe.filter');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/cliente','AnaliseRelacaoAtrasosController@modalCLiente')->name('analise_atrasos_equipe.modal.cliente');
		Route::post('/titulos','AnaliseRelacaoAtrasosController@modalTitulos')->name('analise_atrasos_equipe.modal.titulos');
		Route::post('/representantes','AnaliseRelacaoAtrasosController@modalRepresentante')->name('analise_atrasos_equipe.modal.representantes');
		Route::post('/representantes_titulo','AnaliseRelacaoAtrasosController@modalTitulosRepresentante')->name('analise_atrasos_equipe.modal.titulo.representantes');
		Route::post('/titulos_abertura','AnaliseRelacaoAtrasosController@modalTitulosAbertura')->name('analise_atrasos_equipe.modal.titulo.titulos_abertura');
	});
});

Route::prefix('devolucao_nota')->middleware(['auth'])->group(function(){
	Route::get('/','DevolucaoNotaController@index')->name('devolucao_nota.index');
	Route::post('busca','DevolucaoNotaController@filter')->name('devolucao_nota.filter');

	Route::post('recupera_nota','DevolucaoNotaController@recuperarNota')->name('devolucao_nota.recupera_nota');
	Route::post('download_xml','DevolucaoNotaController@downloadXml')->name('devolucao_nota.download_xml');

	Route::prefix('salvar')->group(function(){
		Route::post('novo','DevolucaoNotaController@salvarNovo')->name('devolucao_nota.salvar.novo');
		Route::post('editar','DevolucaoNotaController@salvarEdicao')->name('devolucao_nota.salvar.editar');
		Route::post('excluir','DevolucaoNotaController@excluir')->name('devolucao_nota.salvar.excluir');
	});

	Route::prefix('documentos')->group(function(){
		Route::post('remover_isento','DevolucaoNotaController@removerDocumentoIsento')->name('devolucao_nota.salvar.remover_isento');
		Route::post('remover_diversos','DevolucaoNotaController@removerDocumentoDiversos')->name('devolucao_nota.salvar.remover_diversos');
		Route::post('reenviar_email_transportadora','DevolucaoNotaController@reenviarEmailTransportadora')->name('devolucao_nota.visualizar.reenviar_email_transportadora');
	});
	
	Route::prefix('modal')->group(function(){
		Route::post('novo','DevolucaoNotaController@modalNovo')->name('devolucao_nota.modal.novo');
		Route::post('editar','DevolucaoNotaController@modalEditar')->name('devolucao_nota.modal.editar');
		Route::post('visualizar','DevolucaoNotaController@visualizarModal')->name('devolucao_nota.modal.visualizar');
		Route::post('excluir','DevolucaoNotaController@modalExcluir')->name('devolucao_nota.modal.excluir');
		Route::post('logs','DevolucaoNotaController@modalLogs')->name('devolucao_nota.modal.logs');
	});

	Route::prefix('motivo')->group(function(){
		Route::get('/','DevolucaoNotaMotivoController@index')->name('devolucao_nota_motivo.index');
		Route::post('filter','DevolucaoNotaMotivoController@filter')->name('devolucao_nota_motivo.filter');

		Route::prefix('modal')->group(function(){
			Route::post('novo','DevolucaoNotaMotivoController@modalNovo')->name('devolucao_nota_motivo.modal.novo');
			Route::post('editar','DevolucaoNotaMotivoController@modalEditar')->name('devolucao_nota_motivo.modal.editar');
			Route::post('status','DevolucaoNotaMotivoController@modalStatus')->name('devolucao_nota_motivo.modal.status');
		});

		Route::prefix('salvar')->group(function(){
			Route::post('novo','DevolucaoNotaMotivoController@salvarNovo')->name('devolucao_nota_motivo.salvar.novo');
			Route::post('editar','DevolucaoNotaMotivoController@salvarEdicao')->name('devolucao_nota_motivo.salvar.editar');
			Route::post('excluir','DevolucaoNotaMotivoController@excluir')->name('devolucao_nota_motivo.salvar.excluir');
			Route::post('status','DevolucaoNotaMotivoController@salvarStatus')->name('devolucao_nota_motivo.salvar.status');
		});
	});

	Route::prefix('aprovacao')->group(function(){
		Route::get('/','DevolucaoNotaAprovacaoController@index')->name('devolucao_nota_aprovacao.index');
		Route::post('filter','DevolucaoNotaAprovacaoController@filter')->name('devolucao_nota_aprovacao.filter');

		Route::prefix('salvar')->group(function(){
			Route::post('aprovar','DevolucaoNotaAprovacaoController@aprovacao')->name('devolucao_nota_aprovacao.aprovar');
			Route::post('reprovar','DevolucaoNotaAprovacaoController@reprovacao')->name('devolucao_nota_aprovacao.reprovar');
			Route::post('cancelar','DevolucaoNotaAprovacaoController@cancelamento')->name('devolucao_nota_aprovacao.cancelar');
			Route::post('frete','DevolucaoNotaAprovacaoController@frete')->name('devolucao_nota_aprovacao.frete');

			Route::post('editar','DevolucaoNotaAprovacaoController@editar')->name('devolucao_nota_aprovacao.editar');

			Route::post('recebimento','DevolucaoNotaAprovacaoController@acusarRecebimento')->name('devolucao_nota_aprovacao.recebimento');

			
		});

		Route::prefix('modal')->group(function(){
			Route::post('aprovar','DevolucaoNotaAprovacaoController@aprovarModal')->name('devolucao_nota_aprovacao.modal.aprovar');
			Route::post('reprovar','DevolucaoNotaAprovacaoController@reprovarModal')->name('devolucao_nota_aprovacao.modal.reprovar');
			Route::post('cancelar','DevolucaoNotaAprovacaoController@cancelarModal')->name('devolucao_nota_aprovacao.modal.cancelar');
			Route::post('frete','DevolucaoNotaAprovacaoController@freteModal')->name('devolucao_nota_aprovacao.modal.frete');

			Route::post('editar','DevolucaoNotaAprovacaoController@editarModal')->name('devolucao_nota_aprovacao.modal.editar');
		});

		Route::prefix('comercial')->group(function(){
			Route::get('/','DevolucaoNotaAprovacaoController@indexGerentes')->name('devolucao_nota_aprovacao.gerente.index');
			Route::post('filter','DevolucaoNotaAprovacaoController@filterGerente')->name('devolucao_nota_aprovacao.gerente.filter');
		});

		Route::prefix('pdf')->group(function(){
			Route::get('/{id}/{tipo?}','DevolucaoNotaController@gerarPdf')->name('devolucao_nota_aprovacao.pdf.gerar');
		});

	});

	Route::prefix('status')->group(function(){
		Route::get('/','DevolucaoNotaStatusController@index')->name('devolucao_nota_status.index');
		Route::post('filter','DevolucaoNotaStatusController@filter')->name('devolucao_nota_status.filter');
		
		Route::prefix('salvar')->group(function(){
			Route::post('novo','DevolucaoNotaStatusController@novoSalvar')->name('devolucao_nota_status.salvar.novo');
			Route::post('editar','DevolucaoNotaStatusController@editarSalvar')->name('devolucao_nota_status.salvar.editar');
			Route::post('excluir','DevolucaoNotaStatusController@excluir')->name('devolucao_nota_status.salvar.excluir');
		});

		Route::prefix('modal')->group(function(){
			Route::post('novo','DevolucaoNotaStatusController@novoModal')->name('devolucao_nota_status.modal.novo');
			Route::post('editar','DevolucaoNotaStatusController@editarModal')->name('devolucao_nota_status.modal.editar');
			Route::post('usuarios','DevolucaoNotaStatusController@usuariosModal')->name('devolucao_nota_status.modal.usuarios');
			
		});
	});
});

Route::prefix('faturamento_cmv')->middleware(['auth'])->group(function(){
	Route::get('/','FaturamentoCmvController@index')->name('faturamento_vs_cmn.index');
	Route::post('/filtro','FaturamentoCmvController@filtro')->name('faturamento_vs_cmn.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/','FaturamentoCmvController@modalAbertura')->name('faturamento_vs_cmn.modal');
		Route::post('/filtro','FaturamentoCmvController@filtroModal')->name('faturamento_vs_cmn.modal.filtro');
	});
});

Route::prefix('segmentos')->middleware(['auth'])->group(function(){
	
	Route::get('/','SegmentoController@index')->name('segmentos.index');
	Route::post('/filter','SegmentoController@filter')->name('segmentos.filter');
	Route::post('/excluir','SegmentoController@excluir')->name('segmentos.excluir');
	
	Route::prefix('salvar')->group(function(){
		Route::post('/novo','SegmentoController@salvarNovo')->name('segmentos.salvar.novo');
		Route::post('/edicao','SegmentoController@salvarEdicao')->name('segmentos.salvar.edicao');
	});

	Route::prefix('modal')->group(function(){
		Route::post('/novo','SegmentoController@modalNovo')->name('segmentos.modal.novo');
		Route::post('/editar','SegmentoController@modalEditar')->name('segmentos.modal.editar');
		Route::post('/deletar','SegmentoController@modalDeletar')->name('segmentos.modal.deletar');
	});
});

Route::prefix('consulta_devolucao')->middleware(['auth'])->group(function(){
	Route::get('/','ConsultaDeDevolucaoController@index')->name('consulta_devolucao.index');
	Route::post('filter', 'ConsultaDeDevolucaoController@filter')->name('consulta_devolucao.filter');

	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/', 'ConsultaDeDevolucaoController@modalAnalitica')->name('consulta_devolucao.modal');
	});
});

Route::prefix('compras_recebimento')->middleware(['auth'])->group(function(){
	Route::get('/','RecebimentosComprasController@index')->name('recebimentos_compras.index');
	Route::post('/filtro','RecebimentosComprasController@filter')->name('recebimentos_compras.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/busca_notas_pedido','RecebimentosComprasController@buscarNota')->name('busca_notas_pedido.abertura');
	});
});

Route::prefix('notas_entradas_nasajon')->middleware(['auth'])->group(function(){

	Route::get('/','NotasEntradasNasajonController@index')->name('notas_entradas_nasajon.index');
	Route::post('filter','NotasEntradasNasajonController@filter')->name('notas_entradas_nasajon.filter');

	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/notas_entradas','NotasEntradasNasajonController@exibirNota')->name('notas_entradas_nasajon.nota');
		Route::post('/notas_entradas_busca','NotasEntradasNasajonController@exibirNotaBusca')->name('notas_entradas_nasajon.modal.exibir_busca');
	});
});


Route::prefix('unidade_negocio')->middleware(['auth'])->group(function(){
	Route::get('/','UnidadeNegocioController@index')->name('unidade_negocio.index');
	Route::post('/adicionar', 'UnidadeNegocioController@adicionar')->name('unidade_negocio.adicionar');
	Route::post('/filtro', 'UnidadeNegocioController@filtro')->name('unidade_negocio.filtro');
	Route::post('/editar', 'UnidadeNegocioController@editar')->name('unidade_negocio.editar');
	Route::post('/deletar', 'UnidadeNegocioController@deletar')->name('unidade_negocio.deletar');
	Route::post('/autocomplete', 'UnidadeNegocioController@autoComplete')->name('unidade_negocio.autocomplete'); 

	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'UnidadeNegocioController@modalAdicionar')->name('unidade_negocio.modal.adicionar');
		Route::post('/editar', 'UnidadeNegocioController@modalEditar')->name('unidade_negocio.modal.editar');
		Route::post('/deletar', 'UnidadeNegocioController@modalDeletar')->name('unidade_negocio.modal.deletar');
		Route::post('/buscar', 'UnidadeNegocioController@modalBuscar')->name('unidade_negocio.modal.buscar');
	});

	Route::prefix('metas')->group(function(){
		Route::get('/', 'UnidadeNegocioMetaController@index')->name('unidade_negocio.metas.index');
		Route::post('/adicionar_representante', 'UnidadeNegocioMetaController@adicionarRepresentante')->name('unidade_negocio.metas.adicionar_representante');
		Route::post('/deletar_representante', 'UnidadeNegocioMetaController@deletarRepresentante')->name('unidade_negocio.metas.deletar_representante');
		Route::post('/adicionar', 'UnidadeNegocioMetaController@adicionar')->name('unidade_negocio.metas.adicionar');
		Route::post('/editar', 'UnidadeNegocioMetaController@editar')->name('unidade_negocio.metas.editar');
		Route::post('/deletar', 'UnidadeNegocioMetaController@deletar')->name('unidade_negocio.metas.deletar');
		Route::post('/get_representante_por_unidade_negocio_meta', 'UnidadeNegocioMetaController@getRepresentantesPorUnidadeNegocioMeta')->name('unidade_negocio.metas.get_representante_por_unidade_negocio_meta');
		Route::post('/filter', 'UnidadeNegocioMetaController@filterMetas')->name('unidade_negocio.metas.filter');
		Route::prefix('modal')->group(function(){
			Route::get('/adicionar', 'UnidadeNegocioMetaController@modalAdicionar')->name('unidade_negocio.metas.modal.adicionar');
			Route::post('/editar', 'UnidadeNegocioMetaController@modalEditar')->name('unidade_negocio.metas.modal.editar');
			Route::post('/deletar', 'UnidadeNegocioMetaController@modalDeletar')->name('unidade_negocio.metas.modal.deletar');
		});
	});
});

Route::prefix('book_virtual_exibicao')->middleware(['auth'])->group(function(){
	Route::get('/','BookVirtualExibicaoController@index')->name('book_virtual_exibicao.index');
	Route::get('/busca/{segmentos_id}/{categoria}/{filter?}','BookVirtualExibicaoController@busca')->name('book_virtual_exibicao.busca');
	Route::post('filter','BookVirtualExibicaoController@filter')->name('book_virtual_exibicao.filter');

	Route::get('book/{filter}/{id}','BookVirtualExibicaoController@bookVirtualExibicao')->name('book_virtual_exibicao.exibicao');
	Route::post('produtos','BookVirtualExibicaoController@retornaInfoProdutos')->name('book_virtual_exibicao.produtos');

	Route::get('book/pdf/{filter}/{id}','BookVirtualExibicaoController@bookVirtualPdf')->name('book_virtual_exibicao.pdf');

	Route::post('gerar_link_busca', 'BookVirtualExibicaoController@gerarLinkBusca')->name('book_virtual_exibicao.gerar_link_busca');
	Route::post('gerar_link_busca_produtos', 'BookVirtualExibicaoController@gerarLinkBuscaProdutos')->name('book_virtual_exibicao.gerar_link_busca_produtos');

	Route::get('busca_produtos/{filter}', 'BookVirtualExibicaoController@buscaProdutos')->name('book_virtual_exibicao.busca_produtos');	

	Route::post('autocomplete', 'BookVirtualExibicaoController@autocomplete')->name('book_virtual_exibicao.autocomplete');
	Route::post('fitro_precos', 'BookVirtualExibicaoController@filtroEstoquePrecos')->name('book_virtual_exibicao.filtro_precos');
	Route::post('fitro_carrinho', 'BookVirtualExibicaoController@filtroCarrinho')->name('book_virtual_exibicao.filtro');
	Route::post('adicionar', 'BookVirtualExibicaoController@adicionarCarrinho')->name('book_virtual_exibicao.adicionar');
	Route::post('adicionar_transportadora', 'BookVirtualExibicaoController@editarTransportadora')->name('book_virtual_exibicao.editar_transportadora');
	Route::post('atualizar_carrinho', 'BookVirtualExibicaoController@atualizarItensCarrinho')->name('book_virtual_exibicao.atualizar_carrinho');
	Route::post('adicionar_cliente', 'BookVirtualExibicaoController@adicionarCliente')->name('book_virtual_exibicao.adicionar_cliente');
	Route::post('editar_cliente', 'BookVirtualExibicaoController@editarCliente')->name('book_virtual_exibicao.editar_cliente');
	Route::post('finalizar', 'BookVirtualExibicaoController@finalizarCarrinho')->name('book_virtual_exibicao.finalizar');
	Route::post('deletar_item', 'BookVirtualExibicaoController@deletarItemCarrinho')->name('book_virtual_exibicao.deletar_item');
	Route::post('cancelar_pedido', 'BookVirtualExibicaoController@cancelarPedidoCarrinho')->name('book_virtual_exibicao.cancelar_pedido');
	Route::post('excluir_carrinho', 'BookVirtualExibicaoController@excluirCarrinho')->name('book_virtual_exibicao.excluir_carrinho');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'BookVirtualExibicaoController@modalAdicionar')->name('book_virtual_exibicao.modal.adicionar');
		Route::post('/adicionar_transportadora', 'BookVirtualExibicaoController@modalAdicionarTransportadora')->name('book_virtual_exibicao.modal.adicionar_transportadora');
		Route::post('/editar_transportadora', 'BookVirtualExibicaoController@modalEditarTransportadora')->name('book_virtual_exibicao.modal.editar_transportadora');
		Route::post('/editar', 'BookVirtualExibicaoController@modalEditarCarrinho')->name('book_virtual_exibicao.modal.editar');
		Route::post('/deletar', 'BookVirtualExibicaoController@modalDeletarCarrinho')->name('book_virtual_exibicao.modal.deletar');
		Route::post('/cancelar', 'BookVirtualExibicaoController@modalCancelarPedidoCarrinho')->name('book_virtual_exibicao.modal.cancelar');
		Route::post('/excluir', 'BookVirtualExibicaoController@modalExcluirCarrinho')->name('book_virtual_exibicao.modal.excluir');
	});
});


Route::prefix('mapa_venda')->middleware(['auth'])->group(function(){
	Route::get('/', 'MapaVendaController@index')->name('mapa_venda.index');
	Route::post('/filtro', 'MapaVendaController@filtro')->name('mapa_venda.filtro');
	Route::post('/filtro_grupos', 'MapaVendaController@filtroGrupo')->name('mapa_venda.filtro_grupos');
	Route::post('/filtro_produtos', 'MapaVendaController@filtroGrupo')->name('mapa_venda.filtro_produtos');
	Route::post('/filtro_devolucao_produtos', 'MapaVendaController@filtroDevolucaoProduto')->name('mapa_venda.filtro_devolucao_produtos');
	Route::post('/filtro_devolucao_clientes', 'MapaVendaController@filtroDevolucaoCliente')->name('mapa_venda.filtro_devolucao_clientes');
	Route::post('/filtro_clientes', 'MapaVendaController@filtroCliente')->name('mapa_venda.filtro_clientes');
	Route::prefix('/modal')->group(function(){
		Route::post('/equipe', 'MapaVendaController@modalEquipe')->name('mapa_venda.modal.equipe');
		Route::post('/equipe_individual', 'MapaVendaController@modalEquipeIndividual')->name('mapa_venda.modal.equipe_individual');
		Route::post('/cliente', 'MapaVendaController@modalCliente')->name('mapa_venda.modal.cliente');
		Route::post('/produto', 'MapaVendaController@modalProduto')->name('mapa_venda.modal.produto');
		Route::post('/devolucao', 'MapaVendaController@modalDevolucao')->name('mapa_venda.modal.devolucao');
		Route::post('/grupo', 'MapaVendaController@modalGrupo')->name('mapa_venda.modal.grupo');
	});
	Route::prefix('/excecao')->group(function(){
		Route::get('/', 'MapaVendaExcecaoController@index')->name('mapa_venda.excecao.index');
		Route::post('/adicionar', 'MapaVendaExcecaoController@adicionar')->name('mapa_venda.excecao.adicionar');
		Route::post('/editar', 'MapaVendaExcecaoController@editar')->name('mapa_venda.excecao.editar');
		Route::post('/deletar', 'MapaVendaExcecaoController@deletar')->name('mapa_venda.excecao.deletar');
		Route::post('/filtro', 'MapaVendaExcecaoController@filtro')->name('mapa_venda.excecao.filtro');
		Route::post('/get_cliente', 'MapaVendaExcecaoController@getClienteNota')->name('mapa_venda.excecao.get_cliente');
		Route::prefix('/modal')->group(function(){
			Route::get('/adicionar', 'MapaVendaExcecaoController@modalAdicionar')->name('mapa_venda.excecao.modal.adicionar');
			Route::post('/editar', 'MapaVendaExcecaoController@modalEditar')->name('mapa_venda.excecao.modal.editar');
			Route::post('/deletar', 'MapaVendaExcecaoController@modalDeletar')->name('mapa_venda.excecao.modal.deletar');
		});
	});
});

Route::prefix('retornos_cobrancas')->middleware(['auth'])->group(function(){
	Route::get('/','RetornoCobrancaController@index')->name('retornos_cobrancas.index');
	Route::post('/adicionar','RetornoCobrancaController@adicionar')->name('retornos_cobrancas.adicionar');
	Route::post('/filter', 'RetornoCobrancaController@filtrar')->name('retornos_cobrancas.filtrar');
	Route::post('/editar', 'RetornoCobrancaController@editar')->name('retornos_cobrancas.editar');
	Route::post('/deletar', 'RetornoCobrancaController@deletar')->name('retornos_cobrancas.deletar');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'RetornoCobrancaController@modalAdicionar')->name('retornos_cobrancas.modal.adicionar');
		Route::get('/editar', 'RetornoCobrancaController@modalEditar')->name('retornos_cobrancas.modal.editar');
		Route::get('/deletar', 'RetornoCobrancaController@modalDeletar')->name('retornos_cobrancas.modal.deletar');
	});
});

Route::prefix('historico_financeiro')->middleware(['auth'])->group(function () {
	Route::prefix('/modal')->group(function(){
		Route::post('/adicionar', 'HistoricoFinanceiroClienteController@modalAdcionar')->name('historico_financeiro.modal.adicionar');
		Route::post('/titulos', 'HistoricoFinanceiroClienteController@modalTitulos')->name('historico_financeiro.modal.titulos');
	});
	Route::post('/adicionar', 'HistoricoFinanceiroClienteController@adcionar')->name('historico_financeiro.adicionar');
	Route::post('/busca', 'HistoricoFinanceiroClienteController@retornoHistorico')->name('historico_financeiro.busca');
});

Route::prefix('status_projeto')->middleware(['auth'])->group(function(){
	Route::get('/','StatusProjetoExibicaoController@index')->name('status_projeto.index');
	Route::post('/adicionar','StatusProjetoExibicaoController@adicionar')->name('status_projeto.adicionar');
	Route::post('/filter', 'StatusProjetoExibicaoController@filtrar')->name('status_projeto.filtrar');
	Route::post('/editar', 'StatusProjetoExibicaoController@editar')->name('status_projeto.editar');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'StatusProjetoExibicaoController@modalAdicionar')->name('status_projeto.modal.adicionar');
		Route::get('/editar', 'StatusProjetoExibicaoController@modalEditar')->name('status_projetos.modal.editar');
	});
});

Route::prefix('fases_projeto')->middleware(['auth'])->group(function(){
	Route::get('/','StatusProjetoController@index')->name('fases_projeto.index');
	Route::post('/adicionar','StatusProjetoController@adicionar')->name('fases_projeto.adicionar');
	Route::post('/filter', 'StatusProjetoController@filtrar')->name('fases_projeto.filtrar');
	Route::post('/editar', 'StatusProjetoController@editar')->name('fases_projeto.editar');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'StatusProjetoController@modalAdicionar')->name('fases_projeto.modal.adicionar');
		Route::get('/editar', 'StatusProjetoController@modalEditar')->name('fases_projetos.modal.editar');
	});
});

Route::prefix('notas_entrada_prologos')->middleware(['auth'])->group(function () {
	Route::prefix('modal')->group(function(){
		Route::post('/', 'NotasEntradaPrologosController@modal')->name('notas_entrada_prologos.modal');
	});
});

Route::prefix('historico_cobranca')->middleware(['auth'])->group(function () {
	Route::get('/', 'HistoricoFinanceiroClienteController@indexHistorico')->name('historico_cobranca.index');
	Route::post('filter', 'HistoricoFinanceiroClienteController@filterHistorico')->name('historico_cobranca.filter');
	
	Route::prefix('modal')->group(function(){
		Route::post('/', 'HistoricoFinanceiroClienteController@modalHistorico')->name('historico_cobranca.modal.index');
		Route::post('titulos', 'HistoricoFinanceiroClienteController@modalTituloDetalhes')->name('historico_cobranca.modal.titulo');
	});
});

Route::prefix('acompanhamento_comissao')->middleware(['auth'])->group(function () {
	Route::get('/', 'AcompanhamentoComissaoController@index')->name('acompanhamento_comissao.index');
	Route::post('/filtro', 'AcompanhamentoComissaoController@filtro')->name('acompanhamento_comissao.filtro');
	Route::post('/verificacao_gerar_integracao_com_folha', 'AcompanhamentoComissaoController@verificacaoGeracaoIntegracaoComFolha')->name('acompanhamento_comissao.verificacao_gerar_integracao_com_folha');
	Route::post('/gerar_integracao_com_folha', 'AcompanhamentoComissaoController@gerarIntegracaoComFolha')->name('acompanhamento_comissao.gerar_integracao_com_folha');
	Route::post('/atualizar_cadastro_user_folha', 'AcompanhamentoComissaoController@atualizarCadastroUserFolha')->name('acompanhamento_comissao.atualizar_cadastro_user_folha');
	Route::prefix('/modal')->group(function(){
		Route::post('/membro', 'AcompanhamentoComissaoController@modalMembros')->name('acompanhamento_comissao.modal.membro');
		Route::post('/cadastro_folha', 'AcompanhamentoComissaoController@modalCadastroFolha')->name('acompanhamento_comissao.modal.cadastro_folha');
	});
});

Route::prefix('titulos_apagar')->middleware(['auth'])->group(function(){
	Route::get('/','TitulosAPagarNasajonController@index')->name('titulos_apagar.index');
	Route::post('/filter', 'TitulosAPagarNasajonController@filtrar')->name('titulos_apagar.filtrar');
	Route::prefix('modal')->group(function(){
		Route::post('/', 'TitulosAPagarNasajonController@modalFornecedor')->name('titulos_apagar.modal.fornecedor');
		Route::post('/titulos_abertura','TitulosAPagarNasajonController@modalTitulosAbertura')->name('titulos_apagar.modal.titulos_abertura');
		Route::post('/titulos','TitulosAPagarNasajonController@modalTitulos')->name('titulos_apagar.modal.titulos');
	});
});

Route::prefix('renegociacao_titulos')->middleware(['auth'])->group(function(){
	Route::get('/','ProrrogacaoTitulosController@index')->name('prorrogacao_titulos.index');
	Route::post('/busca_valores','ProrrogacaoTitulosController@buscaValores')->name('prorrogacao_titulos.busca_valores');
	Route::post('/processar_titulos','ProrrogacaoTitulosController@processarTitulos')->name('prorrogacao_titulos.processar_titulos');
});

Route::prefix('notas_importadas')->middleware(['auth'])->group(function(){
	Route::get('/','NotasImportadasEntradasController@index')->name('consulta_notas_importadas.index');
	Route::post('/filtro','NotasImportadasEntradasController@filter')->name('consulta_notas_importadas.filter');
	Route::post('/pdf','NotasImportadasEntradasController@gerarPdf')->name('consulta_notas_importadas.pdf');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/notas_estabelecimento','NotasImportadasEntradasController@modalNotas')->name('notas_importadas.modal.notas');
		Route::post('/notas_total','NotasImportadasEntradasController@modalNotasTotal')->name('notas_importadas.modal.notas.total');
		Route::post('/notas_entradas_importadas','NotasImportadasEntradasController@exibirNotaImportada')->name('modal.notas.importadas');
		Route::post('/cte','NotasImportadasEntradasController@exibirCfop')->name('notas_importadas.modal.notas.cte');
        Route::post('/download_xml','NotasImportadasEntradasController@downloadXML')->name('notas_importadas.modal.notas.download_xml');
	});
});

Route::prefix('transporte_frete')->middleware(['auth'])->group(function(){
	Route::get('/','ConsultaTransportadorasFretesController@index')->name('consulta_transportadora_frete.index');
	Route::post('/filter','ConsultaTransportadorasFretesController@filter')->name('consulta_transportadora_frete.filter');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/','FaturamentoCmvController@modalAbertura')->name('faturamento_vs_cmn.modal');
		Route::post('/lancadas','ConsultaTransportadorasFretesController@modalTitulosLancadas')->name('consulta_transportadora_frete.modal.lancadas');
		Route::post('/notas','ConsultaTransportadorasFretesController@modalNotasMN')->name('consulta_transportadora_frete.modal.notas');
		Route::post('/emitidas','ConsultaTransportadorasFretesController@modalTitulosEmitidos')->name('consulta_transportadora_frete.modal.emitidas');
	});
});

Route::prefix('cliente_bionexo')->middleware(['auth'])->group(function(){
	Route::get('/','ClienteBionexoController@index')->name('cliente_bionexo.index');
	Route::post('/adicionar','ClienteBionexoController@ClienteAdicionar')->name('cliente_bionexo.adicionar');
	Route::post('/editar','ClienteBionexoController@ClienteEditar')->name('cliente_bionexo.editar');
	Route::post('/deletar','ClienteBionexoController@ClienteDeletar')->name('cliente_bionexo.deletar');
	Route::post('/filter','ClienteBionexoController@ClienteFilter')->name('cliente_bionexo.filter');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'ClienteBionexoController@ModalAdicionar')->name('cliente_bionexo.modal.adicionar');
		Route::get('/editar', 'ClienteBionexoController@ModalEditar')->name('cliente_bionexo.modal.editar');
		Route::get('/deletar', 'ClienteBionexoController@ModalDeletar')->name('cliente_bionexo.modal.deletar');
	});
});

Route::prefix('analise_preco')->middleware(['auth'])->group(function(){
	Route::get('/', 'AnaliseDePrecoController@index')->name('analise_preco.index');
	Route::post('/filter', 'AnaliseDePrecoController@filter')->name('analise_preco.filter');
	Route::prefix('modal')->group(function(){
		Route::post('/estoque', 'AnaliseDePrecoController@produtoEstoque')->name('analise_preco.modal.estoque');
		Route::post('/produtos', 'AnaliseDePrecoController@modalProdutos')->name('analise_preco.modal.produtos');
	});
});

Route::prefix('comissao_duplicatas')->middleware(['auth'])->group(function(){
	Route::get('/','ComissaoDuplicatasController@index')->name('comissao_duplicatas.index');
	Route::post('filter','ComissaoDuplicatasController@filter')->name('comissao_duplicatas.filtro');
	Route::post('aprovar','ComissaoDuplicatasController@aprovarComissao')->name('comissao_duplicatas.aprovar');
	Route::prefix('modal')->group(function(){
		Route::post('dialog','ComissaoDuplicatasController@dialog')->name('comissao_duplicatas.dialog');
		Route::post('abertos','ComissaoDuplicatasController@titulosAbertosModal')->name('comissao_duplicatas.abertos');
		Route::post('pedido','ComissaoDuplicatasController@retornarPedido')->name('comissao_duplicatas.modal.retorna_pesquisa');
		Route::post('pdf','ComissaoDuplicatasController@gerarArquivoPdf')->name('comissao_duplicatas.modal.pdf');
		Route::post('pdfAbertos','ComissaoDuplicatasController@gerarArquivoAbertosPdf')->name('comissao_duplicatas.modal.abertos_pdf');
		Route::post('editar','ComissaoDuplicatasController@alterarComissaoDuplicataModal')->name('comissao_duplicatas.modal.editar');
	});

	Route::post('salvar_comissao_duplicata','ComissaoDuplicatasController@salvarComissaoDuplicata')->name('comissao_duplicatas.duplicata.salvar_comissao_duplicata');	
});
		
Route::prefix('duplicatas_cheque_banco')->middleware(['auth'])->group(function(){
	Route::get('/','DuplicatasChequeBancoController@index')->name('duplicatas_cheque_banco.index');
	Route::post('/filter','DuplicatasChequeBancoController@filter')->name('duplicatas_cheque_banco.filter');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/cliente','DuplicatasChequeBancoController@modalCLiente')->name('duplicatas_cheque_banco.modal.cliente');
		Route::post('/titulos','DuplicatasChequeBancoController@modalTitulos')->name('duplicatas_cheque_banco.modal.titulos');
		Route::post('/representantes','DuplicatasChequeBancoController@modalRepresentante')->name('duplicatas_cheque_banco.modal.representantes');
		Route::post('/representantes_titulo','DuplicatasChequeBancoController@modalTitulosRepresentante')->name('duplicatas_cheque_banco.modal.titulo.representantes');
		Route::post('/titulos_abertura','DuplicatasChequeBancoController@modalTitulosAbertura')->name('duplicatas_cheque_banco.modal.titulo.titulos_abertura');
	});
});

Route::prefix('coleta_canhoto')->middleware(['auth'])->group(function(){
	Route::get('/','ColetaCanhotoController@index')->name('coleta_canhoto.index');
	Route::post('/gravar','ColetaCanhotoController@gravar')->name('coleta_canhoto.gravar');
	Route::post('/busca_nota','ColetaCanhotoController@buscaNota')->name('coleta_canhoto.busca_nota');
});

Route::prefix('controle_pilotagem')->middleware(['auth'])->group(function(){
	Route::get('/','ControlePilotagemController@index')->name('controle_pilotagem.index');
	Route::post('/filter','ControlePilotagemController@filter')->name('controle_pilotagem.filter');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/produto','ControlePilotagemController@aberturaProduto')->name('controle_pilotagem.modal.produto');
		Route::post('/cliente','ControlePilotagemController@aberturaCliente')->name('controle_pilotagem.modal.cliente');
		Route::post('/produto_acumulado','ControlePilotagemController@aberturaProdutoAcumulado')->name('controle_pilotagem.modal.produto_acumulado');
	});
});

Route::prefix('contrato')->middleware(['auth'])->group(function(){
	Route::prefix('cliente')->group(function(){
		Route::get('/','ContratoController@indexCliente')->name('contrato.cliente.index');
		Route::post('/aceito','ContratoController@aceitoCliente')->name('contrato.cliente.aceito');
		Route::post('/recusado','ContratoController@recusadoCliente')->name('contrato.cliente.recusado');
	});
	Route::prefix('fornecimento')->group(function(){
		Route::get('/','ContratoController@contratoFornecimento')->name('contrato.fornecimento.index');
	});
});

Route::prefix('acompanhamento_cielo')->middleware(['auth'])->group(function(){
	Route::get('/','AcompanhamentoCieloController@index')->name('acompanhamento_cielo.index');
	Route::post('/filtro','AcompanhamentoCieloController@filtro')->name('acompanhamento_cielo.filtro');
	Route::post('/filtro_pagos','AcompanhamentoCieloController@filtroPagos')->name('acompanhamento_cielo.filtro_pagos');
	Route::post('/cancelar_pedido','AcompanhamentoCieloController@cancelarPedido')->name('acompanhamento_cielo.cacelar_pedido');
	Route::post('/liberar_pedido','AcompanhamentoCieloController@liberarPedido')->name('acompanhamento_cielo.liberar_pedido');
	Route::post('/envair_email','AcompanhamentoCieloController@enviarEmail')->name('acompanhamento_cielo.envair_email');
	Route::post('/estorno','AcompanhamentoCieloController@pagarmeEstorno')->name('acompanhamento_cielo.estorno');
	Route::prefix('modal')->group(function(){
		Route::post('/em_aberto','AcompanhamentoCieloController@modalEmAberto')->name('acompanhamento_cielo.modal.em_aberto');
		Route::post('/em_aberto_diferenca','AcompanhamentoCieloController@modalEmAbertoDiferenca')->name('acompanhamento_cielo.modal.em_aberto_diferenca');
		Route::post('/pago','AcompanhamentoCieloController@modalPago')->name('acompanhamento_cielo.modal.pago');

		Route::post('/pagamentos_parciais','AcompanhamentoCieloController@pagamentosParciais')->name('acompanhamento_cielo.modal.pagamentos_parciais');

		Route::post('/estorno','AcompanhamentoCieloController@modalEstorno')->name('acompanhamento_cielo.modal.estorno');

		Route::post('/pagos_filtro','AcompanhamentoCieloController@modalPagoFiltro')->name('acompanhamento_cielo.modal.pagos_filtro');
		Route::post('/erros','AcompanhamentoCieloController@modalErros')->name('acompanhamento_cielo.modal.erros');

		Route::post('/pix_sem_nota','AcompanhamentoCieloController@pixSemNota')->name('acompanhamento_cielo.modal.pix_sem_nota');
		Route::post('/pix_com_nota','AcompanhamentoCieloController@pixComNota')->name('acompanhamento_cielo.modal.pix_com_nota');
		Route::post('/boleto_sem_nota','AcompanhamentoCieloController@boletoSemNota')->name('acompanhamento_cielo.modal.boleto_sem_nota');
		Route::post('/boleto_com_nota','AcompanhamentoCieloController@boletoComNota')->name('acompanhamento_cielo.modal.boleto_com_nota');

		Route::post('/venda_presencial','AcompanhamentoCieloController@vendaPresencial')->name('acompanhamento_cielo.modal.venda_presencial');

		Route::post('/credito_sem_nota','AcompanhamentoCieloController@modalCreditoSemNota')->name('acompanhamento_cielo.modal.credito_sem_nota');
		Route::post('/credito_com_nota','AcompanhamentoCieloController@modalCreditoComNota')->name('acompanhamento_cielo.modal.credito_com_nota');
		Route::post('/debito_sem_nota','AcompanhamentoCieloController@modalDebitoSemNota')->name('acompanhamento_cielo.modal.debito_sem_nota');
		Route::post('/debito_com_nota','AcompanhamentoCieloController@modalDebitoComNota')->name('acompanhamento_cielo.modal.debito_com_nota');

		Route::post('/listar_estornos','AcompanhamentoCieloController@listarEstornos')->name('acompanhamento_cielo.modal.listar_estornos');
	});
});

Route::prefix('pedido_online')->group(function(){
	Route::get('/{token}','PedidoCieloIntegracaoController@pagamentoOnline')->name('pedido_online.pagamento');
	Route::get('/restante/{token}','PedidoCieloIntegracaoController@pagamentoOnlineRestante')->name('pedido_online.pagamento_restante');
	Route::post('/pagar','PedidoCieloIntegracaoController@pagar')->name('pedido_online.pagar');
	Route::post('/pagar_restante','PedidoCieloIntegracaoController@pagarRestante')->name('pedido_online.pagar_restante');
	Route::post('/itens_pedido','PedidoCieloIntegracaoController@itensPedido')->name('pedido_online.itens_pedido');
});

Route::prefix('vendas_pcmn')->middleware(['auth'])->group(function(){
	Route::get('/','AnaliseVendasPcmnController@index')->name('analise_vendas_pcmn.index');
	Route::post('/filter','AnaliseVendasPcmnController@filter')->name('analise_vendas_pcmn.filter');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/produto','AnaliseVendasPcmnController@mediaVenda')->name('analise_vendas_pcmn.modal.media_mes');
	});
});

Route::prefix('beneficio_representante_frete_devolucao')->middleware(['auth'])->group(function(){
	Route::get('/','BeneficioRepresentanteFreteDevolucaoController@indexFreteDevolucao')->name('beneficio_representante.frete_devolucao.index');
	Route::post('/filtro','BeneficioRepresentanteFreteDevolucaoController@filtroFreteDevolucao')->name('beneficio_representante.frete_devolucao.filtro');
	Route::post('/filtro_devolucao_produtos','BeneficioRepresentanteFreteDevolucaoController@filtroDevolucaoProdutoFreteDevolucao')->name('beneficio_representante.frete_devolucao.filtro_devolucao_produtos');
	Route::post('/filtro_devolucao_clientes','BeneficioRepresentanteFreteDevolucaoController@filtroDevolucaoClienteFreteDevolucao')->name('beneficio_representante.frete_devolucao.filtro_devolucao_clientes');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/devolucao','BeneficioRepresentanteFreteDevolucaoController@modalDevolucaoFreteDevolucao')->name('beneficio_representante.frete_devolucao.modal.devolucao');
	});
});

Route::prefix('comissao_ragazzi')->middleware(['auth'])->group(function(){
	Route::get('/','ComissaoRagazziController@index')->name('comissao_ragazzi.index');
	Route::post('/filtro','ComissaoRagazziController@filtro')->name('comissao_ragazzi.filtro');
	Route::get('/index_novo','ComissaoRagazziController@indexNovo')->name('comissao_ragazzi.index_novo');
	Route::post('/filtro_novo','ComissaoRagazziController@filtroNovo')->name('comissao_ragazzi.filtro_novo');
});

Route::prefix('controle_geral_expedicao')->middleware(['auth'])->group(function(){
	Route::get('/', 'ControleGeralExpedicaoController@index')->name('controle_geral_expedicao.index');
	Route::post('/filtro', 'ControleGeralExpedicaoController@filtro')->name('controle_geral_expedicao.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/notas_a_conferir','ControleGeralExpedicaoController@modalNotasConferir')->name('controle_geral_expedicao.modal.notas_a_conferir');
		Route::post('/notas_conferida','ControleGeralExpedicaoController@modalNotasConferida')->name('controle_geral_expedicao.modal.notas_conferida');
		Route::post('/pedidos_conferidos','ControleGeralExpedicaoController@modalPedidosConferidos')->name('controle_geral_expedicao.modal.pedidos_conferidos');
		Route::post('/pedidos_a_conferir','ControleGeralExpedicaoController@modalPedidosConferir')->name('controle_geral_expedicao.modal.pedidos_a_conferir');
		Route::post('/exibir_nota','ControleGeralExpedicaoController@modalNotaConferencia')->name('controle_geral_expedicao.modal.exibir_nota_conferencia');
	});
});

Route::prefix('comissao_venda_interna')->middleware(['auth'])->group(function(){
	Route::get('/','ComissaoVendaInternaController@index')->name('comissao_venda_interna.index');
	Route::post('/filtro', 'ComissaoVendaInternaController@filtro')->name('comissao_venda_interna.filtro');
	Route::prefix('/modal')->group(function(){
		Route::post('/membro', 'ComissaoVendaInternaController@modalMembros')->name('comissao_venda_interna.modal.membro');
	});
});

Route::prefix('desconto_representante')->middleware(['auth'])->group(function(){
	Route::prefix('/modal')->group(function(){
		Route::post('/','DescontoRepresentanteController@exibicao')->name('desconto_representante.exibicao');
		Route::post('detalhes','DescontoRepresentanteController@titulosDetalhes')->name('desconto_representante.titulos');
		Route::post('pdf','DescontoRepresentanteController@gerarArquivoAbertosPdf')->name('desconto_representante.modal.abertos_pdf');
		Route::post('xlsx','DescontoRepresentanteController@gerarArquivoAbertosExcel')->name('desconto_representante.modal.abertos_xlsx');

		Route::post('gerar','DescontoRepresentanteController@lancarDebitos')->name('desconto_representante.lancar_debitos');
	});
});

Route::prefix('estatisticas_vendas')->middleware(['auth'])->group(function(){
	Route::get('/','EstatisticasVendasController@index')->name('estatisticas_vendas.index');
	Route::post('/filtro','EstatisticasVendasController@filtro')->name('estatisticas_vendas.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/forma_pagamento','EstatisticasVendasController@modalFormaPagamento')->name('estatisticas_vendas.modal.forma_pagamento');
		Route::post('/linha','EstatisticasVendasController@modalLinha')->name('estatisticas_vendas.modal.linha');
		Route::post('/grupo','EstatisticasVendasController@modalGrupo')->name('estatisticas_vendas.modal.grupo');
		Route::post('/marca','EstatisticasVendasController@modalMarca')->name('estatisticas_vendas.modal.marca');
	});
});

Route::prefix('cenprot')->middleware(['auth'])->group(function(){
	Route::get('/','CenprotController@index')->name('cenprot.index');
	Route::post('/enviar_titulo', 'CenprotController@enviarTitulo')->name('cenprot.enviar_titulo');
	Route::post('/remover_titulo', 'CenprotController@remocaoTitulo')->name('cenprot.remover_titulo');
	Route::post('/filter', 'CenprotController@filter')->name('cenprot.filter');
	Route::post('/reenviar_titulo', 'CenprotController@reenvioTitulo')->name('cenprot.reenviar_titulo');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/historico','CenprotController@modalHistorico')->name('cenprot.modal.historico');
	});
});

Route::prefix('ocorrencia_de_entrega')->middleware(['auth'])->group(function(){
	Route::get('/','OcorrenciasDeEntregaController@index')->name('consulta_fatura_transportadora.index');
	Route::post('filtro/','OcorrenciasDeEntregaController@filtro')->name('consulta_fatura_transportadora.filtro');
	Route::post('/aprovar_fatura', 'OcorrenciasDeEntregaController@aprovarFatura')->name('ocorrencia_entrega.aprovar_fatura');
	Route::prefix('modal')->group(function(){
		Route::post('/ocorrencia', 'OcorrenciasDeEntregaController@modalOcorrenciaEntrega')->name('ocorrencia_entrega.modal.abertura');
		Route::post('/faturas', 'OcorrenciasDeEntregaController@modalFaturas')->name('ocorrencia_entrega.modal.faturas');
		Route::post('/exibir_fatura', 'OcorrenciasDeEntregaController@modalExibirFatura')->name('ocorrencia_entrega.modal.exibir_fatura');
	});
});

Route::prefix('premiacao')->middleware(['auth'])->group(function(){
	Route::get('/','PremiacaoController@index')->name('premiacao.index');
	Route::post('/filtro','PremiacaoController@filtro')->name('premiacao.filtro');
	Route::post('/filtro_representante','PremiacaoController@filtroRepresentante')->name('premiacao.filtro_representante');
	Route::post('/aprovar','PremiacaoController@aprovarPremio')->name('premiacao.aprovar');
	Route::prefix('/modal')->group(function(){
		Route::post('/membro', 'PremiacaoController@modalMembros')->name('premiacao.modal.membro');
	});
});

Route::prefix('mensagem')->middleware(['auth'])->group(function(){
	Route::get('/','MensagemController@index')->name('mensagem.index');
	Route::post('/filtro','MensagemController@filtro')->name('mensagem.filtro');

	Route::post('/adicionar','MensagemController@salvar')->name('mensagem.adicionar');
	Route::post('/excluir','MensagemController@excluir')->name('mensagem.excluir');
	Route::post('/editar','MensagemController@editar')->name('mensagem.editar');

	Route::prefix('modal')->group(function(){
		Route::post('/adicionar','MensagemController@modalAdicionar')->name('mensagem.modal.adicionar');
		Route::post('/editar','MensagemController@modalEditar')->name('mensagem.modal.editar');
	});
});

Route::prefix('tipo_usuario')->middleware(['auth'])->group(function(){
	Route::post('/filtro_modal','TipoUsuarioController@filtroModal')->name('tipo_usuario.filter_modal');
	Route::prefix('/modal')->group(function(){
		Route::get('/', 'TipoUsuarioController@modalBusca')->name('tipo_usuario.modal.busca');
	});
});

Route::prefix('controle_devolucao')->middleware(['auth'])->group(function(){
	Route::get('/','ControleDevolucaoController@index')->name('controle_devolucao.index');
	Route::post('/filtro','ControleDevolucaoController@filtro')->name('controle_devolucao.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/notas_com_processo','ControleDevolucaoController@modalNotasComProcesso')->name('controle_devolucao.modal.notas_com_processo');
		Route::post('/notas_sem_processo','ControleDevolucaoController@modalNotasSemProcesso')->name('controle_devolucao.modal.notas_sem_processo');
		Route::post('/notas_sem_critica','ControleDevolucaoController@modalNotasSemCritica')->name('controle_devolucao.modal.notas_sem_critica');	
		Route::post('/notas_com_critica','ControleDevolucaoController@modalNotasComCritica')->name('controle_devolucao.modal.notas_com_critica');	
		Route::post('/notas_entrada','ControleDevolucaoController@modalNotas')->name('controle_devolucao.modal.notas_entrada');
	});
});


Route::prefix('transportadoras_edi')->middleware(['auth'])->group(function(){
	Route::get('/','TransportadorasEdiController@index')->name('transportadoras_edi.index');
	Route::post('/adicionar','TransportadorasEdiController@adicionarTransportadora')->name('transportadoras_edi.adicionar');
	Route::post('/editar','TransportadorasEdiController@editarTransportadora')->name('transportadoras_edi.editar');
	Route::post('/deletar','TransportadorasEdiController@deletarTransportadora')->name('transportadoras_edi.deletar');
	Route::post('/filtro','TransportadorasEdiController@filtro')->name('transportadoras_edi.filtro');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'TransportadorasEdiController@modalAdicionar')->name('transportadoras_edi.modal.adicionar');
		Route::post('/editar', 'TransportadorasEdiController@modalEditar')->name('transportadoras_edi.modal.editar');
		Route::post('/deletar', 'TransportadorasEdiController@modalDeletar')->name('transportadoras_edi.modal.deletar');
	});
});

Route::prefix('cliente_whitelist')->middleware(['auth'])->group(function(){
	Route::get('/','ClienteWhitelistController@index')->name('cliente_whitelist.index');
	Route::post('/filter','ClienteWhitelistController@filter')->name('cliente_whitelist.filter');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar','ClienteWhitelistController@modalAdicionar')->name('cliente_whitelist.modal.adicionar');
		Route::post('/deletar','ClienteWhitelistController@modalDelete')->name('cliente_whitelist.modal.excluir');
	});
	Route::post('/adicionar','ClienteWhitelistController@adicionar')->name('cliente_whitelist.adicionar');
	Route::post('/excluir','ClienteWhitelistController@excluir')->name('cliente_whitelist.excluir');
});

Route::prefix('renegociacao_titulo')->middleware(['auth'])->group(function(){
	Route::post('/salvar_renegociacao','RenegociacaoTituloController@salvarRenegociacao')->name('renegociacao_titulo.salvar_renegociacao');
	Route::post('/adicionar_avalista','RenegociacaoTituloController@adicionarAvalista')->name('renegociacao_titulo.adicionar_avalista');
	Route::post('/adicionar_socio','RenegociacaoTituloController@adicionarSocio')->name('renegociacao_titulo.adicionar_socio');
	Route::post('/editar_renegociacao','RenegociacaoTituloController@editarRenegociacao')->name('renegociacao_titulo.editar_renegociacao');
	Route::post('/deletar_renegociacao','RenegociacaoTituloController@deletarRenegociacao')->name('renegociacao_titulo.deletar_renegociacao');
	Route::post('/filtro_selecionar_titulo','RenegociacaoTituloController@filtroSelecionarTitulo')->name('aprovacao_renegociacao_titulo.filtro_selecionar_titulo');
	Route::post('/filtro','RenegociacaoTituloController@filtro')->name('renegociacao_titulo.filtro');
	Route::post('/reenvio_email_avaslista','RenegociacaoTituloController@reenvioEmailAvalista')->name('renegociacao_titulo.reenvio_email_avaslista');
	Route::post('/deletar_socio','RenegociacaoTituloController@deletarSocio')->name('renegociacao_titulo.deletar_socio');
	Route::post('/deletar_avalista','RenegociacaoTituloController@deletarAvalista')->name('renegociacao_titulo.deletar_avalista');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/selecionar_titulo','RenegociacaoTituloController@modalSelecionarTitulos')->name('renegociacao_titulo.modal.selecionar_titulo');
		Route::post('/adicionar','RenegociacaoTituloController@modalAdicionar')->name('renegociacao_titulo.modal.adicionar');
		Route::post('/editar','RenegociacaoTituloController@modalEditar')->name('renegociacao_titulo.modal.editar');
		Route::post('/deletar','RenegociacaoTituloController@modalDeletar')->name('renegociacao_titulo.modal.deletar');
		Route::post('/modal_renegociacao_titulo','RenegociacaoTituloController@modalRenegociacaoTitulo')->name('renegociacao_titulo.modal.renegociacao_titulo');
		Route::post('/modal_email_previa','RenegociacaoTituloController@modalEnviarPrevia')->name('renegociacao_titulo.modal.email_previa');
		Route::post('/confirmar','RenegociacaoTituloController@modalConfirmarRenegociacao')->name('renegociacao_titulo.modal.confirmar_renegociacao');
	});
});

Route::prefix('aprovacao_renegociacao_titulo')->group(function(){
	Route::prefix('auth')->middleware(['auth'])->group(function(){
		Route::get('/','AprovacaoRenegociacaoTituloController@index')->name('aprovacao_renegociacao_titulo.index');
		Route::post('/filtro','AprovacaoRenegociacaoTituloController@filtro')->name('aprovacao_renegociacao_titulo.filtro');
		Route::post('/aprovacao_diretoria','AprovacaoRenegociacaoTituloController@aprovacaoDiretoria')->name('aprovacao_renegociacao_titulo.aprovacao_diretoria');
		Route::post('/recusa_diretoria','AprovacaoRenegociacaoTituloController@recusaDiretoria')->name('aprovacao_renegociacao_titulo.recusa_diretoria');
		Route::post('/gera_pdf','RenegociacaoTituloController@gerarArquivoPdf')->name('renegociacao_titulo.gera_pdf');
		Route::prefix('modal')->group(function(){
			Route::post('/recusa_diretoria','AprovacaoRenegociacaoTituloController@modalRecusaDiretoria')->name('aprovacao_renegociacao_titulo.modal.recusa_diretoria');
		});
	});

	Route::get('aprovacao_cliente/{hash}', 'AprovacaoRenegociacaoTituloController@indexAprovacaoCliente')->name('aprovacao_renegociacao_titulo.index_aprovacao_cliente');
	Route::get('aprovacao_previa_cliente/{hash}', 'AprovacaoRenegociacaoTituloController@indexAprovacaoPreviaCliente')->name('aprovacao_renegociacao_titulo.index_aprovacao_previa_cliente');
	Route::post('/recusa_cliente','AprovacaoRenegociacaoTituloController@recusaCliente')->name('aprovacao_renegociacao_titulo.recusa_cliente');
	Route::post('/aprovacao_cliente','AprovacaoRenegociacaoTituloController@aprovacaoCliente')->name('aprovacao_renegociacao_titulo.aprovacao_cliente');
	Route::prefix('modal')->group(function(){
		Route::post('/modal_recusa_cliente','AprovacaoRenegociacaoTituloController@modalRecusaCliente')->name('aprovacao_renegociacao_titulo.modal.recusa_cliente');
	});
});

Route::prefix('comissao_data_fechamento')->group(function(){
	Route::get('/','ComissaoDataFechamentoController@index')->name('comissao_data_fechamento.index');
	Route::post('filter','ComissaoDataFechamentoController@filter')->name('comissao_data_fechamento.filter');

	Route::post('novo','ComissaoDataFechamentoController@novo')->name('comissao_data_fechamento.novo');
	Route::post('editar','ComissaoDataFechamentoController@editar')->name('comissao_data_fechamento.editar');
	Route::post('excluir','ComissaoDataFechamentoController@excluir')->name('comissao_data_fechamento.excluir');

	Route::prefix('modal')->group(function(){
		Route::post('novo','ComissaoDataFechamentoController@modalNovo')->name('comissao_data_fechamento.modal.novo');
		Route::post('editar','ComissaoDataFechamentoController@modalEditar')->name('comissao_data_fechamento.modal.editar');
	});
});

Route::prefix('analise_performance_fornecedor')->middleware(['auth'])->group(function(){
	Route::get('/','AnalisePerformanceFornecedorController@index')->name('analise_performance_fornecedor.index');
	Route::post('filtro','AnalisePerformanceFornecedorController@filtro')->name('analise_performance_fornecedor.filtro');
	Route::prefix('modal')->group(function(){
		Route::post('/pedidos_abertos_prazo','AnalisePerformanceFornecedorController@modalPedidosAbertosPrazo')->name('analise_performance_fornecedor.modal.pedidos_abertos_prazo');
		Route::post('/pedidos_abertos_atraso','AnalisePerformanceFornecedorController@modalPedidosAbertosAtraso')->name('analise_performance_fornecedor.modal.pedidos_abertos_atraso');
		Route::post('/pedidos_entregues_prazo','AnalisePerformanceFornecedorController@modalPedidosEntreguesPrazo')->name('analise_performance_fornecedor.modal.pedidos_entregues_prazo');
		Route::post('/pedidos_entregues_atraso','AnalisePerformanceFornecedorController@modalPedidosEntreguesAtraso')->name('analise_performance_fornecedor.modal.pedidos_entregues_atraso');
	});
});

Route::prefix('frete_cobrado_x_pago')->middleware(['auth'])->group(function(){
	Route::get('/','FreteCobradoXPagoController@index')->name('frete_cobrado_x_pago.index');
	Route::post('filter','FreteCobradoXPagoController@filter')->name('frete_cobrado_x_pago.filter');
	Route::post('export','FreteCobradoXPagoController@export')->name('frete_cobrado_x_pago.export');
	
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/','FreteCobradoXPagoController@modal')->name('frete_cobrado_x_pago.modal.index');
		Route::post('notas','FreteCobradoXPagoController@modalNotas')->name('frete_cobrado_x_pago.modal.notas');
		Route::post('export','FreteCobradoXPagoController@modalExport')->name('frete_cobrado_x_pago.modal.export');
	});
});

Route::prefix('cliente_black_list')->middleware(['auth'])->group(function(){
	Route::get('/','ClienteBlackListController@index')->name('cliente_black_list.index');
	Route::post('/filtro','ClienteBlackListController@filtro')->name('cliente_black_list.filtro');
	Route::post('/liberar','ClienteBlackListController@liberar')->name('cliente_black_list.liberar');
	Route::post('/bloquear','ClienteBlackListController@bloquear')->name('cliente_black_list.bloquear');
	Route::post('/autocomplete_titulo_pago','ClienteBlackListController@autoCompleteTituloPago')->name('cliente_black_list.autocomplete_titulo_pago');
	Route::post('/adicionar_titulo','ClienteBlackListController@adicionarTitulo')->name('cliente_black_list.adicionar_titulo');
	Route::post('/adicionar','ClienteBlackListController@adicionar')->name('cliente_black_list.adicionar');
	Route::post('/editar','ClienteBlackListController@editar')->name('cliente_black_list.editar');
	Route::post('/filtro_titulos_para_pago', 'ClienteBlackListController@filtroTitulosParaPago')->name('cliente_black_list.filtro_titulos_para_pago');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/adicionar','ClienteBlackListController@modalAdicionar')->name('cliente_black_list.modal.adicionar');
		Route::post('/editar','ClienteBlackListController@modalEditar')->name('cliente_black_list.modal.editar');
		Route::post('/historico','ClienteBlackListController@modalHistorico')->name('cliente_black_list.modal.historico');
		Route::post('/buscar_titulo_pago', 'ClienteBlackListController@modalBuscarTitulosPago')->name('cliente_black_list.modal.buscar_titulo_pago');
	});
});

Route::prefix('motivo_cliente_black_list')->middleware(['auth'])->group(function(){
	Route::get('/','MotivoClienteBlackListController@index')->name('motivo_cliente_black_list.index');
	Route::post('/filtro','MotivoClienteBlackListController@filtro')->name('motivo_cliente_black_list.filtro');
	Route::post('/adicionar','MotivoClienteBlackListController@adicionar')->name('motivo_cliente_black_list.adicionar');
	Route::post('/editar','MotivoClienteBlackListController@editar')->name('motivo_cliente_black_list.editar');
	Route::post('/deletar','MotivoClienteBlackListController@deletar')->name('motivo_cliente_black_list.deletar');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/adicionar','MotivoClienteBlackListController@modalAdicionar')->name('motivo_cliente_black_list.modal.adicionar');
		Route::post('/editar','MotivoClienteBlackListController@modalEditar')->name('motivo_cliente_black_list.modal.editar');
		Route::post('/deletar', 'MotivoClienteBlackListController@modalDeletar')->name('motivo_cliente_black_list.modal.deletar');
	});
});

Route::prefix('giro_de_estoque')->middleware(['auth'])->group(function(){
	Route::get('/','GiroDeEstoqueController@index')->name('giro_de_estoque.index');
	Route::post('filtro','GiroDeEstoqueController@filtroTelaGiroDeEstoque')->name('giro_de_estoque.filtro');
	Route::post('export','GiroDeEstoqueController@export')->name('giro_de_estoque.export');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/compras','GiroDeEstoqueController@modalCompras')->name('giro_de_estoque.compras');
		Route::post('/vendas','GiroDeEstoqueController@modalVendas')->name('giro_de_estoque.vendas');
	});
});

Route::prefix('incoterm')->middleware(['auth'])->group(function(){
	Route::get('/','IncotermController@index')->name('incoterm.index');
	Route::post('/adicionar','IncotermController@adicionarIncoterm')->name('incoterm.adicionar');
	Route::post('/editar','IncotermController@editarIncoterm')->name('incoterm.editar');
	Route::post('/deletar','IncotermController@deletarIncoterm')->name('incoterm.deletar');
	Route::post('/filtro','IncotermController@filtro')->name('incoterm.filtro');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'IncotermController@modalAdicionar')->name('incoterm.modal.adicionar');
		Route::post('/editar', 'IncotermController@modalEditar')->name('incoterm.modal.editar');
		Route::post('/deletar', 'IncotermController@modalDeletar')->name('incoterm.modal.deletar');
	});
});

Route::prefix('pesquisa_satisfacao_formulario')->middleware(['auth'])->group(function(){
	Route::get('/','PesquisaSatisfacaoFormularioController@index')->name('pesquisa_satisfacao_formulario.index');
	Route::get('/carrega_formulario','PesquisaSatisfacaoFormularioController@carregaFormulario')->name('pesquisa_satisfacao_formulario.carrega_formulario');
	Route::post('/grava_questao','PesquisaSatisfacaoFormularioController@gravarQuestao')->name('pesquisa_satisfacao_formulario.gravar_questao');
	Route::post('/editar_questao','PesquisaSatisfacaoFormularioController@editarQuestao')->name('pesquisa_satisfacao_formulario.editar_questao');
	Route::post('/excluir_questao','PesquisaSatisfacaoFormularioController@excluirDocumento')->name('pesquisa_satisfacao_formulario.excluir_questao');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::get('/adicionar_questao','PesquisaSatisfacaoFormularioController@modalAdicionarQuestao')->name('pesquisa_satisfacao_formulario.modal.adicionar_questao');
		Route::post('/editar_questao','PesquisaSatisfacaoFormularioController@modalEditarQuestao')->name('pesquisa_satisfacao_formulario.modal.editar_questao');
	});
});

Route::prefix('pesquisa_satisfacao_consulta')->middleware(['auth'])->group(function(){
	Route::get('/','PesquisaSatisfacaoConsultaController@index')->name('pesquisa_satisfacao_consulta.index');
	Route::post('/filtro','PesquisaSatisfacaoConsultaController@filtro')->name('pesquisa_satisfacao_consulta.filtro');
	Route::post('/filtro_posicao_sintetica','PesquisaSatisfacaoConsultaController@buscarPesquisaPosicaoSinteticaCliente')->name('pesquisa_satisfacao_consulta.filtro.posicao_sintetica');
    Route::prefix('modal')->middleware(['auth'])->group(function(){
        Route::post('/abertura_clientes','PesquisaSatisfacaoConsultaController@aberturaClientes')->name('pesquisa_satisfacao_consulta.modal.abertura_clientes');
		Route::post('/abertura_formulario','PesquisaSatisfacaoConsultaController@aberturaFormulario')->name('pesquisa_satisfacao_consulta.modal.abertura_formulario');
	});
});

Route::prefix('pesquisa_satisfacao_cliente')->group(function(){
	Route::get('/formulario/{id}','PesquisaSatisfacaoFormularioController@formularioPesquisa')->name('formulario.pesquisa');
	Route::post('/gravar_pesquisa','PesquisaSatisfacaoFormularioController@gravarFormulario')->name('pesquisa_satisfacao_formulario.gravar_pesquisa');
});

Route::prefix('politica')->middleware(['auth'])->group(function(){
	Route::get('/','PoliticaController@index')->name('politica.index');
    Route::prefix('cadastro')->middleware(['auth'])->group(function(){
		Route::get('/','PoliticaController@indexCadastro')->name('politica.cadastro.index');
		Route::post('/filtro','PoliticaController@filtro')->name('politica.cadastro.filtro');
	
		Route::post('/adicionar','PoliticaController@adicionar')->name('politica.cadastro.adicionar');
		Route::post('/editar','PoliticaController@editar')->name('politica.cadastro.editar');
		Route::post('/excluir','PoliticaController@excluir')->name('politica.cadastro.excluir');
	
		Route::prefix('modal')->group(function(){
			Route::post('/adicionar','PoliticaController@modalAdicionar')->name('politica.cadastro.modal.adicionar');
			Route::post('/editar','PoliticaController@modalEditar')->name('politica.cadastro.modal.editar');
		});
	});	
});

Route::prefix('documentos_nao_processados')->middleware(['auth'])->group(function(){
	Route::get('','DocumentosNaoProcessadosNasajonController@index')->name('documentos_nao_processados.index');
	Route::post('filtro','DocumentosNaoProcessadosNasajonController@filter')->name('documentos_nao_processados.filter');
});

Route::prefix('projeto_acompanhamento')->middleware(['auth'])->group(function(){
	Route::get('/', 'AcompanhamentoProjetoController@index')->name('projeto.acompanhamento.index');
	Route::post('/filtro', 'AcompanhamentoProjetoController@filtro')->name('projeto.acompanhamento.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
        Route::post('/dialog','AcompanhamentoProjetoController@dialog')->name('projeto.acompanhamento.modal.dialog');
	});
});

Route::prefix('emitir_pedido_inventario')->middleware(['auth'])->group(function(){
	Route::get('/', 'EmitirPedidoPeloInventarioController@index')->name('emitir_pedido_inventario.index');
	Route::post('/filtro', 'EmitirPedidoPeloInventarioController@filtro')->name('emitir_pedido_inventario.filtro');
	Route::post('/gerar_pedido', 'EmitirPedidoPeloInventarioController@gerarPedido')->name('emitir_pedido_inventario.gerar_pedido');
	Route::prefix('modal')->group(function(){
		Route::post('/gerar_pedido','EmitirPedidoPeloInventarioController@modalGerarPedido')->name('emitir_pedido_inventario.modal.gerar_pedido');
	});
});

Route::prefix('importacao')->middleware(['auth'])->group(function(){
	Route::get('/','ImportacaoController@index')->name('importacao.index');
	Route::post('filtro','ImportacaoController@filtro')->name('importacao.filtro');
	Route::post('/adicionar','ImportacaoController@adicionar')->name('importacao.adicionar');
	Route::post('/editar','ImportacaoController@editar')->name('importacao.editar');
	Route::post('/deletar','ImportacaoController@deletar')->name('importacao.deletar');
	Route::post('/filtro_buscar_proforma','ImportacaoController@filtroBuscarProforma')->name('importacao.filtro_buscar_proforma');
	Route::post('/autocomplete_proforma','ImportacaoController@autocompleteProforma')->name('importacao.autocomplete_proforma');
	Route::post('/retorno_dados_proforma','ImportacaoController@retornoDadosProforma')->name('importacao.retorno_dados_proforma');
	Route::post('/salvamento_proforma', 'ImportacaoController@salvamentoProforma')->name('importacao.salvamento_proforma');
	Route::post('/adicionar_outras_depesas', 'ImportacaoController@adicionarOutrasDepesas')->name('importacao.adicionar_outras_depesas');
	Route::post('/excluir_outras_depesas', 'ImportacaoController@excluirOutrasDepesas')->name('importacao.excluir_outras_depesas');
	Route::post('/adicionar_mudanca_valor', 'ImportacaoController@adicionarMudancaValor')->name('importacao.adicionar_mudanca_valor');
	Route::post('/adicionar_mudanca_valor_embarque', 'ImportacaoController@adicionarMudancaValorEmbarque')->name('importacao.adicionar_mudanca_valor_embarque');
	Route::post('/adicionar_mudanca_valor_financeiro', 'ImportacaoController@adicionarMudancaValorFinanceiro')->name('importacao.adicionar_mudanca_valor_financeiro');
	Route::post('/adicionar_mudanca_valor_custo', 'ImportacaoController@adicionarMudancaValorCusto')->name('importacao.adicionar_mudanca_valor_custo');
	Route::post('/adicionar_mudanca_valor_produto_contabil', 'ImportacaoController@adicionarMudancaValorProdutoContábil')->name('importacao.adicionar_mudanca_valor_produto_contabil');

	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/adicionar','ImportacaoController@modalAdicionar')->name('importacao.modal.adicionar');
		Route::post('/editar','ImportacaoController@modalEditar')->name('importacao.modal.editar');
		Route::post('/deletar', 'ImportacaoController@modalDeletar')->name('importacao.modal.deletar');
		Route::post('/buscar_proforma', 'ImportacaoController@modalBuscarProforma')->name('importacao.modal.buscar_proforma');
		Route::post('/detalhes', 'ImportacaoController@modalDetalhes')->name('importacao.modal.detalhes');
		Route::post('/historico_dolar_refencia', 'ImportacaoController@modalHistoricoDolarReferencia')->name('importacao.modal.historico_dolar_refencia');
		Route::post('/alteracao_data_etd_eta', 'ImportacaoController@modalAlteracaoDataEtdEta')->name('importacao.modal.alteracao_data_etd_eta');
	});

	Route::prefix('dados_complementares_follow_up')->middleware(['auth'])->group(function(){
		Route::get('/','ImportacaoDadoComplemetarFollowUpController@index')->name('importacao.dados_complementares_follow_up.index');
		Route::post('/editar','ImportacaoDadoComplemetarFollowUpController@editar')->name('importacao.dados_complementares_follow_up.editar');
		Route::post('/adicionar_financeiro_lancamento','ImportacaoDadoComplemetarFollowUpController@adicionarFinanceiroLancamento')->name('importacao.dados_complementares_follow_up.adicionar_financeiro_lancamento');
		Route::post('/buscar_editar_financeiro_lancamento','ImportacaoDadoComplemetarFollowUpController@getEditarFinanceiroLancamento')->name('importacao.dados_complementares_follow_up.buscar_editar_financeiro_lancamento');
		Route::post('/editar_financeiro_lancamento','ImportacaoDadoComplemetarFollowUpController@editarFinanceiroLancamento')->name('importacao.dados_complementares_follow_up.editar_financeiro_lancamento');
		Route::post('/deletar_financeiro_lancamento','ImportacaoDadoComplemetarFollowUpController@deletarFinanceiroLancamento')->name('importacao.dados_complementares_follow_up.deletar_financeiro_lancamento');
		Route::post('/carregar_tabela_financeiro_lancamento','ImportacaoDadoComplemetarFollowUpController@carregarTabelaFinanceiroLancamento')->name('importacao.dados_complementares_follow_up.carregar_tabela_financeiro_lancamento');
		Route::post('/carregar_dados','ImportacaoDadoComplemetarFollowUpController@carregarDados')->name('importacao.dados_complementares_follow_up.carregar_dados');
		Route::post('/salvar_mudanca','ImportacaoDadoComplemetarFollowUpController@salvarMudanca')->name('importacao.dados_complementares_follow_up.salvar_mudanca');
		Route::post('/carregar_resumo','ImportacaoDadoComplemetarFollowUpController@carregarResumo')->name('importacao.dados_complementares_follow_up.carregar_resumo');
		Route::post('/adicionar_financeiro_previsto','ImportacaoDadoComplemetarFollowUpController@adicionarFinanceiroPrevisto')->name('importacao.dados_complementares_follow_up.adicionar_financeiro_previsto');
		Route::post('/adicionar_red','ImportacaoDadoComplemetarFollowUpController@adicionarRed')->name('importacao.dados_complementares_follow_up.adicionar_red');
		Route::post('/deletar_red','ImportacaoDadoComplemetarFollowUpController@deletarRed')->name('importacao.dados_complementares_follow_up.deletar_red');
		Route::prefix('modal')->middleware(['auth'])->group(function(){
			Route::post('/editar','ImportacaoDadoComplemetarFollowUpController@modalEditar')->name('importacao.dados_complementares_follow_up.modal.editar');
			Route::post('/financeiro_lancamento','ImportacaoDadoComplemetarFollowUpController@modalFinanceiroLancamento')->name('importacao.dados_complementares_follow_up.modal.financeiro_lancamento');
			Route::post('/detalhes', 'ImportacaoDadoComplemetarFollowUpController@modalDetalhes')->name('importacao.dados_complementares_follow_up.modal.detalhes');
			Route::post('/historico_aprovacao', 'ImportacaoDadoComplemetarFollowUpController@modalHistoricoAprovacao')->name('importacao.dados_complementares_follow_up.modal.historico_aprovacao');
		});
	});
	Route::prefix('valor_padrao')->middleware(['auth'])->group(function(){
		Route::get('/','ImportacaoValorPadraoController@index')->name('importacao.valor_padrao.index');
		Route::post('/modificar', 'ImportacaoValorPadraoController@modificarValorPadrao')->name('importacao.valor_padrao.modificar');
	});
	Route::prefix('fornecedor_credito_debito')->middleware(['auth'])->group(function(){
		Route::get('/','ImportacaoFornecedorCreditoDebitoController@index')->name('importacao.fornecedor_credito_debito.index');
		Route::post('/adicionar','ImportacaoFornecedorCreditoDebitoController@adicionar')->name('importacao.fornecedor_credito_debito.adicionar');
		Route::post('/editar','ImportacaoFornecedorCreditoDebitoController@editar')->name('importacao.fornecedor_credito_debito.editar');
		Route::post('/deletar','ImportacaoFornecedorCreditoDebitoController@deletar')->name('importacao.fornecedor_credito_debito.deletar');
		Route::post('/filtro','ImportacaoFornecedorCreditoDebitoController@filtro')->name('importacao.fornecedor_credito_debito.filtro');
		Route::prefix('modal')->middleware(['auth'])->group(function(){
			Route::get('/adicionar','ImportacaoFornecedorCreditoDebitoController@modalAdicionar')->name('importacao.fornecedor_credito_debito.modal.adicionar');
			Route::post('/editar','ImportacaoFornecedorCreditoDebitoController@modalEditar')->name('importacao.fornecedor_credito_debito.modal.editar');
			Route::post('/deletar', 'ImportacaoFornecedorCreditoDebitoController@modalDeletar')->name('importacao.fornecedor_credito_debito.modal.deletar');
		});
		Route::prefix('consulta')->middleware(['auth'])->group(function(){
			Route::get('/','ImportacaoFornecedorCreditoDebitoController@indexConsulta')->name('importacao.fornecedor_credito_debito.consulta.index');
			Route::post('/filtro_consulta','ImportacaoFornecedorCreditoDebitoController@filtroConsulta')->name('importacao.fornecedor_credito_debito.consulta.filtro_consulta');
			Route::prefix('modal')->middleware(['auth'])->group(function(){
				Route::post('/importacao_por_status', 'ImportacaoFornecedorCreditoDebitoController@modalImportacaoPorStatus')->name('importacao.fornecedor_credito_debito.modal.importacao_por_status');
			});
		});
	});
	Route::prefix('consulta_pedido_aberto')->middleware(['auth'])->group(function(){
		Route::get('/', 'ImportacaoController@indexPedido')->name('importacao.consulta_pedido_aberto.index');
		Route::post('/consulta_pedido_aberto', 'ImportacaoController@consultaPedidoAberto')->name('importacao.consulta_pedido_aberto');
	});
	Route::prefix('consulta_compra_produto')->middleware(['auth'])->group(function(){
		Route::get('/', 'ImportacaoController@indexCompraProduto')->name('importacao.consulta_compra_produto.index');	
		Route::post('/consulta_compra_produto', 'ImportacaoController@consultaCompraProduto')->name('importacao.consulta_compra_produto');	
	});
});

Route::prefix('pedido_maior_estoque')->middleware(['auth'])->group(function(){
	Route::get('/', 'HistoricoComprasPedidoMaiorEstoqueController@index')->name('pedido_maior_estoque.index');
	Route::post('/filtro', 'HistoricoComprasPedidoMaiorEstoqueController@filtroTelaPedidoMaiorEstoque')->name('pedido_maior_estoque.filtro');
	Route::prefix('modal')->group(function(){
        Route::post('/compras','HistoricoComprasPedidoMaiorEstoqueController@modalCompras')->name('pedido_maior_estoque.modal.compras');
		Route::post('/pedidos','HistoricoComprasPedidoMaiorEstoqueController@modalPedidosAbertos')->name('pedido_maior_estoque.modal.pedidos');
		Route::post('/historico_compras','HistoricoComprasPedidoMaiorEstoqueController@modalHistoricoCompras')->name('pedido_maior_estoque.modal.historico_compras');
		Route::post('/historico_pedidos','HistoricoComprasPedidoMaiorEstoqueController@modalHistoricoPedidosAbertos')->name('pedido_maior_estoque.modal.historico_pedidos');
	});
});

Route::prefix('feriado')->middleware(['auth'])->group(function(){
	Route::get('/', 'FeriadoController@index')->name('feriado.index');
	Route::post('/adicionar', 'FeriadoController@adicionar')->name('feriado.adicionar');
	Route::post('/editar', 'FeriadoController@editar')->name('feriado.editar');
	Route::post('/deletar', 'FeriadoController@deletar')->name('feriado.deletar');
	Route::post('/filtro', 'FeriadoController@filtro')->name('feriado.filtro');
	Route::prefix('modal')->group(function(){
        Route::get('/adicionar','FeriadoController@modalAdicionar')->name('feriado.modal.adicionar');
		Route::post('/editar','FeriadoController@modalEditar')->name('feriado.modal.editar');
		Route::post('/deletar','FeriadoController@modalDeletar')->name('feriado.modal.deletar');
	});
});


Route::prefix('score_fornecedor')->middleware(['auth'])->group(function(){

	Route::get('/','ScoreFornecedorController@index')->name('score_fornecedores.index');
	Route::post('/filtro','ScoreFornecedorController@filtro')->name('score_fornecedores.filtro');
	Route::post('/salvar','ScoreFornecedorController@salvar')->name('score_fornecedores.salvar');
	Route::prefix('modal')->group(function(){
		Route::post('/formulario_score','ScoreFornecedorController@modalFormularioScore')->name('score_fornecedores.modal.formulario_score');
		Route::post('/formulario_respondido_editar','ScoreFornecedorController@modalFormularioEditar')->name('score_fornecedores_consulta.modal.formulario_respondido_editar');
	});

	Route::prefix('score_fornecedor_consulta')->middleware(['auth'])->group(function(){
		Route::get('/','ScoreFornecedorConsultaController@index')->name('score_fornecedores_consulta.index');
		Route::post('/filtro','ScoreFornecedorConsultaController@filtro')->name('score_fornecedores_consulta.filtro');
		Route::prefix('modal')->group(function(){
			Route::post('/lista_fornecedores','ScoreFornecedorConsultaController@modalListaFornecedores')->name('score_fornecedores_consulta.modal.lista_fornecedores');
			Route::post('/formulario_respondido','ScoreFornecedorConsultaController@scoreRespondido')->name('score_fornecedores_consulta.modal.formulario_respondido');
		});
	});

	Route::prefix('score_fornecedor_formulario')->middleware(['auth'])->group(function(){
		Route::get('/','ScoreFornecedorFormularioController@index')->name('score_fornecedores_formularios.index');
		Route::post('/carrega_formulario','ScoreFornecedorFormularioController@carregaFormulario')->name('score_fornecedores_formularios.carrega_formulario');
		Route::post('/gravar_questao','ScoreFornecedorFormularioController@gravarQuestao')->name('score_fornecedores_formularios.gravar_questao');
		Route::post('/editar_questao','ScoreFornecedorFormularioController@editarPergunta')->name('score_fornecedores_formularios.editar_questao');
		Route::post('/excluir_pergunta','ScoreFornecedorFormularioController@excluirPergunta')->name('score_fornecedores_formularios.excluir_pergunta');
		Route::post('/gravar_grupo','ScoreFornecedorFormularioController@gravarGrupo')->name('score_fornecedores_formularios.gravar_grupo');
		Route::post('/excluir_grupo','ScoreFornecedorFormularioController@excluirGrupo')->name('score_fornecedores_formularios.excluir_grupo');
		Route::prefix('modal')->group(function(){
			Route::post('/adicionar','ScoreFornecedorFormularioController@modalAdicionarQuestao')->name('score_fornecedores_formularios.modal.adicionar');
			Route::post('/gerenciar_grupos','ScoreFornecedorFormularioController@modalGerenciarGrupos')->name('score_fornecedores_formularios.modal.gerenciar_grupos');
			Route::post('/editar','ScoreFornecedorFormularioController@modalEditarPergunta')->name('score_fornecedores_formularios.modal.editar');
		});
	});

});

Route::prefix('acompanhamento_orcamentario')->middleware(['auth'])->group(function(){
	Route::get('/', 'AcompanhamentoOrcamentarioController@index')->name('acompanhamento_orcamentario.index');
	Route::post('/filtro', 'AcompanhamentoOrcamentarioController@filtro')->name('acompanhamento_orcamentario.filtro');
	Route::post('/detalhes_despesa', 'AcompanhamentoOrcamentarioController@detalhesDespesa')->name('acompanhamento_orcamentario.detalhes_despesa');
	Route::prefix('modal')->group(function(){
        Route::post('/compras_detalhes','AcompanhamentoOrcamentarioController@modalComprasDetalhes')->name('acompanhamento_orcamentario.modal.compras_detalhes');
		Route::post('/despesa_detalhes','AcompanhamentoOrcamentarioController@modalDespesaDetalhes')->name('acompanhamento_orcamentario.modal.despesa_detalhes');
		Route::post('/despesa_array_contas_detalhes','AcompanhamentoOrcamentarioController@modalDespesaArrayContasDetalhes')->name('acompanhamento_orcamentario.modal.despesa_array_contas_detalhes');
		Route::post('/banco_detalhes','AcompanhamentoOrcamentarioController@modalBancoDetalhes')->name('acompanhamento_orcamentario.modal.banco_detalhes');
		Route::post('/banco_titulo_detalhes','AcompanhamentoOrcamentarioController@modalBancoTituloDetalhes')->name('acompanhamento_orcamentario.modal.banco_titulo_detalhes');
		Route::post('/compras_previsto_detalhes','AcompanhamentoOrcamentarioController@modalComprasPrevistoDetalhes')->name('acompanhamento_orcamentario.modal.compras_previsto_detalhes');
		Route::post('/compras_titulos_nao_lancados','AcompanhamentoOrcamentarioController@modalTituloNaoLancado')->name('acompanhamento_orcamentario.modal.compras_titulos_nao_lancados');
		Route::post('/compras_titulos_duplicatas','AcompanhamentoOrcamentarioController@modalTituloDuplicatas')->name('acompanhamento_orcamentario.modal.compras_titulos_duplicatas');
	});
});

Route::prefix('orcamento_compras')->middleware(['auth'])->group(function(){
	Route::get('/', 'OrcamentoCompraController@index')->name('orcamento_compras.index');
	Route::post('/filtro', 'OrcamentoCompraController@filtro')->name('orcamento_compras.filtro');
	Route::post('/adicionar','OrcamentoCompraController@adicionar')->name('orcamento_compras.adicionar');
	Route::post('/editar','OrcamentoCompraController@editar')->name('orcamento_compras.editar');
	Route::post('/deletar','OrcamentoCompraController@deletar')->name('orcamento_compras.deletar');
	Route::prefix('modal')->group(function(){
        Route::get('/adicionar','OrcamentoCompraController@modalAdicionar')->name('orcamento_compras.modal.adicionar');
		Route::post('/editar','OrcamentoCompraController@modalEditar')->name('orcamento_compras.modal.editar');
		Route::post('/deletar','OrcamentoCompraController@modalDeletar')->name('orcamento_compras.modal.deletar');
	});
});

Route::prefix('pesquisa_satisfacao_consulta_equipes')->middleware(['auth'])->group(function(){
	Route::get('/','PesquisaSatisfacaoConsultaEquipeController@index')->name('pesquisa_satisfacao_consulta_equipes.index');
	Route::post('/filtro','PesquisaSatisfacaoConsultaEquipeController@filtro')->name('pesquisa_satisfacao_consulta_equipes.filtro');
});

Route::prefix('liberacao_pilotagem')->middleware(['auth'])->group(function(){
	Route::get('/','LiberacaoPilotagemController@index')->name('liberacao_pilotagem.index');
	Route::get('/aprovacao','LiberacaoPilotagemController@indexAprovacao')->name('aprovacao_liberacao_pilotagem.index');
	Route::post('/filtro_aprovacao','LiberacaoPilotagemController@filtroAprovarPilotagem')->name('aprovacao_liberacao_pilotagem.filtro');
	Route::post('/aprovar','LiberacaoPilotagemController@aprovarPilotagem')->name('liberacao_pilotagem.aprovar');
	Route::post('/reprovar','LiberacaoPilotagemController@reprovarPilotagem')->name('liberacao_pilotagem.reprovar');
	Route::post('/filtro_pilotagem','LiberacaoPilotagemController@filtroLiberacaoPilotagem')->name('liberacao_pilotagem.filtro_pilotagem');
	Route::post('/filtro_adicionar','LiberacaoPilotagemController@filtroPilotagemAdicionar')->name('liberacao_pilotagem.filtro_adicionar');
	Route::post('/filtro_editar','LiberacaoPilotagemController@filtroPilotagemEditar')->name('liberacao_pilotagem.filtro_editar');
	Route::post('/adicionar','LiberacaoPilotagemController@adicionarLiberacaoPilotagem')->name('liberacao_pilotagem.adicionar');
	Route::post('/editar','LiberacaoPilotagemController@editarLiberacaoPilotagem')->name('liberacao_pilotagem.editar');
	Route::post('/deletar','LiberacaoPilotagemController@deletarPilotagem')->name('liberacao_pilotagem.deletar');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'LiberacaoPilotagemController@modalAdicionar')->name('liberacao_pilotagem.modal.adicionar');
		Route::post('/editar', 'LiberacaoPilotagemController@modalEditar')->name('liberacao_pilotagem.modal.editar');
		Route::post('/visualizar', 'LiberacaoPilotagemController@modalVisualizar')->name('liberacao_pilotagem.modal.visualizar');
		Route::post('/deletar', 'LiberacaoPilotagemController@modalDeletar')->name('liberacao_pilotagem.modal.deletar');
		Route::post('/recusar', 'LiberacaoPilotagemController@modalReprovarPilotagem')->name('aprovacao_liberacao_pilotagem.modal.recusar');
	});
});

Route::prefix('remessa_consignacao')->middleware(['auth'])->group(function(){
	Route::get('/', 'RemessaConsignacaoController@index')->name('remessa_consignacao.index');
	Route::post('/enviar_arquivo','RemessaConsignacaoController@enviarArquivo')->name('remessa_consignacao.enviar_arquivo');
	Route::post('/salvar_pedido','RemessaConsignacaoController@salvarPedido')->name('remessa_consignacao.salvar_pedido');
});

Route::prefix('faturamento_previsto')->middleware(['auth'])->group(function(){
	Route::get('/', 'FaturamentoPrevistoController@index')->name('faturamento_previsto.index');
	Route::post('/filtro', 'FaturamentoPrevistoController@filtro')->name('faturamento_previsto.filtro');
	Route::post('/adicionar','FaturamentoPrevistoController@adicionar')->name('faturamento_previsto.adicionar');
	Route::post('/editar','FaturamentoPrevistoController@editar')->name('faturamento_previsto.editar');
	Route::post('/deletar','FaturamentoPrevistoController@deletar')->name('faturamento_previsto.deletar');
	Route::prefix('modal')->group(function(){
        Route::get('/adicionar','FaturamentoPrevistoController@modalAdicionar')->name('faturamento_previsto.modal.adicionar');
		Route::post('/editar','FaturamentoPrevistoController@modalEditar')->name('faturamento_previsto.modal.editar');
		Route::post('/deletar','FaturamentoPrevistoController@modalDeletar')->name('faturamento_previsto.modal.deletar');
	});
});

Route::prefix('despesa_planejada')->middleware(['auth'])->group(function(){
	Route::get('/', 'DespesaPlanejadaController@index')->name('despesa_planejada.index');
	Route::post('/filtro', 'DespesaPlanejadaController@filtro')->name('despesa_planejada.filtro');
	Route::post('/adicionar','DespesaPlanejadaController@adicionar')->name('despesa_planejada.adicionar');
	Route::post('/editar','DespesaPlanejadaController@editar')->name('despesa_planejada.editar');
	Route::post('/deletar','DespesaPlanejadaController@deletar')->name('despesa_planejada.deletar');
	Route::prefix('modal')->group(function(){
        Route::get('/adicionar','DespesaPlanejadaController@modalAdicionar')->name('despesa_planejada.modal.adicionar');
		Route::post('/editar','DespesaPlanejadaController@modalEditar')->name('despesa_planejada.modal.editar');
		Route::post('/deletar','DespesaPlanejadaController@modalDeletar')->name('despesa_planejada.modal.deletar');
	});
});

Route::prefix('nota_complementar')->name('remessa_x_valor.')->group(function(){
	Route::get('/', 'RemessaXValoresController@index')->name('index');
	Route::post('/filtro', 'RemessaXValoresController@filtro')->name('filtro');
	Route::prefix('modal')->name('modal.')->group(function(){
		Route::post('/historico', 'RemessaXValoresController@modalHistorico')->name('historico');
	});
});

Route::prefix('familia')->middleware(['auth'])->group(function(){
	Route::get('/','FamiliaController@index')->name('familia.index');
	Route::post('/filtro','FamiliaController@filtro')->name('familia.filtro');
	Route::post('/salvar','FamiliaController@salvarFamilia')->name('familia.salvar');
	Route::post('/editar','FamiliaController@editarFamilia')->name('familia.editar');
	Route::post('/excluir','FamiliaController@deletarFamilia')->name('familia.excluir');
	Route::prefix('modal')->group(function(){
		Route::post('/salvar', 'FamiliaController@modalAdicionar')->name('familia.modal.salvar');
		Route::post('/editar', 'FamiliaController@modalEditar')->name('familia.modal.editar');
		Route::post('/excluir', 'FamiliaController@modalDeletar')->name('familia.modal.excluir');
	});

});

Route::prefix('banco_previsto')->middleware(['auth'])->group(function(){
	Route::get('/', 'OrcamentoCompraController@indexOutro')->name('banco_previsto.index');
	Route::post('/filtro', 'BancoPrevistoController@filtro')->name('banco_previsto.filtro');
	Route::post('/adicionar','BancoPrevistoController@adicionar')->name('banco_previsto.adicionar');
	Route::post('/editar','BancoPrevistoController@editar')->name('banco_previsto.editar');
	Route::post('/deletar','BancoPrevistoController@deletar')->name('banco_previsto.deletar');
	Route::prefix('modal')->group(function(){
        Route::get('/adicionar','BancoPrevistoController@modalAdicionar')->name('banco_previsto.modal.adicionar');
		Route::post('/editar','BancoPrevistoController@modalEditar')->name('banco_previsto.modal.editar');
		Route::post('/deletar','BancoPrevistoController@modalDeletar')->name('banco_previsto.modal.deletar');
	});
});

Route::prefix('venda_playstation')->middleware(['auth'])->group(function(){
	Route::get('/', 'ProdutoPlaystationController@index')->name('venda_playstation.index');
	Route::post('/filtro', 'ProdutoPlaystationController@filtro')->name('venda_playstation.filtro');
});

Route::get('/ponto_web', function(){
	return redirect('http://pontoweb.nasajon.com.br/');
})->middleware(['auth'])->name('ponto_web.link');

Route::prefix('estoque_poder_terceiro')->middleware(['auth'])->group(function(){
	Route::get('/', 'EstoquePoderTerceiroController@index')->name('estoque_poder_terceiro.index');
	Route::post('/filtro', 'EstoquePoderTerceiroController@filtro')->name('estoque_poder_terceiro.filtro');
	Route::post('/filtro_produto', 'EstoquePoderTerceiroController@filtroProduto')->name('estoque_poder_terceiro.filtro_produto');
	Route::prefix('modal')->group(function(){
        Route::post('/produtos','EstoquePoderTerceiroController@modalProdutos')->name('estoque_poder_terceiro.modal.produtos');
		Route::post('/fornecedor','EstoquePoderTerceiroController@modalFornecedor')->name('estoque_poder_terceiro.modal.fornecedor');
	});
});

Route::prefix('posicao_sintetica_fornecedor')->middleware(['auth'])->group(function(){
	Route::get('/', 'PosicaoSinteticaFornecedorController@index')->name('posicao_sintetica_fornecedor.index');
	Route::post('filtro/', 'PosicaoSinteticaFornecedorController@filtro')->name('posicao_sintetica_fornecedor.filtro');
	Route::post('filtro_titulos/', 'PosicaoSinteticaFornecedorController@filtroTitulos')->name('posicao_sintetica_fornecedor.filtro_titulos');
	Route::post('filtro_pedidos/', 'PosicaoSinteticaFornecedorController@filtroPedidos')->name('posicao_sintetica_fornecedor.filtro_pedidos');
	Route::prefix('modal')->group(function(){
        Route::post('/pedidos','PosicaoSinteticaFornecedorController@modalPedidos')->name('posicao_sintetica_fornecedor.modal.pedidos');
		Route::post('/titulos_a_vencer','PosicaoSinteticaFornecedorController@titulosAvencer')->name('posicao_sintetica_fornecedor.modal.titulos_a_vencer');
	});
});

Route::prefix('score_fornecedor_nota')->middleware(['auth'])->group(function(){
	Route::get('/', 'ScoreFornecedorNotasController@index')->name('score_fornecedor_nota.index');
	Route::post('filtro/', 'ScoreFornecedorNotasController@filtro')->name('score_fornecedor_nota.filtro');
	Route::post('salvar/', 'ScoreFornecedorNotasController@salvar')->name('score_fornecedor_nota.salvar');
	Route::post('deletar_documento/', 'ScoreFornecedorNotasController@excluirDocumento')->name('score_fornecedor_nota.deletar_documento');
	Route::prefix('modal')->group(function(){
        Route::post('/lancamento_formulario','ScoreFornecedorNotasController@modalFormularioLancamento')->name('score_fornecedor_nota.modal.lancamento_formulario');
		Route::post('/edicao_formulario','ScoreFornecedorNotasController@modalFormularioEditar')->name('score_fornecedor_nota.modal.edicao_formulario');
		Route::post('/exibir_formulario','ScoreFornecedorNotasController@scoreRespondido')->name('score_fornecedor_nota.modal.exibir_formulario');
	});
});

Route::prefix('acompanhamento_compras_anual')->middleware(['auth'])->group(function(){
	Route::get('/', 'AcompanhamentoComprasAnualController@index')->name('acompanhamento_compras_anual.index');
	Route::post('/filtro', 'AcompanhamentoComprasAnualController@filtro')->name('acompanhamento_compras_anual.filtro');
	Route::prefix('modal')->group(function(){
        Route::post('/compras_detalhes','AcompanhamentoComprasAnualController@modalComprasDetalhes')->name('acompanhamento_compras_anual.modal.compras_detalhes');
	});
});

Route::prefix('pagamento_pix')->middleware(['auth'])->group(function(){
	Route::get('/', 'PagamentoPixNasajonController@index')->name('pagamento_pix.index');
	Route::get('/dialog', 'ClienteController@indexDialog')->name('cliente.index.dialog');
	Route::post('/filtro', 'PagamentoPixNasajonController@filterPagamentoPix')->name('pagamento_pix.filter');
	Route::post('/cria_credito', 'PagamentoPixNasajonController@criaCredito')->name('pagamento_pix.cria_credito');
	Route::post('/cria_credito_sem_pedido', 'PagamentoPixNasajonController@criaCreditoSemPedido')->name('pagamento_pix.cria_credito_sem_pedido');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/pedido','PagamentoPixNasajonController@modalPedidoAssociarPix')->name('pagamento_pix.modal.pedido');
		Route::post('/todos_pedido','PagamentoPixNasajonController@modalTodosPedidoAssociarPix')->name('pagamento_pix.modal.todos_pedido');
		Route::post('/sem_pedido','PagamentoPixNasajonController@modalSemPedidoAssociarPix')->name('pagamento_pix.modal.sem_pedido');
	});
});

Route::prefix('comunicado_comissao')->group(function(){
	Route::get('/confirmacao/{id}','ComunicadoComissaoController@confirmarComissao')->name('comunicado_comissao.confirmacao');
});

Route::prefix('composicao')->middleware(['auth'])->group(function(){
	Route::get('/','ComposicaoController@index')->name('composicao.index');
	Route::post('/filtro','ComposicaoController@filtro')->name('composicao.filtro');
	Route::post('/salvar','ComposicaoController@salvar')->name('composicao.salvar');
	Route::post('/editar','ComposicaoController@editar')->name('composicao.editar');
	Route::post('/excluir','ComposicaoController@deletar')->name('composicao.excluir');
	Route::prefix('modal')->group(function(){
		Route::post('/salvar', 'ComposicaoController@modalAdicionar')->name('composicao.modal.salvar');
		Route::post('/editar', 'ComposicaoController@modalEditar')->name('composicao.modal.editar');
		Route::post('/excluir', 'ComposicaoController@modalDeletar')->name('composicao.modal.excluir');
	});
});

Route::prefix('sazonalidade')->middleware(['auth'])->group(function(){
	Route::get('/','SazonalidadeController@index')->name('sazonalidade.index');
	Route::post('/filtro','SazonalidadeController@filtro')->name('sazonalidade.filtro');
	Route::post('/salvar','SazonalidadeController@salvar')->name('sazonalidade.salvar');
	Route::post('/editar','SazonalidadeController@editar')->name('sazonalidade.editar');
	Route::post('/excluir','SazonalidadeController@deletar')->name('sazonalidade.excluir');
	Route::prefix('modal')->group(function(){
		Route::post('/salvar', 'SazonalidadeController@modalAdicionar')->name('sazonalidade.modal.salvar');
		Route::post('/editar', 'SazonalidadeController@modalEditar')->name('sazonalidade.modal.editar');
		Route::post('/excluir', 'SazonalidadeController@modalDeletar')->name('sazonalidade.modal.excluir');
	});
});


Route::prefix('construcao')->middleware(['auth'])->group(function(){
	Route::get('/','ConstrucaoController@index')->name('construcao.index');
	Route::post('/filtro','ConstrucaoController@filtro')->name('construcao.filtro');
	Route::post('/salvar','ConstrucaoController@salvar')->name('construcao.salvar');
	Route::post('/editar','ConstrucaoController@editar')->name('construcao.editar');
	Route::post('/excluir','ConstrucaoController@deletar')->name('construcao.excluir');
	Route::prefix('modal')->group(function(){
		Route::post('/salvar', 'ConstrucaoController@modalAdicionar')->name('construcao.modal.salvar');
		Route::post('/editar', 'ConstrucaoController@modalEditar')->name('construcao.modal.editar');
		Route::post('/excluir', 'ConstrucaoController@modalDeletar')->name('construcao.modal.excluir');
	});
});

Route::prefix('aprovacao_proposta')->group(function(){
	Route::get('/{hash}', 'PedidoPortalController@aprovacaoProposta')->name('pedido_portal.aprovacao_proposta');
	Route::post('/aprovar', 'PedidoPortalController@aprovarProposta')->name('pedido_portal.aprovar_proposta');
	Route::post('/recusar', 'PedidoPortalController@recusarProposta')->name('pedido_portal.recusar_proposta');
	Route::post('/gerar_proposta', 'PedidoPortalController@geraProposta')->name('pedido_portal.gerar_proposta');
});

Route::prefix('sugestao_compra')->middleware(['auth'])->group(function(){
	Route::get('/','SugestaoCompraController@index')->name('sugestao_compra.index');
	Route::post('/filtro','SugestaoCompraController@filtro')->name('sugestao_compra.filtro');
	Route::post('/salvar','SugestaoCompraController@adicionar')->name('sugestao_compra.adicionar');
	Route::post('/editar','SugestaoCompraController@editar')->name('sugestao_compra.editar');
	Route::post('/excluir','SugestaoCompraController@deletar')->name('sugestao_compra.excluir');
	Route::prefix('aprovacao')->middleware(['auth'])->group(function(){
		Route::get('/', 'SugestaoCompraController@indexAprovacao')->name('aprovacao_sugestao_compra.index');
		Route::post('/editar', 'SugestaoCompraController@modalMotivo')->name('aprovacao_sugestao_compra.modal.recusa');
		Route::post('/aprovar', 'SugestaoCompraController@aprovarSugestao')->name('aprovacao_sugestao_compra.aprovar');
		Route::post('/reprovar', 'SugestaoCompraController@reprovarSugestao')->name('aprovacao_sugestao_compra.reprovar');
		Route::post('/aprova', 'SugestaoCompraController@modalAprova')->name('aprovacao_sugestao_compra.modal.aprova');
		
	});
	Route::prefix('modal')->group(function(){
		Route::post('/salvar', 'SugestaoCompraController@modalAdicionar')->name('sugestao_compra.modal.salvar');
		Route::post('/editar', 'SugestaoCompraController@modalEditar')->name('sugestao_compra.modal.editar');
		Route::post('/excluir', 'SugestaoCompraController@modalDeletar')->name('sugestao_compra.modal.excluir');
		Route::post('/foto', 'SugestaoCompraController@modalFoto')->name('sugestao_compra.modal.foto');

	});
});

Route::prefix('remessa_venda')->middleware(['auth'])->group(function(){
	Route::get('/','RemessaVendaController@index')->name('remessa_venda.index');
	Route::post('/filtro','RemessaVendaController@filtro')->name('remessa_venda.filtro');
});

Route::prefix('coletor')->middleware(['auth'])->group(function(){
	Route::get('/','LogColetorController@index')->name('consulta_coletor.index');
	Route::post('/filtro','LogColetorController@filtro')->name('consulta_coletor.filtro');
	Route::prefix('modal')->group(function(){
		Route::post('/dialog', 'LogColetorController@dialog')->name('consulta_coletor.dialog');
		Route::post('/operacao', 'LogColetorController@modalOperacao')->name('consulta_coletor.operacao');
		Route::post('/defeito', 'LogColetorController@modalDefeito')->name('consulta_coletor.defeito');
		Route::post('/salvar', 'LogColetorController@salvarDefeito')->name('consulta_coletor.salvar_defeito');
		Route::post('/consulta', 'LogColetorController@modalConsultaDefeito')->name('consulta_coletor.consulta');
	});
});

Route::prefix('ficha_tecnica_comercial')->middleware(['auth'])->group(function(){
	Route::post('/buscar_filtro_detalhes','FichaTecnicaComercialController@buscarFiltroDetalhes')->name('ficha_tecnica_comercial.buscar_filtro_detalhes');
	Route::post('/buscar_filtro_detalhes_progamado','FichaTecnicaComercialController@buscarFiltroDetalhesProgramado')->name('ficha_tecnica_comercial.buscar_filtro_detalhes_progamado');
	Route::post('/salvar_filtro_detalhes','FichaTecnicaComercialController@salvarFiltroDetalhes')->name('ficha_tecnica_comercial.salvar_filtro_detalhes');
	Route::post('/verificar_carrinho','FichaTecnicaComercialController@verificarCarrinho')->name('ficha_tecnica_comercial.verificar_carrinho');
	Route::get('/pdf_ficha_comercial','FichaTecnicaComercialController@fichaProdutoPdf')->name('ficha_tecnica_comercial.gerar_pdf_produto');
	Route::prefix('modal')->group(function(){
		Route::post('/detalhes', 'FichaTecnicaComercialController@modalDetalhes')->name('ficha_tecnica_comercial.modal.detalhes');
		Route::post('/grupo', 'FichaTecnicaComercialController@modalGrupo')->name('ficha_tecnica_comercial.modal.grupo');
	
	});	
	
});

Route::prefix('stone_cadastro')->middleware(['auth'])->group(function(){
	Route::get('/','StoneIntegracaoCadastroController@index')->name('stone_cadastro.index');
	Route::post('/cadastrar','StoneIntegracaoCadastroController@cadastrar')->name('stone_cadastro.cadastrar');
	Route::post('/filtro','StoneIntegracaoCadastroController@filtro')->name('stone_cadastro.filtro');
	Route::post('/excluir','StoneIntegracaoCadastroController@excluirMaquininha')->name('stone_cadastro.excluir');
	Route::post('/configuracao_salvar','StoneIntegracaoCadastroController@configurar')->name('stone_cadastro.configuracao_salvar');
	Route::post('/excluir_vinculo','StoneIntegracaoCadastroController@excluirVinculo')->name('stone_cadastro.excluir_vinculo');
	Route::prefix('modal')->group(function(){
		Route::post('/cadastrar', 'StoneIntegracaoCadastroController@modalCadastrar')->name('stone_cadastro.modal.cadastrar');
		Route::post('/configurar', 'StoneIntegracaoCadastroController@modalConfigurar')->name('stone_cadastro.modal.configurar');
	});
});

Route::prefix('atualizacao_cron')->middleware(['auth'])->group(function(){
	Route::get('/', 'AtualizacaoCronController@index')->name('atualizacao_cron.index');
	Route::post('/filtro', 'AtualizacaoCronController@filtro')->name('atualizacao_cron.filtro');
});

Route::prefix('estado_gnre')->middleware(['auth'])->group(function(){
	Route::get('/','EstadoGnreController@index')->name('estado_gnre.index');
	Route::post('/filtro','EstadoGnreController@filtro')->name('estado_gnre.filtro');
	Route::post('/salvar','EstadoGnreController@adicionar')->name('estado_gnre.adicionar');
	Route::post('/editar','EstadoGnreController@editar')->name('estado_gnre.editar');
	Route::post('/excluir','EstadoGnreController@deletar')->name('estado_gnre.excluir');
	Route::prefix('modal')->group(function(){
		Route::post('/salvar', 'EstadoGnreController@modalAdicionar')->name('estado_gnre.modal.salvar');
		Route::post('/editar', 'EstadoGnreController@modalEditar')->name('estado_gnre.modal.editar');
		Route::post('/excluir', 'EstadoGnreController@modalDeletar')->name('estado_gnre.modal.excluir');

	});
});

Route::prefix('faturamento_previsto_fluxo_caixa')->middleware(['auth'])->group(function(){
	Route::prefix('modal')->group(function(){
		Route::post('/detalhes', 'FaturamentoPrevistoFluxoCaixaController@modalDetalhes')->name('faturamento_previsto_fluxo_caixa.modal.detalhes');
		Route::post('/detalhes_comparativo', 'FaturamentoPrevistoFluxoCaixaController@modalDetalhesComparativo')->name('faturamento_previsto_fluxo_caixa.modal.detalhes_comparativo');
	});	
});

Route::prefix('stone_pagamentos')->middleware(['auth'])->group(function(){
	Route::get('/','StonePagamentosController@index')->name('stone_pagamentos.index');
	Route::post('/filtro','StonePagamentosController@filtro')->name('stone_pagamentos.filtro');
	Route::post('/registrar','StonePagamentosController@registrar')->name('stone_pagamentos.registrar');
	Route::post('/editar','StonePagamentosController@editarPagamento')->name('stone_pagamentos.editar');
	Route::post('/registrar_pagamento_restante','StonePagamentosController@registrarRestante')->name('stone_pagamentos.registrar_pagamento_restante');
	Route::post('/editar_pagamento_restante','StonePagamentosController@editarPagamentoRestante')->name('stone_pagamentos.editar_pagamento_restante');
	Route::prefix('modal')->group(function(){
		Route::post('/registrar_pagamentos', 'StonePagamentosController@modalRegistrarPagamento')->name('stone_pagamentos.modal.registrar_pagamentos');
		Route::post('/editar_pagamentos', 'StonePagamentosController@modalEditarPagamento')->name('stone_pagamentos.modal.editar_pagamentos');
		Route::post('/registrar_pagamento_restante', 'StonePagamentosController@modalRegistrarPagamentoRestante')->name('stone_pagamentos.modal.registrar_pagamento_restante');
		Route::post('/editar_pagamento_restante', 'StonePagamentosController@modalEditarPagamentoRestante')->name('stone_pagamentos.modal.editar_pagamento_restante');
	});
});

Route::prefix('compras_previsto_fluxo_caixa')->middleware(['auth'])->group(function(){
	Route::prefix('modal')->group(function(){
		Route::post('/detalhes', 'ComprasPrevistoFluxoCaixaController@modalDetalhes')->name('compras_previsto_fluxo_caixa.modal.detalhes');
	});	
});

Route::prefix('book_virtual_new')->middleware(['auth'])->group(function(){
	Route::prefix('cadastrar')->group(function(){
		Route::get('/', 'BookVirtualControllerNew@cadastroIndex')->name('book_virtual_new.cadastro.index');
		Route::post('/filtrar', 'BookVirtualControllerNew@cadastroFilter')->name('book_virtual_new.cadastro.filter');
		Route::post('/filtrarchild', 'BookVirtualControllerNew@cadastroFilterChild')->name('book_virtual_new.cadastro.filter_child');
		Route::post('/editar', 'BookVirtualControllerNew@cadastroEditar')->name('book_virtual_new.cadastro.editar');
		Route::prefix('modal')->group(function(){
			Route::post('/editar', 'BookVirtualControllerNew@cadastroModalEditar')->name('book_virtual_new.cadastro.modal.editar');
		});

		Route::prefix('desenho')->group(function(){
			Route::post('/salvar', 'BookVirtualControllerNew@salvarDesenho')->name('book_virtual_new.desenho.salvar');
			Route::post('/editar', 'BookVirtualControllerNew@editarDesenho')->name('book_virtual_new.desenho.editar');
		});

		Route::prefix('desenho')->group(function(){
			Route::post('/deletar', 'BookVirtualControllerNew@excluirDesenho')->name('book_virtual_new.desenho.deletar');
		});
	});
});

Route::prefix('produto_defeito')->middleware(['auth'])->group(function(){
	Route::get('/','ProdutoDefeitoController@index')->name('produto_defeito.index');
	Route::post('/filtro','ProdutoDefeitoController@filtro')->name('produto_defeito.filtro');
	Route::post('/salvar','ProdutoDefeitoController@salvar')->name('produto_defeito.salvar');
	Route::post('/editar','ProdutoDefeitoController@editar')->name('produto_defeito.editar');
	Route::post('/excluir','ProdutoDefeitoController@deletar')->name('produto_defeito.excluir');
	Route::prefix('modal')->group(function(){
		Route::post('/salvar', 'ProdutoDefeitoController@modalAdicionar')->name('produto_defeito.modal.salvar');
		Route::post('/editar', 'ProdutoDefeitoController@modalEditar')->name('produto_defeito.modal.editar');
		Route::post('/excluir', 'ProdutoDefeitoController@modalDeletar')->name('produto_defeito.modal.excluir');
	});
});

Route::prefix('romaneio')->middleware(['auth'])->group(function(){
	Route::get('/','RomaneioController@index')->name('romaneio.index');
	Route::post('/filtro','RomaneioController@filtro')->name('romaneio.filtro');
	Route::prefix('modal')->group(function(){
		Route::post('/dialog', 'RomaneioController@dialog')->name('romaneio.dialog');
		Route::post('/operacao', 'RomaneioController@modalOperacao')->name('romaneio.operacao');
		Route::post('/finaliza', 'RomaneioController@modalFinaliza')->name('romaneio.modal.finaliza');
	});
	Route::post('guarda_mercadoria','RomaneioController@guardaMercadoria')->name('romaneio.guarda_mercadoria');
	Route::post('finaliza_processo','RomaneioController@finalizaProcesso')->name('romaneio.finaliza_processo');
	Route::post('/dialog_defeito', 'RomaneioController@dialogDefeito')->name('romaneio.dialog_defeito');
});

Route::prefix('motivo_cancelamento_pedido')->middleware(['auth'])->group(function(){
	Route::get('/', 'MotivoCancelamentoPedidoController@index')->name('motivo_cancelamento_pedido.index');
	Route::post('/filtrar', 'MotivoCancelamentoPedidoController@filter')->name('motivo_cancelamento_pedido.filter');
	Route::post('/adicionar', 'MotivoCancelamentoPedidoController@adicionar')->name('motivo_cancelamento_pedido.adicionar');
	Route::post('/editar', 'MotivoCancelamentoPedidoController@editar')->name('motivo_cancelamento_pedido.editar');
	Route::post('/deletar', 'MotivoCancelamentoPedidoController@deletar')->name('motivo_cancelamento_pedido.deletar');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'MotivoCancelamentoPedidoController@modalAdicionar')->name('motivo_cancelamento_pedido.modal.adicionar');
		Route::post('/editar', 'MotivoCancelamentoPedidoController@modalEditar')->name('motivo_cancelamento_pedido.modal.editar');
		Route::post('/deletar', 'MotivoCancelamentoPedidoController@modalDeletar')->name('motivo_cancelamento_pedido.modal.deletar');
		Route::post('/cancelamento_pedido_nasajon', 'MotivoCancelamentoPedidoController@modalCancelamentoPedidoNasajon')->name('motivo_cancelamento_pedido.modal.cancelamento_pedido_nasajon');
	});
});

Route::prefix('consulta_cliente_grupo')->middleware(['auth'])->group(function(){
	Route::get('/','ConsultaClienteGrupoController@index')->name('consulta_cliente_grupo.index');
	Route::post('/filtro','ConsultaClienteGrupoController@filtro')->name('consulta_cliente_grupo.filtro');
	Route::prefix('modal')->group(function(){
		Route::post('/detalhes', 'ConsultaClienteGrupoController@modalDetalhes')->name('consulta_cliente_grupo.modal.detalhes');
		Route::post('/detalhes_clientes', 'ConsultaClienteGrupoController@modalDetalhesCliente')->name('consulta_cliente_grupo.modal.detalhes_clientes');
		Route::post('/detalhes_vendedor', 'ConsultaClienteGrupoController@modalDetalhesVendedor')->name('consulta_cliente_grupo.modal.detalhes_vendedor');
	});
});

Route::prefix('romaneio_entrada')->middleware(['auth'])->group(function(){
	Route::get('/','RomaneioEntradaController@index')->name('romaneio_entrada.index');
	Route::post('/filtro','RomaneioEntradaController@filtro')->name('romaneio_entrada.filtro');
	Route::post('/solucao', 'RomaneioEntradaController@solucao')->name('romaneio_entrada.solucao');
	Route::prefix('modal')->group(function(){
		Route::post('/dialog', 'RomaneioEntradaController@dialog')->name('romaneio_entrada.dialog');
		Route::post('/operacao', 'RomaneioEntradaController@modalOperacao')->name('romaneio_entrada.operacao');
		Route::post('/solucao', 'RomaneioEntradaController@modalSolucao')->name('romaneio_entrada.modal.solucao');
		Route::post('/dialog_defeito', 'RomaneioEntradaController@dialogDefeito')->name('romaneio_entrada.dialog_defeito');
		
	});
});

Route::prefix('motivo_divergencia')->middleware(['auth'])->group(function(){
	Route::get('/','MotivoDivergenciaController@index')->name('motivo_divergencia.index');
	Route::post('/filtro','MotivoDivergenciaController@filtro')->name('motivo_divergencia.filtro');
	Route::post('/salvar','MotivoDivergenciaController@salvar')->name('motivo_divergencia.salvar');
	Route::post('/editar','MotivoDivergenciaController@editar')->name('motivo_divergencia.editar');
	Route::post('/excluir','MotivoDivergenciaController@deletar')->name('motivo_divergencia.excluir');
	Route::prefix('modal')->group(function(){
		Route::post('/salvar', 'MotivoDivergenciaController@modalAdicionar')->name('motivo_divergencia.modal.salvar');
		Route::post('/editar', 'MotivoDivergenciaController@modalEditar')->name('motivo_divergencia.modal.editar');
		Route::post('/excluir', 'MotivoDivergenciaController@modalDeletar')->name('motivo_divergencia.modal.excluir');
	});
});

Route::prefix('red')->middleware(['auth'])->group(function(){
	Route::get('/','RedController@index')->name('red.index');
	Route::post('/filtro','RedController@filtro')->name('red.filtro');
	Route::post('/adicionar','RedController@adicionar')->name('red.adicionar');
	Route::post('/editar','RedController@editar')->name('red.editar');
	Route::post('/deletar','RedController@deletar')->name('red.deletar');
	Route::post('/autocomplete', 'RedController@autoComplete')->name('red.autocomplete');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'RedController@modalAdicionar')->name('red.modal.adicionar');
		Route::post('/editar', 'RedController@modalEditar')->name('red.modal.editar');
		Route::post('/deletar', 'RedController@modalDeletar')->name('red.modal.deletar');
		Route::post('/buscar', 'RedController@modalBuscar')->name('red.modal.buscar');
		Route::post('/detalhes', 'RedController@modalDetalhes')->name('red.modal.detalhes');
	});
});

Route::prefix('pendencia_pagamento_processo_importacao')->middleware(['auth'])->group(function(){
	Route::get('/','PendenciaPagamentoProcessoImportacaoController@index')->name('pendencia_pagamento_processo_importacao.index');
	Route::post('/filtro','PendenciaPagamentoProcessoImportacaoController@filtro')->name('pendencia_pagamento_processo_importacao.filtro');
	Route::prefix('modal')->group(function(){
		Route::post('/detalhes', 'PendenciaPagamentoProcessoImportacaoController@modalDetalhes')->name('pendencia_pagamento_processo_importacao.modal.detalhes');
	});
});

Route::prefix('transportadora_estabelecimento')->middleware(['auth'])->group(function(){
	Route::get('/','TransportadoraEstabelecimentoController@index')->name('transportadora_estabelecimento.index');
	Route::post('/filtro','TransportadoraEstabelecimentoController@filtro')->name('transportadora_estabelecimento.filtro');
	Route::post('/filtro_modal_dialog','TransportadoraEstabelecimentoController@filterModalDialog')->name('transportadora_estabelecimento.filtro_modal_dialog');
	Route::post('/salvar','TransportadoraEstabelecimentoController@salvar')->name('transportadora_estabelecimento.salvar');
	Route::post('/editar','TransportadoraEstabelecimentoController@editar')->name('transportadora_estabelecimento.editar');
	Route::post('/excluir','TransportadoraEstabelecimentoController@deletar')->name('transportadora_estabelecimento.excluir');
	Route::post('/autocomplete', 'TransportadoraEstabelecimentoController@autocomplete')->name('transportadora_estabelecimento.autocomplete');
	Route::post('/autocomplete_redespacho', 'TransportadoraEstabelecimentoController@autocompleteRedespacho')->name('transportadora_estabelecimento.autocomplete_redespacho');
	Route::prefix('modal')->group(function(){
		Route::post('/salvar', 'TransportadoraEstabelecimentoController@modalAdicionar')->name('transportadora_estabelecimento.modal.salvar');
		Route::post('/editar', 'TransportadoraEstabelecimentoController@modalEditar')->name('transportadora_estabelecimento.modal.editar');
		Route::post('/excluir', 'TransportadoraEstabelecimentoController@modalDeletar')->name('transportadora_estabelecimento.modal.excluir');
		Route::post('/dialog', 'TransportadoraEstabelecimentoController@modalDialog')->name('transportadora_estabelecimento.modal.dialog');
	});		
});

Route::prefix('book_virtual_exibicao_new')->middleware(['auth'])->group(function(){
	Route::get('/','BookVirtualExibicaoControllerNew@index')->name('book_virtual_exibicao_new.index');
	Route::get('/busca/{segmentos_id}/{marca}/{linha}/{navegacao}','BookVirtualExibicaoControllerNew@busca')->name('book_virtual_exibicao_new.busca');
	Route::post('filter','BookVirtualExibicaoControllerNew@filter')->name('book_virtual_exibicao_new.filter');
	Route::get('book/{id}/{grupo}/{codigo_produto}/{codigos_produtos}/{navegacao}','BookVirtualExibicaoControllerNew@bookVirtualExibicao')->name('book_virtual_exibicao_new.exibicao');
	Route::post('book_liso','BookVirtualExibicaoControllerNew@bookLisos')->name('book_virtual_exibicao_new.book_lisos');
	Route::post('book_desenho','BookVirtualExibicaoControllerNew@bookDesenho')->name('book_virtual_exibicao_new.book_desenho');
	Route::post('filtro_segmento','BookVirtualExibicaoControllerNew@filtroSegmetos')->name('book_virtual_exibicao_new.segmentos');
	Route::post('gerar_link_busca', 'BookVirtualExibicaoControllerNew@gerarLinkBusca')->name('book_virtual_exibicao_new.gerar_link_busca');	
	Route::post('fitro_precos', 'BookVirtualExibicaoControllerNew@filtroEstoquePrecos')->name('book_virtual_exibicao_new.estoque_precos');
	Route::post('fitro_carrinho', 'BookVirtualExibicaoControllerNew@filtroCarrinho')->name('book_virtual_exibicao_new.filtro');
	Route::post('adicionar', 'BookVirtualExibicaoControllerNew@adicionarCarrinho')->name('book_virtual_exibicao_new.adicionar');
	Route::post('adicionar_transportadora', 'BookVirtualExibicaoControllerNew@editarTransportadora')->name('book_virtual_exibicao_new.editar_transportadora');
	Route::post('atualizar_carrinho', 'BookVirtualExibicaoControllerNew@atualizarItensCarrinho')->name('book_virtual_exibicao_new.atualizar_carrinho');
	Route::post('adicionar_cliente', 'BookVirtualExibicaoControllerNew@adicionarCliente')->name('book_virtual_exibicao_new.adicionar_cliente');
	Route::post('editar_cliente', 'BookVirtualExibicaoControllerNew@editarCliente')->name('book_virtual_exibicao_new.editar_cliente');
	Route::post('finalizar', 'BookVirtualExibicaoControllerNew@finalizarCarrinho')->name('book_virtual_exibicao_new.finalizar');
	Route::post('deletar_item', 'BookVirtualExibicaoControllerNew@deletarItemCarrinho')->name('book_virtual_exibicao_new.deletar_item');
	Route::post('cancelar_pedido', 'BookVirtualExibicaoControllerNew@cancelarPedidoCarrinho')->name('book_virtual_exibicao_new.cancelar_pedido');
	Route::post('excluir_carrinho', 'BookVirtualExibicaoControllerNew@excluirCarrinho')->name('book_virtual_exibicao_new.excluir_carrinho');
	Route::get('download/{id}/{filter}/{link?}', 'BookVirtualExibicaoControllerNew@downloadPDF')->name('book_virtual_exibicao_new.download');
	Route::get('link/{id}/{filter}/{link}', 'BookVirtualExibicaoControllerNew@downloadPDF')->name('book_virtual_exibicao_new.link');
	Route::post('enviar', 'BookVirtualExibicaoControllerNew@envioPDFEmail')->name('book_virtual_exibicao_new.enviar');
	Route::post('pdf_ficha', 'BookVirtualExibicaoControllerNew@gerarPdfFichatecnica')->name('book_virtual_exibicao_new.pdf_ficha');
	Route::post('deletar_cliente', 'BookVirtualExibicaoControllerNew@deletarCliente')->name('book_virtual_exibicao_new.deletar_cliente');
	Route::post('book_produtos', 'BookVirtualExibicaoControllerNew@bookDesenhoProdutos')->name('book_virtual_exibicao_new.book_produtos');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar', 'BookVirtualExibicaoControllerNew@modalAdicionar')->name('book_virtual_exibicao_new.modal.adicionar');
		Route::post('/adicionar_transportadora', 'BookVirtualExibicaoControllerNew@modalAdicionarTransportadora')->name('book_virtual_exibicao_new.modal.adicionar_transportadora');
		Route::post('/editar_transportadora', 'BookVirtualExibicaoControllerNew@modalEditarTransportadora')->name('book_virtual_exibicao_new.modal.editar_transportadora');
		Route::post('/editar', 'BookVirtualExibicaoControllerNew@modalEditarCarrinho')->name('book_virtual_exibicao_new.modal.editar');
		Route::post('/deletar', 'BookVirtualExibicaoControllerNew@modalDeletarCarrinho')->name('book_virtual_exibicao_new.modal.deletar');
		Route::post('/cancelar', 'BookVirtualExibicaoControllerNew@modalCancelarPedidoCarrinho')->name('book_virtual_exibicao_new.modal.cancelar');
		Route::post('/excluir', 'BookVirtualExibicaoControllerNew@modalExcluirCarrinho')->name('book_virtual_exibicao_new.modal.excluir');
		Route::post('/zoom', 'BookVirtualExibicaoControllerNew@modalZoom')->name('book_virtual_exibicao_new.modal.zoom');
		Route::post('/ficha_tecnica', 'BookVirtualExibicaoControllerNew@modalFichaTecnica')->name('book_virtual_exibicao_new.modal.ficha_tecnica');
		Route::post('/pdf', 'BookVirtualExibicaoControllerNew@modalBaixarEnviarPDF')->name('book_virtual_exibicao_new.modal.pdf');
		Route::post('/enviar_pdf', 'BookVirtualExibicaoControllerNew@modalEnviarPDF')->name('book_virtual_exibicao_new.modal.enviar_pdf');
		Route::post('/deletar_cliente', 'BookVirtualExibicaoControllerNew@modalDeletarCliente')->name('book_virtual_exibicao_new.modal.deletar_cliente');
	});
});

Route::prefix('titulo_excluido')->middleware(['auth'])->group(function(){
	Route::get('/','TituloExcluidoController@index')->name('titulo_excluido.index');
	Route::post('/filtro','TituloExcluidoController@filtro')->name('titulo_excluido.filtro');
	Route::prefix('modal')->group(function(){
		Route::post('/titulos','TituloExcluidoController@modalTitulos')->name('titulo_excluido.modal.titulos');
		Route::post('/detalhes', 'TituloExcluidoController@modalTitulosAbertura')->name('titulo_excluido.modal.titulos_abertura');
		Route::post('/cliente','TituloExcluidoController@modalCLiente')->name('titulo_excluido.modal.cliente');
		Route::post('/representantes','TituloExcluidoController@modalRepresentante')->name('titulo_excluido.modal.representantes');
		Route::post('/representantes_titulo','TituloExcluidoController@modalTitulosRepresentante')->name('titulo_excluido.modal.titulo.representantes');
	});
});

Route::prefix('cobranca_ragazzi')->middleware(['auth'])->group(function(){
	Route::get('/','CobrancaRagazziController@index')->name('cobranca_ragazzi.index');
	Route::post('/filter','CobrancaRagazziController@filter')->name('cobranca_ragazzi.filter');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/cliente','CobrancaRagazziController@modalCLiente')->name('cobranca_ragazzi.modal.cliente');
		Route::post('/titulos','CobrancaRagazziController@modalTitulos')->name('cobranca_ragazzi.modal.titulos');
		Route::post('/representantes','CobrancaRagazziController@modalRepresentante')->name('cobranca_ragazzi.modal.representantes');
		Route::post('/titulos_cenprot','CobrancaRagazziController@modalTitulosCenprot')->name('cobranca_ragazzi.modal.titulo.titulos_cenprot');
		Route::post('/titulos_abertura','CobrancaRagazziController@modalTitulosAbertura')->name('cobranca_ragazzi.modal.titulo.titulos_abertura');
	});
});

Route::prefix('cliente_triangular')->middleware(['auth'])->group(function(){
	Route::get('/','ClienteTriangularController@index')->name('cliente_triangular.index');
	Route::post('/adicionar','ClienteTriangularController@adicionar')->name('cliente_triangular.adicionar');
	Route::post('/deletar','ClienteTriangularController@deletar')->name('cliente_triangular.deletar');
	Route::post('/filter','ClienteTriangularController@filtro')->name('cliente_triangular.filtro');
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar', 'ClienteTriangularController@modalAdicionar')->name('cliente_triangular.modal.adicionar');
		Route::post('/deletar', 'ClienteTriangularController@modalDeletar')->name('cliente_triangular.modal.deletar');
	});
});

Route::prefix('icms_estoque_armazem')->middleware(['auth'])->group(function(){
	Route::get('/','IcmsEstoqueArmazemController@index')->name('icms_estoque_armazem.index');
	Route::post('/filtro','IcmsEstoqueArmazemController@filtro')->name('icms_estoque_armazem.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/entrada','IcmsEstoqueArmazemController@modalEntrada')->name('icms_estoque_armazem.modal.entrada');
		Route::post('/saida','IcmsEstoqueArmazemController@modalSaida')->name('icms_estoque_armazem.modal.saida');
	});
});

Route::prefix('valor_estoque_armazem')->middleware(['auth'])->group(function(){
	Route::get('/','ValorEstoqueArmazemController@index')->name('valor_estoque_armazem.index');
	Route::post('/filtro','ValorEstoqueArmazemController@filtro')->name('valor_estoque_armazem.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/entrada','ValorEstoqueArmazemController@modalEntrada')->name('valor_estoque_armazem.modal.entrada');
		Route::post('/saida','ValorEstoqueArmazemController@modalSaida')->name('valor_estoque_armazem.modal.saida');
	});
});


Route::prefix('pedidos_compras_abertos_titulos_futuros')->middleware(['auth'])->group(function(){
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/detalhes','PedidoComprasAbertoTituloFuturoController@modalDetalhes')->name('pedidos_compras_abertos_titulos_futuros.modal.detalhes');
	});
});

Route::prefix('video')->middleware(['auth'])->group(function(){
	Route::get('/','VideoController@index')->name('video.index');
	Route::post('/filtro','VideoController@filtro')->name('video.filtro');
	Route::post('/salvar','VideoController@salvar')->name('video.salvar');
	Route::post('/editar','VideoController@editar')->name('video.editar');
	Route::post('/excluir','VideoController@deletar')->name('video.excluir');
	Route::prefix('modal')->group(function(){
		Route::post('/salvar', 'VideoController@modalAdicionar')->name('video.modal.salvar');
		Route::post('/editar', 'VideoController@modalEditar')->name('video.modal.editar');
		Route::post('/excluir', 'VideoController@modalDeletar')->name('video.modal.excluir');
		Route::post('/video', 'VideoController@modalVideo')->name('video.modal.video');
	});
});

Route::prefix('tutorial')->middleware(['auth'])->group(function(){
	Route::get('/','VideoController@indexTutorial')->name('tutorial.index');
	Route::post('/filtro','VideoController@filtroTutorial')->name('tutorial.filtro');
	Route::prefix('modal')->group(function(){
		Route::post('/dialog', 'VideoController@modalTutorial')->name('tutorial.modal.dialog');
		Route::post('/video', 'VideoController@modalVideoTutorial')->name('tutorial.modal.video');
	});
});

Route::prefix('rastreabilidade')->middleware(['auth'])->group(function(){
	Route::get('/','RastreabilidadeController@index')->name('rastreabilidade.index');
	Route::post('/filtro','RastreabilidadeController@filtro')->name('rastreabilidade.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/fracoes','RastreabilidadeController@modalRastreamentoFracoes')->name('rastreabilidade.modal.fracoes');
	});
});

Route::prefix('faturamento_feira')->middleware(['auth'])->group(function(){
	Route::get('/','FaturamentoFeiraController@index')->name('faturamento_feira.index');
	Route::post('/filtro','FaturamentoFeiraController@filtro')->name('faturamento_feira.filtro');
	Route::prefix('modal')->middleware(['auth'])->group(function(){
		Route::post('/detalhes_produtos','FaturamentoFeiraController@modalDetalhesProdutos')->name('faturamento_feira.modal.detalhes_produtos');
	});
});

Route::prefix('contato_emergencia')->middleware(['auth'])->group(function(){
	Route::get('/','ContatoDeEmergenciaController@index')->name('contato_emergencia.index');
	Route::post('/filtro','ContatoDeEmergenciaController@filtro')->name('contato_emergencia.filtro');
	Route::post('/autocomplete','ContatoDeEmergenciaController@autoCompleteUsuario')->name('usuario.autocomplete');
	Route::post('/salvar','ContatoDeEmergenciaController@salvar')->name('contato_emergencia.salvar');
	Route::post('/editar','ContatoDeEmergenciaController@editar')->name('contato_emergencia.editar');
	Route::post('/excluir', 'ContatoDeEmergenciaController@deletar')->name('contato_emergencia.excluir');
	Route::prefix('modal')->group(function(){
		Route::post('/salvar', 'ContatoDeEmergenciaController@modalAdicionar')->name('contato_emergencia.modal.salvar');
		Route::post('/editar', 'ContatoDeEmergenciaController@modalEditar')->name('contato_emergencia.modal.editar');
		Route::post('/excluir', 'ContatoDeEmergenciaController@modalDeletar')->name('contato_emergencia.modal.excluir');
	});
});

Route::prefix('promocao_ultra_black')->middleware(['auth'])->group(function(){
	Route::post('/adicionar_ultra_black','PromocaoUltraBlackController@adicionar')->name('promocao_ultra_black.adicionar_ultra_black');
	Route::post('/excluir_ultra_black','PromocaoUltraBlackController@excluir')->name('promocao_ultra_black.excluir_ultra_black');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar','PromocaoUltraBlackController@modalAdicionar')->name('promocao_ultra_black.modal.adicionar');
	});
});

Route::prefix('tempo_espera_pedido')->middleware(['auth'])->group(function(){
	Route::get('/','TempoEsperaPedidoController@index')->name('tempo_espera_pedido.index');
	Route::post('/filtro','TempoEsperaPedidoController@filtro')->name('tempo_espera_pedido.filtro');
	Route::post('/cadastrar','TempoEsperaPedidoController@cadastrarTempoEspera')->name('tempo_espera_pedido.cadastrar');
	Route::prefix('modal')->group(function(){
		Route::post('/adicionar','TempoEsperaPedidoController@modalCadastrarTempoEspera')->name('tempo_espera_pedido.modal.cadastrar_tempo_espera');
	});
});

Route::prefix('tempo_espera_pedido')->group(function(){
	Route::get('/tempo_espera/{filter}','TempoEsperaPedidoController@tempoEspera')->name('formulario.pesquisa');
	Route::post('/monitorar_tempo_espera','TempoEsperaPedidoController@monitorarTempoEspera')->name('tempo_espera_pedido.monitorar');
	
	Route::prefix('tempo_espera_pedido_separacao')->middleware(['auth'])->group(function(){
		Route::get('/','TempoEsperaPedidoSeparacaoController@index')->name('tempo_espera_pedido_separacao.index');
		Route::post('/filtro','TempoEsperaPedidoSeparacaoController@filtro')->name('tempo_espera_pedido_separacao.filtro');
	});
});

Route::get('/manual_cliente', function(){
	if(!empty(env('APP_HOST'))){
		if((env('APP_HOST') == 'HOMOLOGAÇÃO')){
			return redirect('http://portal.mntecidos.com.br:88/pdf/manual_do_cliente.pdf/');
		}else{
			return redirect('http://localhost/pdf/manual_do_cliente.pdf');
		}
	}else{
		return redirect('http://portal.mntecidos.com.br/pdf/manual_do_cliente.pdf/');
	}
})->name('manual.cliente');

Route::prefix('campanhas')->middleware(['auth'])->group(function(){
	Route::get('/','CampanhasController@index')->name('campanha.index');
	Route::post('/filtro','CampanhasController@filtro')->name('campanha.filtro');
	Route::post('/buscar_produtos','CampanhasController@buscarProdutos')->name('campanha.buscar_produtos');
	Route::post('/registrar','CampanhasController@registrarCampanha')->name('campanha.registrar');
	Route::post('/remover_produto','CampanhasController@removerProdutoCampanha')->name('campanha.remover_produto');
	Route::post('/editar','CampanhasController@editarCampanha')->name('campanha.editar');
	Route::post('/excluir','CampanhasController@desativarCampanha')->name('campanha.excluir');
	Route::post('/export_campanha','CampanhasController@exportCampanha')->name('campanha.export_excel');
	Route::post('/ativar_produto','CampanhasController@ativarProdutoCampanha')->name('campanha.ativar_produto');
	Route::post('/importar_produtos','CampanhasController@importarProtudosArquivo')->name('campanha.importar_produtos');

	Route::prefix('mapa')->group(function(){
		Route::get('/','CampanhasController@campanhaMapa')->name('campanha_mapa.index');
		Route::get('/gerente','CampanhasController@campanhaMapaGerente')->name('campanha_mapa_gerentes.index');
		Route::get('/diretoria','CampanhasController@campanhaMapaDiretoria')->name('campanha_mapa_diretoria.index');
		Route::post('/filtro_meta','CampanhasController@filtroCampanhaMeta')->name('campanha.meta.filtro_meta');
		Route::post('/filtro_meta_gerentes','CampanhasController@filtroCampanhaMetaGerentes')->name('campanha.meta.filtro_meta_gerentes');
		Route::post('/filtro_meta_diretor','CampanhasController@filtroCampanhaMetaDiretor')->name('campanha.meta.filtro_meta_diretor');
		Route::post('/auto_complete_campanha','CampanhasController@autoCompleteCampanhaNome')->name('campanha.meta.auto_complete');
		Route::post('/listar_periodos','CampanhasController@listarPeriodos')->name('campanha.meta.listar_periodos');
		Route::prefix('modal')->group(function(){
			Route::post('/valor_bruto','CampanhasController@modalValorBruto')->name('campanha.modal.valor_bruto');
			Route::post('/lista_nota_devolucao','CampanhasController@modalDevolucao')->name('campanha.modal.lista_nota_devolucao');
			Route::post('/segmentos_gerentes','CampanhasController@modalSegmentosGerentes')->name('campanha.modal.segmentos_gerentes');
			Route::post('/equipes_gerentes','CampanhasController@modalEquipesGerentes')->name('campanha.modal.equipes_gerentes');
		});
	});
	
	Route::prefix('modal')->group(function(){
		Route::get('/adicionar','CampanhasController@modalInserirCampanha')->name('campanha.modal.inserir');
		Route::post('/pesquisa_produto','CampanhasController@modalPesquisaProduto')->name('campanha.modal.pesquisa_produto');
		Route::post('/editar_campanha','CampanhasController@modalEditarCampanha')->name('campanha.modal.editar');
		Route::post('/consultar','CampanhasController@consultarCampanha')->name('campanha.consultar');
	});
});

Route::prefix('assinatura')->middleware(['auth'])->group(function(){
	Route::get('/','AssinaturaController@index')->name('assinatura.index');
	Route::post('/atualiza','AssinaturaController@atualizar')->name('assinatura.atualiza');
});

Route::prefix('inventario_novo')->middleware(['auth'])->group(function () {
	Route::get('/', 'InventarioNovoController@index')->name('inventario_novo.index');
	Route::post('/filtro', 'InventarioNovoController@filtro')->name('inventario_novo.filtro');
	Route::post('/iniciar', 'InventarioNovoController@iniciar')->name('inventario_novo.iniciar');
	Route::post('/finalizar','InventarioNovoController@finalizar')->name('inventario_novo.finalizar');
	Route::post('/exportar_excel','InventarioNovoController@exportarExcel')->name('inventario_novo.exportar_excel');
	Route::post('/exportar_produto_excel','InventarioNovoController@exportarProdutoExcel')->name('inventario_novo.exportar_produto_excel');
	Route::post('/exportar_log_excel','InventarioNovoController@exportarLogExcel')->name('inventario_novo.exportar_log_excel');
	Route::post('/exportar_peca_nao_encontrada_excel','InventarioNovoController@exportarPecasNaoEncontradaExcel')->name('inventario_novo.exportar_peca_nao_encontrada_excel');
	Route::post('/download','InventarioNovoController@download')->name('inventario_novo.download');
	Route::prefix('modal')->group(function(){
		Route::get('/iniciar','InventarioNovoController@modalIniciar')->name('inventario_novo.modal.iniciar');
		Route::post('/finalizar','InventarioNovoController@modalFinalizar')->name('inventario_novo.modal.finalizar');
		Route::post('/detalhes_produtos','InventarioNovoController@modalDetalhesProdutos')->name('inventario_novo.modal.detalhes_produtos');
		Route::post('/detalhes_pecas','InventarioNovoController@modalDetalhesPecas')->name('inventario_novo.modal.detalhes_pecas');
	});
});

Route::prefix('representante')->group(function(){
	Route::get('/trocar_senha/{hash}','RepresentanteController@novaSenhaIndex')->name('representante.trocar_senha');
	Route::post('/salvar_nova_senha','RepresentanteController@salvarNovaSenha')->name('representante.salvar_nova_senha');
});
