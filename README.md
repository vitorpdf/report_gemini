# report_gemini_data — Plugin de Relatório Moodle com IA Gemini

Plugin de relatório para Moodle que consulta a API do **Google Gemini** com prompts pré-definidos e exibe os resultados em tabelas estruturadas.

## Consultas disponíveis

| Propt | Dados retornados |
|---|---|
| **Continente e suas populações** | Nome, continente e população de todos os países do mundo |
| **Estados do Brasil e data de fundação** | Nome, sigla, capital, região e data de fundação dos 27 UFs |
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

### Opção B — Instalação manual

1. Copie a pasta `report_gemini_data` para:
   ```
   /caminho/do/moodle/report/gemini_data/
   ```
2. Acesse o Moodle como administrador → **Administração do site → Notificações**.
3. O Moodle detectará o novo plugin e executará a instalação automaticamente.

---

## Configuração

1. Acesse **Administração do site → Plugins → Relatórios → Relatórios de Dados Gemini**.
2. Insira sua **Chave de API do Gemini**.
3. Ajuste **temperatura** e **máximo de tokens** se desejar.
4. Salve as alterações.

---

## Como usar

1. Acesse **Administração do site → Relatórios → Relatórios de Dados Gemini**  
   (ou navegue diretamente para `/report/gemini_data/index.php`).
2. Selecione um dos prompts pré-definidos no menu suspenso.
3. Clique em **Gerar Relatório**.
4. Aguarde a consulta à IA — a tabela será exibida automaticamente.
5. Use o botão **Limpar** para resetar.

---

## Estrutura do plugin

```
report/gemini_data/
├── index.php                 # Página principal do relatório
├── ajax.php                  # Endpoint AJAX → chama a API Gemini
├── version.php               # Metadados do plugin
├── settings.php              # Configurações do administrador
├── styles.css                # Estilos da interface
├── amd/
│   ├── src/report.js         # Módulo AMD (fonte)
│   └── build/report.min.js   # Módulo AMD (build)
├── db/
│   └── access.php            # Capabilities
└── lang/
    ├── en/report_gemini_data.php    # Strings em inglês
    └── pt_br/report_gemini_data.php # Strings em português (BR)
```

---

## Segurança

- Toda requisição exige login ativo no Moodle.
- O `sesskey` é validado em cada chamada AJAX (proteção contra CSRF).
- A capability `report/gemini_data:view` controla o acesso.
- A chave de API é armazenada de forma segura na configuração do plugin.

