# coding: utf-8
import time
from datetime import date, timedelta
import psycopg2
import psycopg2.extras
import sys
from signal import signal, SIGINT

def handler(signal_received, frame):
    print "\n"
    conn_portal.close()
    conn_nasajon.close()
    print '\n\nexit'
    exit(0)
    
def exit_program():
    conn_portal.close()
    conn_nasajon.close()
    print '\n\nexit'
    exit(0)

def removerVendas(conn, mes_data_ini, ano_data_ini):
    cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

    sql_busca = """delete from notas_vendas where (mes_data_entrada = '%s' and ano_data_entrada = '%s');""" % (mes_data_ini, ano_data_ini)
    cursor.execute(sql_busca)

    conn.commit()

    cursor.close()
    return False


def buscaVendas(conn, data_inicial, data_final):
    cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
    sqlBusca = " \
        select \
            vw_faturamentos.\"Estabelecimento\" as estabelecimento, \
            extract(month from vw_faturamentos.\"Data de Emissão\") as mes, \
            extract(year from vw_faturamentos.\"Data de Emissão\") as ano, \
            vw_faturamentos_itens.\"Item - Código\" as codigo_produto, \
            sum(vw_faturamentos_itens.\"Item - Quantidade\") as quantidade, \
            sum(vw_faturamentos_itens.\"Item - Valor Total\" / vw_faturamentos_itens.\"Item - Quantidade\") as valor \
        from \
            integracoes.vw_faturamentos \
            left join integracoes.vw_faturamentos_itens on (vw_faturamentos.\"Identificador Documento\" = vw_faturamentos_itens.\"Identificador Documento\") \
        where \
            vw_faturamentos.\"Data de Emissão\" between '"+data_inicial+"' and '"+data_final+"' and \
            vw_faturamentos_itens.\"Item - Quantidade\" > 0 and \
            vw_faturamentos.\"TIPO\" ILIKE 'VENDA' and \
            vw_faturamentos.\"Identificador Cliente\" not in (select id from integracoes.vw_dados_clientes where replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%' or replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%') and \
            vw_faturamentos_itens.\"Item - Código\" != '' \
        group by \
            vw_faturamentos.\"Estabelecimento\", \
            extract(month from vw_faturamentos.\"Data de Emissão\"), \
            extract(year from vw_faturamentos.\"Data de Emissão\"), \
            vw_faturamentos_itens.\"Item - Código\" \
    "
    sqlBusca = str(sqlBusca)
    cursor.execute(sqlBusca)
    dados = cursor.fetchall()
    cursor.close()
    return dados

def incluirDadosVendas(conn, dados):
    cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
    for nota in dados:
        estabelecimento = str(nota['estabelecimento'])
        codigo_produto = str(nota['codigo_produto']).decode('cp1252').encode('utf8')
        mes = str(int(nota['mes']))
        ano = str(int(nota['ano']))
        quantidade = str(nota['quantidade'])
        valor = str(nota['valor'])

        sql_insert_nota = "INSERT INTO public.notas_vendas (estabelecimento, mes_data_entrada, ano_data_entrada, codigo_produto, quantidade, valor, created_at) VALUES('%s', '%s', '%s', '%s', '%s', '%s', NOW());" % (estabelecimento, mes, ano,codigo_produto, quantidade, valor)
        cursor.execute(sql_insert_nota)
        conn.commit()
    cursor.close()
    return True

try:
    connect_str = "dbname='postgres' user='userportal' password='gNyVrq&59jM0X' host='localhost'"
    # connect_str = "dbname='postgres' user='userportal' password='@mntecidos2018' host='bdprod.mntecidos.local'"
    conn_portal = psycopg2.connect(connect_str)
except Exception as e:
    print e
    exit(0)

try:
    connect_str = "dbname='tecidos_mn' user='userportal' password='gNyVrq&59jM0X' host='bdprod.mntecidos.local'"
    conn_nasajon = psycopg2.connect(connect_str)
except Exception as e:
    print e
    exit(0)
    
signal(SIGINT, handler)

data_final = date.today()
data_inicial = data_final.replace(day = 1)
mes = data_inicial.month
ano = data_inicial.year
data_final = str(data_final)
data_inicial = str(data_inicial)

removerVendas(conn_portal, mes, ano)


print str(time.strftime("%H:%M:%S")) + ' Inicio busca de notas - NASAJON'
vendas_nasajon = buscaVendas(conn_nasajon, data_inicial, data_final)
print str(time.strftime("%H:%M:%S")) + ' Quantidade de registos encontrados: ' + str(len(vendas_nasajon))
print str(time.strftime("%H:%M:%S")) + ' Fim busca de notas - NASAJON \n'

print str(time.strftime("%H:%M:%S")) + ' Inicio inclusão de vendas - NASAJON'
incluirDadosVendas(conn_portal, vendas_nasajon)
print str(time.strftime("%H:%M:%S")) + ' Fim inclusão de vendas - NASAJON \n'

exit_program()