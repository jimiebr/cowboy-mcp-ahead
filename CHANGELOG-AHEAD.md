# Notas do Cowboy Ahead

## 0.2.0 — 2026-10-01

Estado: fontes preparadas; publicação e CI em andamento. Não instalado como fork no site.

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

Fork criado no GitHub. A primeira tentativa de gravação foi recusada com HTTP 403 (Resource not accessible by integration). O usuário instalou e autorizou o ChatGPT Codex Connector somente para este repositório; a criação de árvore Git foi então confirmada. git diff --check passou. Lint e regressões PHP aguardam o resultado de CI. A instalação do fork no site não foi realizada.
