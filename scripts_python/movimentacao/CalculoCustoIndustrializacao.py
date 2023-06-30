# coding: utf-8
import time
from datetime import datetime, timedelta
import psycopg2
import psycopg2.extras
import sys
import os
from dotenv import load_dotenv
from LogExecucao import LogExecucao

class ImportacaoPrepago:
	def __init__(self):
		self.conn_portal = ''
		load_dotenv('../../.env')

		self.openConnecion()

		LogExecucao('calculo_custo_industrializado', False)
		try:
			self.ini()
		except OSError as err:
			print '{0}'. format(err)
		except (RuntimeError, TypeError, NameError) as err:
			print '{0}'. format(err)

		LogExecucao('calculo_custo_industrializado', True)

		self.exit_program()

	def closeConnection(self):
		self.conn_portal.close()

		self.conn_portal = ''

	def openConnecion(self):
		try:
			connect_str = "dbname='{0}' user='{1}' password='{2}' host='{3}'". format(os.getenv('DB_DATABASE'), os.getenv('DB_USERNAME'), os.getenv('DB_PASSWORD'), os.getenv('DB_HOST'))
			self.conn_portal = psycopg2.connect(connect_str)
		except Exception as e:
			print e
			exit(0)

	def exit_program(self):
		self.closeConnection()
		print '\n\nexit'

	def tratarArrayMovimento(self, movimentos):
		array = {}
		for movimento in movimentos:
			array[movimento['produto_codigo']] = movimento
		return array

	def movimentacaoIndustrializacao(self, data_ini, data_fim):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sqlBusca = """
		select
			*
		from
			movimentacao_recalculo
		where
			data_movimentacao >= '2022-08-01'
			and cfop in ('1124', '2124', '1125', '2125')
			and (preco_pcmn = 0 or preco_pcmn is null or custo_sem_imposto is null or custo_sem_imposto = 0 or custo = 0 or custo is null)
		order by
			estabelecimento,
			produto_codigo,
			data_movimentacao,
			tipo_operacao,
			id
		""". format(data_ini, data_fim)

		cursor.execute(sqlBusca)
		movimentacoes = cursor.fetchall()
		cursor.close()
		return movimentacoes

	def buscaFichaTecninca(self, produto):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sqlBusca = """
		select * from ficha_tecnica_produtos where codigo_produto = '{0}' and deleted_at is null
		""". format(produto)
		cursor.execute(sqlBusca)
		ficha = cursor.fetchone()
		cursor.close()
		return ficha

	def buscaDadosFichaTecninca(self, ficha_tecnica, estabelecimento):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

		sqlBusca = "select * from ficha_tecnica_produto_tecidos where ficha_tecnica_produtos_id = {0} and deleted_at is null;". format(ficha_tecnica)
		cursor.execute(sqlBusca)
		tecidos = cursor.fetchall()

		sqlBusca = "select * from ficha_tecnica_produto_servicos where ficha_tecnica_produtos_id = {0} and deleted_at is null;". format(ficha_tecnica)
		cursor.execute(sqlBusca)
		servicos = cursor.fetchall()

		sqlBusca = "select * from ficha_tecnica_produto_insumos where ficha_tecnica_produtos_id = {0} and deleted_at is null;". format(ficha_tecnica)
		cursor.execute(sqlBusca)
		insumos = cursor.fetchall()

		cursor.close()
		return self.tratarProdutosFichaTecnica({"tecidos":tecidos, "servicos":servicos, "insumos": insumos}, estabelecimento)

	def tratarProdutosFichaTecnica(self, fichas_tecnicas, estabelecimento):
		for chave, ficha_tecnica in fichas_tecnicas.items():
			for id, valores in enumerate(ficha_tecnica):
				if chave == 'servicos':
					fichas_tecnicas[chave][id].append(self.buscaPrecos(valores['codigo_produto']))
				else:
					fichas_tecnicas[chave][id].append(self.buscaCusto(valores['codigo_produto'], estabelecimento))
		return fichas_tecnicas

	def buscaPrecos(self, produto):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sqlBusca = """
		select * from precos where codigo_produto = '{0}' and deleted_at is null limit 1;
		""". format(produto)
		cursor.execute(sqlBusca)
		custos = cursor.fetchone()
		cursor.close()
		return self.tratarpreco(custos)

	def buscaPreco(self, produto):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sqlBusca = """
		select * from precos where codigo_produto = '{0}' and deleted_at is null limit 1;
		""". format(produto)
		cursor.execute(sqlBusca)
		preco = cursor.fetchone()
		cursor.close()
		if preco is not None:
			return preco
		else:
			return {'compra_real': 0, 'preco_real': 0}

	def buscaCusto(self, produto, estabelecimento):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sqlBusca = """
		select
			produtos_custos.produto_codigo,
			produtos_custos.custo_medio_contabil,
			produtos_custos.custo_medio_gerencial
		from
			produtos_custos
		where
			estabelecimento = '{1}' and
			produto_codigo = '{0}';
		""". format(produto, estabelecimento)
		cursor.execute(sqlBusca)
		custos = cursor.fetchall()
		cursor.close()
		if(len(custos) > 0):
			return self.tratarCustos(custos)
		else:
			return self.buscaPrecos(produto)

	def tratarpreco(self, preco):
		retorno = {'contabil': 0, 'gerencial': 0}
		if preco is None or preco['compra_real'] is None or float(preco['compra_real']) == 0:
			preco['compra_real'] = float(preco['preco_real']) / 1.35
		retorno = {'contabil': 0, 'gerencial': float(preco['compra_real'])}

		return retorno

	def tratarCustos(self, custos):
		retorno = {'contabil': 0, 'gerencial': 0}
		if len(custos) == 0:
			return retorno
		temp = {'contabil': []}
		for custo in custos:
			temp['contabil'].append(custo['custo_medio_contabil'])
		for chave, valores in temp.items():
			total = 0
			for valor in valores:
				total += valor
			retorno[chave] = total

		preco = self.buscaPreco(str(custo['produto_codigo']))
		retorno['gerencial'] = float(preco['compra_real'])
		return retorno

	def calcularCustos(self, fichas_tecnicas):
		calculo = {'contabil': 0, 'gerencial': 0}
		for chave, ficha_tecnica in fichas_tecnicas.items():
			for valores in ficha_tecnica:
				if chave == 'servicos':
					custos = valores[len(valores) - 1]
					calculo['gerencial'] += custos['gerencial']
					calculo['contabil'] += custos['gerencial']
				else:
					consumo = valores['consumo_unitario']
					custos = valores[len(valores) - 1]
					calculo['gerencial'] += (custos['gerencial'] * consumo)
					calculo['contabil'] += (custos['contabil'] * consumo)
		return calculo

	def updateMovimentacao(self, movimentacao, custo):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sql_update = "UPDATE movimentacao_recalculo SET custo_sem_imposto={1}, custo={2}, preco_pcmn={2} WHERE id={0};". format(movimentacao, custo['contabil'], custo['gerencial'])
		cursor.execute(sql_update)
		cursor.close()
		self.conn_portal.commit()

	def updatePreco(self, movimento, custo):
		buscaValorCusto = self.buscaValorCusto(movimento['data_movimentacao'].strftime("%Y-%m-%d"), movimento['documento'], movimento['estabelecimento'], movimento['cliente_codigo'])
		if buscaValorCusto is None:
			buscaValorCusto = self.insetValorCusto(movimento['data_movimentacao'].strftime("%Y-%m-%d"), movimento['documento'], movimento['estabelecimento'], movimento['cliente_codigo'])
		buscaProdutoValorCusto = self.buscaProdutoValorCusto(buscaValorCusto, movimento['produto_codigo'])

		if buscaProdutoValorCusto is None:
			self.insetProdutoValorCusto(buscaValorCusto, movimento['produto_codigo'], movimento['preco'], movimento['documento'], movimento['quantidade'], float(custo['gerencial']))
		elif float(buscaProdutoValorCusto['custo_gerencial']) != float(custo['gerencial']):
			self.updateProdutoValorCusto(buscaProdutoValorCusto['id'], float(custo['gerencial']))

		self.updateCustoGerencial(movimento['produto_codigo'], custo['gerencial'])

	def buscaProdutoValorCusto(self, id, produto):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sqlBusca = """
		select * from valor_custo_nota_produtos where valor_custo_notas_id = {0} and codigo_produto = '{1}' and deleted_at is null
		""". format(id, produto)
		cursor.execute(sqlBusca)
		retorno = cursor.fetchone()
		cursor.close()
		return retorno

	def buscaValorCusto(self, data, nota, estabelecimento, codigo_cliente):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sqlBusca = """
		select
			*
		from
			valor_custo_notas
		where
			valor_custo_notas.estabelecimento = '{0}'
			and valor_custo_notas.numero_pedido = '{1}'
			and valor_custo_notas.data_compra = '{2}'
			and valor_custo_notas.fornecedor_codigo = '{3}'
			and deleted_at is null
		""". format(estabelecimento, nota, data, codigo_cliente)
		cursor.execute(sqlBusca)
		retorno = cursor.fetchone()
		cursor.close()
		if retorno is not None:
			retorno = retorno['id']
		return retorno

	def insetValorCusto(self, data, nota, estabelecimento, codigo_cliente):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sql_insert = "insert into valor_custo_notas (estabelecimento, fornecedor_cnpj, fornecedor_codigo, numero_pedido, data_compra, proforma, created_by, created_at) values ('{0}', '', '{1}', '{2}', '{3}', '', 1, now()) RETURNING id". format(estabelecimento, codigo_cliente, nota, data)
		cursor.execute(sql_insert)
		id = cursor.fetchone()[0]
		cursor.close()

		self.conn_portal.commit()
		return id


	def insetProdutoValorCusto(self, id, produto, valor_nota, nota, quantidade, custo_gerencial):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sql_insert = "insert into valor_custo_nota_produtos (valor_custo_notas_id, codigo_produto, custo, numero_pedido, proforma, quantidade, valor_dolar, custo_gerencial, created_by, created_at) values ({0}, '{1}', '{2}', '{3}', '', '{4}', 0, {5}, 1, now()) RETURNING id". format(id, produto, valor_nota, nota, quantidade, custo_gerencial)
		cursor.execute(sql_insert)
		id = cursor.fetchone()[0]
		cursor.close()

		self.conn_portal.commit()
		return id

	def updateProdutoValorCusto(self, id, custo):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sql_update = "UPDATE valor_custo_nota_produtos SET custo_gerencial={1} WHERE id={0};". format(id, custo)
		cursor.execute(sql_update)
		cursor.close()
		self.conn_portal.commit()

	def updateCustoGerencial(self, produto, custo):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sql_update = "UPDATE precos SET compra_real={1} WHERE codigo_produto = '{0}';". format(produto, custo)
		cursor.execute(sql_update)
		cursor.close()
		self.conn_portal.commit()


	def ini(self):
		data_final = datetime.today()
		data_inicial = str('2021-01-01')
		data_final = str(data_final.strftime("%Y-%m-%d"))

		movimentacoes = self.movimentacaoIndustrializacao(data_inicial, data_final)
		for movimentacao in movimentacoes:
			ficha_tecnica = self.buscaFichaTecninca(str(movimentacao['produto_codigo']))
			if ficha_tecnica is not None:
				dados_ficha_tecnica = self.buscaDadosFichaTecninca(str(ficha_tecnica['id']), str(movimentacao['estabelecimento']))
				calculo_custo = self.calcularCustos(dados_ficha_tecnica)
				self.updateMovimentacao(str(movimentacao['id']), calculo_custo)
				self.updatePreco(movimentacao, calculo_custo)

ImportacaoPrepago = ImportacaoPrepago()