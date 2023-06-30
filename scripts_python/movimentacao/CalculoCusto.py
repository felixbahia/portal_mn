# coding: utf-8
import time
from datetime import datetime, timedelta
import psycopg2
import psycopg2.extras
import sys
import os
from dotenv import load_dotenv
from LogExecucao import LogExecucao
load_dotenv('../../.env')

try:
    connect_str = "dbname='{0}' user='{1}' password='{2}' host='{3}'". format(os.getenv('DB_DATABASE'), os.getenv('DB_USERNAME'), os.getenv('DB_PASSWORD'), os.getenv('DB_HOST'))
    conn_portal = psycopg2.connect(connect_str)
    conn_portal_2 = psycopg2.connect(connect_str)
    cursor_portal = conn_portal.cursor(cursor_factory=psycopg2.extras.DictCursor)
    cursor_portal_2 = conn_portal_2.cursor(cursor_factory=psycopg2.extras.DictCursor)
except Exception as e:
    print e
    exit(0)

LogExecucao('movimentacao_calculo_custo', False)

def check_ficha_tecnica(produto):
    sql_busca = "select * from ficha_tecnica_produtos where codigo_produto = '{0}' and deleted_at is null". format(produto)
    cursor_portal.execute(sql_busca)
    busca = cursor_portal.fetchone()
    if busca is None:
        return False
    else:
        return True

codigos_iguinorar_custo = ['0916', '1116', '1202', '1551', '1556', '1903', '1911', '1915', '1949', '2202', '2503', '2551', '2553', '2556', '2903', '2906', '2911', '2915', '2949', '5101', '5102', '5106', '5117', '5123', '5201', '5202', '5551', '5556', '5901', '5910', '5911', '5914', '5915', '5927', '5929', '5949', '6102', '6106', '6108', '6117', '6119', '6123', '6201', '6202', '6501', '6551', '6901', '6910', '6911', '6915', '6922', '6949', '7102', '8000', '8001', '8002', '9999', '5118', '5119', '6118', '6119', '5101', '6101', '6110', '5110', '6905', '5905', '1201', '1410', '1901', '1902', '1906', '1912', '1913', '1916', '1922', '1925', '2201', '2902', '2907', '2913', '2916', '2923', '2925', '3949', '5413', '5905', '5912', '5913', '5923', '6000', '6124', '6556', '6912', '6923', '6924']

codigos_contas_especial = ['1124', '2124', '1125', '2125']
codigos_transferecia = ['5156', '6156', '6152', '6552', '2552', '6557', '5152', '6151', '5151', '1152', '2152', '1151', '2151', '0']

cfop_terceiro_entrada = ['1554', '1902', '1902', '1903', '1905', '1905', '1908', '1909', '1915', '1916', '1918', '1919', '1925', '1925', '1949', '2554', '2902', '2902', '2903', '2905', '2905', '2906', '2908', '2909', '2915', '2916', '2918', '2919', '2925', '2925', '2949', '2949.1']
cfop_terceiro_saida = ['5106', '5106', '5106', '5123', '5123', '5124', '5554', '5901', '5905', '5905', '5906', '5906', '5909', '5910', '5911', '5911', '5915', '5917', '5923', '5923', '5949', '6106', '6106', '6106', '6107', '6108', '6108', '6108', '6108', '6117', '6123', '6123', '6124', '6156', '6554', '6901', '6905', '6905', '6906', '6906', '6909', '6910', '6911', '6911', '6915', '6917', '6923', '6923', '6949']

data_final = datetime.today()
data_inicial = (data_final - timedelta(1*365/12))

data_inicial = '2022-07-01'
data_final = str(data_final.strftime("%Y-%m-%d"))

