# coding: utf-8
import time
from datetime import datetime, timedelta
import psycopg2
import psycopg2.extras
import sys
import os
from dotenv import load_dotenv
from LogExecucao import LogExecucao

class ImportacaoMovimentoDiferenca:
    def __init__(self):
        self.conn_nasajon = ''
        self.conn_portal = ''
        load_dotenv('../../.env')

        self.openConnecion()
        
        LogExecucao('movimentacao', False)

        self.ini()

        LogExecucao('movimentacao', True)

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
        self.closeConnection()
        print '\n\nexit'

    def buscamovimentacao(self, conn, data_ini, data_end, estabelecimento):
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        try:
            sql_movimentacao = "select sum(quantidade) as quantidade, estabelecimento_codigo, produto_codigo, data, sinal, origem, documento_id, documento_numero, cliente_codigo, item_cfop, item_precounitario, item_aliquota_icms, item_unidade, case when item_frete is null then 0 else cast(item_frete as float) end as item_frete, case when item_ipi is null then 0 else cast(item_ipi as float) end as item_ipi, case when item_desconto is null then 0 else cast(item_desconto as float) end as item_desconto, case when item_seguro is null then 0 else cast(item_seguro as float) end as item_seguro, case when item_rataframm is null then 0 else cast(item_rataframm as float) end as item_rataframm, case when item_valorpis is null then 0 else cast(item_valorpis as float) end as item_valorpis, case when item_valorcofins is null then 0 else cast(item_valorcofins as float) end as item_valorcofins, case when item_valorii is null then 0 else cast(item_valorii as float) end as item_valorii, case when item_ratoutrasdesp is null then 0 else cast(item_ratoutrasdesp as float) end as item_ratoutrasdesp from integracoes.exportar_produtos_movimentacoes('%s', '%s', '%s') group by estabelecimento_codigo, produto_codigo, data, sinal, origem, documento_id, documento_numero, cliente_codigo, item_cfop, item_precounitario, item_aliquota_icms, item_unidade, item_frete, item_ipi, item_desconto, item_seguro, item_rataframm,  item_valorpis, item_valorcofins, item_valorii, item_ratoutrasdesp having sum(quantidade) > 0 order by data, sinal;" % (estabelecimento, data_ini, data_end)
            cursor.execute(sql_movimentacao)
            movimentacao = cursor.fetchall()

            cursor.close()
            return movimentacao
        except Exception as e:
            cursor.close()
            print e
            if str(e).strip() == str('current transaction is aborted, commands ignored until end of transaction block'):
                self.conn_portal.commit()
                self.closeConnection()
                self.openConnecion()
                return self.buscamovimentacao(self.conn_nasajon, data_ini, data_end, estabelecimento, produto)
            else:
                return []

    def removerMovimentos(self, conn, data_ini, data_end):
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        sql_busca = """delete from movimentacao_recalculo_diferenca where documento != 'custo_contail' and data_movimentacao between '%s' and '%s';""" % (data_ini, data_end)
        cursor.execute(sql_busca)

        conn.commit()

        cursor.close()
        return False


    def insert_movimentao(self, conn, movimentos):
        cursor_portal = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        for movimento in movimentos:
            sql_exist = "SELECT COUNT(*) FROM public.movimentacao_recalculo where produto_codigo = '%s' and estabelecimento = '%s' and data_movimentacao = '%s' and documento = '%s' and cliente_codigo = '%s' and cfop = '%s' and quantidade = %s and preco = %s; " % (movimento['produto_codigo'], movimento['estabelecimento'], movimento['data'], movimento['documento'], movimento['cliente_codigo'], movimento['cfop'], movimento['quantidade'], movimento['preco'])
            print sql_exist
            cursor_portal.execute(sql_exist)
            row = cursor_portal.fetchone()[0]

            print row
            if row == 0 :
                movimento['sinal'] = movimento['sinal'].replace('Í', 'I')
                sql_inset_movimentos = "INSERT INTO movimentacao_recalculo_diferenca (produto_codigo, estabelecimento, data_movimentacao, documento, tipo_operacao, quantidade, preco, aliquota, cfop, sinal, cliente_codigo, unidade, frete, ipi, desconto, seguro, valor_outras_despesas, valor_pis, valor_cofins, valor_aframm, valor_2) VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');" % (movimento['produto_codigo'], movimento['estabelecimento'], movimento['data'], movimento['documento'], movimento['operacao'], movimento['quantidade'], movimento['preco'], movimento['aliquota'], movimento['cfop'], movimento['sinal'], movimento['cliente_codigo'], movimento['unidade'], movimento['frete'], movimento['ipi'], movimento['desconto'], movimento['seguro'], movimento['item_ratoutrasdesp'], movimento['item_valorpis'], movimento['item_valorcofins'], movimento['item_rataframm'], movimento['item_valorii'])
                try:
                    cursor_portal.execute(sql_inset_movimentos)
                    self.conn_portal.commit()
                except Exception as e:
                    print e
                    return []
        cursor_portal.close()
        return True

    def ini(self):
        data_final = datetime.today()
        data_inicial = (data_final - timedelta(1*365/12))

        if str(data_final.strftime("%H")) == '0':
            data_inicial = (data_final - timedelta(2*365/12))
            data_inicial = (data_final - timedelta(1))
            data_inicial = str(data_inicial.strftime("%Y-%m-%d"))
        else:
            data_inicial = str(data_inicial.strftime("%Y-%m-%d"))
        data_inicial = '2018-07-01'
        data_final = '2018-12-31'
        #data_inicial = '2019-01-01'
        #data_final = '2019-12-31'
        #data_inicial = '2020-01-01'
        #data_final = '2020-12-31'
        #data_inicial = '2021-01-01'
        #data_final = '2021-12-31'

        print data_inicial, data_final
        self.removerMovimentos(self.conn_portal, data_inicial, data_final)
        
        for estabelecimento in range(1, 9):
            estabelecimento = '%02d' % estabelecimento
            print estabelecimento
            movimentacao = self.buscamovimentacao(self.conn_nasajon, data_inicial, data_final, estabelecimento)
            if len(movimentacao) > 0:
                insert_movimento = []
                for movimento in movimentacao:  
                    for (key, value) in movimento.items():
                        if value is None:
                            if key != 'item_aliquota_icms' and key != 'item_cfop' and key != 'item_precounitario' and key != 'item_ipi' and key != 'item_frete' and key != 'item_desconto' and key != 'item_seguro':
                                movimento[key] = ''
                            else:
                                movimento[key] = '0'
                    insert_movimento.append({
                        'produto_codigo': str(movimento['produto_codigo']).decode('cp1252').encode('utf8'),
                        'estabelecimento': str(estabelecimento),
                        'data': str(movimento['data']),
                        'quantidade': movimento['quantidade'],
                        'operacao': str(movimento['sinal']),
                        'sinal': str(movimento['sinal']),
                        'documento': str(movimento['documento_numero']).decode('cp1252').encode('utf8'),
                        'cliente_codigo': str(movimento['cliente_codigo']).decode('cp1252').encode('utf8'),
                        'cfop': str(movimento['item_cfop']).decode('cp1252').encode('utf8'),
                        'preco': str(movimento['item_precounitario']).decode('cp1252').encode('utf8'),
                        'aliquota': str(movimento['item_aliquota_icms']).decode('cp1252').encode('utf8'),
                        'unidade': str(movimento['item_unidade']).decode('cp1252').encode('utf8'),
                        'frete': movimento['item_frete'],
                        'ipi': movimento['item_ipi'],
                        'desconto': movimento['item_desconto'],
                        'seguro': movimento['item_seguro'],
                        'item_ratoutrasdesp': movimento['item_ratoutrasdesp'],
                        'item_valorpis': movimento['item_valorpis'],
                        'item_valorcofins': movimento['item_valorcofins'],
                        'item_rataframm': movimento['item_rataframm'],
                        'item_valorii': movimento['item_valorii']
                    })
                
                self.insert_movimentao(self.conn_portal, insert_movimento)
                
                try:
                    self.conn_portal.commit()
                except Exception as e:
                    print e
                    self.exit_program()
                
            self.closeConnection()
            self.openConnecion()
