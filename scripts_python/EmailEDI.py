#!/usr/bin/env python3

import imaplib
import email
from email.header import decode_header
import os
import re
import smtplib
import datetime
import time
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
import psycopg2
import psycopg2.extras
from dotenv import load_dotenv
import random
import math  

pattern_uid = r"\d+ \(UID (?P<uid>\d+)\)"

host = 'sharedrelay-cluster.mandic.net.br'
port = 587
user = 'cd167394@shared.mandic.net.br'
senha = '(QE[,2z3Zb{P'
server = smtplib.SMTP(host, port)
server.ehlo()
server.starttls()
server.login(user, senha)

def parse_uid(data):
    match = re.search(pattern_uid, str(data))
    return match.group('uid')

def insert_movimentao(arquivo):
    load_dotenv('../.env')
    try:
        connect_str = "dbname='{0}' user='{1}' password='{2}' host='{3}'". format(os.getenv('DB_DATABASE'), os.getenv('DB_USERNAME'), os.getenv('DB_PASSWORD'), os.getenv('DB_HOST'))
        conn_portal = psycopg2.connect(connect_str)
    except Exception as e:
        print(e)
        exit(0)
    
    cursor_portal = conn_portal.cursor(cursor_factory=psycopg2.extras.DictCursor)
    now = datetime.datetime.now()
    sql_inset_movimentos = "INSERT INTO log_importacao_edis (processo, arquivo, created_at, updated_at) VALUES('%s', '%s', '%s', '%s');" % ('Importacao Python', arquivo, now, now)
    try:
        cursor_portal.execute(sql_inset_movimentos)
        conn_portal.commit()
    except Exception as e:
        print(e)
        return []
    cursor_portal.close()
    return True

username = "editransporte@tecidosmn.com.br"
password = "Transportemn@2020"

imap = imaplib.IMAP4_SSL("imap.tecidosmn.com.br")
imap.login(username, password)
imap.select("INBOX")

type, data = imap.search(None, 'ALL')
mail_ids = data[0]
id_list = mail_ids.split()
array_comandos = []

for num in data[0].split():
    typ, data  = imap.uid('fetch', num, "(RFC822)")    
    resp_uid = imap.uid('fetch',num, "(UID)")
    if(data[0] is None):
        typ, data = imap.fetch(num, "(RFC822)")
        resp_uid = imap.fetch(num, "(UID)")

    raw_email = data[0][1]
# converts byte literal to string removing b''
    raw_email_string = raw_email.decode('cp1252')
    email_message = email.message_from_string(raw_email_string)

    msg_uid = parse_uid(resp_uid)
# downloading attachments
    for part in email_message.walk():
        
        if part.get_content_maintype() == 'multipart':
            continue
        if part.get('Content-Disposition') is None:
            continue

        fileName = part.get_filename()

        if bool(fileName):
            path_file = '../storage/app/ocorrencias'
            if not os.path.isdir(path_file):
                os.mkdir(path_file)
        
            random.seed() 
            aleatorio = str(math.trunc(random.random() * 1000000))
            fileName = aleatorio + '__' + fileName
            fileName = ''.join(fileName.split())

            if(fileName[-3:] == 'dat'):
                fileName = fileName[:-4]
            
            filePath = os.path.join(path_file, fileName)
            if not os.path.isfile(filePath) :
                fp = open(filePath, 'wb')
                fp.write(part.get_payload(decode=True))
                fp.close()
                subject = str(email_message).split("Subject: ", 1)[1].split("\nTo:", 1)[0]

            caminho = 'php ../artisan ocorrencias:entregas '+filePath
            array_comandos.append(caminho)
            
            result = imap.uid('COPY', msg_uid, 'INBOX.emails_importados')
            if result[0] == 'OK':
                imap.uid('STORE', msg_uid , '+FLAGS', '(\Deleted)')

imap.expunge()
imap.close()
imap.logout()
server.quit()

for x in array_comandos:
    try:
        insert_movimentao(x)
        output = os.system(x)
    except InterruptedError as e:
        message = 'Erro: \n'+e+'\n\n'+part
        email_msg = MIMEMultipart()
        email_msg['From'] = 'portal@tecidosmn.com.br'
        email_msg['To'] = 'ti@tecidosmn.com.br,michel.diniz@tecidosmn.com.br'
        email_msg['Subject'] = 'Erro ao importar arquivo EDI'
        email_msg.attach(MIMEText(message, 'plain'))
        server.sendmail(email_msg['From'], email_msg['To'].split(","), email_msg.as_string())
        print (e)
        exit(0)