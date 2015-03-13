<?php
//montagem do nome do arquivo local {{{
/* Dados dispon�veis para montar o nome do arquivo:
	Cargo
	Ano
	�rg�o
	Institui��o
	N�vel
*/
$nomeLocalConfig = array();
$nomeLocalConfig[0] = 'ano';
$nomeLocalConfig[1] = 'banca';
$nomeLocalConfig[2] = 'orgao';
$nomeLocalConfig[3] = 'cargo';
$separador = " - ";
//}}}
//biblioteca necess�ria {{{
require('simple_html_dom.php');
//}}}
//verifica se � http ou console {{{
if (isset($_SERVER["SERVER_SOFTWARE"])) {
	die("ERRO: Este script � somente para execu��o via console (Interface de Linha de Comando).");
}
//}}}
//configuracoes {{{
define('VERSAO', '0.5');
define('URL_PCI', "http://www.pciconcursos.com.br");
define('URL_PROVAS_PCI', URL_PCI."/provas");
//}}}
//dados {{
print("PCILeecher - v. 0.3 - by From Isengard - from.isengard@gmail.com\n");
print("PCILeecher - v. ".VERSAO." - by Hugo Tacito - hugotacito@gmail.com\n");
print("Participe da TI-Masters: timasters@yahoogroups.com\n");
print("Entre com os dados da pesquisa...\n");
print("Busca: ");
$busca = readcmd();
if(!file_exists($busca))
	mkdir($busca);
//}}}
//busca {{{
print("Buscando em ".URL_PROVAS_PCI." por '$busca' ...\n");

$pagina = 1;
while(true)
{
	print("Buscando na p�gina $pagina ...\n");

	//formata a string de busca
	$buscaFormatada = urlencode(str_replace(' ', '-', $busca));
	$urlBusca = URL_PROVAS_PCI."/".$buscaFormatada;
	if (strlen($pagina) > 0) {
		$urlBusca .= "/$pagina";
	}
	
	//obtem html da p�gina, extrai a lista de provas e conta o n�mero de provas
	$paginaComResultados = obterDadosDeUrl($urlBusca);
	$listaDeProvasDaPagina = extrairListaDeProvas($paginaComResultados);
	$numeroDeProvasDaPagina = count($listaDeProvasDaPagina);
	print("Foram encontradas ".$numeroDeProvasDaPagina." provas!\n");
	
	//Enquanto houver p�ginas com mais de uma prova ele segue baixando
	if($numeroDeProvasDaPagina <= 0)
		break;
	
	//Salva todas as provas de uma determinada lista se ainda houverem provas
	salvarProvasDaPagina($listaDeProvasDaPagina);
	$pagina++;
}
print("Pronto!\n");
print("-- Rip them all down.\n");
?>
<?
//mecanismo que efetivamente salva as provas
function salvarProvasDaPagina($listaDeProvas) 
{
    global $busca;
	
	//pega cada prova da p�gina
    foreach ($listaDeProvas as $i => $dadosDaProva) 
	{
		//descobre a URL da prova e define o nome do arquivo a ser salvo
		$urlNova = extrairUrlNova($dadosDaProva["link"]);
		$nomeDoArquivoLocal = atribuirNomeLocal($dadosDaProva);
		$nomeCompletoDoArquivo = $busca."/".$nomeDoArquivoLocal;

		//mecanismo de resume
		if(file_exists($nomeCompletoDoArquivo))
		{
			print("Arquivo $nomeDoArquivoLocal j� baixado! Pulando ...\n");
			continue;
		}	
		
		//baixa e salva o arquivo
		$f = fopen($nomeCompletoDoArquivo, "w") or die("N�o foi poss�vel criar o arquivo local: $nomeCompletoDoArquivo !\n");
		print("Baixando prova ".($i+1).": $nomeDoArquivoLocal ...\n");
		$zip = obterDadosDeUrl($urlNova);
		fwrite($f, $zip);
		fclose($f);
	}
}

//recupera todas as informa��es sobre provas de uma determinada p�gina
function extrairListaDeProvas($paginaComResultados) 
{
	$html = str_get_html($paginaComResultados);
	$removeCabecalho = false;
	
	//as provas est�o dentro de uma tabela por isso o (tr)
	foreach($html->find('tr') as $prova) 
	{
	
		//retira a primeira linha que contem o cabecalho da tabel
		if($removeCabecalho == false)
		{
			$removeCabecalho = true;
			continue;
		}
		
		//cria um array com todas as informa��es pra baixar a prova e colocar o nome
		$item['cargo'] = $prova->children(0)->plaintext;
		$item['link']  = $prova->children(0)->children(0)->href;
		$item['ano']   = $prova->children(1)->plaintext;
		$item['orgao'] = $prova->children(2)->plaintext;
		$item['banca'] = $prova->children(3)->plaintext;
		$item['nivel'] = $prova->children(4)->plaintext;
		$provas[] = $item;
	}
	return $provas;
}

//atribui o nome ao arquivo que ser� salvo localmente
function atribuirNomeLocal($dadosDaProva)
{
	global $separador, $nomeLocalConfig;
	$arquivo = "";
	
	//adiciona todos os dados dispon�veis pra montar o nome do arquivo, junto com um separador
	/*
	Cargo
	Ano
	�rg�o
	Institui��o
	N�vel
	*/
	foreach($nomeLocalConfig as $dado)
	{
		$arquivo .= utf8_decode($dadosDaProva[$dado]).$separador;
	}
	
	//retira o ultimo separador do nome do arquivo
	$arquivo = substr($arquivo, 0, -3);
	
	//substitui caracteres especiais
	$arquivo = str_replace(array('^','|','?','*','<','\\','"',':','>',',','/'), '-', $arquivo.".zip");
	return $arquivo;
}

//filtra a url que esta localizada na nova pagina
function extrairUrlNova($urlAntiga)
{
	$html = file_get_html($urlAntiga);
	
	//filtra at� a classe css "zip"
	$link = $html->find(".zip",0)->children(0)->children(0)->href;
	return $link;
}

//obtem dados de uma p�gina e simplesmente coloca em uma string
function obterDadosDeUrl($url, $ctx = null) 
{
	$dados = file_get_contents($url, false, $ctx);
	if ($dados === false) 
	{
		throw new Exception("Problema com $url, $php_errormsg");
	}
	return $dados;
}

//From: http://php.net/manual/en/function.fopen.php
function readcmd($length='255')
{
    if (!isset($GLOBALS['StdinPointer']))
	{
        $GLOBALS['StdinPointer']=fopen("php://stdin","r");
    }
    $line=fgets($GLOBALS['StdinPointer'],$length);
    return trim($line);
}

?>
