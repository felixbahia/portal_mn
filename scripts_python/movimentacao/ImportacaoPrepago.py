
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
        self.conn_nasajon = ''
        load_dotenv('../../.env')

        self.openConnecion()

        LogExecucao('movimentacao_prepago', False)
        
        self.ini()

        LogExecucao('movimentacao_prepago', True)

        self.exit_program()

    def closeConnection(self):
        self.conn_portal.close()
        self.conn_nasajon.close()

        self.conn_portal = ''
        self.conn_nasajon = ''

    def openConnecion(self):
        try:
            connect_str = "dbname='{0}' user='{1}' password='{2}' host='{3}'". format(os.getenv('DB_DATABASE'), os.getenv('DB_USERNAME'), os.getenv('DB_PASSWORD'), os.getenv('DB_HOST'))
            self.conn_portal = psycopg2.connect(connect_str)
        except Exception as e:
            print e
            exit(0)
        try:
            connect_str = "dbname='{0}' user='{1}' password='{2}' host='{3}'". format(os.getenv('DB_NASAJON_DATABASE'), os.getenv('DB_NASAJON_USERNAME'), os.getenv('DB_NASAJON_PASSWORD'), os.getenv('DB_NASAJON_HOST'))
            self.conn_nasajon = psycopg2.connect(connect_str)
            self.conn_nasajon.set_client_encoding('UTF8')
        except Exception as e:
            print e
            exit(0)

    def exit_program(self):
        self.closeConnection()
        print '\n\nexit'

    def buscaPedidosPrepegao(self):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        data_final = datetime.today()
        data_inicial = (data_final - timedelta(6*365/12))
        data_inicial = str(data_inicial.strftime("%Y-%m-%d"))
        data_final = str(data_final.strftime("%Y-%m-%d"))

        sql_busca = """
            select
                *
            from
                pedidos_prepagos
            where
                created_at between '{1}' and '{2}' and 
                deleted_at is null
        """. format(id, data_inicial, data_final)
        cursor.execute(sql_busca)
        dado = cursor.fetchall()
        cursor.close()
        if dado is not None:
            return dado
        else:
            return ''

    def buscaPedidoNota(self, id):
        conn = self.conn_nasajon
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        data_final = datetime.today()
        data_inicial = (data_final - timedelta(6*365/12))
        data_inicial = str(data_inicial.strftime("%Y-%m-%d"))
        data_final = str(data_final.strftime("%Y-%m-%d"))

        sql_busca = """
            select
                vw_notas_itens.cod_produto,
                vw_notas_itens.valor_total as valor,
                vw_pedidos_venda_v3.id,
                vw_pedidos_venda_v3.notafiscal_numero,
                vw_pedidos_venda_v3.estabelecimento_codigo
            from
                integracoes.vw_pedidos_venda_v3
                inner join integracoes.vw_notas_itens on (vw_pedidos_venda_v3.notafiscal_id = vw_notas_itens.id_nota)
            where
                vw_pedidos_venda_v3.emissao between '{1}' and '{2}' and
                vw_pedidos_venda_v3.notafiscal_numero != '' and
                vw_pedidos_venda_v3.notafiscal_numero != '0' and
                vw_pedidos_venda_v3.grupodeoperacao = 'VENDA' and
                vw_pedidos_venda_v3.id = '{0}'
        """. format(id, data_inicial, data_final)
        cursor.execute(sql_busca)
        dado = cursor.fetchall()
        cursor.close()
        if dado is not None and len(dado) > 0:
            return dado
        else:
            return ''
    
    def buscaMovimentos(self, nota, estabelecimento):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = """
            select
                *
            from
                movimentacao_recalculo
            where
                data_movimentacao >= '2020-01-01' and
                documento = '{0}' and
                sinal ilike 'saida' and
                estabelecimento = '{1}'
        """. format(nota, estabelecimento)

        cursor.execute(sql_busca)
        dado = cursor.fetchall()
        cursor.close()
        if dado is not None and len(dado) > 0:
            return dado
        else:
            return ''

    def atualizaMovimentos(self, preco, ids):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_update = "UPDATE movimentacao_recalculo set preco_prepago = '{0}' where id in({1}) and data_movimentacao >= '2020-01-01';". format(preco, ids)
        cursor.execute(sql_update)
        cursor.close()
        self.conn_portal.commit()

    def atualizaPrepago(self, preco, id):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_update = "UPDATE pedidos_prepagos set valor = '{0}' where id = {1} and created_at >= '2020-01-01';". format(preco, id)
        cursor.execute(sql_update)
        cursor.close()
        self.conn_portal.commit()

    def tratarArrayMovimento(self, movimentos):
        array = {}
        for movimento in movimentos:
            array[movimento['produto_codigo']] = movimento
        return array

    def ini(self):
        prepagos = self.buscaPedidosPrepegao()
        for prepago in prepagos:
            pedido_nasajon = self.buscaPedidoNota(prepago['pedido_nasajon_id'])
            if pedido_nasajon != '':
                pedido_nasajon_busca = pedido_nasajon[0]
                nota = pedido_nasajon_busca['notafiscal_numero']
                estabelecimento = pedido_nasajon_busca['estabelecimento_codigo']
                id = pedido_nasajon_busca['id']
                total = 0
                movimentos = self.buscaMovimentos(nota, estabelecimento)
                if len(movimentos) > 0:
                    movimentos = self.tratarArrayMovimento(movimentos)
                    if len(movimentos) > 0:
                        for pedido in pedido_nasajon:
                            if pedido['cod_produto'] in movimentos:
                                self.atualizaMovimentos(pedido['valor'], movimentos[pedido['cod_produto']]['id'])
                            total += pedido['valor']
                self.atualizaPrepago(total, prepago['id'])
