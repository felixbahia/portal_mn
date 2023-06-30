@extends('layouts.app-lista-preco')
@section('content')
<div class="content-lista-preco-antiga">
	<div id="title-lista-preco">Rondônia</div>
    <div class="content-lista">
		<div>
			<div id="title-lista">Dolar</div>
			<div class="lista"><a href="{{ $url }}/RONDONIA_DOLAR_FOB.pdf" title="Entre aqui para acesso a tabela de preço Rondonia(Dolar) em Pdf" target="_blanck">Tabela de preço - FOB</a></div>
		</div>
		<div>
			<div id="title-lista">Real</div>
			<div class="lista"><a href="{{ $url }}/RONDONIA_REAIS_4_FOB.pdf" title="Entre aqui para acesso a tabela de preço Rondonia(Reais 4%) em Pdf" target="_blanck">Tabela de preço - Alíquota 4% - FOB</a></div>
			<div class="lista"><a href="{{ $url }}/RONDONIA_REAIS_19_FOB.pdf" title="Entre aqui para acesso a tabela de preço Rondonia(Reais 19%) em Pdf" target="_blanck">Tabela de preço - Alíquota 19% - FOB</a></div>
		</div>
    </div>
    <div class="content-lista">
		<div>
			<div class="lista"><a href="{{ $url }}/RONDONIA_DOLAR_CIF.pdf" title="Entre aqui para acesso a tabela de preço Rondonia(Dolar) em Pdf" target="_blanck">Tabela de preço - CIF</a></div>
		</div>
		<div>
			<div class="lista"><a href="{{ $url }}/RONDONIA_REAIS_4_CIF.pdf" title="Entre aqui para acesso a tabela de preço Rondonia(Reais 4%) em Pdf" target="_blanck">Tabela de preço - Alíquota 4% - CIF</a></div>
			<div class="lista"><a href="{{ $url }}/RONDONIA_REAIS_19_CIF.pdf" title="Entre aqui para acesso a tabela de preço Rondonia(Reais 19%) em Pdf" target="_blanck">Tabela de preço - Alíquota 19% - CIF</a></div>
		</div>
    </div>
    <hr />
	<div id="title-lista-preco">Tocantins</div>
    <div class="content-lista">
		<div>
			<div id="title-lista">1ª Qualidade em Reais</div>
			<div class="lista"><a href="{{ $url }}/TOCANTINS_Q1_REAIS_4_FOB.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 4% para Tocantins (1a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 4% - FOB</a></div>
			<div class="lista"><a href="{{ $url }}/TOCANTINS_Q1_REAIS_12_FOB.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 12% para Tocantins (1a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 12% - FOB</a></div>
			<div class="lista"><a href="{{ $url }}/TOCANTINS_Q1_REAIS_19_FOB.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 19% para Tocantins (1a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 19% - FOB</a></div>
		</div>
		<div>
			<div id="title-lista">2ª Qualidade em Reais</div>
			<div class="lista"><a href="{{ $url }}/TOCANTINS_Q2_REAIS_12_FOB.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 12% para Tocantins (2a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 12% - FOB</a></div>
			<div class="lista"><a href="{{ $url }}/TOCANTINS_Q2_REAIS_19_FOB.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 19% para Tocantins (2a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 19% - FOB</a></div>
		</div>
    </div>
    <div class="content-lista">
		<div>
			<div class="lista"><a href="{{ $url }}/TOCANTINS_Q1_REAIS_4_CIF.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 4% para Tocantins (1a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 4% - CIF</a></div>
			<div class="lista"><a href="{{ $url }}/TOCANTINS_Q1_REAIS_12_CIF.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 12% para Tocantins (1a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 12% - CIF</a></div>
			<div class="lista"><a href="{{ $url }}/TOCANTINS_Q1_REAIS_19_CIF.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 19% para Tocantins (1a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 19% - CIF</a></div>
		</div>
		<div>
			<div class="lista"><a href="{{ $url }}/TOCANTINS_Q2_REAIS_12_CIF.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 12% para Tocantins (2a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 12% - CIF</a></div>
			<div class="lista"><a href="{{ $url }}/TOCANTINS_Q2_REAIS_19_CIF.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 19% para Tocantins (2a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 19% - CIF</a></div>
		</div>
    </div>
    <hr />
	<div id="title-lista-preco">São Paulo</div>
    <div class="content-lista">
		<div>
			<div id="title-lista">1ª Qualidade em Reais</div>
			<div class="lista"><a href="{{ $url }}/SAOPAULO_Q1_REAIS_12_FOB.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 12% para São Paulo (1a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 12% - FOB</a></div>
			<div class="lista"><a href="{{ $url }}/SAOPAULO_Q1_REAIS_18_FOB.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 18% para São Paulo (1a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 18% - FOB</a></div>
		</div>
		<div>
			<div id="title-lista">2ª Qualidade em Reais</div>
			<div class="lista"><a href="{{ $url }}/SAOPAULO_Q2_REAIS_12_FOB.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 12% para São Paulo (2a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 12% - FOB</a></div>
			<div class="lista"><a href="{{ $url }}/SAOPAULO_Q2_REAIS_18_FOB.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 18% para São Paulo (2a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 18% - FOB</a></div>
		</div>
    </div>
    <div class="content-lista">
		<div>
			<div class="lista"><a href="{{ $url }}/SAOPAULO_Q1_REAIS_12_CIF.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 12% para São Paulo (1a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 12% - CIF</a></div>
			<div class="lista"><a href="{{ $url }}/SAOPAULO_Q1_REAIS_18_CIF.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 18% para São Paulo (1a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 18% - CIF</a></div>
		</div>
		<div>
			<div class="lista"><a href="{{ $url }}/SAOPAULO_Q2_REAIS_12_CIF.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 12% para São Paulo (2a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 12% - CIF</a></div>
			<div class="lista"><a href="{{ $url }}/SAOPAULO_Q2_REAIS_18_CIF.pdf" title="Entre aqui para acesso a tabela de preço em Reais com Alíquota de 18% para São Paulo (2a.Qualidade) em Pdf" target="_blanck">Tabela de preço - Alíquota 18% - CIF</a></div>
		</div>
    </div>
</div>
@endsection
