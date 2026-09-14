# Análise de Sentimento Financeiro com FinBERT

## Integrantes
- Arthur e João Gabriel

## Objetivo
Aplicação em PHP que consome a API do Hugging Face para analisar o sentimento
(positivo, negativo ou neutro) de frases relacionadas ao mercado financeiro,
utilizando o modelo FinBERT.

## API / Modelo utilizado
- **Modelo:** [ProsusAI/finbert](https://huggingface.co/ProsusAI/finbert)
- **Tarefa:** Text Classification (classificação de texto)
- **Entrada:** um texto em inglês
- **Saída:** rótulo de sentimento (`positive`, `negative` ou `neutral`) com o
  respectivo score de confiança

O FinBERT é uma versão do BERT re-treinada especificamente em textos
financeiros (Financial PhraseBank), o que o torna mais preciso do que um
modelo de sentimento genérico para esse tipo de linguagem.

## Como funciona
1. O usuário digita uma frase em inglês no formulário.
2. O PHP monta uma requisição POST para a API do Hugging Face, enviando o
   texto em formato JSON.
3. A API retorna as probabilidades para cada rótulo (positive/negative/neutral).
4. O PHP identifica o rótulo com maior probabilidade e exibe o resultado na tela.

## Como executar
1. Clone este repositório:
```bash
   git clone https://github.com/Arthurzxx/finbert-sentimento-proz.git
```
2. Crie um arquivo `.env` na raiz do projeto com o seguinte conteúdo,
   substituindo pelo seu token da Hugging Face:
