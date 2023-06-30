# coding: utf-8
import time
import psycopg2
import psycopg2.extras
import sys
import os
from dotenv import load_dotenv
from signal import signal, SIGINT
from LogExecucao import LogExecucao

def handler(signal_received, frame):
    print 'exit'
    exit(0)

class Processa:
    def __init__(self):
        self.conn_portal = ''
        load_dotenv('../../.env')
        self.openConnecion()
        LogExecucao('movimentacao_ultimo_custo', False)

        self.ini()

        LogExecucao('movimentacao_ultimo_custo', True)
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
        self.conn_portal.close()
        print '\n\nexit'
        exit(0)
        
    def buscaProdutos(self):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlBusca = "select estabelecimento, produto_codigo, max(data_movimentacao) as data_movimentacao from movimentacao_recalculo where (custo > 0 or custo_sem_imposto > 0 or custo_pcmn > 0) group by estabelecimento, produto_codigo"
        cursor.execute(sqlBusca)
        dados = cursor.fetchall()
        cursor.close()
        return dados

    def buscaProduto(self, estabelecimento, produto, data):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlBusca = "select * from movimentacao_recalculo where estabelecimento = '{0}' and produto_codigo = '{1}' and data_movimentacao = '{2}' and (custo > 0 or custo_sem_imposto > 0 or custo_pcmn > 0)". format(estabelecimento, produto, data)
        cursor.execute(sqlBusca)
        dados = cursor.fetchone()
        cursor.close()
        if dados is not None:
            return dados
        else:
            return ''

    def insertCustoProdutos(self, estabelecimento, produto, custo_medio_contabil, custo_medio_gerencial, custo_medio_gerencial_antigo):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        if custo_medio_contabil is None:
            custo_medio_contabil = 0.0
        if custo_medio_gerencial is None:
            custo_medio_gerencial = 0.0
        if custo_medio_gerencial_antigo is None:
            custo_medio_gerencial_antigo = 0.0
        if custo_medio_contabil == "None":
            custo_medio_contabil = 0.0
        if custo_medio_gerencial == "None":
            custo_medio_gerencial = 0.0
        if custo_medio_gerencial_antigo == "None":
            custo_medio_gerencial_antigo = 0.0
        sqlInsert = "insert into produtos_custos (estabelecimento, produto_codigo, custo_medio_contabil, custo_medio_gerencial, custo_medio_gerencial_antigo,data_atualizacao) values ('{0}','{1}','{2}','{3}','{3}', now())". format(estabelecimento, produto, custo_medio_contabil, custo_medio_gerencial, custo_medio_gerencial_antigo)
        cursor.execute(sqlInsert)
        cursor.close()
        self.conn_portal.commit()

    def limpaTabelaCusto(self):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlTruncate = "TRUNCATE TABLE ONLY public.produtos_custos RESTART IDENTITY RESTRICT;"
        cursor.execute(sqlTruncate)
        cursor.close()
        self.conn_portal.commit()


    def ini(self):
        self.limpaTabelaCusto()
        produtos = self.buscaProdutos()

        for produto in produtos:
            movimento = self.buscaProduto(str(produto['estabelecimento']), str(produto['produto_codigo']), str(produto['data_movimentacao']) )
            if(movimento != ''):
                self.insertCustoProdutos(str(produto['estabelecimento']), str(produto['produto_codigo']), str(movimento['custo_sem_imposto']), str(movimento['custo_pcmn']), str(movimento['custo']))

signal(SIGINT, handler)

processo = Processa()
