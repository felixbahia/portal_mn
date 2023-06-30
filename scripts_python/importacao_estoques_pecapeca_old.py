# coding: utf-8
import time
import psycopg2
import psycopg2.extras

try:
    # connect_str = "dbname='postgres' user='userportal' password='@mntecidos2018' host='bdprod.mntecidos.local'"
    connect_str = "dbname='postgres' user='userportal' password='gNyVrq&59jM0X' host='localhost'"
    conn_portal = psycopg2.connect(connect_str)
    cursor_portal = conn_portal.cursor(cursor_factory=psycopg2.extras.DictCursor)
except Exception as e:
    print e
    exit(0)

try:
    connect_str = "dbname='tecidos_mn' user='userportal' password='gNyVrq&59jM0X' host='bdprod.mntecidos.local'"
    conn_nasajon = psycopg2.connect(connect_str)
    cursor_nasajon = conn_nasajon.cursor(cursor_factory=psycopg2.extras.DictCursor)
except Exception as e:
    print e
    exit(0)

tempo = int(time.strftime("%S"))
print "----------------------------------------------------------------"
print "--------------------------Inicio - Zera campos -----------------"
print "----------------------------------------------------------------"
print time.strftime("%H:%M:%S")
zerar_estoque = 'UPDATE public.produtos_estoques set compras = 0, compras_aberto = 0, estoque = 0, custo = 0, saldo_fiscal = 0, saldo_em_terceiros = 0, saldo_de_terceiros = 0, saldo_armazem = 0 ;'
cursor_portal.execute(zerar_estoque)
print time.strftime("%H:%M:%S")
tempo_fim = int(time.strftime("%S"))
print tempo_fim - tempo
print "----------------------------------------------------------------"
print "----------------------------FIM - Zera campos ------------------"
print "----------------------------------------------------------------\n"

tempo = int(time.strftime("%S"))
print "----------------------------------------------------------------"
print "-------------------Inicio - Busca saldo Nasajon-----------------"
print "----------------------------------------------------------------"
print time.strftime("%H:%M:%S")
print "----------------------------------------------------------------"

estabelecimentos_nasajon = ['01', '02', '03', '04', '05', '06', '07', '08']
for estabelecimento_key in estabelecimentos_nasajon:
    tempo = int(time.strftime("%S"))
    print time.strftime("%H:%M:%S")
    
    sql_pecas = "SELECT * FROM integracoes.exportar_produtos_saldos('%s') where saldo_fiscal > 0 OR saldo_em_terceiros > 0 OR saldo_de_terceiros > 0 OR saldo_armazem > 0" % (estabelecimento_key)
    cursor_nasajon.execute(sql_pecas)

    for dados_produto in cursor_nasajon.fetchall():
        estabelecimento = str(dados_produto[0])
        produto = str(dados_produto[1]).decode('cp1252').encode('utf8')
        saldo_fiscal = str(dados_produto[2])
        saldo_em_terceiros = str(dados_produto[3])
        saldo_de_terceiros = str(dados_produto[4])
        saldo_armazem = str(dados_produto[5])
        custo = str(dados_produto[6])
        empenho = str(0)

        if estabelecimento == "04" or estabelecimento == "03":
            quantidade = str(dados_produto[5])
        else:
            quantidade = str(dados_produto[2])

        sql_exist = "SELECT COUNT(*) FROM public.produtos_estoques where codigo_produto = %s and estabelecimento = %s; "
        cursor_portal.execute(sql_exist, (produto, estabelecimento))
        row = cursor_portal.fetchone()[0]

        if row > 0 :
            sql_insert_peca = "UPDATE public.produtos_estoques set estoque = %s, custo = %s, data_atulizacao = NOW(), saldo_fiscal = %s, saldo_em_terceiros = %s, saldo_de_terceiros = %s, saldo_armazem = %s WHERE estabelecimento = %s AND codigo_produto = %s;"
            cursor_portal.execute(sql_insert_peca, (quantidade, custo, saldo_fiscal, saldo_em_terceiros, saldo_de_terceiros, saldo_armazem, estabelecimento, produto))
        else:
            sql_insert_peca = "INSERT INTO public.produtos_estoques (estabelecimento, codigo_produto, estoque, custo, empenho, data_atulizacao, saldo_fiscal, saldo_em_terceiros, saldo_de_terceiros, saldo_armazem) VALUES(%s, %s, %s, %s, %s, NOW(), %s, %s, %s, %s)"
            cursor_portal.execute(sql_insert_peca, (estabelecimento, produto, quantidade, custo, empenho, saldo_fiscal, saldo_em_terceiros, saldo_de_terceiros, saldo_armazem))

    print time.strftime("%H:%M:%S")
    tempo_fim = int(time.strftime("%S"))
    print tempo_fim - tempo
