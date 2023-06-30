# coding: utf-8
import time
from datetime import date, timedelta
import psycopg2
import psycopg2.extras
import sys
import os
from dotenv import load_dotenv
from LogExecucao import LogExecucao
from signal import signal, SIGINT

def handler(signal_received, frame):
    print "\n"
    print '\n\nexit'
    exit(0)

class ImportacaoPrecosMovimentacao:
    def __init__(self):
        self.conn_portal = ''
        load_dotenv('../../.env')

        self.openConnecion()
        
        LogExecucao('movimentacao_preco_pcmn', False)
        
        self.ini()

        LogExecucao('movimentacao_preco_pcmn', True)

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
    
    def buscaMovimentosSemCusto(self):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = """
            select
                *
            from
                movimentacao_recalculo
            where
                (preco_pcmn = 0 or preco_pcmn is null)
                and data_movimentacao > '2020-01-01'
            order by produto_codigo, data_movimentacao;
            """
        cursor.execute(sql_busca)
        dado = cursor.fetchall()
        cursor.close()
        if dado is not None:
            return dado
        else:
            return ''
    
    def udpateMovimento(self, idMovimento, custo):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_update = "update movimentacao_recalculo set preco_pcmn = '{0}' where id = '{1}';". format(custo, idMovimento)
        cursor.execute(sql_update)
        cursor.close()
        self.conn_portal.commit()

    def busca_custos(self, produtos):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = """
            select
                case when custo_gerencial > 0 then custo_gerencial else custo end as custo,
                codigo_produto,
                data_compra as data
            from
                valor_custo_nota_produtos
                inner join valor_custo_notas on valor_custo_notas.id = valor_custo_nota_produtos.valor_custo_notas_id
            where
                valor_custo_nota_produtos.deleted_at is null and
                codigo_produto in({0})
            union all 
            select
                compra_real_novo as custo,
                codigo_produto,
                max(created_at) as data
            from
                precos_logs
            where
                compra_real_antigo != compra_real_novo and
                codigo_produto in({0})
            group by 
                codigo_produto,
                compra_real_novo
            order by data;
        """. format(produtos)
        if produtos == '':
            return []

        cursor.execute(sql_busca)
        dado = cursor.fetchall()
        cursor.close()
        if dado is not None:
            return self.tratar_custos(dado)
        else:
            return []
    
    def arry_produtos(self, produtos):
        array = ''
        for produto in produtos:
            array += '\'' + str(produto['produto_codigo']) + '\', '
        array = array[:-2]
        return array

    def tratar_custos(self, custos):
        retorno = {}
        for custo in custos:
            if custo['codigo_produto'] not in retorno:
                retorno[custo['codigo_produto']] = []
            valor = {'custo': custo['custo'], 'data': custo['data']}
            retorno[custo['codigo_produto']].append(valor)

        return retorno 

    def remover_codigos(self, dados):
        codigos = []
        for dado in dados:
            if dado['produto_codigo'] not in codigos:
                codigos.append(dado['produto_codigo'])
        return codigos 


    def ini(self):
        movimentos = self.buscaMovimentosSemCusto()
        codigos_produtos = self.arry_produtos(movimentos)
        custos = self.busca_custos(codigos_produtos)
        for movimento in movimentos:
            valor = 0
            if movimento['produto_codigo'] in custos:
                for custo in  custos[movimento['produto_codigo']]:
                    if custo['data'] <= movimento['data_movimentacao']:
                        valor = custo['custo']
                if valor == 0:
                    for custo in  custos[movimento['produto_codigo']]:
                        if custo['data'] > movimento['data_movimentacao']:
                            valor = custo['custo']
                            break
                self.udpateMovimento(movimento['id'], valor)
            
signal(SIGINT, handler)
ImportacaoPrecosMovimentacaoObj = ImportacaoPrecosMovimentacao()
