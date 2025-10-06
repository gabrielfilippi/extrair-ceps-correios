<?php
/**
 * Interface Web para buscar CEPs no site dos Correios
 * Acesse via navegador: http://localhost/extrair-ceps-correios/
 */

session_start();

// Configurações
define('LOG_FILE', 'logs/debug.log');
define('RESULTADOS_DIR', 'resultados');

// Criar diretórios necessários
if (!is_dir('logs')) mkdir('logs', 0755, true);
if (!is_dir(RESULTADOS_DIR)) mkdir(RESULTADOS_DIR, 0755, true);

// Função de log centralizada
function logDebug($mensagem, $dados = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $mensagem";
    if ($dados !== null) {
        $logEntry .= " | Dados: " . (is_string($dados) ? $dados : json_encode($dados, JSON_UNESCAPED_UNICODE));
    }
    $logEntry .= "\n";
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

// Função para normalizar nomes de arquivos
function normalizarNomeArquivo($texto) {
    // Remove acentos
    $texto = str_replace(
        ['á', 'à', 'ã', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'õ', 'ô', 'ö', 'ú', 'ù', 'û', 'ü', 'ç', 'ñ'],
        ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'n'],
        strtolower($texto)
    );
    // Remove caracteres especiais e substitui espaços por underscore
    $texto = preg_replace('/[^a-z0-9]/', '_', $texto);
    // Remove underscores múltiplos
    $texto = preg_replace('/_+/', '_', $texto);
    // Remove underscores do início e fim
    return trim($texto, '_');
}

// Função para criar estrutura de pastas
function criarEstruturaPastas($estado, $cidade) {
    $estadoNorm = normalizarNomeArquivo($estado);
    $cidadeNorm = normalizarNomeArquivo($cidade);
    
    $caminho = RESULTADOS_DIR . '/' . $estadoNorm . '/' . $cidadeNorm;
    if (!is_dir($caminho)) {
        mkdir($caminho, 0755, true);
    }
    return $caminho;
}

// Processar requisições AJAX
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'carregar_captcha') {
        echo json_encode(carregarCaptcha());
        exit;
    }
    
    if ($_GET['action'] === 'buscar_ceps') {
        echo json_encode(buscarCeps());
        exit;
    }
}

// ==========================================
// FUNÇÕES DO BACKEND
// ==========================================

function carregarCaptcha() {
    $baseUrl = 'https://buscacepinter.correios.com.br';
    $cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'correios_' . session_id();
    
    // Salvar cookieFile na sessão
    $_SESSION['cookieFile'] = $cookieFile;
    
    // 1. Carregar página inicial
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/app/logradouro_bairro/index.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    curl_close($ch);
    
    // 2. Baixar CAPTCHA
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/core/securimage/securimage_show.php?' . time(),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_REFERER => $baseUrl . '/app/logradouro_bairro/index.php',
    ]);
    
    $imagemData = curl_exec($ch);
    curl_close($ch);
    
    if ($imagemData && strlen($imagemData) > 100) {
        // Converter para base64 para mostrar no navegador
        $base64 = base64_encode($imagemData);
        return [
            'sucesso' => true,
            'imagem' => 'data:image/png;base64,' . $base64
        ];
    }
    
    return ['sucesso' => false, 'erro' => 'Erro ao carregar CAPTCHA'];
}

