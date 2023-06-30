
<style>
    html,
    body,
    p,
    ol,
    ul,
    li,
    dl,
    dt,
    dd,
    blockquote,
    figure,
    fieldset,
    legend,
    textarea,
    pre,
    iframe,
    hr,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
    margin: 0;
    padding: 0;
    }
    * {
    box-sizing: inherit;
    }
    .container {
        margin: 0 auto;
        position: relative;
        max-width: 960px;
        border: 1px solid black;
        padding: 2px;
    }
    .columns {
        display: flex;
        margin-top: -0.75rem;
    }
    .column {
        display: block;
        padding-top: 5px;
    }
    .data-recebimento{
        float: left;
        width: 180px;
        height: 50px;
        border: 2px black solid;
    }
    .columns:not(:last-child) {
        margin-bottom: calc(1.5rem - 0.75rem);
    }
    .is-pulled-right {
        float: right !important;
    }
    .is-pulled-left {
       float: left !important;
    }
    .column.is-1 {
        -webkit-box-flex: 0;
        -ms-flex: none;
        flex: none;
        width: 8.33333%;
    }
    span {
        font-style: inherit;
        font-weight: inherit;
    }
    *:before,
    *:after {
        -webkit-box-sizing: inherit;
        box-sizing: inherit;
    }
    table {
        border-collapse: collapse;
        border-spacing: 0;
    }
    td,
    th {
        padding: 0;
        text-align: left;
    }
    .content.is-small {
        font-size: 0.75rem;
    }
    .content.is-medium {
        font-size: 1.25rem;
    }
    .content.is-large {
        font-size: 1.5rem;
    }

    html,
    body {
        font-family: Courier New;
        margin: 12px;
        margin-top: 0px;
        line-height: 1;
        color: black !important;
        font-size: 11px;
        font-weight: 500 !important;
    }
    .area {
        width: 850px !important;
    }
    .quadro_codigo_barra {
        float: left;
        width:396px;
        height: 150px;
    }
    .codigo_barra {
        height: 56px;
    }
    .chave {
        height: 33px;
        font-size: 82%;
        font-weight: bold;
        text-align: center;
    }
    .inscricao_estdual{
        float: left;
        height: 40px;
        width: 282px;
    }
    .inscricao_estdual_sub{
        float: left;
        width: 282px;
        height: 40px;
    }
    .cpf{
        float: left;
        width: 282px;
        height: 40px;
    }
    .destinatario_campos{
        float: left;
        width: 282px;
        height: 45px;
    }
    .destinatario_endereco{
        float: left;
        width: 211.2px;
        height: 40px;
    }
    .destinatario_municipio{
        float: left;
        width: 200px;
        height: 40px;
    }
    .destinatario_uf{
        float: left;
        width: 50px;
        height: 40px;
    }
    .destinatario_telefone{
        float: left;
        width: 343.7px;
        height: 40px;
    }
    .destinatario_ie{
        float: left;
        width: 150px;
        height: 40px;
    }
    .destinatario_data{
        float: left;
        width: 100px;
        height: 40px;
        text-align: center;
    }
    .destinatario_titulo{
        float: left;
        width: 100%;
        height: 25px;
    }
    .imposto_valores{
        float: left;
        width: 168.97px;
        height: 50px;
        text-align: center;
    }
    .imposto_valor{
        float: left;
        width: 140.65px;
        height: 50px;
        text-align: center;
    }
    .fatura_outros{
        float: left;
        width: 100%;
        height: 40px;
    }
    .protocolo,
    .consulta,
    .chave {
        padding: 2px;
    }
    .protocolo {
        flex: 0 0 296px;
    }
    .codigo_barra,
    .chave {
        border-bottom: 1px solid black;
    }
    .consulta {
        font-size: 10px;
        text-align: center;
    }
    .quadro_danfe {
        float: left;
        width: 150px;
        height: 150px;
    }
    .quadro_identificacao {
        float: left;
        width: 300px;
        height: 150px;
        font-weight: bold;
        font-size: 13px
    }
    .quadro_cabecalho {
        height: 150px !important;
    }
    div.quadro div.columns:nth-child(2) {
        border-top: 1px solid black;
    }
    div.quadro div.columns:first-child div.column {
        padding: 0px;
    }
    .linha div.column:first-child {
        border-left: 1px solid black;
    }
    .quadro .linha div.column {
        border-bottom: 1px solid black;
        border-right: 1px solid black;
    }
    .conteudo_campo {
        padding: 0 0 0 2px;
    }
    .quadro.imposto div.linha div.column div:nth-child(2),
    .direita {
        text-align: right;
    }
    .quadro div.columns:first-child div.column {
        margin-top: 5px;
    }
    .grupo .quadro .linha {
        height: 33px;
    }
    .grupo {
        margin-top: 5px;
    }
    .tcampo {
        font-size: 11px;
        padding-bottom: 2px;
        padding-left: 2px;
        text-align: left;
        padding: 5px;
    }
    .texto_recibo {
        font-weight: bold;
        font-size: 9px;
        text-align: left;
    }
    .center {
        text-align: center;
    }
    .bold {
        font-weight: bold;
        margin-bottom: 2px;
    }
    .operacao {
        font-size: 14px;
        width: 20px;
        border: 1px solid black;
        padding: 2px;
    }
    .natureza_operacao{
        float: left;
        width: 848px;
        height: 40px;
    }
    .numero,
    .canhoto_nr {
        font-size: 11px;
        font-weight: bold;
    }
    .danfe {
        font-size: 13px;
        font-weight: bold;
    }
    .itens {
        font-size: 10px;
    }
    .data {
        text-align: center;
    }
    .uf {
        flex: 0 0 30px;
    }
    .placa {
        flex: 0 0 70px;
    }
    .nome {
        flex: 0 0 300px;
    }
    .complemento {
        height: 114px !important;
        border-left: 0px !important;
        border: 1px solid black;
        font-size: 10px;
    }
    .canhoto_nr {
        flex: 0 0 171px;
    }
    .area_canhoto_nr {
        float: right;
        height: 50px;
        width: 213.4px;
        border-left: 0px !important;
        line-height: 1.5;
        padding-left: 3px !important;
    }
    .canhoto_assinatura {
        float: left;
        width: 450px;
        height: 50px;
        border: 2px black solid;
    }
    .duplicatas .duplicata {
        font-size: 0.9rem;
        text-align: center;
        padding-right: 3px;
        padding-left: 3px;
        border-right: 1px solid gray;
    }
    .duplicatas .duplicata div {
        margin-top: 2px;
    }
    .table_titulo{
        margin: 4px;
    }
    .duplicatas .tcampo {
        font-size: 0.8rem;
        margin-top: 1px;
    }
    table td,
    table th {
        border: 1px solid black !important;
        border-top: 0px solid black !important;
        padding: 2px !important;
        color: black !important;
    }
    table {
        margin-bottom: 1px !important;
        width: 100%;
    }
    .informacoes_adicionaris_titulo{
        margin-top: 5px;
        margin-bottom: 10px;
    }
