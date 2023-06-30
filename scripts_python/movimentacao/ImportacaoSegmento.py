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
        
    def buscaMovimentoSemSegmento(self):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlBusca = "select distinct produto_codigo from movimentacao_recalculo mr where segmento_id is null;"
        cursor.execute(sqlBusca)
        dados = cursor.fetchall()
        cursor.close()
        return dados

    def buscaSegmento(self, produto_codigo):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlBusca = "select s.id as segmento_id, s.descricao as segmento_descricao from produto_especificacaos pe left join produto_grupos pg on pe.produto_grupos_id = pg.id left join segmentos s on pg.segmentos_id = s.id where pe.codigo_produto = '{0}';". format(produto_codigo)
        cursor.execute(sqlBusca)
        dados = cursor.fetchone()
        cursor.close()
        if dados is not None:
            return dados
        else:
            return ''

    def updateEquipe(self, produto_codigo, segmento_id, segmento_descricao):
         if segmento_id is not None and segmento_id != "None":
            conn = self.conn_portal
            cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
            sqlUpdate = "UPDATE public.movimentacao_recalculo SET segmento_id = {1}, segmento='{2}' where produto_codigo  = '{0}';". format(produto_codigo, segmento_id, segmento_descricao)
            cursor.execute(sqlUpdate)
            cursor.close()
            self.conn_portal.commit()

    def ini(self):
        movimentos_sem_segmento = self.buscaMovimentoSemSegmento()

        for movimento_sem_segmento in movimentos_sem_segmento:
            segmento = self.buscaSegmento(str(movimento_sem_segmento['produto_codigo']) )
            if(segmento != ''):
                self.updateEquipe(str(movimento_sem_segmento['produto_codigo']), str(segmento['segmento_id']), str(segmento['segmento_descricao']))

signal(SIGINT, handler)

equipe = ImportacaoEquipe()