function buscarCeps() {
    $baseUrl = 'https://buscacepinter.correios.com.br';
    $cookieFile = $_SESSION['cookieFile'] ?? '';
    
    if (!$cookieFile || !file_exists($cookieFile)) {
        return ['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue o CAPTCHA.'];
    }
    
    $uf = $_POST['uf'] ?? '';
    $localidade = $_POST['localidade'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $captcha = $_POST['captcha'] ?? '';
    
    if (empty($uf) || empty($localidade) || empty($bairro) || empty($captcha)) {
        return ['sucesso' => false, 'erro' => 'Preencha todos os campos'];
    }
    
    // Enviar formulário
    $postData = [
        'uf' => $uf,
        'localidade' => $localidade,
        'bairro' => $bairro,
        'captcha' => $captcha,
        'pagina' => '/app/logradouro_bairro/index.php',
        'letraLocalidade' => '',
        'letraBairro' => '',
        'cepaux' => '',
        'mensagem_alerta' => '',
        // Alguns backends do Correios exigem a presença do botão
        'btn_pesquisar' => 'Buscar',
        // Campo "acao" pode ser checado em alguns fluxos
        'acao' => 'buscar'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/app/logradouro_bairro/index.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_REFERER => $baseUrl . '/app/logradouro_bairro/index.php',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Origin: https://buscacepinter.correios.com.br',
        ],
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log da resposta HTML
    logDebug('Resposta HTML recebida', 'Tamanho: ' . strlen($html) . ' bytes');
    
    // Lista expandida de mensagens de erro de CAPTCHA
    $errosCaptcha = [
        'caracteres não correspondem',
        'captcha incorreto',
        'código de segurança inválido',
        'incorrect security code',
        'captcha inválido',
        'campo captcha',
        'digite o código',
    ];
    
    $htmlLower = mb_strtolower($html);
    foreach ($errosCaptcha as $erro) {
        if (stripos($htmlLower, $erro) !== false) {
            return [
                'sucesso' => false, 
                'erro' => 'CAPTCHA incorreto. Tente novamente.',
                'debug' => 'Erro detectado: ' . $erro
            ];
        }
    }
    
    // Tentar extrair diretamente do HTML (em casos onde já vem renderizado)
    $ceps = extrairCeps($html);

    // Caso a tabela seja carregada por AJAX, chamar o endpoint oficial
    if (count($ceps) === 0) {
        $ajaxResultado = carregarResultadosAjax($cookieFile, [
            'uf' => $uf,
            'localidade' => $localidade,
            'bairro' => $bairro,
            'captcha' => $captcha,
            'pagina' => '/app/logradouro_bairro/index.php',
            'letraLocalidade' => '',
            'letraBairro' => '',
            'cepaux' => '',
            'mensagem_alerta' => '',
        ]);

        // Log da resposta AJAX
        logDebug('Resposta AJAX recebida', $ajaxResultado);

        // Se vier JSON com 'dados', extraímos diretamente e paginamos
        if (is_array($ajaxResultado) && isset($ajaxResultado['erro']) && $ajaxResultado['erro'] === false) {
            $totalEsperado = isset($ajaxResultado['total']) ? (int)$ajaxResultado['total'] : 0;
            $dados = isset($ajaxResultado['dados']) && is_array($ajaxResultado['dados']) ? $ajaxResultado['dados'] : [];
            $ceps = extrairCepsDeDados($dados);

            // Paginação conforme JS oficial: usar inicio/final e capt
            $inicio = 1;
            $final = count($dados); // normalmente 50
            $coletados = $final;
            $capt = 1; // após primeira busca, JS seta conteudocapt = 1
            $passo = 50;
            $pagina = 1;

            while ($coletados < $totalEsperado) {
                $inicio = $final + 1;
                $final = min($inicio + $passo - 1, $totalEsperado);
                $pagina++;

                $respPaginada = carregarResultadosAjax($cookieFile, [
                    'uf' => $uf,
                    'localidade' => $localidade,
                    'bairro' => $bairro,
                    'captcha' => $captcha,
                    'pagina' => '/app/logradouro_bairro/index.php',
                    'inicio' => $inicio,
                    'final' => $final,
                    'capt' => $capt,
                ]);

                logDebug("Página AJAX - Início: $inicio, Final: $final", $respPaginada);

                if (!(is_array($respPaginada) && isset($respPaginada['erro']) && $respPaginada['erro'] === false && isset($respPaginada['dados']) && is_array($respPaginada['dados']))) {
                    break; // não avançou
                }

                $lista = $respPaginada['dados'];
                $novos = count($lista);
                if ($novos === 0) {
                    break; // fim
                }

                $ceps = array_merge($ceps, extrairCepsDeDados($lista));
                $coletados += $novos;
            }
        } else {
            // Normaliza qualquer string/HTML devolvido
            $ajaxHtml = is_string($ajaxResultado) ? $ajaxResultado : '';
            if ($ajaxHtml !== '') {
                $ceps = extrairCeps($ajaxHtml);
            }
        }
    }
    
    if (count($ceps) === 0) {
        // Verificar se a tabela existe mas está vazia
        if (stripos($html, 'resultado-DNEC') !== false) {
            return [
                'sucesso' => false, 
                'erro' => 'Bairro não encontrado ou sem CEPs cadastrados',
                'debug' => 'Tabela existe mas vazia'
            ];
        }
        
        return [
            'sucesso' => false, 
            'erro' => 'Nenhum resultado. Verifique os dados ou o CAPTCHA. HTML salvo em debug_resposta.html',
            'debug' => 'HTTP: ' . $httpCode . ' | Tamanho HTML: ' . strlen($html)
        ];
    }
    
    // Criar estrutura de pastas e salvar arquivo
    $caminhoPasta = criarEstruturaPastas($uf, $localidade);
    $nomeArquivo = gerarNomeArquivo($uf, $localidade, $bairro);
    $caminhoCompleto = $caminhoPasta . '/' . $nomeArquivo;
    $conteudo = implode("\n", $ceps);
    file_put_contents($caminhoCompleto, $conteudo);
    
    logDebug("Arquivo salvo", "Caminho: $caminhoCompleto | CEPs: " . count($ceps));
    
    return [
        'sucesso' => true,
        'total' => count($ceps),
        'ceps' => $ceps,
        'arquivo' => $nomeArquivo
    ];
}

function extrairCepsDeDados($dados) {
    $lista = [];
    foreach ($dados as $item) {
        if (is_array($item) && isset($item['cep'])) {
            $cep = preg_replace('/[^0-9]/', '', (string)$item['cep']);
            if ($cep !== '' && preg_match('/^\d{8}$/', $cep)) {
                $lista[] = $cep;
            }
        }
    }
    return $lista;
}

function carregarResultadosAjax($cookieFile, $params) {
    $baseUrl = 'https://buscacepinter.correios.com.br';
    $endpoint = '/app/logradouro_bairro/carrega-logradouro-bairro.php';

    // Monta payload padrão observado no site (página inicial = 1)
    $payload = [
        'uf' => $params['uf'] ?? '',
        'localidade' => $params['localidade'] ?? '',
        'bairro' => $params['bairro'] ?? '',
        'captcha' => $params['captcha'] ?? '',
        'pagina' => $params['pagina'] ?? '/app/logradouro_bairro/index.php',
        'offset' => isset($params['offset']) ? (int)$params['offset'] : 0,
        'page' => isset($params['page']) ? (int)$params['page'] : 1,
        'acao' => 'buscar',
    ];

    // Parametrização conforme JS: se existir, envia inicio/final/capt
    if (isset($params['inicio'])) $payload['inicio'] = (int)$params['inicio'];
    if (isset($params['final'])) $payload['final'] = (int)$params['final'];
    if (isset($params['capt'])) $payload['capt'] = (int)$params['capt'];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_REFERER => $baseUrl . '/app/logradouro_bairro/index.php',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Accept: */*',
            'X-Requested-With: XMLHttpRequest',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Origin: https://buscacepinter.correios.com.br',
        ],
    ]);

    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Tenta decodificar como JSON; se falhar, retorna string para fallback
    $json = json_decode($resp, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        return $json;
    }
    return $resp;
}

function extrairCeps($html) {
    $ceps = [];
    
    // Normaliza encoding para parsing
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    
    $xpath = new DOMXPath($dom);

    // Estratégia 1: tabela oficial por id
    $linhas = $xpath->query("//table[@id='resultado-DNEC']//tbody/tr");
    foreach ($linhas as $linha) {
        $colunas = $xpath->query(".//td", $linha);
        if ($colunas->length >= 4) {
            $cep = trim($colunas->item($colunas->length - 1)->textContent);
            $cep = preg_replace('/[^0-9]/', '', $cep);
            if ($cep !== '') {
                $ceps[] = $cep;
            }
        }
    }

    // Estratégia 2: qualquer tabela que tenha o cabeçalho CEP
    if (count($ceps) === 0) {
        $tabelas = $xpath->query("//table[.//th[contains(translate(normalize-space(.), 'cep', 'CEP'), 'CEP')] or .//td[contains(translate(normalize-space(.), 'cep', 'CEP'), 'CEP')]]//tbody/tr");
        foreach ($tabelas as $linha) {
            $colunas = $xpath->query(".//td", $linha);
            if ($colunas->length > 0) {
                $textoUltima = trim($colunas->item($colunas->length - 1)->textContent);
                if (preg_match('/\\b(\\d{5})-?(\\d{3})\\b/u', $textoUltima, $m)) {
                    $ceps[] = $m[1] . $m[2];
                }
            }
        }
    }

    // Estratégia 3: regex global como fallback
    if (count($ceps) === 0) {
        $matches = [];
        if (preg_match_all('/\\b(\\d{5})-?(\\d{3})\\b/u', $html, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $ceps[] = $matches[1][$i] . $matches[2][$i];
            }
        }
    }

    // Deduplica e normaliza
    $ceps = array_values(array_unique(array_filter($ceps, function ($c) {
        return preg_match('/^\\d{8}$/', $c);
    })));
    
    return $ceps;
}

function gerarNomeArquivo($uf, $localidade, $bairro) {
    $ufNorm = normalizarNomeArquivo($uf);
    $localidadeNorm = normalizarNomeArquivo($localidade);
    $bairroNorm = normalizarNomeArquivo($bairro);
    $data = date('Y-m-d');
    return "{$ufNorm}-{$localidadeNorm}-{$bairroNorm}-{$data}.txt";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Busca CEP - Correios</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>🔍 Busca CEP - Correios</h1>
        <p class="subtitle">Extraia CEPs por bairro de forma simples</p>
        
        <form id="buscaForm">
            <div class="form-group">
                <label for="uf">Estado (UF):</label>
                <select id="uf" name="uf" required>
                    <option value="">Selecione...</option>
                    <option value="AC">AC - Acre</option>
                    <option value="AL">AL - Alagoas</option>
                    <option value="AP">AP - Amapá</option>
                    <option value="AM">AM - Amazonas</option>
                    <option value="BA">BA - Bahia</option>
                    <option value="CE">CE - Ceará</option>
                    <option value="DF">DF - Distrito Federal</option>
                    <option value="ES">ES - Espírito Santo</option>
                    <option value="GO">GO - Goiás</option>
                    <option value="MA">MA - Maranhão</option>
                    <option value="MT">MT - Mato Grosso</option>
                    <option value="MS">MS - Mato Grosso do Sul</option>
                    <option value="MG">MG - Minas Gerais</option>
                    <option value="PA">PA - Pará</option>
                    <option value="PB">PB - Paraíba</option>
                    <option value="PR">PR - Paraná</option>
                    <option value="PE">PE - Pernambuco</option>
                    <option value="PI">PI - Piauí</option>
                    <option value="RJ">RJ - Rio de Janeiro</option>
                    <option value="RN">RN - Rio Grande do Norte</option>
                    <option value="RS">RS - Rio Grande do Sul</option>
                    <option value="RO">RO - Rondônia</option>
                    <option value="RR">RR - Roraima</option>
                    <option value="SC">SC - Santa Catarina</option>
                    <option value="SP">SP - São Paulo</option>
                    <option value="SE">SE - Sergipe</option>
                    <option value="TO">TO - Tocantins</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="localidade">Cidade:</label>
                <input type="text" id="localidade" name="localidade" placeholder="Ex: Joinville" required>
            </div>
            
            <div class="form-group">
                <label for="bairro">Bairro:</label>
                <input type="text" id="bairro" name="bairro" placeholder="Ex: Costa e Silva" required>
            </div>
            
            <button type="button" class="btn btn-primary" id="carregarCaptchaBtn">
                Carregar CAPTCHA
            </button>
        </form>
        
        <div id="captchaBox" class="captcha-box">
            <img id="captchaImage" src="" alt="CAPTCHA">
            <div class="form-group">
                <label for="captcha">Digite o texto do CAPTCHA:</label>
                <input type="text" id="captcha" name="captcha" placeholder="Digite aqui..." autocomplete="off">
            </div>
            <button type="button" class="btn btn-primary" id="buscarBtn">
                Buscar CEPs
            </button>
            
            <div id="alertBox" class="alert"></div>
        </div>
        
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Processando...</p>
        </div>
        
        <div id="resultado" class="resultado">
            <h3>✅ CEPs Encontrados</h3>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number" id="totalCeps">0</div>
                    <div class="stat-label">CEPs</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" id="nomeArquivo">-</div>
                    <div class="stat-label">Arquivo</div>
                </div>
            </div>
            <div class="cep-lista" id="cepLista"></div>
            <button type="button" class="btn btn-primary download-btn" id="downloadBtn">
                📥 Baixar Arquivo TXT
            </button>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>