# report_gemini_data — Plugin de Relatório Moodle com IA Gemini

Plugin de relatório para Moodle que consulta a API do **Google Gemini** com prompts pré-definidos e exibe os resultados em tabelas estruturadas.

## Consultas disponíveis

| Preset | Dados retornados |
|---|---|
| **10 picos mais altos do Brasil** | Ranking, nome, altitude, estado, parque e melhores épocas para subir |

---

## Requisitos

| Requisito | Versão |
|---|---|
| Moodle | 4.0 ou superior |
| PHP | 7.4 ou superior |
| Chave de API Gemini | [Gratuita no Google AI Studio](https://aistudio.google.com/app/apikey) |

---

## Instalação

### Opção A — Upload via interface do Moodle (recomendado)

1. Compacte a pasta `report_gemini_data` em um arquivo ZIP.
2. Acesse **Administração do site → Plugins → Instalar plugins**.
3. Faça o upload do ZIP e siga o assistente.

---

## Configuração

1. Acesse **Administração do site → Plugins → Relatórios → Relatórios de Dados Gemini**.
2. Insira sua **Chave de API do Gemini**.
3. Escolha o **modelo** (padrão: `gemini-2.5-flash`).
4. Ajuste **temperatura** e **máximo de tokens** se desejar.
5. Salve as alterações.

---

## Como usar

1. Navegue diretamente para `/report/gemini_data/index.php`.
2. Selecione um dos prompts pré-definidos no menu suspenso.
3. Clique em **Gerar Relatório**.
4. Aguarde a consulta à IA — a tabela será exibida automaticamente.
5. Use o botão **Limpar** para resetar.

---

## Como funciona o retorno JSON

O plugin usa o recurso `responseSchema` da API Gemini para forçar que a IA retorne dados em um schema JSON predefinido. Isso garante que a resposta seja sempre estruturada e parseável, evitando texto livre ou markdown inesperado.

Exemplo de schema para o preset de países:

```json
{
  "type": "object",
  "properties": {
    "items": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "country":    { "type": "string" },
          "continent":  { "type": "string" },
          "population": { "type": "integer" }
        }
      }
    }
  }
}
```

---

## Segurança

- Toda requisição exige login ativo no Moodle.
- O `sesskey` é validado em cada chamada AJAX (proteção contra CSRF).
- A capability `report/gemini_data:view` controla o acesso.
- A chave de API é armazenada de forma segura na configuração do plugin.

