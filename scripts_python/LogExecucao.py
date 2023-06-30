# coding: utf-8
import time
from datetime import date, timedelta
import psycopg2
import psycopg2.extras
import sys
import os
from dotenv import load_dotenv

class LogExecucao:
    def __init__(self, token, inicio_fim):
        self.conn_portal = ''
        load_dotenv('../../.env')

        self.openConnecion()
        if inicio_fim == True:
            self.atualizaToken(token)
        else:
            self.inicioAtualizaToken(token)            

        self.exit_program()

    def closeConnection(self):
        self.conn_portal.close()

        self.conn_portal = ''

    def openConnecion(self):
        try:
            connect_str = "dbname='{0}' user='{1}' password='{2}' host='{3}'". format(os.getenv('DB_DATABASE'), os.getenv('DB_USERNAME'), os.getenv('DB_PASSWORD'), os.getenv('DB_HOST'))
            self.conn_portal = psycopg2.connect(connect_str)
        except Exception as e:
            print e
            exit(0)

    def exit_program(self):
        self.closeConnection()

    def atualizaToken(self, token):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_update = "update atualizacao_cron set atualizacao = NOW() where \"token\" = '{0}'". format(token)
        cursor.execute(sql_update)
        cursor.close()
        self.conn_portal.commit()

    def inicioAtualizaToken(self, token):
        conn = self.conn_portal
        cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
        sql_update = "update atualizacao_cron set inicio_atualizacao = NOW() where \"token\" = '{0}'". format(token)
        cursor.execute(sql_update)
        cursor.close()
        self.conn_portal.commit()