</style>

<div class="container area">
    <div class="columns grupo">
        <div class="column">
            <div class="quadro">
                <div class="columns linha">
                    <div class="column data-recebimento">
                        <div class="tcampo">DATA DE RECEBIMENTO</div>
                    </div>
                    <div class="column canhoto_assinatura">
                        <div class="tcampo">IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR</div>
                    </div>
                    <div class="column  area_canhoto_nr">
                        <div>@if($nota['tipo'] == 'nfe') NF-e @else CT-e @endif</div>
                        <div>Nº {{ $nota['numero'] }} SÉRIE: {{ $nota['serie'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="columns grupo">
        <div class="column ">
            <div class="quadro">
                <div class="columns linha">
                    <div class="column  quadro_identificacao">
                        <div class="conteudo_campo">{{ $emitente['nome'] }}</div>
                        <div class="conteudo_campo">{{ $emitente['fantasia'] }}</div>
                        <div class="conteudo_campo">{{ $emitente['endereco'] }}, {{   $emitente['numero'] }}</div>
                        <div class="conteudo_campo">{{ $emitente['complemento'] }}</div>
                        <div class="conteudo_campo">{{ $emitente['bairro'] }} - {{ $emitente['municipio'] }} / {{ $emitente['uf'] }}</div>
                        <div class="conteudo_campo">CEP:{{ $emitente['cep'] }} - Fone: {{ $emitente['telefone'] }}</div>
                    </div>
                    <div class="column quadro_danfe">
                        <div class="center danfe">DANFE</div>
                        <div class="center">DOCUMENTO AUXILIAR DA NOTA FISCAL ELETRÔNICA</div>
                        <br>
                        <span class="numero center">
                            <div class="center is-pulled-right operacao">0</div>
                            <div>0- ENTRADA</div>
                            <div>1- SAÍDA</div>
                            <br>
                            <div>Nº {{ $nota['numero'] }}</div>
                            <div>SÉRIE {{ $nota['serie'] }}</div>
                            <div>Página 1 de 1</div>
                        </span>
                    </div>
                    <div class="column quadro_codigo_barra">
                        <div class="codigo_barra">barra</div>
                        <div class="chave">
                            <div class="tcampo">CHAVE DE ACESSO</div>
                            {{ $nota['chave'] }}
                        </div>
                        <div class="chave">
                            <div class="tcampo">PROTOCOLO DE AUTORIZAÇÃO DE USO</div>
                            xxxxxxxxxxxxxxx - XX/XX/XX
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="columns grupo">
        <div class="column ">
            <div class="quadro">
                <div class="columns linha">
                    <div class="column natureza_operacao">
                        <div class="tcampo">NATUREZA DA OPERAÇÃO</div>
                        <span class="conteudo_campo">{{ $natureza_operacao }}</span>
                    </div>
                </div>
                <div class="columns linha">
                    <div class="column inscricao_estdual">
                        <div class="tcampo">INSCRIÇÃO ESTADUAL</div>
                        <span class="conteudo_campo">{{ $emitente['ie'] }}</span>
                    </div>
                    <div class="column inscricao_estdual_sub">
                        <div class="tcampo">INSCRIÇÃO ESTADUAL DO SUBST. TRIB.</div>
                        <span class="conteudo_campo"></span>
                    </div>
                    <div class="column cpf">
                        <div class="tcampo">C.N.P.J.</div>
                        <span class="conteudo_campo">{{ $emitente['cpf_cnpj'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="columns grupo">
        <div class="column">
            <div class="columns destinatario_titulo">
                <div class="column bold">
                    DESTINATÁRIO/REMETENTE
                </div>
            </div>
            <div class="quadro">
                <div class="columns linha">
                    <div class="column destinatario_campos">
                        <div class="tcampo">NOME/RAZÃO SOCIAL</div>
                        <span class="conteudo_campo">{{ $destinatario['nome']}}</span>
                    </div>
                    <div class="column destinatario_campos">
                        <div class="tcampo">C.N.P.J./C.P.F.</div>
                        <span class="conteudo_campo">{{ $destinatario['cpf_cnpj']}}</span>
                    </div>
                    <div class="column data destinatario_campos">
                        <div class="tcampo">DATA DA EMISSÃO</div>
                        <span class="conteudo_campo"> {{ $nota['emissao'] }}</span>
                    </div>
                </div>
                <div class="columns linha">
                    <div class="column destinatario_endereco">
                        <div class="tcampo">ENDEREÇO</div>
                        <span class="conteudo_campo">{{ $destinatario['endereco']}}</span>
                    </div>
                    <div class="column destinatario_endereco">
                        <div class="tcampo ">BAIRRO/DISTRITO</div>
                        <span class="conteudo_campo">{{ $destinatario['bairro']}}</span>
                    </div>
                    <div class="column destinatario_endereco">
                        <div class="tcampo">CEP</div>
                        <span class="conteudo_campo">{{ $destinatario['cep']}}</span>
                    </div>
                    <div class="column destinatario_endereco">
                        <div class="tcampo">DATA SAÍDA/ENTRADA</div>
                        <span class="conteudo_campo"></span>
                    </div>
                </div>
                <div class="columns linha">
                    <div class="column destinatario_municipio">
                        <div class="tcampo">MUNICÍPIO</div>
                        <span class="conteudo_campo">{{ $destinatario['municipio']}}</span>
                    </div>
                    <div class="column destinatario_telefone">
                        <div class="tcampo">FONE/FAX</div>
                        <span class="conteudo_campo">{{ $destinatario['telefone']}}</span>
                    </div>
                    <div class="column destinatario_uf">
                        <div class="tcampo">UF</div>
                        <span class="conteudo_campo">{{ $destinatario['uf']}}</span>
                    </div>
                    <div class="column destinatario_ie">
                        <div class="tcampo">INSCRIÇÃO ESTADUAL</div>
                        <span class="conteudo_campo">{{ $destinatario['ie']}}</span>
                    </div>
                    <div class="column destinatario_data">
                        <div class="tcampo">HORA DA SAÍDA</div>
                        <span class="conteudo_campo"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="columns grupo">
        <div class="column ">
            <div class="quadro duplicatas">
                <div class="columns">
                    <div class="column bold">
                    FATURA/DUPLICATAS
                    </div>
                </div>
                <div class="columns linha">
                    <div class="column fatura_outros">
                        <div class='tcampo'>
                            Outros
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="columns grupo">
        <div class="column ">
            <div class="quadro imposto">
                <div class="columns">
                    <div class="column bold">
                        CÁLCULO DO IMPOSTO
                    </div>
                </div>
                <div class="columns linha">
                    <div class="column imposto_valores">
                        <div class="tcampo">BASE DE CÁLCULO DO ICMS</div>
                        <div>
                            {{ $nota['baseicms'] }}
                        </div>
                    </div>
                    <div class="column imposto_valores">
                        <div class="tcampo">VALOR DO ICMS</div>
                        <div>
                            {{ $nota['valoricms'] }}
                        </div>
                    </div>
                    <div class="column imposto_valores">
                        <div class="tcampo">BASE DE CÁLCULO DO ICMS ST</div>
                        <div>
                            {{ $nota['basesubst'] }}
                        </div>
                    </div>
                    <div class="column imposto_valores">
                        <div class="tcampo">VALOR DO ICMS ST</div>
                        <div>
                            {{ $nota['valoricmsst'] }}
                        </div>
                    </div>
                    <div class="column imposto_valores">
                        <div class="tcampo">@if($nota['tipo'] = 'nfe')VALOR TOTAL DOS PRODUTOS @else VALOR TOTAL DA CARGA @endif</div>
                        <div>
                            {{ $nota['total_produto'] }}
                        </div>
                    </div>
                </div>
                <div class="columns linha">
                    <div class="column imposto_valor">
                        <div class="tcampo">VALOR DO FRETE</div>
                        <div>
                            {{ $nota['frete'] }}
                        </div>
                    </div>
                    <div class="column imposto_valor">
                        <div class="tcampo">VALOR DO SEGURO</div>
                        <div>
                            {{ $nota['seguro'] }}
                        </div>
                    </div>
                    <div class="column imposto_valor">
                        <div class="tcampo">DESCONTO</div>
                        <div>
                            {{ $nota['total_desconto'] }}
                        </div>
                    </div>
                    <div class="column imposto_valor">
                        <div class="tcampo">OUTRAS DESPESAS ACESSÓRIAS</div>
                        <div>
                            {{ $nota['outras'] }}
                        </div>
                    </div>
                    <div class="column imposto_valor">
                        <div class="tcampo">VALOR DO IPI</div>
                        <div>
                            {{ $nota['ipi'] }}
                        </div>
                    </div>
                    <div class="column imposto_valor">
                        <div class="tcampo">VALOR TOTAL DA NOTA</div>
                        <div>
                            {{ $nota['valor'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(!empty($itens[0]))
    <div class="columns grupo">
        <div class="column ">
            <div class="quadro">
                <div class="columns table_titulo">
                    <div class="column bold">
                    DADOS DOS PRODUTOS/SERVIÇOS
                    </div>
                </div>
                <div class="columns">
                    <table class="table is-bordered itens">
                        <thead>
                            <tr>
                            <th>CÓDIGO</th>
                            <th>DESCRIÇÃO</th>
                            <th>NCM/SH</th>
                            <th>CST</th>
                            <th>CFOP</th>
                            <th>UN.</th>
                            <th>QUANT.</th>
                            <th>V.UNIT.</th>
                            <th>V.DESC.</th>
                            <th>V.TOTAL</th>
                            <th>BC.ICMS</th>
                            <th>VALOR PIS</th>
                            <th>V.IPI</th>
                            <th>Quantidade Tributável</th>
                            <th>%IPI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($itens as $item)
                            <tr>
                            <td>{{ $item['codigo'] }}</td>
                            <td>{{ $item['descricao'] }}</td>
                            <td>{{ $item['ncm'] }}</td>
                            <td>{{ $item['cst'] }}</td>
                            <td>{{ $cfop }}</td>
                            <td>{{ $item['unidade'] }}</td>
                            <td class="direita">{{$item['quantidade'] }}</td>
                            <td class="direita">{{$item['valor'] }}</td>
                            <td class="direita">{{$item['desconto'] }}</td>
                            <td class="direita">{{$item['total'] }}</td>
                            <td class="direita">{{$item['base_calculo'] }}</td>
                            <td class="direita">{{$item['icms'] }}</td>
                            <td class="direita">{{$item['pis'] }}</td>
                            <td class="direita">{{$item['quantidade_tributavel'] }}</td>
                            <td class="direita">{{$item['cofins_valor'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
    <div class="column grupo">
        <div class="quadro">
            <div class="columns informacoes_adicionaris_titulo">
                <div class="column bold">
                    DADOS ADICIONAIS
                </div>
            </div>
        </div>
        <span class="conteudo_campo">
            <div class="columns linha complemento">
                <div class="column">
                    <div class="tcampo">INFORMAÇÕES COMPLEMENTARES</div>
                        {{$nota['informacao_complementar']}}
                    </div>
                <div class="column">
                    <div class="tcampo">RESERVADO AO FISCO</div>
                </div>
            </div>
        </span>
    </div>
</div>