print "----------------------------------------------------------------"
print "---------------------Fim - Busca saldo Nasajon------------------"
print "----------------------------------------------------------------\n"


tempo = int(time.strftime("%S"))
print "----------------------------------------------------------------"
print "------------------Inicio - Busca compras Nasajon----------------"
print "----------------------------------------------------------------"
print time.strftime("%H:%M:%S")
sql_compras_nasajon = "SELECT estabelecimento, cod_produto AS codigo_produto, SUM (quantidade * CASE WHEN vw_produtos_unidadesdeconversoes.razao IS NOT NULL THEN vw_produtos_unidadesdeconversoes.razao ELSE 1 END) AS quantidade FROM estoque.vw_produtos_compras_tecidos_mn LEFT JOIN integracoes.vw_produtos_unidadesdeconversoes ON (vw_produtos_unidadesdeconversoes.codigo_unidadeconversao = vw_produtos_compras_tecidos_mn.unidade_comercial AND vw_produtos_unidadesdeconversoes.codigo_produto = vw_produtos_compras_tecidos_mn.cod_produto) WHERE (situacao = 'Aguardando Documento') GROUP BY estabelecimento, cod_produto"
cursor_nasajon.execute(sql_compras_nasajon)

for dados_produto in cursor_nasajon.fetchall():
    estabelecimento = str(dados_produto[0])
    produto = str(dados_produto[1]).decode('cp1252').encode('utf8')
    quantidade = str(dados_produto[2])
    empenho = str(0)
    estoque = str(0)

    sql_exist = "SELECT COUNT(*) FROM public.produtos_estoques where codigo_produto = %s and estabelecimento = %s; "
    cursor_portal.execute(sql_exist, (produto, estabelecimento))
    row = cursor_portal.fetchone()[0]

    if row > 0 :
        sql_insert_peca = "UPDATE public.produtos_estoques set compras = compras + %s, data_atulizacao = NOW() WHERE estabelecimento = %s AND codigo_produto = %s;"
        cursor_portal.execute(sql_insert_peca, (quantidade, estabelecimento, produto))
    else:
        sql_insert_peca = "INSERT INTO public.produtos_estoques (estabelecimento, codigo_produto, estoque, compras, empenho, data_atulizacao) VALUES(%s, %s, %s, %s, %s, NOW())"
        cursor_portal.execute(sql_insert_peca, (estabelecimento, produto, estoque, quantidade, empenho))

print time.strftime("%H:%M:%S")
tempo_fim = int(time.strftime("%S"))
print tempo_fim - tempo

tempo = int(time.strftime("%S"))
print time.strftime("%H:%M:%S")
sql_compras_nasajon = "SELECT estabelecimento, cod_produto AS codigo_produto, SUM (quantidade * CASE WHEN vw_produtos_unidadesdeconversoes.razao IS NOT NULL THEN vw_produtos_unidadesdeconversoes.razao ELSE 1 END) AS quantidade FROM estoque.vw_produtos_compras_tecidos_mn LEFT JOIN integracoes.vw_produtos_unidadesdeconversoes ON (vw_produtos_unidadesdeconversoes.codigo_unidadeconversao = vw_produtos_compras_tecidos_mn.unidade_comercial AND vw_produtos_unidadesdeconversoes.codigo_produto = vw_produtos_compras_tecidos_mn.cod_produto) WHERE (situacao in ('Aberto', 'Aguardando Documento')) GROUP BY estabelecimento, cod_produto UNION ALL SELECT estabelecimento_codigo AS estabelecimento, produto AS cod_produto, SUM(quantidade) AS quantidade FROM integracoes.vw_saldos_em_transitos GROUP BY estabelecimento_codigo, produto"
cursor_nasajon.execute(sql_compras_nasajon)

