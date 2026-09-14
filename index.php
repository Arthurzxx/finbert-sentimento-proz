<?php
$resultado = '';
$erro = '';

// Função simples para carregar variáveis de ambiente do arquivo .env
function carregarEnv($caminho) {
    if (!file_exists($caminho)) {
        return false;
    }
    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        if (strpos(trim($linha), '#') === 0) continue;
        list($chave, $valor) = explode('=', $linha, 2);
        $_ENV[trim($chave)] = trim($valor);
    }
    return true;
}

// Processa o envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['texto'])) {
    $texto = trim($_POST['texto']);

    if (!empty($texto)) {
        carregarEnv(__DIR__ . '/.env');
        $token = $_ENV['HF_TOKEN'] ?? '';

        if (empty($token)) {
            $erro = "Token não encontrado no arquivo .env!";
        } else {
            $url = 'https://router.huggingface.co/hf-inference/models/ProsusAI/finbert';
            $payload = json_encode(['inputs' => $texto]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $erro = "Erro na requisição cURL: " . $curlError;
            } elseif ($httpCode !== 200) {
                $erro = "Erro na API (HTTP $httpCode): " . $response;
            } else {
                $dados = json_decode($response, true);

                // A resposta da API do FinBERT vem como uma lista de listas de rótulos/scores
                if (is_array($dados) && isset($dados[0]) && is_array($dados[0])) {
                    $previsoes = $dados[0];
                    
                    // Ordena pelo maior score
                    usort($previsoes, function ($a, $b) {
                        return $b['score'] <=> $a['score'];
                    });

                    $maiorScore = $previsoes[0];
                    $label = $maiorScore['label'];
                    $porcentagem = round($maiorScore['score'] * 100);

                    $resultado = "Sentimento: <strong>{$label}</strong> ({$porcentagem}%)";
                } else {
                    $erro = "Resposta em formato inesperado da API.";
                }
            }
        }
    } else {
        $erro = "Por favor, digite um texto para analisar.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Análise de Sentimento Financeiro (FinBERT)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; max-width: 600px; }
        textarea { width: 100%; height: 100px; margin-bottom: 10px; padding: 8px; }
        button { padding: 10px 20px; cursor: pointer; }
        .resultado { margin-top: 20px; padding: 15px; background-color: #e2f0d9; border: 1px solid #b2d8a3; }
        .erro { margin-top: 20px; padding: 15px; background-color: #fce4d6; border: 1px solid #f4b084; color: #c00000; }
    </style>
</head>
<body>

    <h2>Análise de Sentimento (FinBERT)</h2>

    <form method="POST" action="">
        <label for="texto">Digite a frase em inglês:</label><br><br>
        <textarea name="texto" id="texto" placeholder="Ex: Company reported a record profit in Q3."><?= htmlspecialchars($_POST['texto'] ?? '') ?></textarea><br>
        <button type="submit">Enviar</button>
    </form>

    <?php if ($resultado): ?>
        <div class="resultado">
            <?= $resultado ?>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="erro">
            <?= $erro ?>
        </div>
    <?php endif; ?>

</body>
</html>