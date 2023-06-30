import time
from datetime import datetime, timedelta
import psycopg2
import psycopg2.extras
import sys
import os
from dotenv import load_dotenv
from signal import signal, SIGINT
from LogExecucao import LogExecucao

def handler(signal_received, frame):
    print ("\n")
    print ('\n\nexit')
    exit(0)

class ImportacaoEstoque:
    def __init__(self):
        self.conn_nasajon = ''
        self.conn_portal = ''
        load_dotenv('../.env')

        self.openConnecion()

        conn = self.conn_portal
        cursor_portal = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
            
        sql_verificacao = "select * from atualizacao_cron ac where \"token\" in ('campanha:verificar_importacao', 'estoque') and inicio_atualizacao > atualizacao"

        cursor_portal.execute(sql_verificacao)
        row = cursor_portal.fetchone()
        cursor_portal.close()
        
        if row > 0 :
            print ('\n\nexit')
        else:
            LogExecucao('estoque', False)
            self.ini()
            LogExecucao('estoque', True)
        
        self.exitProgram()

    def closeConnection(self):
        self.conn_portal.close()
        self.conn_nasajon.close()

        self.conn_nasajon = ''
        self.conn_portal = ''

    def openConnecion(self):
        try:

            #connect_str = "dbname='postgres' user='userportal' password='@mntecidos2018' host='10.2.14.8'"
            connect_str = "dbname='{0}' user='{1}' password='{2}' host='{3}'". format(os.getenv('DB_DATABASE'), os.getenv('DB_USERNAME'), os.getenv('DB_PASSWORD'), os.getenv('DB_HOST'))
            #connect_str = "dbname='postgres' user='postgres' password='postgres' host='localhost'"

            self.conn_portal = psycopg2.connect(connect_str)
        except Exception as e:
            print (e)
            exit(0)

        try:
            #connect_str = "dbname='tecidos_mn' user='userportal' password='@mntecidos2018' host='10.2.14.8'"
            connect_str = "dbname='{0}' user='{1}' password='{2}' host='{3}'". format(os.getenv('DB_NASAJON_DATABASE'), os.getenv('DB_NASAJON_USERNAME'), os.getenv('DB_NASAJON_PASSWORD'), os.getenv('DB_NASAJON_HOST'))
            self.conn_nasajon = psycopg2.connect(connect_str)
            self.conn_nasajon.set_client_encoding('UTF8')
        except Exception as e:
            print (e)
            exit(0)

    def exitProgram(self):
        self.closeConnection()
        print ('\n\nexit')
        exit(0)

    def zerarEstoque(self):

        conn = self.conn_portal
        cursor_portal = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        zerar_estoque = 'UPDATE public.produtos_estoques set compras = 0, compras_aberto = 0, estoque = 0, saldo_fiscal = 0, saldo_em_terceiros = 0, saldo_de_terceiros = 0, saldo_armazem = 0, saldo_movimento_nao_efetivado = 0, reserva = 0, reserva_pronta_entrega = 0 where (compras <> 0 or compras_aberto <> 0 or estoque <> 0 or saldo_fiscal <> 0 or saldo_em_terceiros <> 0 or saldo_de_terceiros <> 0 or saldo_armazem <> 0 or saldo_movimento_nao_efetivado <> 0 or reserva <> 0 or reserva_pronta_entrega <> 0) and estabelecimento in (\'03\', \'04\',\'05\',\'06\',\'07\',\'08\');'
        cursor_portal.execute(zerar_estoque)

        cursor_portal.close()

        return True

    def buscaEstoque(self, estabelecimento):

        data_final = datetime.today()
        data_final = str(data_final)
        #data_inicial = "2019-07-05"
        data_inicial = "2021-11-05"
        data_inicial_05_06 = "2018-07-01"
        data_inicial_03 = "2022-02-11"
        data_inicial_04 = "2021-02-09"

        conn = self.conn_nasajon
        cursor_nasajon = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        if estabelecimento == "05" or estabelecimento == "06":
            sql_pecas = """
            SELECT sal.estabelecimento_codigo, sal.produto_codigo, sal.saldo_fiscal, sal.saldo_em_terceiros, sal.saldo_de_terceiros, sal.custo
                FROM integracoes.exportar_produtos_saldos('{}') as sal
                    where (sal.saldo_fiscal > 0 OR sal.saldo_em_terceiros > 0 OR sal.saldo_de_terceiros > 0 OR custo > 0)
                    group by sal.estabelecimento_codigo, sal.produto_codigo, sal.saldo_fiscal, sal.saldo_em_terceiros, sal.saldo_de_terceiros, sal.custo;
                """.format(estabelecimento)
        else:
            sql_pecas = """
            SELECT sal.estabelecimento_codigo, sal.produto_codigo, sal.saldo_fiscal, sal.saldo_em_terceiros, sal.saldo_de_terceiros, sal.custo
                FROM integracoes.exportar_produtos_saldos('{}') as sal
                    where (sal.saldo_fiscal > 0 OR sal.saldo_em_terceiros > 0 OR sal.saldo_de_terceiros > 0)
                    group by sal.estabelecimento_codigo, sal.produto_codigo, sal.saldo_fiscal, sal.saldo_em_terceiros, sal.saldo_de_terceiros, sal.custo;
                """.format(estabelecimento)

        cursor_nasajon.execute(sql_pecas)

        dados = cursor_nasajon.fetchall()
        cursor_nasajon.close()

        return dados
    
    def insertMovimentos(self, dados, busca_saldo_movimento_nao_efetivado):
        conn = self.conn_portal
        cursor_portal = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        sql_insert = ''
        for dados_produto in dados:
            estabelecimento = str(dados_produto['estabelecimento_codigo'])
            produto = str(dados_produto['produto_codigo'])
            saldo_fiscal = str(dados_produto['saldo_fiscal'])
            saldo_em_terceiros = str(dados_produto['saldo_em_terceiros'])
            saldo_de_terceiros = str(dados_produto['saldo_de_terceiros'])
            custo = str(dados_produto['custo'])
            empenho = str(0)

            if estabelecimento == "04" or estabelecimento == "03":
                try:
                    teste = busca_saldo_movimento_nao_efetivado[produto]
                except:
                    busca_saldo_movimento_nao_efetivado[produto] = 0

                saldo_movimento_nao_efetivado = str(busca_saldo_movimento_nao_efetivado[produto])
                saldo_armazem = str(dados_produto['saldo_em_terceiros'] - busca_saldo_movimento_nao_efetivado[produto])
                quantidade = str(dados_produto['saldo_em_terceiros'] - busca_saldo_movimento_nao_efetivado[produto])
            else:
                saldo_movimento_nao_efetivado = "0"
                saldo_armazem = "0"
                quantidade = str(dados_produto['saldo_fiscal'])

            sql_insert_peca = """
                            INSERT INTO
                    public.produtos_estoques
                    (
                        estabelecimento, codigo_produto, estoque, custo, empenho, data_atulizacao, saldo_fiscal, saldo_em_terceiros, saldo_de_terceiros, saldo_armazem, saldo_movimento_nao_efetivado, reserva_pronta_entrega
                    ) VALUES(
                        '{}', '{}', '{}', '{}', '{}', NOW(), '{}', '{}', '{}', '{}', '{}', 0
                    )
                    ON CONFLICT (estabelecimento, codigo_produto) DO UPDATE
                        SET
                            estoque = EXCLUDED.estoque,
                            custo = EXCLUDED.custo,
                            data_atulizacao = NOW(),
                            saldo_fiscal = EXCLUDED.saldo_fiscal,
                            saldo_em_terceiros = EXCLUDED.saldo_em_terceiros,
                            saldo_de_terceiros = EXCLUDED.saldo_de_terceiros,
                            saldo_armazem = EXCLUDED.saldo_armazem,
                            saldo_movimento_nao_efetivado = EXCLUDED.saldo_movimento_nao_efetivado;
                    """.format(estabelecimento, produto, quantidade, custo, empenho, saldo_fiscal, saldo_em_terceiros, saldo_de_terceiros, saldo_armazem, saldo_movimento_nao_efetivado)
            cursor_portal.execute(sql_insert_peca)
        cursor_portal.close()

    def buscaCompras(self):

        conn = self.conn_nasajon
        cursor_nasajon = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        sql_pecas = """
        select
            estabelecimento,
            cod_produto as codigo_produto,
            SUM (quantidade_restante * case when vw_produtos_unidadesdeconversoes.razao is not null then vw_produtos_unidadesdeconversoes.razao else 1 end) as quantidade
        from
            integracoes.vw_produtos_compras
        left join
            integracoes.vw_produtos_unidadesdeconversoes
        on
            (vw_produtos_unidadesdeconversoes.codigo_unidadeconversao = vw_produtos_compras.unidade_comercial
        and
            vw_produtos_unidadesdeconversoes.codigo_produto = vw_produtos_compras.cod_produto)
        where
            (situacao in ('Aguardando Documento', 'Parcialmente Liquidado'))
        group by
            estabelecimento,
            cod_produto"""
        cursor_nasajon.execute(sql_pecas)

        dados = cursor_nasajon.fetchall()
        cursor_nasajon.close()

        return dados

    def insertCompras(self, dados):
        conn = self.conn_portal
        cursor_portal = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        sql_insert = ''
        for dados_produto in dados:
            estabelecimento = str(dados_produto['estabelecimento'])
            produto = str(dados_produto['codigo_produto'])
            quantidade = str(dados_produto['quantidade'])

            sql_insert_peca = """
                INSERT INTO
                    public.produtos_estoques
                    (
                        estabelecimento, codigo_produto, estoque, custo, empenho, data_atulizacao, saldo_fiscal, saldo_em_terceiros, saldo_de_terceiros, saldo_armazem, compras, reserva_pronta_entrega
                    ) VALUES(
                        '{}', '{}', 0, 0, 0, NOW(), 0, 0, 0, 0, '{}', 0
                    )
                    ON CONFLICT (estabelecimento, codigo_produto) DO UPDATE
                        SET
                            compras = EXCLUDED.compras,
                            data_atulizacao = NOW();
                    """
            sql_insert += sql_insert_peca.format(estabelecimento, produto, quantidade)

        cursor_portal.execute(sql_insert)

        cursor_portal.close()
    
    def buscaComprasAberto(self):

        conn = self.conn_nasajon
        cursor_nasajon = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        sql_pecas = """
        select
            estabelecimento,
            cod_produto as codigo_produto,
            SUM (distinct quantidade_restante * case when vw_produtos_unidadesdeconversoes.razao is not null then vw_produtos_unidadesdeconversoes.razao else 1 end) as quantidade
        from
            integracoes.vw_produtos_compras
        left join integracoes.vw_produtos_unidadesdeconversoes on (vw_produtos_unidadesdeconversoes.codigo_unidadeconversao = vw_produtos_compras.unidade_comercial and vw_produtos_unidadesdeconversoes.codigo_produto = vw_produtos_compras.cod_produto)
        where
            situacao in ('Aberto', 'Aguardando Documento', 'Parcialmente Liquidado')
        group by
            estabelecimento,
            cod_produto
        union all
            select
                estabelecimento_codigo as estabelecimento,
                produto as cod_produto,
                SUM(quantidade) as quantidade
            from
                integracoes.vw_saldos_em_transitos
            group by
                estabelecimento_codigo,
                produto"""
        cursor_nasajon.execute(sql_pecas)

        dados = cursor_nasajon.fetchall()
        cursor_nasajon.close()

        return dados

    def insertComprasAberto(self, dados):
        conn = self.conn_portal
        cursor_portal = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        sql_insert = ''
        for dados_produto in dados:
            estabelecimento = str(dados_produto['estabelecimento'])
            produto = str(dados_produto['codigo_produto'])
            quantidade = str(dados_produto['quantidade'])

            sql_insert_peca = """
                INSERT INTO
                    public.produtos_estoques
                    (
                        estabelecimento, codigo_produto, estoque, custo, empenho, data_atulizacao, saldo_fiscal, saldo_em_terceiros, saldo_de_terceiros, saldo_armazem, compras_aberto, reserva_pronta_entrega
                    ) VALUES(
                        '{}', '{}', 0, 0, 0, NOW(), 0, 0, 0, 0, '{}', 0
                    )
                    ON CONFLICT (estabelecimento, codigo_produto) DO UPDATE
                        SET
                            compras_aberto = EXCLUDED.compras_aberto,
                            data_atulizacao = NOW();
                    """

            sql_insert += sql_insert_peca.format(estabelecimento, produto, quantidade)

        cursor_portal.execute(sql_insert)

        cursor_portal.close()

    def buscaComprasRestante(self):

        conn = self.conn_nasajon
        cursor_nasajon = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        sql_pecas = """
        select
            estabelecimento,
            cod_produto as codigo_produto,
            SUM (quantidade_restante * case when vw_produtos_unidadesdeconversoes.razao is not null then vw_produtos_unidadesdeconversoes.razao else 1 end) as quantidade
        from
            integracoes.vw_produtos_compras
            left join integracoes.vw_produtos_unidadesdeconversoes on (vw_produtos_unidadesdeconversoes.codigo_unidadeconversao = vw_produtos_compras.unidade_comercial and vw_produtos_unidadesdeconversoes.codigo_produto = vw_produtos_compras.cod_produto)
        where
            situacao = 'Parcialmente Liquidado'
            and quantidade_restante > 0
        group by
            estabelecimento,
            cod_produto"""
        cursor_nasajon.execute(sql_pecas)

        dados = cursor_nasajon.fetchall()
        cursor_nasajon.close()

        return dados
    
    def insertComprasRestante(self, dados):

        conn = self.conn_portal
        cursor_portal = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        sql_insert = ''
        for dados_produto in dados:
            estabelecimento = str(dados_produto['estabelecimento'])
            produto = str(dados_produto['codigo_produto'])
            quantidade = str(dados_produto['quantidade'])

            sql_insert_peca = """
                INSERT INTO
                    public.produtos_estoques
                    (
                        estabelecimento, codigo_produto, estoque, custo, empenho, data_atulizacao, saldo_fiscal, saldo_em_terceiros, saldo_de_terceiros, saldo_armazem, compras, compras_aberto, reserva_pronta_entrega
                    ) VALUES(
                        '{}', '{}', 0, 0, 0, NOW(), 0, 0, 0, 0, '{}', '{}', 0
                    )
                    ON CONFLICT (estabelecimento, codigo_produto) DO UPDATE
                        SET
                            compras_aberto = produtos_estoques.compras_aberto + EXCLUDED.compras_aberto,
                            compras = produtos_estoques.compras + EXCLUDED.compras,
                            data_atulizacao = NOW();
                    """

            sql_insert += sql_insert_peca.format(estabelecimento, produto, quantidade, quantidade)

        cursor_portal.execute(sql_insert)

        cursor_portal.close()

    def limparProdutosNaoImportados(self):

        conn = self.conn_portal
        cursor_portal = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_excluir = "DELETE FROM produtos_estoques WHERE data_atulizacao < (SELECT max(data_atulizacao) FROM produtos_estoques LIMIT 1)"
        cursor_portal.execute(sql_excluir)
        cursor_portal.close()

    def buscaReserva(self):
        conn_portal = self.conn_portal
        cursor_portal = conn_portal.cursor(cursor_factory=psycopg2.extras.DictCursor)

        conn_nasajon = self.conn_nasajon
        cursor_nasajon = conn_nasajon.cursor(cursor_factory=psycopg2.extras.DictCursor)

        data_verificacao = datetime.today()
        data_verificacao = (data_verificacao + timedelta(15))

        sqlPedidoPortal = """
            select cod_produto, estabelecimento, sum(quantidade) as quantidade
                from pedido_item as pi2
                    left join pedido as p  on p.id = pi2.pedido
                    where exists
                    ( select * from pedido as p  where p.id = pi2.pedido and status_pedido not in(3,5,7) and p.deleted_at is null)
                    and pi2.deleted_at is null
                    group by cod_produto, estabelecimento;
            """
        sqlPedidoPortal = sqlPedidoPortal.format(data_verificacao)
        cursor_portal.execute(sqlPedidoPortal)
        reserva_portal = cursor_portal.fetchall()
        cursor_portal.close()

        sqlPedidoNasajon = """
            select codigo_produto, codigo_estabelecimento, sum(quantidade) as quantidade
                from integracoes.vw_pedido_nota_aberto
                group by codigo_produto, codigo_estabelecimento;
            """
        cursor_nasajon.execute(sqlPedidoNasajon)
        reserva_nasajon = cursor_nasajon.fetchall()
        cursor_nasajon.close()

        saida = {}

        for produto_nasajon in reserva_nasajon:
            saida[produto_nasajon['codigo_produto']] = {
                'estabelecimento': produto_nasajon['codigo_estabelecimento'],
                'codigo_produto': produto_nasajon['codigo_produto'],
                'quantidade': float(produto_nasajon['quantidade'])
            }

        for produto_portal in reserva_portal:
            if produto_portal['cod_produto'] in saida:
                itens = saida[produto_portal['cod_produto']]

                saida[produto_portal['cod_produto']].update({
                    'estabelecimento': '0'+str(produto_portal['estabelecimento']),
                    'codigo_produto': produto_portal['cod_produto'],
                    'quantidade':  produto_portal['quantidade'] + itens['quantidade']
                })
            else:
                saida[produto_portal['cod_produto']] = {
                    'estabelecimento': '0'+str(produto_portal['estabelecimento']),
                    'codigo_produto': produto_portal['cod_produto'],
                    'quantidade': produto_portal['quantidade']
                }
        return saida
    
    def insertReserva(self, dados):
        conn = self.conn_portal
        cursor_portal = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        sql_insert = ''
        for codigo_produto in dados:
            sql_insert_peca = ''
            itens = dados[codigo_produto]

            estabelecimento = str(itens['estabelecimento'])
            produto = itens['codigo_produto']
            quantidade = itens['quantidade']

            sql_insert_peca = """
                INSERT INTO
                    public.produtos_estoques
                    (
                        estabelecimento, codigo_produto, estoque, custo, empenho, data_atulizacao, saldo_fiscal, saldo_em_terceiros, saldo_de_terceiros, saldo_armazem, compras, compras_aberto, reserva, reserva_pronta_entrega
                    ) VALUES(
                        '{}', '{}', 0, 0, 0, NOW(), 0, 0, 0, 0, 0, 0, {}, 0
                    )
                    ON CONFLICT (estabelecimento, codigo_produto) DO UPDATE
                        SET
                            reserva = EXCLUDED.reserva,
                            data_atulizacao = NOW();
                    """
            sql_insert_peca = sql_insert_peca.format(estabelecimento, produto, quantidade)
            cursor_portal.execute(sql_insert_peca)

        cursor_portal.close()

    def buscaReservaProntaEntrega(self):
        conn_portal = self.conn_portal
        cursor_portal = conn_portal.cursor(cursor_factory=psycopg2.extras.DictCursor)

        conn_nasajon = self.conn_nasajon
        cursor_nasajon = conn_nasajon.cursor(cursor_factory=psycopg2.extras.DictCursor)

        data_verificacao = datetime.today()
        data_verificacao = (data_verificacao + timedelta(15))

        sqlPedidoPortal = """
            select cod_produto, estabelecimento, sum(quantidade) as quantidade
                from pedido_item as pi2
                    left join pedido as p  on p.id = pi2.pedido
                    where exists
                    ( select * from pedido as p  where p.id = pi2.pedido and status_pedido not in(3,5,7) and p.deleted_at is null and p.data_previsao_entrega < '{}')
                    and pi2.deleted_at is null
                    group by cod_produto, estabelecimento;
            """
        sqlPedidoPortal = sqlPedidoPortal.format(data_verificacao)
        cursor_portal.execute(sqlPedidoPortal)
        reserva_portal = cursor_portal.fetchall()
        cursor_portal.close()

        sqlPedidoNasajon = """
            select codigo_produto, codigo_estabelecimento, sum(quantidade) as quantidade
                from integracoes.vw_pedido_nota_aberto
                group by codigo_produto, codigo_estabelecimento;
            """
        cursor_nasajon.execute(sqlPedidoNasajon)
        reserva_nasajon = cursor_nasajon.fetchall()
        cursor_nasajon.close()

        saida = {}

        for produto_nasajon in reserva_nasajon:
            saida[produto_nasajon['codigo_produto']] = {
                'estabelecimento': produto_nasajon['codigo_estabelecimento'],
                'codigo_produto': produto_nasajon['codigo_produto'],
                'quantidade': float(produto_nasajon['quantidade'])
            }

        for produto_portal in reserva_portal:
            if produto_portal['cod_produto'] in saida:
                itens = saida[produto_portal['cod_produto']]

                saida[produto_portal['cod_produto']].update({
                    'estabelecimento': '0'+str(produto_portal['estabelecimento']),
                    'codigo_produto': produto_portal['cod_produto'],
                    'quantidade':  produto_portal['quantidade'] + itens['quantidade']
                })
            else:
                saida[produto_portal['cod_produto']] = {
                    'estabelecimento': '0'+str(produto_portal['estabelecimento']),
                    'codigo_produto': produto_portal['cod_produto'],
                    'quantidade': produto_portal['quantidade']
                }
        return saida
    
    def insertReservaProntaEntrega(self, dados):
        conn = self.conn_portal
        cursor_portal = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        sql_insert = ''
        for codigo_produto in dados:
            sql_insert_peca = ''
            itens = dados[codigo_produto]

            estabelecimento = str(itens['estabelecimento'])
            produto = itens['codigo_produto']
            quantidade = itens['quantidade']

            sql_insert_peca = """
                INSERT INTO
                    public.produtos_estoques
                    (
                        estabelecimento, codigo_produto, estoque, custo, empenho, data_atulizacao, saldo_fiscal, saldo_em_terceiros, saldo_de_terceiros, saldo_armazem, compras, compras_aberto, reserva_pronta_entrega, reserva 
                    ) VALUES(
                        '{}', '{}', 0, 0, 0, NOW(), 0, 0, 0, 0, 0, 0, {}, 0
                    )
                    ON CONFLICT (estabelecimento, codigo_produto) DO UPDATE
                        SET
                            reserva_pronta_entrega = EXCLUDED.reserva_pronta_entrega,
                            data_atulizacao = NOW();
                    """
            sql_insert_peca = sql_insert_peca.format(estabelecimento, produto, quantidade)
            cursor_portal.execute(sql_insert_peca)

        cursor_portal.close()


    def buscaSaldoMovimentoNaoEfetivado(self, estabelecimento):

        conn_nasajon = self.conn_nasajon
        cursor_nasajon = conn_nasajon.cursor(cursor_factory=psycopg2.extras.DictCursor)

        data_final = datetime.today()
        data_inicial_03 = str(data_final - timedelta(3))
        data_inicial_04 = str(data_final - timedelta(3))
        data_final = str(data_final)

        if estabelecimento == "03":
            sql_pecas = """
                select
                produto_codigo, sum(quantidade) as quantidade
                from integracoes.exportar_produtos_movimentacoes
                ('{}', '{}', '{}')
                where efetivado = false
                group by produto_codigo;
            """.format(estabelecimento, data_inicial_03, data_final)
        else:
            sql_pecas = """
                select
                produto_codigo, sum(quantidade) as quantidade
                from integracoes.exportar_produtos_movimentacoes
                ('{}', '{}', '{}')
                where efetivado = false
                group by produto_codigo;
            """.format(estabelecimento, data_inicial_04, data_final)

        cursor_nasajon.execute(sql_pecas)

        dados = cursor_nasajon.fetchall()
        cursor_nasajon.close()

        retorno = {}
        for dados_produto in dados:
            retorno[str(dados_produto['produto_codigo'])] = dados_produto['quantidade']

        return retorno

    def ini(self):
        print ("----------------------------------------------------------------")
        print ("--------------------------Inicio - Zera campos -----------------")
        print ("----------------------------------------------------------------")
        print (time.strftime("%H:%M:%S"))
        self.zerarEstoque()

        print (time.strftime("%H:%M:%S"))
        print ("----------------------------------------------------------------")
        print ("----------------------------FIM - Zera campos ------------------")
        print ("----------------------------------------------------------------\n")

        print ("----------------------------------------------------------------")
        print ("-------------------Inicio - Busca saldo Nasajon-----------------")
        print ("----------------------------------------------------------------")
        print (time.strftime("%H:%M:%S"))
        for estabelecimento in range(3, 9):
            estabelecimento = '%02d' % estabelecimento
            print ("----------------------------------------------------------------")
            print ("---------------Inicio - saldo estabelecimento {}---------------".format(estabelecimento))
            print ("----------------------------------------------------------------")
            print (time.strftime("%H:%M:%S"))
            busca_estoque = self.buscaEstoque(estabelecimento)
            if len(busca_estoque) > 0:
                print (time.strftime("%H:%M:%S"))
                busca_saldo_movimento_nao_efetivado = {}
                if estabelecimento == "03" or estabelecimento == "04":
                    busca_saldo_movimento_nao_efetivado = self.buscaSaldoMovimentoNaoEfetivado(estabelecimento)
                self.insertMovimentos(busca_estoque, busca_saldo_movimento_nao_efetivado)
                print (time.strftime("%H:%M:%S"))

            print (time.strftime("%H:%M:%S"))
            print ("----------------------------------------------------------------")
            print ("------------------FIM - saldo estabelecimento {}---------------".format(estabelecimento))
            print ("----------------------------------------------------------------")

        print (time.strftime("%H:%M:%S"))
        print ("----------------------------------------------------------------")
        print ("--------------------FIM - Busca saldo Nasajon ------------------")
        print ("----------------------------------------------------------------\n")


        print ("----------------------------------------------------------------")
        print ("------------------Inicio - Busca compas Nasajon-----------------")
        print ("----------------------------------------------------------------")
        print (time.strftime("%H:%M:%S"))

        compras = self.buscaCompras()
        self.insertCompras(compras)

        print (time.strftime("%H:%M:%S"))
        print ("----------------------------------------------------------------")
        print ("-------------------FIM - Busca compas Nasajon ------------------")
        print ("----------------------------------------------------------------\n")

        print ("----------------------------------------------------------------")
        print ("--------------Inicio - Busca compas aberto Nasajon--------------")
        print ("----------------------------------------------------------------")
        print (time.strftime("%H:%M:%S"))

        compras = self.buscaComprasAberto()
        self.insertComprasAberto(compras)

        print (time.strftime("%H:%M:%S"))
        print ("----------------------------------------------------------------")
        print ("----------------FIM - Busca compas aberto Nasajon --------------")
        print ("----------------------------------------------------------------\n")

        print ("----------------------------------------------------------------")
        print ("--------------Inicio - Busca Reserva--------------")
        print ("----------------------------------------------------------------")
        print (time.strftime("%H:%M:%S"))

        reserva = self.buscaReserva()
        self.insertReserva(reserva)

        print (time.strftime("%H:%M:%S"))
        print ("----------------------------------------------------------------")
        print ("----------------FIM - Busca Reserva--------------")
        print ("----------------------------------------------------------------\n")


        print ("----------------------------------------------------------------")
        print ("--------------Inicio - Busca Reserva Pronta Entrega--------------")
        print ("----------------------------------------------------------------")
        print (time.strftime("%H:%M:%S"))

        reserva_pronta_entrega = self.buscaReservaProntaEntrega()
        self.insertReservaProntaEntrega(reserva_pronta_entrega)

        print (time.strftime("%H:%M:%S"))
        print ("----------------------------------------------------------------")
        print ("----------------FIM - Busca Reserva Pronta Entrega--------------")
        print ("----------------------------------------------------------------\n")

        print (time.strftime("%H:%M:%S"))
        print ("----------------------------------------------------------------")
        print ("----------------Inicio - Verificar Campanha--------------")
        print ("----------------------------------------------------------------\n")

        #output = os.system("php ../artisan campanha:verifica_produtos_importados")
        
        print (time.strftime("%H:%M:%S"))
        print ("----------------------------------------------------------------")
        print ("----------------Fim - Verificar Campanha--------------")
        print ("----------------------------------------------------------------\n")

        self.limparProdutosNaoImportados()

        self.conn_portal.commit()

signal(SIGINT, handler)

ImportacaoEstoqueObj = ImportacaoEstoque()