for dados_produto in cursor_nasajon.fetchall():
    estabelecimento = str(dados_produto[0])
    produto = str(dados_produto[1]).decode('cp1252').encode('utf8')
    quantidade = str(dados_produto[2])
    empenho = str(0)
    estoque = str(0)

    sql_exist = "SELECT COUNT(*) FROM public.produtos_estoques where codigo_produto = %s and estabelecimento = %s; "
    cursor_portal.execute(sql_exist, (produto, estabelecimento))
    row = cursor_portal.fetchone()[0]

    if row > 0 :
        sql_insert_peca = "UPDATE public.produtos_estoques set compras_aberto = compras_aberto + %s, data_atulizacao = NOW() WHERE estabelecimento = %s AND codigo_produto = %s;"
        cursor_portal.execute(sql_insert_peca, (quantidade, estabelecimento, produto))
    else:
        sql_insert_peca = "INSERT INTO public.produtos_estoques (estabelecimento, codigo_produto, estoque, compras_aberto, empenho, data_atulizacao) VALUES(%s, %s, %s, %s, %s, NOW())"
        cursor_portal.execute(sql_insert_peca, (estabelecimento, produto, estoque, quantidade, empenho))

print time.strftime("%H:%M:%S")
tempo_fim = int(time.strftime("%S"))
print tempo_fim - tempo

tempo = int(time.strftime("%S"))
print time.strftime("%H:%M:%S")
sql_compras_nasajon = "SELECT estabelecimento, cod_produto AS codigo_produto, SUM (quantidade_restante * CASE WHEN vw_produtos_unidadesdeconversoes.razao IS NOT NULL THEN vw_produtos_unidadesdeconversoes.razao ELSE 1 END) AS quantidade FROM estoque.vw_produtos_compras_tecidos_mn LEFT JOIN integracoes.vw_produtos_unidadesdeconversoes ON (vw_produtos_unidadesdeconversoes.codigo_unidadeconversao = vw_produtos_compras_tecidos_mn.unidade_comercial AND vw_produtos_unidadesdeconversoes.codigo_produto = vw_produtos_compras_tecidos_mn.cod_produto) WHERE (situacao = 'Parcialmente Liquidado') and quantidade_restante > 0  GROUP BY estabelecimento, cod_produto"
cursor_nasajon.execute(sql_compras_nasajon)

for dados_produto in cursor_nasajon.fetchall():
    estabelecimento = str(dados_produto[0])
    produto = str(dados_produto[1]).decode('cp1252').encode('utf8')
    quantidade = str(dados_produto[2])
    empenho = str(0)
    estoque = str(0)

    sql_exist = "SELECT COUNT(*) FROM public.produtos_estoques where codigo_produto = %s and estabelecimento = %s; "
    cursor_portal.execute(sql_exist, (produto, estabelecimento))
    row = cursor_portal.fetchone()[0]

    if row > 0 :
        sql_insert_peca = "UPDATE public.produtos_estoques set compras = compras + %s, compras_aberto = compras_aberto + %s, data_atulizacao = NOW() WHERE estabelecimento = %s AND codigo_produto = %s;"
        cursor_portal.execute(sql_insert_peca, (quantidade, quantidade, estabelecimento, produto))
    else:
        sql_insert_peca = "INSERT INTO public.produtos_estoques (estabelecimento, codigo_produto, estoque, compras, empenho, data_atulizacao) VALUES(%s, %s, %s, %s, %s, NOW())"
        cursor_portal.execute(sql_insert_peca, (estabelecimento, produto, estoque, quantidade, empenho))

print time.strftime("%H:%M:%S")
tempo_fim = int(time.strftime("%S"))
print tempo_fim - tempo
print "----------------------------------------------------------------"
print "--------------------Fim - Busca compras Nasajon-----------------"
print "----------------------------------------------------------------\n"

sql_excluir = "DELETE FROM produtos_estoques WHERE data_atulizacao < (SELECT max(data_atulizacao) FROM produtos_estoques LIMIT 1)"
cursor_portal.execute(sql_excluir)


conn_portal.commit()

conn_portal.close()
conn_nasajon.close()