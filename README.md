# Cowboy Ahead

Fork independente de [Cowboy MCP](https://github.com/februality/cowboy-mcp), mantido por jimiebr. Correções rápidas, melhorias próprias e acompanhamento do upstream.

Versão: **0.3.1**, baseada no Cowboy MCP **1.6.9**, commit `9670cd63c1f0f7e0640e24cbbe4701635efaaa30`. Licença GPL-2.0-or-later; créditos e licença originais preservados.

### Instalar ZIP público pelo MCP

Use `wp_install_plugin_from_url` com `url`, `sha256` (hash esperado do ZIP) e `confirm: true`. URLs públicas HTTPS de assets de releases GitHub são suportadas; não envie tokens na URL. O ZIP precisa conter uma pasta de plugin e um arquivo PHP principal com cabeçalho `Plugin Name`. A ferramenta instala **inativo**, sem sobrescrever plugins existentes; ative depois com `wp_activate_plugin` se desejado. `dry_run: true` valida os argumentos e mostra o plano, sem baixar ou validar o conteúdo do ZIP. Não funciona com repositórios privados, nem substitui o próprio Cowboy Ahead. Limites: 20 MiB compactado, 100 MiB descompactado, 2000 entradas.

## Alterações próprias

- Upload por URL e base64 sem depender de wp_tempnam() ausente no contexto REST observado.
- Correção de listagem recursiva com SplFileInfo.
- Nome, versão e Update URI próprios. Atualizações do WordPress.org não devem substituir esta distribuição. Não há atualizador automático GitHub nesta versão.
- Histórico, manifesto SHA-256 e testes de regressão.

## Instalação e migração

O pacote deve conter a pasta cowboy-mcp-ahead/ com cowboy-mcp.php na raiz. Faça backup de arquivos e banco antes da troca. **Não ative junto com Cowboy MCP**: as classes e constantes internas são compartilhadas. Desative o original antes de ativar o Ahead. Não desinstale o original durante a migração: sua rotina de remoção pode apagar dados compartilhados.

Mantemos endpoint /wp-json/cowboy-mcp/v1/endpoint, opções, autenticação e tabelas para compatibilidade. Migração realizada em 2026-10-01; a versão 0.3.1 está instalada e validada nos testes registrados em [DEPLOYMENTS.md](DEPLOYMENTS.md).

## Atualizações do upstream

Consulte [MAINTENANCE.md](MAINTENANCE.md). Nunca sincronize substituindo nossas alterações. Cada atualização passa por comparação, ajustes, regressões e revisão antes de instalação.

## Histórico e documentação

- [Notas das versões](CHANGELOG-AHEAD.md)
- [Manifesto 0.2.0](releases/0.2.0/manifest.json)
- [Manifesto 0.3.1](releases/0.3.1/manifest.json)
- [Instalações e testes no WordPress](DEPLOYMENTS.md)
- [Documentação original preservada](README-UPSTREAM.md)

A documentação original descreve capacidades do upstream; nem todas foram testadas neste fork. Nunca inclua credenciais em commits, issues ou logs.
