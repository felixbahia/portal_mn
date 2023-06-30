# coding: utf-8
import time
from datetime import date, timedelta
import psycopg2
import psycopg2.extras
import sys
import os
from dotenv import load_dotenv
from LogExecucao import LogExecucao

class UltimoPrecoEDataCompra:
	def __init__(self):
		self.conn_portal = ''
		load_dotenv('../../.env')

		self.open_connecion()
		
		LogExecucao('atualiza_preco_ultima_compra', False)
		
		self.ini()

		LogExecucao('atualiza_preco_ultima_compra', True)

		self.exit_program()

	def close_connection(self):
		self.conn_portal.close()

		self.conn_portal = ''

	def open_connecion(self):
		try:
			connect_str = "dbname='{0}' user='{1}' password='{2}' host='{3}'". format(os.getenv('DB_DATABASE'), os.getenv('DB_USERNAME'), os.getenv('DB_PASSWORD'), os.getenv('DB_HOST'))
			self.conn_portal = psycopg2.connect(connect_str)
		except Exception as e:
			print e
			exit(0)

	def exit_program(self):
		self.close_connection()
		print '\n\nexit'

	def busca_ultima_compra(self):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		sql_busca = """
			select
				case when custo_gerencial > 0 then custo_gerencial else custo end as custo,
				codigo_produto,
				data_compra as data,
				'compra' as dado
			from
				valor_custo_nota_produtos
				inner join valor_custo_notas on valor_custo_notas.id = valor_custo_nota_produtos.valor_custo_notas_id
			where
				valor_custo_nota_produtos.deleted_at is null and 
				valor_custo_notas.deleted_at is null
			union all 
			select
				compra_real_novo as custo,
				codigo_produto,
				max(created_at)::date as data,
				'log' as dado
			from
				precos_logs
			where
				compra_real_antigo != compra_real_novo
			group by 
				codigo_produto,
				compra_real_novo
			order by data;"""
		cursor.execute(sql_busca)
		dados = cursor.fetchall()
		cursor.close()
		if dados is not None:
			return self.tratar_produtos(dados)
		else:
			return []

	def tratar_produtos(self, produtos):
		produtos_retorno = {}
		for produto in produtos:
			if produto['codigo_produto'] not in produtos_retorno:
				produtos_retorno[produto['codigo_produto']] = {
					'codigo_produto': produto['codigo_produto'],
					'data': produto['data'],
					'custo': produto['custo'],
					'dado': produto['dado']
				}
			elif produtos_retorno[produto['codigo_produto']]['data'] <  produto['data']:
				produtos_retorno[produto['codigo_produto']]['data'] = produto['data']
				produtos_retorno[produto['codigo_produto']]['custo'] = produto['custo']
				produtos_retorno[produto['codigo_produto']]['dado'] = produto['dado']

			elif produtos_retorno[produto['codigo_produto']]['data'] == produto['data'] and produto['dado'] == 'compra':
				produtos_retorno[produto['codigo_produto']]['data'] = produto['data']
				produtos_retorno[produto['codigo_produto']]['custo'] = produto['custo']
				produtos_retorno[produto['codigo_produto']]['dado'] = produto['dado']

		return produtos_retorno

	def atualiza_preco(self, produto):
		conn = self.conn_portal
		cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
		if(produto['dado'] == 'log'):
			sql_update = "UPDATE precos SET compra_real={1} WHERE codigo_produto = '{0}';". format(produto['codigo_produto'], produto['custo'])
		else:
			sql_update = "UPDATE precos SET compra_real={1}, ultima_compra_real='{2}' WHERE codigo_produto = '{0}';". format(produto['codigo_produto'], produto['custo'], produto['data'])

		cursor.execute(sql_update)
		cursor.close()
		self.conn_portal.commit()

	def ini(self):
		busca_ultima_compra = self.busca_ultima_compra()
		for produto in busca_ultima_compra:
			self.atualiza_preco(busca_ultima_compra[produto])

            
UltimoPrecoEDataCompraObj = UltimoPrecoEDataCompra()
