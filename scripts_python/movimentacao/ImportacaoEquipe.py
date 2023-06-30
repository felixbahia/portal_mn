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

class ImportacaoEquipe:
    def __init__(self):
        self.conn_portal = ''
        load_dotenv('../../.env')
        self.openConnecion()
        LogExecucao('movimentacao_equipe', False)

        self.ini()

        LogExecucao('movimentacao_equipe', True)
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
        
    def buscaMovimentoSemEquipe(self):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlBusca = "select distinct vendedor, EXTRACT(month from data_movimentacao) as mes, EXTRACT(year from data_movimentacao) as ano from movimentacao_recalculo mr where vendedor not in ('', 'None', '001') and vendedor is not null and equipe is null;"
        cursor.execute(sqlBusca)
        dados = cursor.fetchall()
        cursor.close()
        return dados

    def buscaEquipe(self, vendedor, mes, ano):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlBusca = "select codigo_representante, un.id as unidades_negocios_id, un.unidade as equipe from users u left join unidade_negocio_metas_x_users unmxu on unmxu.users_id = u.id left join unidade_negocio_metas unm on unm.id = unmxu.unidade_negocio_metas_id left join unidades_negocios un on un.id = unm.unidades_negocios_id where codigo_representante = '{0}' and EXTRACT(month from unm.data) = '{1}' and EXTRACT(year from unm.data) = '{2}';". format(vendedor, mes, ano)
        cursor.execute(sqlBusca)
        dados = cursor.fetchone()
        cursor.close()
        if dados is not None:
            return dados
        else:
            return ''

    def updateEquipe(self, vendedor, mes, ano, equipe, unidades_negocios_id):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlUpdate = "UPDATE public.movimentacao_recalculo SET unidades_negocios_id={0},equipe='{1}' where vendedor = '{2}' and EXTRACT(month from data_movimentacao) = '{3}' and EXTRACT(year from data_movimentacao) = '{4}';". format(unidades_negocios_id, equipe, vendedor, mes, ano)
        cursor.execute(sqlUpdate)
        cursor.close()
        self.conn_portal.commit()

    def ini(self):
        movimentos_sem_equipe = self.buscaMovimentoSemEquipe()

        for movimento_sem_equipe in movimentos_sem_equipe:
            equipe = self.buscaEquipe(str(movimento_sem_equipe['vendedor']), str(movimento_sem_equipe['mes']), str(movimento_sem_equipe['ano']) )
            if(equipe != ''):
                self.updateEquipe(str(movimento_sem_equipe['vendedor']), str(movimento_sem_equipe['mes']), str(movimento_sem_equipe['ano']), str(equipe['equipe']), str(equipe['unidades_negocios_id']))

signal(SIGINT, handler)

equipe = ImportacaoEquipe()