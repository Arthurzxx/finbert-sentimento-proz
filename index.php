<?php
$resultado = '';
$erro = '';
$labelClasse = '';

// Carrega as variáveis do arquivo .env para dentro de $_ENV
function carregarEnv($caminho) {
    if (!file_exists($caminho)) return false;

    foreach (file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        if (str_starts_with(trim($linha), '#')) continue;
        [$chave, $valor] = explode('=', $linha, 2);
        $_ENV[trim($chave)] = trim($valor);
    }
    return true;
}

// Envia o texto para a API do FinBERT e retorna o resultado já tratado
function analisarSentimento($texto, $token) {
    $ch = curl_init('https://router.huggingface.co/hf-inference/models/ProsusAI/finbert');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['inputs' => $texto]),
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
        return ['erro' => "Erro na requisição cURL: $curlError"];
    }
    if ($httpCode !== 200) {
        return ['erro' => "Erro na API (HTTP $httpCode): $response"];
    }

    $dados = json_decode($response, true);
    if (!isset($dados[0]) || !is_array($dados[0])) {
        return ['erro' => "Resposta em formato inesperado da API."];
    }

    $previsoes = $dados[0];
    usort($previsoes, fn($a, $b) => $b['score'] <=> $a['score']);
    $melhor = $previsoes[0];

    return [
        'label' => $melhor['label'],
        'porcentagem' => round($melhor['score'] * 100)
    ];
}

// Processa o envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['texto'] ?? ''))) {
    $texto = trim($_POST['texto']);

    carregarEnv(__DIR__ . '/.env');
    $token = $_ENV['HF_TOKEN'] ?? '';

    if (empty($token)) {
        $erro = "Token não encontrado no arquivo .env!";
    } else {
        $saida = analisarSentimento($texto, $token);

        if (isset($saida['erro'])) {
            $erro = $saida['erro'];
        } else {
            $labelClasse = strtolower($saida['label']);
            $resultado = "Sentimento: <strong>{$saida['label']}</strong> ({$saida['porcentagem']}%)";
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $erro = "Por favor, digite um texto para analisar.";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise de Sentimento Financeiro (FinBERT)</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: #ffffff;
            width: 100%;
            max-width: 560px;
            border-radius: 16px;
            padding: 36px 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        }

        h2 { margin: 0 0 4px; color: #1e3c72; font-size: 24px; }
        .subtitulo { color: #6b7280; font-size: 14px; margin-bottom: 28px; }
        label { display: block; font-weight: 600; font-size: 14px; color: #374151; margin-bottom: 8px; }

        textarea {
            width: 100%;
            height: 110px;
            padding: 14px;
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        textarea:focus {
            outline: none;
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.15);
        }

        button {
            margin-top: 16px;
            width: 100%;
            padding: 13px;
            background: #2a5298;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        button:hover { background: #1e3c72; }
        button:active { transform: scale(0.98); }

        .resultado, .erro {
            margin-top: 22px;
            padding: 16px 18px;
            border-radius: 10px;
            font-size: 15px;
            border-left: 5px solid;
        }

        .erro { background-color: #fef2f2; border-color: #dc2626; color: #991b1b; }
        .resultado.positive { background-color: #f0fdf4; border-color: #16a34a; color: #166534; }
        .resultado.negative { background-color: #fef2f2; border-color: #dc2626; color: #991b1b; }
        .resultado.neutral   { background-color: #f1f5f9; border-color: #64748b; color: #334155; }
    </style>
</head>
<body>

    <div class="container">
        <h2>Análise de Sentimento Financeiro</h2>
        <p class="subtitulo">Powered by FinBERT (Hugging Face)</p>

        <form method="POST" action="">
            <label for="texto">Digite a frase em inglês:</label>
            <textarea name="texto" id="texto" placeholder="Ex: The company reported a record profit in Q3."><?= htmlspecialchars($_POST['texto'] ?? '') ?></textarea>
            <button type="submit">Analisar sentimento</button>
        </form>

        <?php if ($resultado): ?>
            <div class="resultado <?= htmlspecialchars($labelClasse) ?>"><?= $resultado ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>
    </div>

</body>
</html>
