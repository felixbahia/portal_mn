# coding: utf-8
import time
import psycopg2
import psycopg2.extras
import sys
import os
from dotenv import load_dotenv

load_dotenv('../.env')

try:
    connect_str = "dbname='{0}' user='{1}' password='{2}' host='{3}'". format(os.getenv('DB_DATABASE'), os.getenv('DB_USERNAME'), os.getenv('DB_PASSWORD'), os.getenv('DB_HOST'))
    conn_portal = psycopg2.connect(connect_str)
    cursor_portal = conn_portal.cursor(cursor_factory=psycopg2.extras.DictCursor)
except Exception as e:
    print e
    exit(0)

try:
    connect_str = "dbname='{0}' user='{1}' password='{2}' host='{3}'". format(os.getenv('DB_NASAJON_DATABASE'), os.getenv('DB_NASAJON_USERNAME'), os.getenv('DB_NASAJON_PASSWORD'), os.getenv('DB_NASAJON_HOST'))
    conn_nasajon = psycopg2.connect(connect_str)
    conn_nasajon.set_client_encoding('UTF8')
    cursor_nasajon = conn_nasajon.cursor(cursor_factory=psycopg2.extras.DictCursor)
except Exception as e:
    print e
    exit(0)

tempo = int(time.strftime("%S"))
print "----------------------------------------------------------------"
print "---------------Inicio - Busca de produto Nasajon----------------"
print "----------------------------------------------------------------"
print time.strftime("%H:%M:%S")
sql_produtos_nasajon = 'SELECT case when pesoliquido is null then 0 else cast(pesoliquido as float) end as peso, * FROM integracoes.vw_produtos;'
cursor_nasajon.execute(sql_produtos_nasajon)

sqls_update = ''
for dados_produto in cursor_nasajon.fetchall():
    produto = str(dados_produto['codigo'])
    descricao = str(dados_produto['especificacao'])
    descricao = descricao.replace('\'','\\\'')
    descricao = descricao.strip()

    sql_exist = "SELECT COUNT(*) FROM public.produto_especificacaos where codigo_produto = '%s'; " % (produto)
    cursor_portal.execute(sql_exist)
    row = cursor_portal.fetchone()[0]
    unidade = str(dados_produto['unidade'])
    procedenciaprod = str(dados_produto['origemmercadoria'])
    peso = float(dados_produto['peso'])
    data_cadastro = str(dados_produto['data_criacao'])

    if row > 0 :
		sqls_update = 'UPDATE public.produto_especificacaos SET descricao = %s, procedencia = %s, peso = %s, unidade = %s, data_de_cadastro = %s WHERE codigo_produto = %s;'
		cursor_portal.execute(sqls_update, (descricao, procedenciaprod, peso, unidade, data_cadastro, produto))
    else:
        sql_insert = "INSERT INTO public.produto_especificacaos (codigo_produto, descricao, procedencia, unidade, data_de_cadastro, marca, linha, grupo, produto_grupos_id, subgrupo, created_at) VALUES(%s, %s, %s, %s, %s, 'A CADASTRAR', 'A CADASTRAR', 'A CADASTRAR', '5256', 'A CADASTRAR', NOW())"
        cursor_portal.execute(sql_insert, (produto, descricao, procedenciaprod, unidade, data_cadastro))

print time.strftime("%H:%M:%S")
tempo_fim = int(time.strftime("%S"))
print tempo_fim - tempo
print "-----------------------------------------------------------------"
print "------------------Fim - Busca de produto Nasajon------------------"
print "------------------------------------------------------------------\n"


tempo = int(time.strftime("%S"))
print "----------------------------------------------------------------"
print "------------Inicio - Busca de produto industrializado-----------"
print "----------------------------------------------------------------"
print time.strftime("%H:%M:%S")
sql_produtos_movimentacao = "select produto_codigo from movimentacao_recalculo where cfop in ('1124', '2124', '1125', '2125') and data_movimentacao > '2019-07-07' and estabelecimento = '05' group by produto_codigo;"

sqls_update = 'UPDATE public.produto_especificacaos SET industrializado = false;'
cursor_portal.execute(sqls_update)

cursor_portal.execute(sql_produtos_movimentacao)

sqls_update = ''
for dados_produto in cursor_portal.fetchall():
    produto = str(dados_produto['produto_codigo'])

    sqls_update = "UPDATE public.produto_especificacaos SET industrializado = true WHERE codigo_produto = '{0}';". format(produto)
    cursor_portal.execute(sqls_update)

print time.strftime("%H:%M:%S")
tempo_fim = int(time.strftime("%S"))
print tempo_fim - tempo
print "-----------------------------------------------------------------"
print "---------------Fim - Busca de produto industrializado-------------"
print "------------------------------------------------------------------\n"

conn_portal.commit()

conn_portal.close()
conn_nasajon.close()
