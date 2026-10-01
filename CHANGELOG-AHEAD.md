# Notas do Cowboy Ahead

## 0.2.0 — 2026-10-01

Estado: fontes publicadas e testes automatizados aprovados. Instalação no site confirmada em 2026-10-01; ver [DEPLOYMENTS.md](DEPLOYMENTS.md) para validação real e limitações.

- Fork baseado no Cowboy MCP 1.6.9, commit 9670cd63c1f0f7e0640e24cbbe4701635efaaa30.
- Incorporadas as duas correções da versão local 0.1.0: temporários de mídia e listagem recursiva.
- Identidade própria, Update URI e versão independente; créditos e GPL preservados.
- Mantidos endpoint, armazenamento e nomes internos para compatibilidade. Não pode ser ativado simultaneamente com o original.
- Documentação upstream preservada em README-UPSTREAM.md.
- Adicionados testes de regressão e lint PHP em CI. Resultados devem ser conferidos no workflow; sua existência não significa execução bem-sucedida.
- Manifesto em releases/0.2.0/manifest.json. Recuperação descrita em MAINTENANCE.md.

Validação herdada de 0.1.0: upload base64, imagem destacada, listagem recursiva, consultas de post/mídia/plugins, diagnóstico do banco e catálogo Elementor. Não representa teste integral do fork. Nenhuma alteração editorial nesta versão.

## 0.1.0 — 2026-10-01

Correções locais aplicadas ao Cowboy MCP 1.6.9 instalado: upload de mídia e listagem recursiva. Teste editorial confirmado no site: rascunho 9, mídia 10 e imagem destacada 10. O usuário confirmou a imagem na galeria. Não foram testadas todas as 131 ferramentas.

### Histórico de publicação da 0.2.0

Fork criado no GitHub. A primeira tentativa de gravação foi recusada com HTTP 403 (Resource not accessible by integration). O usuário instalou e autorizou o ChatGPT Codex Connector somente para este repositório; a publicação foi então confirmada no commit 24cb999494fae4ced115f23e2f68533b2be649ec. git diff --check passou. A instalação do fork no site não foi realizada.

### Validação automatizada da 0.2.0

[Execução GitHub Actions](https://github.com/jimiebr/cowboy-mcp-ahead/actions/runs/36913173497): aprovada em PHP 8.0 e PHP 8.3.

- Lint de todos os arquivos PHP aprovado.
- Upload base64 e URL chega ao sideload sem wp_tempnam e sem carregar includes administrativos.
- Base64 inválido retorna erro explícito.
- Listagem simples ignora entradas ponto; listagem recursiva retorna arquivo aninhado sem erro SplFileInfo.
- Tentativa de escapar do diretório continua rejeitada.
- Testes isolados usam funções WordPress simuladas e transporte HTTP simulado. Não substituem validação integral em WordPress nem teste de download externo real.
