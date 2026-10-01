# Manutenção do Cowboy Ahead

## Remotes

- origin: https://github.com/jimiebr/cowboy-mcp-ahead.git
- upstream: https://github.com/februality/cowboy-mcp.git

## Processo de atualização

1. git fetch upstream --tags
2. Criar branch de integração a partir da versão Ahead atual.
3. Comparar commits e arquivos upstream com a base indicada no manifesto.
4. Fazer merge da versão escolhida sem reset --hard ou force push.
5. Revisar conflitos e mudanças sem conflitos que afetem mídia, arquivos, autenticação e permissões. Se upstream corrigir o mesmo problema, avaliar remover nosso ajuste redundante.
6. Preservar identidade Ahead, Update URI, compatibilidade e GPL.
7. Executar lint PHP e php tests/regression.php. Validar em WordPress de teste upload URL/base64, imagem destacada, listagem e autenticação. Não executar exclusões reais de conteúdo do usuário.
8. Atualizar notas, versão e manifesto com hashes e testes realmente realizados.
9. Publicar pacote e somente então planejar a atualização do site.

## Recuperação

Guardar backup anterior e banco. Para reverter a migração: desativar Ahead e reativar o original compatível, sem ambos ativos. Não desinstalar durante a recuperação; dados e classes são compartilhados. Restaurar arquivos ou banco apenas se necessário e sem sobrescrever alterações posteriores.

## Distribuição

O diretório de instalação é cowboy-mcp-ahead. Pacotes devem excluir .git, node_modules, testes e arquivos locais. Atualizações são manuais por versões revisadas; acompanhar upstream não instala automaticamente suas mudanças.