sql_produtos = "SELECT distinct produto_codigo FROM movimentacao_recalculo where data_movimentacao between '%s' and '%s'"% (data_inicial, data_final)
cursor_portal_2.execute(sql_produtos)
for dados_produto in cursor_portal_2.fetchall():
    produto_codigo = str(dados_produto['produto_codigo'])
    sql_movimentacao = "SELECT * FROM movimentacao_recalculo where produto_codigo = '%s' ORDER BY estabelecimento, produto_codigo, data_movimentacao, tipo_operacao, id"% (produto_codigo)
    produto_ultimo = ''
    custo_ultimo = 0
    custo_ultimo_icm = 0
    custo_ultimo_pcmn = 0
    estoque_ultimo = 0
    estabelecimento_ultimo = ''
    ultimo_pcmn = 0

    cursor_portal.execute(sql_movimentacao)
    for movimento in cursor_portal.fetchall():
        estabelecimento = str(movimento['estabelecimento'])
        produto_codigo = str(movimento['produto_codigo'])
        data_movimentacao = str(movimento['data_movimentacao'])
        tipo_operacao = str(movimento['tipo_operacao'])
        cfop = str(movimento['cfop'])
        quantidade = round(movimento['quantidade'], 2)
        preco_custo = movimento['preco']

        item_precototal = movimento['item_precototal']
        if item_precototal is None:
            item_precototal = 0
        item_ratdespaduaneira = movimento['item_ratdespaduaneira']
        if item_ratdespaduaneira is None:
            item_ratdespaduaneira = 0
        custo_importacao_outrasdespesas = movimento['custo_importacao_outrasdespesas']
        if custo_importacao_outrasdespesas is None:
            custo_importacao_outrasdespesas = 0
        custo_importacao_ii = movimento['custo_importacao_ii']
        if custo_importacao_ii is None:
            custo_importacao_ii = 0
        custo_importacao_aframm = movimento['custo_importacao_aframm']
        if custo_importacao_aframm is None:
            custo_importacao_aframm = 0
        custo_importacao_siscomex = movimento['custo_importacao_siscomex']
        if custo_importacao_siscomex is None:
            custo_importacao_siscomex = 0
        custo_importacao_pis = movimento['custo_importacao_pis']
        if custo_importacao_pis is None:
            custo_importacao_pis = 0
        custo_importacao_cofins = movimento['custo_importacao_cofins']
        if custo_importacao_cofins is None:
            custo_importacao_cofins = 0
        desconto = movimento['desconto']
        if desconto is None:
            desconto = 0
        seguro = movimento['seguro']
        if seguro is None:
            seguro = 0
        frete = movimento['frete']
        if frete is None:
            frete = 0

        valor_outras_despesas = movimento['valor_outras_despesas']
        if valor_outras_despesas is None:
            valor_outras_despesas = 0
        valor_pis = movimento['valor_pis']
        if valor_pis is None:
            valor_pis = 0
        valor_cofins = movimento['valor_cofins']
        if valor_cofins is None:
            valor_cofins = 0
        valor_2 = movimento['valor_2']
        if valor_2 is None:
            valor_2 = 0
        valor_aframm = movimento['valor_aframm']
        if valor_aframm is None:
            valor_aframm = 0
        valor_ipi = movimento['ipi']
        if valor_ipi is None:
            valor_ipi = 0

        preco_pcmn = movimento['preco_pcmn']
        if preco_pcmn is None:
            preco_pcmn = 0

        preco_custo_icm = (((100 - movimento['aliquota']) / 100) * movimento['preco'])
        if cfop not in codigos_transferecia:
            preco_custo_icm = (((100 - 9.25) / 100) * preco_custo_icm)

        if cfop == '0' and custo_ultimo_icm > 0:
            preco_custo_icm = custo_ultimo_icm

        if(movimento['documento'] == 'custo_contail'):
            preco_custo = 0
            preco_custo_icm = movimento['preco']

        id_movimento = str(movimento['id'])

        if estabelecimento_ultimo != estabelecimento or produto_codigo != produto_ultimo:
            estoque_ultimo = 0
            custo_ultimo = 0
            custo_ultimo_icm = 0
            custo_ultimo_pcmn = 0
            ultimo_pcmn = preco_pcmn

        if custo_ultimo_icm is None:
            custo_ultimo_icm = 0

        estabelecimento_ultimo = estabelecimento
        produto_ultimo = produto_codigo

        estoque_ultimo_old = estoque_ultimo

        if (tipo_operacao[0] == 'S'):
            if cfop not in cfop_terceiro_entrada:
                estoque_ultimo = estoque_ultimo - quantidade
        elif (tipo_operacao[0] == 'E'):
            if (cfop not in codigos_iguinorar_custo and preco_custo > 0):
                if custo_ultimo > 0 and estoque_ultimo > 0:
                    estoque_ultimo_calculo = estoque_ultimo
                    if estoque_ultimo_calculo < 0:
                        estoque_ultimo_calculo = 0
                    if cfop == '3102':

                        valor_estoque = estoque_ultimo_calculo * custo_ultimo 
                        valor_entrada = quantidade * preco_custo 

                        estoque_ultimo = estoque_ultimo + quantidade 
                        if estoque_ultimo > 0:
                            custo_ultimo = (valor_estoque + valor_entrada) / estoque_ultimo

                        valor_estoque = estoque_ultimo_calculo * custo_ultimo_icm #2.043,33527317864

                        valor_entrada = (item_precototal - desconto + seguro + item_ratdespaduaneira + frete + custo_importacao_outrasdespesas + custo_importacao_ii + custo_importacao_aframm + custo_importacao_siscomex + custo_importacao_pis + custo_importacao_cofins)
                        if estoque_ultimo > 0:
                            custo_ultimo_icm = (valor_estoque + valor_entrada) / estoque_ultimo

                        valor_estoque = estoque_ultimo_calculo * custo_ultimo_pcmn
                        valor_entrada = quantidade * preco_pcmn

                        if estoque_ultimo > 0:
                            custo_ultimo_pcmn = (valor_estoque + valor_entrada) / estoque_ultimo


                    elif cfop in codigos_contas_especial:
                        valor_estoque = estoque_ultimo_calculo * custo_ultimo
                        valor_entrada = (quantidade * custo_ultimo)
                        valor_entrada_calculo = (quantidade * preco_custo)
                        estoque_ultimo = estoque_ultimo + quantidade
                        if estoque_ultimo > 0:
                            if check_ficha_tecnica(produto_codigo) == False:
                                custo_ultimo = (valor_estoque + valor_entrada + valor_entrada_calculo) / estoque_ultimo
                            else:
                                custo_ultimo_icm = movimento['custo_sem_imposto']
                        if custo_ultimo_icm is None:
                            custo_ultimo_icm = 0
                        valor_estoque = estoque_ultimo_calculo * custo_ultimo_icm
                        valor_entrada = (quantidade * custo_ultimo_icm)
                        valor_entrada_calculo = (quantidade * preco_custo_icm)
                        if estoque_ultimo > 0:
                            if check_ficha_tecnica(produto_codigo) == False:
                                custo_ultimo_icm = (valor_estoque + valor_entrada + valor_entrada_calculo) / estoque_ultimo
                            else:
                                custo_ultimo_icm = movimento['custo_sem_imposto']

                        valor_estoque = estoque_ultimo_calculo * custo_ultimo_pcmn
                        valor_entrada = (quantidade * custo_ultimo_pcmn)
                        valor_entrada_calculo = (quantidade * preco_pcmn)
                        if estoque_ultimo > 0:
                            if check_ficha_tecnica(produto_codigo) == False:
                                custo_ultimo_pcmn = (valor_estoque + valor_entrada + valor_entrada_calculo) / estoque_ultimo
                            else:
                                custo_ultimo_icm = movimento['custo_sem_imposto']
                    else:
                        valor_estoque = estoque_ultimo_calculo * custo_ultimo
                        valor_entrada = quantidade * preco_custo

                        estoque_ultimo = estoque_ultimo + quantidade
                        if estoque_ultimo > 0:
                            custo_ultimo = (valor_estoque + valor_entrada) / estoque_ultimo

                        valor_estoque = estoque_ultimo_calculo * custo_ultimo_icm
                        valor_entrada = quantidade * preco_custo_icm

                        if estoque_ultimo > 0:
                            custo_ultimo_icm = (valor_estoque + valor_entrada) / estoque_ultimo

                        valor_estoque = estoque_ultimo_calculo * custo_ultimo_pcmn
                        valor_entrada = quantidade * preco_pcmn

                        if estoque_ultimo > 0:
                            custo_ultimo_pcmn = (valor_estoque + valor_entrada) / estoque_ultimo
                else:
                    estoque_ultimo = estoque_ultimo + quantidade
                    custo_ultimo = preco_custo
                    custo_ultimo_icm = preco_custo_icm
                    custo_ultimo_pcmn = preco_pcmn

                    if check_ficha_tecnica(produto_codigo) == True:
                        custo_ultimo_icm = movimento['custo_sem_imposto']

                    if cfop == '3102':

                        custo_ultimo_icm = (item_precototal - desconto + seguro + item_ratdespaduaneira + item_ratdespaduaneira + frete + custo_importacao_outrasdespesas + custo_importacao_ii + custo_importacao_aframm + custo_importacao_siscomex + custo_importacao_pis + custo_importacao_cofins) / quantidade

            else:
                if cfop in codigos_contas_especial:
                    if check_ficha_tecnica(produto_codigo) == False:
                        estoque_ultimo_calculo = estoque_ultimo
                        if estoque_ultimo_calculo < 0:
                            estoque_ultimo_calculo = 0

                        valor_estoque = estoque_ultimo_calculo * custo_ultimo_icm
                        valor_entrada = quantidade * preco_custo_icm

                        estoque_ultimo = estoque_ultimo + quantidade
                        if estoque_ultimo > 0:
                            custo_ultimo_icm = (valor_estoque + valor_entrada) / estoque_ultimo
                    else:
                        estoque_ultimo = estoque_ultimo + quantidade
                        custo_ultimo_icm = movimento['custo_sem_imposto']
                        custo_ultimo = movimento['custo']
                else:
                    if cfop not in cfop_terceiro_saida:
                        estoque_ultimo = estoque_ultimo + quantidade

                        if movimento['documento'] == 'custo_contail':
                            custo_ultimo_icm = preco_custo_icm

        if estoque_ultimo < 0:
            estoque_ultimo = 0

        if custo_ultimo_pcmn is None:
            custo_ultimo_pcmn = 0

        if custo_ultimo_icm is None:
            custo_ultimo_icm = 0
        
        if custo_ultimo is None:
            custo_ultimo = 0

        data_movimentacao = data_movimentacao.replace(" 00:00:00", "")
        data_inicial_teste = datetime.strptime('07-01-2022', "%m-%d-%Y").date()
        data_final_teste = datetime.strptime(data_movimentacao, '%Y-%m-%d').date()

        if data_inicial_teste <= data_final_teste :
            if cfop in codigos_transferecia and custo_ultimo_icm == 0:
                custo_ultimo_icm = custo_ultimo

            if cfop in codigos_iguinorar_custo and custo_ultimo_icm == 0:
                custo_ultimo_icm = custo_ultimo

        estoque_ultimo = round(estoque_ultimo, 2)

        sql_update_produto = 'UPDATE movimentacao_recalculo SET custo = \'%s\', custo_sem_imposto = \'%s\', custo_pcmn = \'%s\', saldo_movimentos = \'%s\' WHERE id = %s; ' % (str(custo_ultimo), str(custo_ultimo_icm), str(custo_ultimo_pcmn), str(estoque_ultimo), id_movimento)

        cursor_portal.execute(sql_update_produto)

        conn_portal.commit()
conn_portal.close()
conn_portal_2.close()

LogExecucao('movimentacao_calculo_custo', True)
