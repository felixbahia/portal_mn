# coding: utf-8
import time
from datetime import date, timedelta
import psycopg2
import psycopg2.extras
import sys
import csv
import os
from dotenv import load_dotenv
from signal import signal, SIGINT
from LogExecucao import LogExecucao

def handler(signal_received, frame):
    print "\n"
    print '\n\nexit'
    exit(0)

class Compras:
    def __init__(self):
        self.conn_nasajon = ''
        self.conn_portal = ''
        load_dotenv('../.env')

        self.openConnecion()

        conn = self.conn_portal
        cursor_portal = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
            
        sql_verificacao = "select * from atualizacao_cron ac where \"token\" = 'importacao_compras' and atualizacao > inicio_atualizacao"

        cursor_portal.execute(sql_verificacao)
        row = cursor_portal.fetchone()
        cursor_portal.close()
        
        LogExecucao('importacao_compras', False)
        self.ini()
        
        self.exit_program()

    def closeConnection(self):
        self.conn_portal.close()
        self.conn_nasajon.close()

        self.conn_nasajon = ''
        self.conn_portal = ''

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
        self.conn_portal.close()
        self.conn_nasajon.close()
        print '\n\nexit'
        exit(0)

    def buscaComprasNasajon(self, data_inicio):
        conn = self.conn_nasajon
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sqlBusca = """
            select
                vw_compras."Estabelecimento" as estabelecimento,
                vw_compras."Data de Emissão" as data_emissao,
                vw_compras_itens."Item - Código" as codigo_produto,
                vw_compras."Número do Documento" as numero_nota,
                '' as serie_nota,
                (case when vw_compras."Pedido" is not null then vw_compras."Pedido" else '' end) as numero_pedido_compra,
                '' as proforma,
                vw_compras."Fornecedor" as fornecedor_codigo,
                vw_compras."CNPJ/CPF do Fornecedor" as fornecedor_cpf_cnpj,
                vw_compras_itens."Item - Unidade" as unidade,
                vw_compras_itens."Item - Quantidade" as quantidade,
                vw_compras_itens."Item - Valor Unitário" as preco,
                'NASAJON' as origem
            from
                integracoes.vw_compras
                left join integracoes.vw_compras_itens on (vw_compras."Identificador Documento" = vw_compras_itens."Identificador Documento")
            where
                vw_compras_itens."Item - Quantidade" > 0 and
                vw_compras_itens."Item - Valor Unitário" > 0 and
                vw_compras."Estabelecimento" not in ('20') and
                vw_compras."Data de Emissão" >= '%s'
        """
        sqlBusca = sqlBusca % (data_inicio)
        sqlBusca = str(sqlBusca)
        cursor.execute(sqlBusca)
        dados = cursor.fetchall()
        cursor.close()
        return dados

    def incluirDadosComprasNasajon(self, dados):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        for nota in dados:
            proforma_num = ''
            estabelecimento = str(nota['estabelecimento'])
            if estabelecimento == '01':
                estabelecimento = '08'
            if estabelecimento == '02':
                estabelecimento = '07'
            data = str(nota['data_emissao']).replace(' 00:00:00', '')
            codigo_produto = nota['codigo_produto']
            pedido = str(nota['numero_pedido_compra'])
            forncedor_codigo = str(nota['fornecedor_codigo'])
            forncedor_cpf_cnpj = str(nota['fornecedor_cpf_cnpj'])
            quantidade = str(nota['quantidade'])
            valor_real = str(nota['preco'])
            valor_dolar = str(0.0)
            origem = str(nota['origem'])
            nota_numero = str(nota['numero_nota'])
            nota_serie = str(nota['serie_nota'])
            unidade = str(nota['unidade']).decode('cp1252').encode('utf8')
            custo_gerencial = str(nota['preco'])

            if(self.verificaRegistro(estabelecimento, codigo_produto, nota_numero, forncedor_codigo) == False):
                sql_insert_nota = "INSERT INTO public.notas_entradas (estabelecimento, data_entrada, codigo_produto, nota, nota_serie, pedido, proforma, forncedor_codigo, forncedor_cpf_cnpj, quantidade, preco_real, preco_dolar, custo_gerencial, origem, unidade, created_at) VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', NOW());" % (estabelecimento, data, codigo_produto, nota_numero, nota_serie, pedido, proforma_num, forncedor_codigo, forncedor_cpf_cnpj, quantidade, valor_real, valor_dolar, custo_gerencial, origem, unidade)
                cursor.execute(sql_insert_nota)
                self.conn_portal.commit()
        cursor.close()
        return True

    def verificaRegistro(self, estabelecimento, codigo_produto, nota, forncedor_codigo):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = "select count(*) as c from notas_entradas ne where estabelecimento = '%s' and codigo_produto = '%s' and nota = '%s' and forncedor_codigo = '%s'" % (estabelecimento, codigo_produto, nota, forncedor_codigo)
        cursor.execute(sql_busca) 
        registros = cursor.fetchone()
        cursor.close()
        if registros['c'] > 0:
            return True
        return False

    def ini(self):

        data_inicial = date.today()
        data_inicial = data_inicial.replace(day = 1)
        if(data_inicial.month > 2):
            data_inicial = data_inicial.replace(month = data_inicial.month - 2)
        else:
            data_inicial = data_inicial.replace(month = 1)
            data_inicial = data_inicial.replace(year = data_inicial.year - 1)
        data_inicial = str(data_inicial)
        self.openConnecion()

        print str(time.strftime("%H:%M:%S")) + ' Inicio busca de notas - NASAJON'
        compras_nasajon = self.buscaComprasNasajon(data_inicial)
        print str(time.strftime("%H:%M:%S")) + ' Quantidade de registos encontrados: ' + str(len(compras_nasajon))
        print str(time.strftime("%H:%M:%S")) + ' Fim busca de notas - NASAJON \n'

        print str(time.strftime("%H:%M:%S")) + ' Inicio inclusão de compras - NASAJON'
        self.incluirDadosComprasNasajon(compras_nasajon)
        print str(time.strftime("%H:%M:%S")) + ' Fim inclusão de compras - NASAJON \n'
        LogExecucao('importacao_compras', True)
        self.exit_program()

signal(SIGINT, handler)

compras = Compras()