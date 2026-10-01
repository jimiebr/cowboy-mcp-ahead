# Notas do Cowboy Ahead

## 0.3.1 — 2026-10-01

Estado: implantado e validado pelo MCP em vinil-eu-org-joneswasmer.wasmer.app.

- Corrigido rename entre volumes na Wasmer: copia os arquivos já validados e conclui com rename no volume de plugins, limpando staging em sucesso/falha.
- Instalado ZIP público de asset GitHub com SHA-256 conferido: Ahead Installer Probe inativo, alteração 18. Repetição bloqueada por pasta existente. Também confirmados pelo MCP: confirmação obrigatória, rejeição de URL privada e hashes malformado/incorreto, dry run sem instalação.
- CI em PHP 8.0/8.3: lint, 7 regressões e 23 verificações do instalador com ZIPs reais e WordPress/HTTP simulados, [execução aprovada](https://github.com/jimiebr/cowboy-mcp-ahead/actions/runs/36918889865). Mudança final da versão no bootstrap verificada pela escrita PHP do MCP.
- Ahead 0.3.1 ativo, upstream preservado inativo; catálogo 132 ferramentas; diagnóstico 13 pass/0 fail/1 warn (novos clientes fechados), HTTP inicial 200. Duas falhas de transporte precederam download bem-sucedido; não há retry automático. Não testadas todas as ferramentas.
- Snapshot anterior 0.2.0, intermediário 0.3.0, aplicado e SHA-256 no workspace: outputs/cowboy-ahead/migration-0.3.0/. Checkpoint 2, escritas 10–15. Recuperação: restaurar bootstrap anterior primeiro, depois instalador/tools; classe nova deixa de ser carregada. Não desinstalar nem restaurar banco apenas para reverter código.
- Fixture mantida inativa; nenhuma mudança editorial. Repositórios privados, atualização de plugins existentes por ZIP e autoativação não suportados. A melhoria de mídia corrompida continua pendente.

## 0.3.0 — 2026-10-01

Estado: pré-lançamento publicado e aplicado; CI passou, mas a instalação real falhou no rename entre volumes. Substituído por 0.3.1; tag e pacote preservados.

- Motivo: o instalador WordPress.org não aceita ZIPs públicos do GitHub.
- Nova ferramenta `wp_install_plugin_from_url`, com URL HTTPS pública, SHA-256 obrigatório e `confirm: true` no modo seguro; instalação inativa, sem substituição de plugins existentes.
- Download limitado a 20 MiB, extração a 100 MiB e 2000 entradas. Destinos dos redirecionamentos revalidados, TLS verificado, staging temporário fora de uploads, paths perigosos/links especiais rejeitados, cabeçalho/requisitos/sintaxe PHP verificados.
- Histórico de desfazer reutiliza o instalador existente. Autoproteção inclui Ahead e upstream.
- Arquivos de execução: cowboy-mcp.php, includes/class-mcp-installer.php, includes/class-mcp-url-installer.php, includes/tools/core/plugins.php. Testes: tests/installer-url.php e workflow validate.yml.
- Limites: somente downloads públicos sem credenciais; não atualiza ZIPs já instalados; não ativa automaticamente; ZIP deve ter uma única pasta e um cabeçalho principal. Sintaxe/requisitos não garantem funcionamento do código de terceiros.
- Recuperação: fontes anteriores em outputs/cowboy-ahead/migration-0.3.0/before/ no workspace; restaurar primeiro bootstrap anterior, depois demais fontes, mantendo endpoint e opções. Não desinstalar o plugin nem restaurar banco para simples reversão de código.
- Nenhuma alteração editorial prevista. A falha conhecida de PNG corrompido permanece pendente.

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
