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
            connect_str = "dbname='tecidos_mn' user='userportal' password='gNyVrq&59jM0X' host='localhost'"
            self.conn_nasajon = psycopg2.connect(connect_str)
        except Exception as e:
            print e
            exit(0)
            
    def busca_auditoria(self):
        conn = self.conn_nasajon
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_busca = """
        select * from util.auditoria_produto_peca_a_peca()
        """
        cursor.execute(sql_busca)
        dados = cursor.fetchall()
        cursor.close()
        if dados is not None:
            return dados
        else:
            return []
    
    def enviaEnail(self, arquivo):

        msg = MIMEMultipart()
        msg['Subject'] = 'Auditoria peca a peca'
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
            "Content-Disposition",'attachment; filename=auditoria_produto_peca_a_peca.csv'
        )
        msg.attach(mime)

        text = msg.as_string()

        smtp_email = str(os.getenv('MAIL_HOST'))+":"+str(os.getenv('MAIL_PORT'))
        server = smtplib.SMTP(smtp_email)

        login = str(os.getenv('MAIL_USERNAME'))
        password = str(os.getenv('MAIL_PASSWORD'))
        server.login(login, password)
        try:
            server.sendmail(msg['From'], [msg['To']], text)

            server.quit()
        except SMTPAuthenticationError:
            print 'teste'
            exit(0)
        return False

    def ini(self):
        load_dotenv('../.env')

        autitoria = self.busca_auditoria()
        
        csv_out = [['Estabelecimento', 'Codigo', 'saldo pecas', 'saldo estabelecimento', 'id item']]

        for produto in autitoria:
            csv_out.append([str(produto['estab_codigo']), str(produto['prod_codigo']), str(produto['saldo_pecas']), str(produto['saldo_estab']), str(produto['id_item'])])
        for key, linha in enumerate(csv_out):
            for chave, valor in enumerate(linha):
                if valor == 'None':
                    csv_out[key][chave] = '0'
                else:
                    csv_out[key][chave] = valor.replace('.', ',')

        nameFile = 'auditoria_produto_peca_a_peca.csv'
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
