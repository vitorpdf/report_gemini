<?php

$string['pluginname']        = '01 - Relatórios de Dados Gemini';
$string['gemini_data:view']  = 'Visualizar Relatórios de Dados Gemini';

$string['pagetitle']         = '0 - Relatórios de Dados Gemini';
$string['pageheading']       = 'Relatórios de Dados com IA';
$string['pagedescription']   = 'Clique em <strong>Gerar Relatório</strong> em qualquer consulta abaixo para buscar dados estruturados do Google Gemini IA e visualizá-los em tabela.';

$string['preset_countries']      = 'Países da América do Sul';
$string['preset_countries_desc'] = 'Lista todos os países da america do sul reconhecidos pela ONU com população estimada e continente.';
$string['preset_states']         = 'Estados do Brasil';
$string['preset_states_desc']    = 'Lista os 26 estados brasileiros e o Distrito Federal com sigla, capital, região e data oficial de criação.';
$string['preset_peaks']          = '10 picos mais altos do Brasil';
$string['preset_peaks_desc']     = 'Ranking dos 10 picos mais altos do Brasil com altitude, localização e as melhores épocas do ano para escalar cada um.';

$string['generate']          = 'Gerar Relatório';
$string['generating']        = 'Gerando…';
$string['clearreport']       = '✕ Limpar';
$string['cached']            = 'Armazenado em cache';

$string['errornoapikey']     = 'A chave de API do Gemini não está configurada. Acesse Administração do site → Relatórios → Configurações dos Relatórios de Dados Gemini.';
$string['errorgeneral']      = 'Ocorreu um erro ao contatar o Gemini. Tente novamente.';
$string['errorsesskey']      = 'Chave de sessão inválida. Atualize a página e tente novamente.';
$string['errorinvalidjson']  = 'A IA retornou um formato de resposta inesperado. Tente novamente.';
$string['errornoresults']    = 'Nenhum resultado foi retornado pela IA. Tente novamente.';

$string['settings']              = 'Configurações dos Relatórios de Dados Gemini';
$string['apikey']                = 'Chave de API do Gemini';
$string['apikey_desc']           = 'Sua chave de API do Google Gemini. Obtenha gratuitamente em <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>.';
$string['model']                 = 'Modelo Gemini';
$string['model_desc']            = 'Modelo usado para gerar os dados dos relatórios.';
$string['temperature']           = 'Temperatura';
$string['temperature_desc']      = 'Controla a criatividade das respostas. Valores baixos (0,1) dão respostas mais consistentes.';
$string['maxoutputtokens']       = 'Máximo de tokens';
$string['maxoutputtokens_desc']  = 'Número máximo de tokens na resposta da IA (256–8192).';
