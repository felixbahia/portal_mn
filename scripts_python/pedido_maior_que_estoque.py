# coding: utf-8
import time
from datetime import date, timedelta, datetime
import psycopg2
import psycopg2.extras
import sys
import csv
import os
import smtplib
import email.message
import mimetypes
from email.mime.multipart import MIMEMultipart
from email.mime.base import MIMEBase
from email.mime.text import MIMEText
from email.utils import COMMASPACE, formatdate
from email import encoders
from dotenv import load_dotenv
from signal import signal, SIGINT

class Busca:
    def __init__(self):
        self.conn_portal = ''
        self.conn_nasajon = ''

        self.openConnecion()

        self.ini()

        self.closeConnection()
    def closeConnection(self):
        self.conn_portal.close()
        self.conn_nasajon.close()

        self.conn_portal = ''
        self.conn_integracao = ''

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
            
    def buscaCompra(self):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = """
        select
            estabelecimento,
            produto_especificacaos.codigo_produto,
            produto_especificacaos.descricao,
            produto_especificacaos.grupo,
            sum(compras) as saldo
        from
            produtos_estoques
            inner join produto_especificacaos on (produto_especificacaos.codigo_produto = produtos_estoques.codigo_produto)
        where 
            compras > 0
        group by 
            estabelecimento,
            produto_especificacaos.codigo_produto,
            produto_especificacaos.descricao,
            produto_especificacaos.grupo
        """
        cursor.execute(sql_busca)
        dados = cursor.fetchall()
        cursor.close()
        if dados is not None:
            return dados
        else:
            return []
    
    def buscaPedidoFuturoPortal(self):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = """
        select
            pedido.estabelecimento,
            pedido_item.cod_produto as codigo_produto,
            sum(pedido_item.quantidade) as saldo
        from
            pedido
            left join pedido_item on (pedido.id = pedido_item.pedido and pedido_item.deleted_at is null)
        where 
            pedido.deleted_at is null and 
            pedido.status_pedido in (8) and 
            pedido_item.id is not null
        group by 
            pedido.estabelecimento,
            pedido_item.cod_produto
        """
        cursor.execute(sql_busca)
        dados = cursor.fetchall()
        cursor.close()
        if dados is not None:
            return dados
        else:
            return []

    def tratarArray(self, dados):
        retorno = {}

        for dado in dados:
            if str(int(dado['estabelecimento'])) not in retorno:
                retorno[str(int(dado['estabelecimento']))] = {}
            retorno[str(int(dado['estabelecimento']))][dado['codigo_produto']] = float(dado['saldo'])

        return retorno

    def produtos(self, dados):
        retorno = {}
        for dado in dados:
            if dado['codigo_produto'] not in retorno:
                retorno[dado['codigo_produto']] = {
                    'grupo': dado['grupo'],
                    'descricao': dado['descricao'],
                }

        return retorno

    def enviaEnail(self, arquivo):

        msg = MIMEMultipart()
        msg['Subject'] = 'Pedido maior que estoque'
        email_content = ''

        msg['From'] = 'portal@tecidosmn.com.br'
        msg['To'] = 'ti_contratos@tecidosmn.com.br'

        msg.attach(MIMEText(email_content, "plain"))

        ctype, encoding = mimetypes.guess_type(arquivo)
        if ctype is None or encoding is not None:
            ctype = "application/octet-stream"

        maintype, subtype = ctype.split("/", 1)

        if maintype == 'text':
            with open(arquivo) as f:
                mime = MIMEText(f.read(), _subtype=subtype)
        elif maintype == 'image':
            with open(arquivo, 'rb') as f:
                mime = MIMEImage(f.read(), _subtype=subtype)
        elif maintype == 'audio':
            with open(arquivo, 'rb') as f:
                mime = MIMEAudio(f.read(), _subtype=subtype)
        else:
            with open(arquivo, 'rb') as f:
                mime = MIMEBase(maintype, subtype)
                mime.set_payload(f.read())

            encoders.encode_base64(mime)

        mime.add_header(
            "Content-Disposition",'attachment; filename=produtos_empenho_maior_que_estoque_futuro.csv'
        )
        msg.attach(mime)

        text = msg.as_string()

        smtp_email = str(os.getenv('MAIL_HOST'))+":"+str(os.getenv('MAIL_PORT'))
        server = smtplib.SMTP(smtp_email)

        login = str(os.getenv('MAIL_USERNAME'))
        password = str(os.getenv('MAIL_PASSWORD'))
        server.login(login, password)

        server.sendmail(msg['From'], [msg['To']], text)

        server.quit()
        return False

    def ini(self):
        load_dotenv('../.env')

        estoque_portal = self.buscaCompra()
        produtoPortal = self.produtos(estoque_portal)
        estoque_portal = self.tratarArray(estoque_portal)
        
        epenho_pedido = self.buscaPedidoFuturoPortal()
        epenho_pedido = self.tratarArray(epenho_pedido)
        
        csv_out = [['Estabelecimento', 'Codigo', 'Descrição', 'Grupo', 'Empenho', 'Compras']]

        for estabelecimento in estoque_portal:
            if estabelecimento in epenho_pedido:
                for produto in estoque_portal[estabelecimento]:
                    if produto in epenho_pedido[estabelecimento]:
                        if epenho_pedido[estabelecimento][produto] > estoque_portal[estabelecimento][produto]:
                            csv_out.append([str(estabelecimento),str(produto), produtoPortal[produto]['descricao'], produtoPortal[produto]['grupo'], str(epenho_pedido[estabelecimento][produto]), str(estoque_portal[estabelecimento][produto])])

        for key, linha in enumerate(csv_out):
            for chave, valor in enumerate(linha):
                if valor == 'None':
                    csv_out[key][chave] = '0'
                else:
                    csv_out[key][chave] = valor.replace('.', ',')

        nameFile = 'produtos_empenho_maior_que_estoque_futuro.csv'
        with open(nameFile, 'wb') as newFile:
            wr = csv.writer(newFile, delimiter=';', quoting=csv.QUOTE_ALL)
            for output in csv_out:
                wr.writerow(output)

        self.enviaEnail(nameFile)



busca = {}

def handler(signal_received, frame):
    busca.closeConnection()
    print "\n"
    print '\n\nexit'
    exit(0)
signal(SIGINT, handler)

busca = Busca()
