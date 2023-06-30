# coding: utf-8
import time
from datetime import datetime, timedelta
import psycopg2
import psycopg2.extras
import sys
import os
from dotenv import load_dotenv
from LogExecucao import LogExecucao

class ImportacaoVendedor:
    def __init__(self):
        self.conn_portal = ''
        self.conn_nasajon = ''
        load_dotenv('../../.env')

        self.openConnecion()

        LogExecucao('movimentacao_vendedor', False)
        
        self.ini()

        LogExecucao('movimentacao_vendedor', True)

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

    def buscaNotasVenda(self, data):
        conn = self.conn_nasajon
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = """
        select
            "Número Documento" as nota,
	        vw_df_vendedores.vendedor_codigo as vendedor_codigo,
            "Estabelecimento" as estabelecimento
        from
            integracoes.vw_faturamentos
            inner join integracoes.vw_df_vendedores on (vw_df_vendedores.id_docfis = vw_faturamentos."Identificador Documento")
        where
            "Data de Emissão" >= '{0}' and
            "TIPO" = 'VENDA'
        order by 
            "Estabelecimento", "Número Documento" 
        """. format(data)
        cursor.execute(sql_busca)
        dado = cursor.fetchall()
        cursor.close()
        if dado is not None:
            return dado
        else:
            return ''

    def buscaNotasDevolucao(self, data):
        conn = self.conn_nasajon
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = """
        select
            "Número Documento" as nota,
	        vw_df_vendedores.vendedor_codigo as vendedor_codigo,
            "Estabelecimento" as estabelecimento
        from
            integracoes.vw_faturamentos
            inner join integracoes.vw_df_vendedores on (vw_df_vendedores.id_docfis = vw_faturamentos."Identificador Documento")
        where
            "Data Lançamento" >= '{0}' and
            "TIPO" = 'DEVOLUÇÃO'
        order by 
            "Estabelecimento", "Número Documento" 
        """. format(data)
        cursor.execute(sql_busca)
        dado = cursor.fetchall()
        cursor.close()
        if dado is not None:
            return dado
        else:
            return ''
        
    def buscaNotasVendedor(self, data):
        conn = self.conn_nasajon
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = """
        select
            vw_nfes_saida.estabelecimento_codigo,
            vw_nfes_saida.numero,
            vw_nfes_saida_itens.cfop,
            vw_df_vendedores.vendedor_codigo
        from
            integracoes.vw_nfes_saida
            inner join integracoes.vw_df_vendedores on (vw_df_vendedores.id_docfis = vw_nfes_saida.id)
            left join ns.vw_nfes_saida_itens on (vw_nfes_saida_itens.id_docfis = vw_nfes_saida.id)
        where 
            vw_nfes_saida.emissao >= '{0}' and 
            vw_nfes_saida_itens.cfop in ('5911', '6911')
        group by 
            vw_nfes_saida.estabelecimento_codigo,
            vw_nfes_saida.numero,
            vw_nfes_saida_itens.cfop,
            vw_df_vendedores.vendedor_codigo;
        """. format(data)
        cursor.execute(sql_busca)
        dado = cursor.fetchall()
        cursor.close()
        if dado is not None:
            return dado
        else:
            return ''

    def buscaNotasFuturasVendedor(self, data):
        conn = self.conn_nasajon
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = """
        select
            vw_nfes_saida.estabelecimento_codigo,
            vw_nfes_saida.numero,
            vw_nfes_saida_itens.cfop,
            vw_df_vendedores.vendedor_codigo
        from
            integracoes.vw_nfes_saida
            inner join integracoes.vw_df_vendedores on (vw_df_vendedores.id_docfis = vw_nfes_saida.id)
            left join ns.vw_nfes_saida_itens on (vw_nfes_saida_itens.id_docfis = vw_nfes_saida.id)
        where 
            vw_nfes_saida.emissao >= '{0}' and
	        (operacao_codigo ilike 'VENDAFUTURA' or operacao_codigo ilike 'VENDAFUTURATORO')
        group by 
            vw_nfes_saida.estabelecimento_codigo,
            vw_nfes_saida.numero,
            vw_nfes_saida_itens.cfop,
            vw_df_vendedores.vendedor_codigo;
        """. format(data)
        cursor.execute(sql_busca)
        dado = cursor.fetchall()
        cursor.close()
        if dado is not None:
            return dado
        else:
            return ''

    
    def atualizaMovimentosSaida(self, vendedor, nota, estabelecimento, data):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_update = "UPDATE movimentacao_recalculo set vendedor = '{0}' where documento = '{1}' and estabelecimento = '{2}' and data_movimentacao >= '{3}' and sinal = 'SAIDA';". format(vendedor, nota, estabelecimento, data)
        cursor.execute(sql_update)
        cursor.close()
        self.conn_portal.commit()

    def atualizaMovimentosEntrada(self, vendedor, nota, estabelecimento, data):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_update = "UPDATE movimentacao_recalculo set vendedor = '{0}' where documento = '{1}' and estabelecimento = '{2}' and data_movimentacao >= '{3}' and sinal = 'ENTRADA';". format(vendedor, nota, estabelecimento, data)
        cursor.execute(sql_update)
        cursor.close()
        self.conn_portal.commit()
    
    def atualizaMovimentosVendedor(self, vendedor, nota, estabelecimento, cfop, data):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_update = "UPDATE movimentacao_recalculo set vendedor = '{0}' where documento = '{1}' and estabelecimento = '{2}' and cfop = '{3}' and data_movimentacao >= '{4}' and sinal = 'SAIDA';". format(vendedor, nota, estabelecimento, cfop, data)
        cursor.execute(sql_update)
        cursor.close()
        self.conn_portal.commit()
    
    def ini(self):
        data_final = datetime.today()
        data_inicial = (data_final - timedelta(1*365/12))
        print data_final.strftime("%H")
        if str(data_final.strftime("%H")) == '00':
            data_inicial = str('2021-01-01')
        else:
            data_inicial = str(data_inicial.strftime("%Y-%m-%d"))

        data_inicial = str('2023-01-01')
        data_final = str(data_final.strftime("%Y-%m-%d"))

        notas = self.buscaNotasVenda(data_inicial)
        for nota in notas:
            self.atualizaMovimentosSaida(nota['vendedor_codigo'], nota['nota'], nota['estabelecimento'], data_inicial)

        notas = self.buscaNotasDevolucao(data_inicial)
        for nota in notas:
            self.atualizaMovimentosEntrada(nota['vendedor_codigo'], nota['nota'], nota['estabelecimento'], data_inicial)

        notas = self.buscaNotasVendedor(data_inicial)
        for nota in notas:
            self.atualizaMovimentosVendedor(nota['vendedor_codigo'], nota['numero'], nota['estabelecimento_codigo'], nota['cfop'], data_inicial)

        notas = self.buscaNotasFuturasVendedor(data_inicial)
        for nota in notas:
            self.atualizaMovimentosVendedor(nota['vendedor_codigo'], nota['numero'], nota['estabelecimento_codigo'], nota['cfop'], data_inicial)

        self.conn_portal.commit()
