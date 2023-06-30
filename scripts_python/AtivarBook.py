# coding: utf-8
import time
from datetime import date, timedelta
import psycopg2
import psycopg2.extras
import sys

class AtivarBooks:
    def __init__(self):
        self.conn_portal = ''
        self.conn_nasajon = ''

        self.openConnecion()

        self.ini()

        self.exit_program()


    def closeConnection(self):
        self.conn_portal.close()
        self.conn_nasajon.close()

        self.conn_nasajon = ''
        self.conn_portal = ''

    def openConnecion(self):
        try:
            connect_str = "dbname='postgres' user='userportal' password='gNyVrq&59jM0X' host='localhost'"
            # connect_str = "dbname='postgres' user='userportal' password='@mntecidos2018' host='bdprod.mntecidos.local'"
            self.conn_portal = psycopg2.connect(connect_str)
        except Exception as e:
            print e
            exit(0)

        try:
            connect_str = "dbname='tecidos_mn' user='userportal' password='gNyVrq&59jM0X' host='bdprod.mntecidos.local'"
            self.conn_nasajon = psycopg2.connect(connect_str)
        except Exception as e:
            print e
            exit(0)

    def exit_program(self):
        self.closeConnection()
        print '\n\nexit'
        
    def buscaBooks(self):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = """
            select
                books_virtuals.id,
                array_to_string(array_agg(itens_books_virtuals.cod_produto order by itens_books_virtuals.cod_produto), ', ') as produtos
            from
                books_virtuals
                left join itens_books_virtuals on (books_virtuals.num_book = itens_books_virtuals.num_book and itens_books_virtuals.deleted_at is null)
            where
                books_virtuals.deleted_at is null
            group by
                books_virtuals.id
            """
        cursor.execute(sql_busca)
        dado = cursor.fetchall()
        cursor.close()
        if dado is not None:
            return dado
        else:
            return ''
    def buscaEstoques(self, produtos):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

        sql_busca = """
            select
                produtos_estoques.codigo_produto,
                produtos_estoques.estoque,
                produtos_estoques.compras,
                (select
                    sum(quantidade)
                from
                    pedido
                    inner join pedido_item on (pedido_item.pedido = pedido.id and pedido_item.deleted_at is null and pedido_item.cod_produto = produtos_estoques.codigo_produto )
                where
                    pedido.deleted_at is null
                    and status_pedido not in (3, 5, 7, 8)) as vendas,
                (select
                    sum(quantidade)
                from
                    pedido
                    inner join pedido_item on (pedido_item.pedido = pedido.id and pedido_item.deleted_at is null and pedido_item.cod_produto = produtos_estoques.codigo_produto )
                where
                    pedido.deleted_at is null
                    and status_pedido not in (8)) as vendas_compras
            from
                produtos_estoques
            where
                (estoque > 0 or compras > 0)
                and produtos_estoques.codigo_produto in(
        """
        for produto in produtos:
            sql_busca += "'{0}',". format(produto)
        sql_busca = sql_busca[:-1]
        sql_busca = sql_busca+')'
        cursor.execute(sql_busca)
        dados = cursor.fetchall()
        cursor.close()
        empenhos =  self.buscaEmpenhoProduto(produtos)
        if dados is not None:
            ativo = False
            for dado in dados:
                empenho = 0
                for val in empenhos:
                    if str(val['codigo_produto']) == str(dado['codigo_produto']):
                        empenho = val['quantidade']
                if dado['vendas'] is None:
                    dado['vendas'] = 0
                if dado['vendas_compras'] is None:
                    dado['vendas_compras'] = 0
                estoque = dado['estoque'] - float(dado['vendas']) - float(empenho)
                compra = dado['compras'] - float(dado['vendas_compras'])
                
                if (estoque > 0 or compra > 0) and ativo == False:
                    ativo = True
            return ativo
        else:
            return ''

    def buscaEmpenhoProduto(self, produtos):
        conn = self.conn_nasajon
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = "select codigo_produto, sum(quantidade) as quantidade from integracoes.vw_pedido_item_qtd_aberto where codigo_produto in("
        for produto in produtos:
            sql_busca += "'{0}',". format(produto)
        sql_busca = sql_busca[:-1]
        sql_busca = sql_busca+') group by codigo_produto;'
        cursor.execute(sql_busca)
        dados = cursor.fetchall()
        cursor.close()

        return dados


    def ativaBook(self, book):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_update = "update books_virtuals set estoque = true where id = {0}". format(book)
        cursor.execute(sql_update)
        cursor.close()
        self.conn_portal.commit()        

    def desativaBook(self, book):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_update = "update books_virtuals set estoque = false where id = {0}". format(book)
        cursor.execute(sql_update)
        cursor.close()
        self.conn_portal.commit()

    def ini(self):
        books = self.buscaBooks()
        for book in books:
            produtos = book['produtos'].split(', ')
            estoque = self.buscaEstoques(produtos)
            if estoque == True:
                self.ativaBook(book['id'])
            else:
                self.desativaBook(book['id'])


AtivarBooksOjb = AtivarBooks()