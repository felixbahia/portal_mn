<div style="margin: 100px;">
    <p align="center" style="margin-left: 0.52cm; text-indent: -0.52cm; margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="center" style="margin-left: 0.52cm; text-indent: -0.52cm; margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>INSTRUMENTO
    PARTICULAR DE CONFISSÃO DE DÍVIDA E OUTRAS AVENÇAS</b></font></font></font></p>
    <p align="center" style="margin-left: 0.52cm; text-indent: -0.52cm; margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; border: 1px solid #000000; padding: 0.04cm 0.14cm; line-height: 115%">
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>CREDORA</b></u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>:</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>TÊXTIL
    MN COMÉRCIO DE TECIDOS E CONFECÇÕES LTDA.,</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    </font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">estabelecida na Rua Dr. Carlos Botelho, 177 – Brás – São Paulo – SP – CEP: 03017-010,
    inscrita no CNPJ sob o nº 06.311.274/0004-20,</font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    doravante denominada simplesmente </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CREDORA.</b></font></font></font></p>
    <p align="justify" style="margin-bottom: 0.42cm; border: none; padding: 0cm; line-height: 115%">
        <br/>
        <br/>
    </p>

    <p align="justify" style="margin-bottom: 0cm; border: 1px solid #000000; padding: 0.04cm 0.14cm; line-height: 115%">
        <font color="#000000">
            <font face="Calibri, serif">
                <font size="3" style="font-size: 12pt">
                    <u><b>DEVEDORA</b></u>
                </font>
            </font>
        </font>
        <font color="#000000">
            <font face="Calibri, serif">
                <font size="3" style="font-size: 12pt">
                    <b>: {{$dados[0]['cliente_nome']}}</b>
                </font>
            </font>
        </font>
        <br/>
        <a name="_heading=h.1fob9te"></a>
        <font face="Calibri, serif">
            <font size="3" style="font-size: 12pt">
                <b>CNPJ</b>
            </font>
        </font>
        <font face="Calibri, serif">
            <font size="3" style="font-size: 12pt">
                : {{$dados[0]['cliente_cpf_cnpj']}}
            </font>
        </font>
        <br/>
        <font face="Calibri, serif">
            <font size="3" style="font-size: 12pt">
                <b>Endereço</b>
            </font>
        </font>
        <font face="Calibri, serif">
            <font size="3" style="font-size: 12pt">
                : {{$dados[0]['cliente_endereco']}}
            </font>
        </font>
    </p>

    <p align="justify" style="margin-bottom: 0.42cm; border: none; padding: 0cm; line-height: 115%">
        <br/>
        <br/>
    </p>

    @foreach($dados as $value)
        @if($value['tipo_signatario'] == 'fiador')
            <p align="justify" style="margin-bottom: 0cm; border: 1px solid #000000; padding: 0.04cm 0.14cm; line-height: 115%">
                <font face="Calibri, serif">
                    <font size="3" style="font-size: 12pt">
                        <u><b>FIADOR(A)</b></u>
                    </font>
                </font>
                <font face="Calibri, serif">
                    <font size="3" style="font-size: 12pt">
                        <b>: {{$value['avalista_nome']}}</b>
                    </font>
                </font>
                <br/>
                <font face="Calibri, serif">
                    <font size="3" style="font-size: 12pt">
                        <b>Estado civil: </b>
                    </font>
                </font>
                <font face="Calibri, serif">
                    <font size="3" style="font-size: 12pt">
                        : {{$value['avalista_estado_civil']}}
                    </font>
                </font>
                <br/>
                <font face="Calibri, serif">
                    <font size="3" style="font-size: 12pt">
                        <b>CPF</b>
                    </font>
                </font>
                <font face="Calibri, serif">
                    <font size="3" style="font-size: 12pt">
                        : {{$value['avalista_cpf']}}
                    </font>
                </font>
                <br/>
                <font face="Calibri, serif">
                    <font size="3" style="font-size: 12pt">
                        <b>Endereço</b>
                    </font>
                </font>
                <font face="Calibri, serif">
                    <font size="3" style="font-size: 12pt">
                        : {{$value['avalista_endereco']}}
                    </font>
                </font>
            </p>

            <p align="justify" style="margin-bottom: 0.42cm; border: none; padding: 0cm; line-height: 115%">
                <br/>
            </p>

            <p align="justify" style="margin-bottom: 0cm; border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.04cm; padding-left: 0.14cm; padding-right: 0.14cm; line-height: 115%">
                <font face="Calibri, serif">
                    <font size="3" style="font-size: 12pt">
                        <u><b>INTERVENIENTE ANUENTE</b></u>
                    </font>
                </font>
                <font face="Calibri, serif">
                    <font size="3" style="font-size: 12pt">
                        : <b>{{$value['nome_venia_conjugal']}}</b>
                    </font>
                </font>
                <br/>
                <font face="Calibri, serif">
                    <font size="3" style="font-size: 12pt">
                        <b>CPF</b>: {{$value['cpf_venia_conjugal']}}
                    </font>
                </font>
            </p>
            <p align="justify" style="margin-top: 0.42cm; margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
                <br/>
            </p>
        @endif
    @endforeach
    <p align="justify" style="margin-top: 0.42cm; margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
        <br/>
        <br/>
    </p>
    <center>
        <table width="614" cellpadding="7" cellspacing="0">
            <col width="174">
            <col width="81">
            <col width="109">
            <col width="193">
            <tr>
                <td colspan="4" width="598" height="6" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.42cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                <b>QUADRO RESUMO</b>
                            </font>
                        </font>
                    </p>
                </td>
            </tr>
            <tr>
                <td colspan="2" width="268" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.42cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                <b>1 - VALOR CONFESSADO:</b>
                            </font>
                        </font>
                    </p>
                </td>
                <td colspan="2" width="316" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.42cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                {{$dados[0]['valor_total_juros']}}
                            </font>
                        </font>
                    </p>
                </td>
            </tr>
            <tr>
                <td colspan="4" width="598" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="justify">
                        <br/>
                    </p>
                </td>
            </tr>
            <tr>
                <td width="174" valign="bottom" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                <b>2 - PARCELAS:</b>
                            </font>
                        </font>
                    </p>
                </td>
                <td colspan="2" width="204" valign="top" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                <b>3 - VALOR </b>
                            </font>
                        </font>
                    </p>
                </td>
                <td width="193" valign="top" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                <b>4 - VENCIMENTO </b>
                            </font>
                        </font>
                    </p>
                </td>
            </tr>
            @foreach($dados[0]['datas'] as $parcela)
            <tr>
                <td width="174" valign="bottom" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                <b>{{$parcela['numero']."/".$dados[0]['quantidade_parcela']}}</b>
                            </font>
                        </font>
                    </p>
                </td>
                <td colspan="2" width="204" valign="top" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                {{$parcela['valor']}}
                            </font>
                        </font>
                    </p>
                </td>
                <td width="193" valign="top" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                {{$parcela['data']}}
                            </font>
                        </font>
                    </p>
                </td>
            </tr>
            @endforeach
        </table>
    </center>
    <p lang="pt-BR" align="justify" style="margin-top: 0.64cm; margin-bottom: 0.21cm; line-height: 150%; page-break-inside: avoid; page-break-after: avoid">
    <br/>
    <br/>
    
    </p>
    <p align="justify" style="margin-top: 0.42cm; margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CONSIDERANDO
    </b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">que
    as partes celebraram contrato empresarial de fornecimento e outras
    avenças, pelo qual a </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CREDORA
    </b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">realizou
    diversas vendas de mercadorias à </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA;</b></font></font></font></p>
    <p align="justify" style="margin-top: 0.42cm; margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CONSIDERANDO</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    que a </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CREDORA</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    realizou as entregas dos produtos nas datas aprazadas, sendo que a
    </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    declara o recebimento de todas as mercadorias, estando de acordo com
    os pedidos, não havendo nada que desabone com as compras;</font></font></font></p>
    <p align="justify" style="margin-top: 0.42cm; margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%"><a name="_heading=h.gjdgxs"></a>
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CONSIDERANDO</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    que a </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA
    </b></font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">não
    realizou o pagamento de diversos títulos devidos à </font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CREDORA
    </b></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">no
    tempo e/ou forma devidos, reconhecendo neste ato </font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">ser
    </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    dos referidos títulos</font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">,
    conforme tabela de títulos com respectivos valores nominais abaixo:</font></font></p>
    <p align="justify" style="margin-top: 0.42cm; margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <center>
        <table width="614" cellpadding="7" cellspacing="0">
            <col width="174">
            <col width="81">
            <col width="109">
            <col width="193">
            <tr>
                <td colspan="2" width="268" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.42cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                <b>Nº do Título</b>
                            </font>
                        </font>
                    </p>
                </td>
                <td width="316" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.42cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                <b>Valor</b>
                            </font>
                        </font>
                    </p>
                </td>
                <td width="316" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.42cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                <b>Vencimento</b>
                            </font>
                        </font>
                    </p>
                </td>
            </tr>
            @foreach($titulos as $index => $titulo)
            <tr>
                <td width="10" valign="bottom" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                {{$index + 1}}
                            </font>
                        </font>
                    </p>
                </td>
                <td width="258" valign="bottom" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                {{$titulo['titulo']}}
                            </font>
                        </font>
                    </p>
                </td>
                <td width="316" valign="top" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                {{$titulo['valor_original']}}
                            </font>
                        </font>
                    </p>
                </td>
                <td width="316" valign="top" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                {{$titulo['data_vencimento']}}
                            </font>
                        </font>
                    </p>
                </td>
            </tr>
            @endforeach
            <tr>
                <td width="10" valign="bottom" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                &nbsp;
                            </font>
                        </font>
                    </p>
                </td>
                <td width="258" valign="bottom" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                &nbsp;
                            </font>
                        </font>
                    </p>
                </td>
                <td width="316" valign="top" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                            </font>
                        </font>
                    </p>
                </td>
                <td width="316" valign="top" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                &nbsp;
                            </font>
                        </font>
                    </p>
                </td>
            </tr>
            <tr>
                <td width="10" valign="bottom" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                &nbsp;
                            </font>
                        </font>
                    </p>
                </td>
                <td width="258" valign="bottom" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                <b>TOTAL</b>
                            </font>
                        </font>
                    </p>
                </td>
                <td width="316" valign="top" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                <b>{{$dados[0]['titulo_total']}}</b>
                            </font>
                        </font>
                    </p>
                </td>
                <td width="316" valign="top" style="border: 1px solid #000000; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.2cm; padding-right: 0.19cm">
                    <p align="center" style="margin-top: 0.21cm">
                        <font face="Calibri, serif">
                            <font size="3" style="font-size: 12pt">
                                &nbsp;
                            </font>
                        </font>
                    </p>
                </td>
            </tr>
        </table>
    </center>
    <p align="justify" style="margin-top: 0.42cm; margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-top: 0.42cm; margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-top: 0.42cm; margin-bottom: 0cm; line-height: 115%; background: #ffffff">
    <font face="Calibri, serif"><font size="3" style="font-size: 12pt">Diante
    dos fatos, decidem as Partes formalizar acordo por este </font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>INSTRUMENTO
    PARTICULAR DE CONFISSÃO DE DÍVIDA E OUTRAS AVENÇAS
    </b></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">(&quot;Instrumento&quot;),
    estabelecendo as seguintes cláusulas e condições a seguir:</font></font></p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%; background: #ffffff">
    <br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>Cláusula
    Primeira</b></u></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:
    Pelo presente instrumento e na melhor forma de direito, a </font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA</b></font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    confessa dever em favor da </font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CREDORA,
    </b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">o
    valor </font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">descrito</font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    no </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><i>“</i></font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><i>item
    1</i></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">”
    do </font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">quadro
    resumo do p</font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">resente</font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">,
    resultado do inadimplemento dos títulos descritos neste instrumento,
    acrescidos de custos administrativos e de cobrança, juros de mora,
    correção monetária e cláusula penal (art. 412 do Código Civil),
    valor livremente pactuado e calculado pelas partes e em relação ao
    qual nada tem a opor uma em relação à outra.</font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>Cláusula
    Segunda</b></u></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>:
    </b></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">O
    valor do débito confessado na </font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><i>“Cláusula
    Primeira</i></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">”
    será pago pela </font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA</b></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">,
    na forma e condições dos </font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><i>“itens
    2, 3 e 4”</i></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    do quadro resumo.</font></font></p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u>§1º</u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:</font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    </font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">O
    pagamento será feito por meio de boleto bancário sacado contra a
    </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">,
    a ser enviado pela </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CREDORA</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    ou p</font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">or
    empresa credenciada</font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">,
    com o prazo de 2 (dois) dias de antecedência do vencimento.</font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u>§2º</u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:
    </font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">A
    falta do recebimento do boleto no prazo acima estipulado não
    desobriga a </font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA,
    </b></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">que
    deve entrar em contato com a</font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>
    CREDORA </b></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    a fim de realizar o pagamento diretamente a esta até a data do
    vencimento, sob pena de incorrer nas sanções previstas na </font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><i>“Cláusula
    Quarta”.</i></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
        </font></font>
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u>§3º</u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:
    </font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">Compete
    à </font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA</b></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    arcar com as despesas da tarifa bancária de emissão , cancelamento
    e/ou suspensão de boleto, além dos custos cartorários e de remessa
    de documentos.</font></font></p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u>§4º</u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:</font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    Compromete-se a </font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA</b></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    a enviar ao e-mail  </font></font><a href="mailto:mntecidos@ragazzi.adv.br"><font color="#0563c1"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u>mntecidos@ragazzi.adv.br</u></font></font></font></a><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    o comprovante de pagamento referente a cada parcela, no prazo de 2
    (dois) dias após o vencimento.    </font></font>
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>Cláusula
    Terceira</b></u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:
    Havendo protestos de títulos abarcados pela presente confissão de
    dívida, a&nbsp;</font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">&nbsp;compromete-se
    a comunicar a&nbsp;</font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CREDORA&nbsp;</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">&nbsp;no
    prazo de 10 (dez) dias, condicionando-se a emissão de Carta de
    Anuência ao efetivo e inequívoco recebimento, pela&nbsp;</font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CREDORA,&nbsp;</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">&nbsp;do
    presente instrumento,&nbsp;&nbsp;devidamente assinado e com firmas
    reconhecidas por todos os representantes legais da&nbsp;</font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA,
    GARANTIDORES E CÔNJUGES (se casados)</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">.</font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u>§1º</u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:</font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    </font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">Após
    a comunicação de que trata o </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><i>caput</i></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    da “</font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><i>Cláusula
    Terceira</i></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">”,
    a </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CREDORA</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
        terá o prazo de 15 (quinze) dias para disponibilizar a carta de
    anuência à </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA.</b></font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u>§2º</u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:
    O envio da carta de anuência dependerá do pagamento prévio pela
    </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA
    </b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">das
    depesas com o reconhecimento de firma e de postagem, além dos
    emolumentos e taxas cartorárias e de boleto.</font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><a name="_GoBack"></a>
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u>§3º</u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:
    O presente acordo não suspende as ordens de protestos já realizadas
    dos títulos vencidos, e em função do protesto, tais apontamentos
    serão retirados de cartório da mesma forma  prevista no </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><i>caput</i></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    desta cláusula.</font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>Cláusula
    Quarta</b></u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:
    O não pagamento de qualquer parcela nas datas avençadas implicará em multa de 10% (dez por cento) sobre o valor da parcela, mais juros de 1% (um por cento) ao mês, além de honorários advocatícios de 15% (quinze por cento).</font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u>§1º</u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:
    </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><span lang="pt-BR">Sem
    prejuízo do disposto no </span></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><span lang="pt-BR"><i>caput</i></span></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><span lang="pt-BR">
    desta cláusula,  inadimplemento de qualquer parcela por mais de 30
    (trinta) dias, implicará </span></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    no vencimento antecipado da dívida, acrescido de cláusula penal
    (art. 412 do Código Civil) de 20% (vinte por cento) sobre o saldo devedor, juros de mora 1% (um por cento) ao mês, calculados pro rata die, contados da data do vencimento até a data do pagamento, mais honorários advocatícios de 20% (vinte por cento) , valor este que será corrigido monetariamente pela tabela de atualização de débitos judiciais do Tribunal de Justiça de São Paulo, valendo o presente instrumento como título executivo para todos os efeitos de direito.</font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%"><a name="_Hlk45281046"></a>
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u>§2º:</u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    Havendo inadimplência, além das penalidades dispostas, a </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    concorda e autoriza tanto a </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CREDORA,</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    quanto </font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">eventual
    empresa contratada pela mesma para gerenciamento de sua cobrança</font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">,
    a efetuar o protesto em Cartório do presente instrumento e das
    parcelas vencidas e não pagas.</font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%"><a name="_Hlk45280916"></a>
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>Cláusula
    </b></u></font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>Quinta</b></u></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>:</b></u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    Em segurança e garantia do cabal cumprimento das obrigações
    contratuais na vigência do presente contrato, decorrentes do
    fornecimento de mercadorias, constituem-se </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>FIADORES
    E PRINCIPAIS PAGADORES do CLIENTE</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">,
    em caráter solidário, conjuntos entre si, renunciando expressamente aos benefícios de ordem, divisão e liquidação, bem assim aos favores e exceções dos artigos 827, 835, 838, 1.642, IV do Código Civil Brasileiro, e 794, §§1º, 2º e 3º Código de Processo Civil, os fiadores qualificados no preâmbulo do presente instrumento, responsabilizando-se autonomamente por todas as obrigações derivadas deste contrato, até o efetivo e real reembolso da credora em juízo ou fora dele.</font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u>Parágrafo
    único:</u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    Por força da solidariedade assumida pelo(s) </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>FIADOR</b></font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>(ES)</b></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">,
    em caso de inadimplemento, faculta-se à </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CREDORA</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    promover as ações pertinentes tanto contra a </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>DEVEDORA</b></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    quanto contra  o(s) </font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>FIADOR</b></font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>(ES)</b></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    para o recebimento integral de seu crédito.</font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>Cláusula
    </b></u></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>Sexta</b></u></font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">:
    Com o cumprimento integral do presente acordo, as partes outorgarão a mais plena, rasa, geral, irrevogável e irretratável quitação em relação aos termos ora avençados, para nada mais reclamarem, seja a que título for.</font></font></p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>Cláusula
    Sétima:</b></u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
        O presente instrumento livremente negociado entre as partes poderá ser assinado fisicamente, eletronicamente, por e-mail ou assinatura digitalizada (aquela resultado da reprodução eletrônica de uma assinatura manuscrita do sujeito de direito inserida manualmente em um contrato) em caráter irrevogável e irretratável, obrigando as partes, seus eventuais herdeiros ou sucessores. </font></font></font>
    </p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>Cláusula
    Oitava:</b></u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    Em caso de necessidade de procedimento judicial com base no presente instrumento, a parte culpada arcará com as custas processuais e os honorários advocatícios na base de 20% (vinte por cento) sobre o valor do débito.</font></font></font></p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%">
    <font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><u><b>Cláusula
    Nona:</b></u></font></font></font><font color="#000000"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">
    </font></font></font><font face="Calibri, serif"><font size="3" style="font-size: 12pt">Fica eleito o Foro da Comarca de São Paulo, SP, para dirimir quaisquer dúvidas oriundas do presente instrumento, dispensando-se qualquer outro por mais privilegiado que seja.</font></font></p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><font face="Calibri, serif"><font size="3" style="font-size: 12pt">E por ser esta a expressão da verdade, e também a vontade das partes, firmam o presente instrumento em 02 (duas) vias de igual teor e forma, perante 02 (duas) testemunhas.</font></font></p>
    <p align="justify" style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    
    <p align="right" style="margin-bottom: 0cm; border: none; padding: 0cm; line-height: 115%; page-break-after: avoid">
        <font color="#000000">
            <font face="Calibri, serif">
                <font size="3" style="font-size: 12pt">
                    São Paulo, {{$dados[0]['data_atual']}}.
                </font>
            </font>
        </font>
    </p>
    
    <p style="margin-bottom: 0cm; line-height: 115%"><br/>
    
    </p>
    <center>
        <table width="676" cellpadding="7" cellspacing="0" style="margin-left: 10px">
            <col width="150">
            <col width="251">
            <col width="17">
            <col width="248">
            <tr>
                <td width="150"></td>
                <td colspan="3" width="544" valign="top" style="border: none; padding: 0cm">
                    <p style="margin-right: 0.2cm; margin-bottom: 0cm">              
                                        
                    </p>
                    <p style="margin-right: 0.2cm; margin-bottom: 0cm"><a name="_heading=h.3znysh7"></a>
                    <br/>
                    <br/>
                    <br/>
                    <br/>
    
                    </p>
                    <table width="563" cellpadding="7" cellspacing="0">
                        <col width="235">
                        <col width="51">
                        <col width="235">
                        <tr>
                            <td width="235" height="15" bgcolor="#f2f2f2" style="background: #f2f2f2" style="border-top: 1px solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
                                <p align="center">
                                    <font face="Calibri, serif">
                                        <font size="3" style="font-size: 12pt">
                                            <b>CREDORA</b>
                                        </font>
                                    </font>
                                </p>
                            </td>
                            <td width="51" style="border: none; padding: 0cm">
                                <p align="center" style="border: none; padding: 0cm"><br/>
    
                                </p>
                            </td>
                            <td width="235" bgcolor="#f2f2f2" style="background: #f2f2f2" style="border-top: 1px solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
                                <p align="center">
                                    <font face="Calibri, serif">
                                        <font size="3" style="font-size: 12pt">
                                            <b>DEVEDORA</b>
                                        </font>
                                    </font>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <p style="margin-bottom: 0cm; line-height: 115%"><br/>
    
                    </p>
                    <p style="margin-bottom: 0cm; line-height: 115%"><br/>
    
                    </p>
                    <p style="margin-bottom: 0cm; line-height: 115%"><br/>
    
                    </p>
                    <p style="margin-bottom: 0cm; line-height: 115%"><br/>
    
                    </p>

                    <table width="543" cellpadding="7" cellspacing="0">
                        <col width="241">
                        <col width="10">
                        <col width="251">
                        <tr>
                            <td width="241" height="11" bgcolor="#f2f2f2" style="background: #f2f2f2" style="border-top: 1px solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
                                <p align="center" style="margin-left: -0.05cm">
                                    <font face="Calibri, serif">
                                        <font size="3" style="font-size: 12pt">
                                            <u><b>FIADOR(A)</b></u> </br>
                                        </font>
                                    </font>
                                </p>
                            </td>
                            <td width="10" style="border: none; padding: 0cm">
                                <p align="center" style="border: none; padding: 0cm"><br/>
    
                                </p>
                            </td>
                            <td width="251" bgcolor="#f2f2f2" style="background: #f2f2f2" style="border-top: 1px solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
                                <p align="center">
                                    <font face="Calibri, serif">
                                        <font size="3" style="font-size: 12pt">
                                            <u><b>INTERVENIENTE ANUENTE</b></u> </br><b>{{$dados[0]['nome_venia_conjugal']}}</b>
                                        </font>
                                    </font>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p style="margin-bottom: 0cm; line-height: 115%"><br/>
    
                    </p>
                    <p align="center" style="margin-right: 0.2cm; margin-bottom: 0cm; line-height: 115%">
                    <br/>
    
                    </p>
                    <p align="center" style="margin-right: 0.2cm; margin-bottom: 0cm; line-height: 115%">
                    <br/>
    
                    </p>
                    <p align="center" style="margin-right: 0.2cm; margin-bottom: 0cm; line-height: 115%"><a name="_heading=h.30j0zll"></a>
                    <font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>TESTEMUNHAS:</b></font></font></p>
                    <p align="center" style="margin-right: 0.2cm; margin-bottom: 0cm; line-height: 115%">
                    <br/>
                    <br/>
                    <br/>
                    <br/>
                    </p>
                </td>
            </tr>
            <tr>
                <td width="150"></td>
                <td width="251" style="border-top: none; border-bottom: 1px solid #000000; border-left: none; border-right: none; padding: 0cm">
                    <p style="margin-bottom: 0cm; line-height: 150%"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>Ass:</b></font></font></p>
                </td>
                <td width="17" style="border: none; padding: 0cm">
                    <p style="margin-bottom: 0cm; line-height: 150%"><br/>
    
                    </p>
                </td>
                <td width="248" style="border-top: none; border-bottom: 1px solid #000000; border-left: none; border-right: none; padding: 0cm">
                    <p style="margin-bottom: 0cm; line-height: 150%"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>Ass:</b></font></font></p>
                </td>
            </tr>
            <tr>
                <td width="150"></td>
                <td width="251" style="border-top: 1px solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
                    <p style="margin-bottom: 0cm; line-height: 150%"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>Nome:</b></font></font></p>
                </td>
                <td width="17" style="border: none; padding: 0cm">
                    <p style="margin-bottom: 0cm; line-height: 150%"><br/>
    
                    </p>
                </td>
                <td width="248" style="border-top: 1px solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
                    <p style="margin-bottom: 0cm; line-height: 150%"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>Nome:</b></font></font></p>
                </td>
            </tr>
            <tr>
                <td width="150"></td>
                <td width="251" style="border: none; padding: 0cm">
                    <p style="margin-bottom: 0cm; line-height: 150%"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CPF:</b></font></font></p>
                </td>
                <td width="17" style="border: none; padding: 0cm">
                    <p style="margin-bottom: 0cm; line-height: 150%"><br/>
    
                    </p>
                </td>
                <td width="248" style="border: none; padding: 0cm">
                    <p style="margin-bottom: 0cm; line-height: 150%"><font face="Calibri, serif"><font size="3" style="font-size: 12pt"><b>CPF:</b></font></font></p>
                </td>
            </tr>
        </table>
    </center>
    <p style="margin-bottom: 0cm; line-height: 150%">
        <br/>
    </p>
</div>