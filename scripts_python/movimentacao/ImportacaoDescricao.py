# coding: utf-8
import time
from datetime import date, timedelta
import psycopg2
import psycopg2.extras
import sys
import re
import os
from dotenv import load_dotenv
from LogExecucao import LogExecucao

class ImportacaoDescricao:
    def __init__(self):
        self.conn_portal = ''
        load_dotenv('../../.env')

        self.openConnecion()

        LogExecucao('movimentacao_descriacao', False)

        self.ini()

        LogExecucao('movimentacao_descriacao', True)
        
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
    
    def buscaProdutosSemEspecificacao(self):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlBusca = "select produto_codigo from movimentacao_recalculo where produto_codigo != '' and (marca = 'A CADASTRAR' or linha = 'A CADASTRAR' or grupo = 'A CADASTRAR' or subgrupo = 'A CADASTRAR'or marca is null or linha is null or grupo is null or descricao is null) group by produto_codigo order by produto_codigo "
        cursor.execute(sqlBusca)
        dado = cursor.fetchall()
        cursor.close()
        return dado
    
    def buscaEspecificacao(self, produto):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlBusca = "select codigo_produto, produto_grupos.descricao as grupo, marca, linha, subgrupo, produto_especificacaos.descricao as descricao, case when procedencia in ('0','3','4','5') then 'nacional' else 'importado' end as procedencia from produto_especificacaos left join produto_grupos on produto_especificacaos.produto_grupos_id = produto_grupos.id where produto_especificacaos.deleted_at is null and codigo_produto in({0}) order by codigo_produto ". format(produto)
        cursor.execute(sqlBusca)
        dado = cursor.fetchall()
        cursor.close()
        return dado
    
    def udpateDadosMovimento(self, especificacoes):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlUdpate = "update movimentacao_recalculo set grupo = '%s', marca = '%s', linha = '%s', subgrupo = '%s', descricao = '%s', produto_procedencia = '%s' where produto_codigo = '%s' and (marca = 'A CADASTRAR' or linha = 'A CADASTRAR' or grupo = 'A CADASTRAR' or subgrupo = 'A CADASTRAR'or marca is null or linha is null or grupo is null or descricao is null);"
        sql = ''
        for codigo_produto in especificacoes:
            sql += sqlUdpate % (especificacoes[codigo_produto]['grupo'], especificacoes[codigo_produto]['marca'], especificacoes[codigo_produto]['linha'], especificacoes[codigo_produto]['subgrupo'], especificacoes[codigo_produto]['descricao'], especificacoes[codigo_produto]['procedencia'], codigo_produto)
        cursor.execute(sql)
        cursor.close()
        self.conn_portal.commit()

    def arryProdutos(self, produtos):
        array = ''
        for produto in produtos:
            array += '\'' + str(produto['produto_codigo']) + '\', '
        array = array[:-2]
        return array
    
    def arrayEspecificao(self, especificacao):
        array = {}
        for produto in especificacao:
            for campo, valor in produto.items():
                produto[campo] = valor
            array[str(produto['codigo_produto'])] = produto
        return array

    def ini(self):
        produtos = self.buscaProdutosSemEspecificacao()
        produtos = self.arryProdutos(produtos)
        especificacoes = self.buscaEspecificacao(produtos)
        especificacoes = self.arrayEspecificao(especificacoes)

        self.udpateDadosMovimento(especificacoes)
