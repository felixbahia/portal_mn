# coding: utf-8
import time
from datetime import date, timedelta
from ImportacaoMovimento import ImportacaoMovimento
from ImportacaoDescricao import ImportacaoDescricao
from ImportacaoVendedor import ImportacaoVendedor
from ImportacaoPrepago import ImportacaoPrepago
# from ImportacaoEquipe import ImportacaoEquipe
import sys
from signal import signal, SIGINT


print "Importando movimentos"
ImportacaoMovimentoObj = ImportacaoMovimento()
print "Importando descrição"
ImportacaoDescricaoObj = ImportacaoDescricao()
print "Importando vendedores"
ImportacaoVendedorObj = ImportacaoVendedor()
print "Importando pre pago"
ImportacaoPrepagoObj = ImportacaoPrepago()
print "Calculando Custo"
# ImportacaoEquipeObj = ImportacaoEquipe()
# print "Importação Equipe"

exit(0)