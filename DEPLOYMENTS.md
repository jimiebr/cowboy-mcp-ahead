# Registro de instalações — Cowboy Ahead

## 0.2.0 — 2026-10-01

Estado: instalado, ativo e validado no WordPress vinil.eu.org, hospedado em vinil-eu-org-joneswasmer.wasmer.app.

### Migração

- Checkpoint do banco criado pelo MCP: ID 1, 16 tabelas, 321646 bytes.
- Pacote 0.2.0 instalado pela interface WordPress. A ferramenta wp_install_plugin atual aceita somente slugs do WordPress.org, sem instalação de ZIP externo.
- Cowboy MCP 1.6.9 desativado pelo MCP, alteração 6. Seus arquivos foram preservados para recuperação; não foi desinstalado.
- Cowboy Ahead ativado pela interface WordPress em outra requisição, evitando carregar as classes do original e do fork simultaneamente.
- Endpoint e autenticação OAuth existentes continuaram funcionando, sem novo login.
- Fontes de bootstrap, mídia e arquivos lidas pelo MCP; seus hashes correspondem ao manifesto da versão.
- Elementor, Elementor Pro, WP Wasmer e tema não foram alterados.

### Verificações reais pelo MCP

- wp_list_plugins: Ahead 0.2.0 ativo; original 1.6.9 inativo.
- wp_connection_doctor: 13 verificações aprovadas, nenhuma falha, um aviso. O aviso é a janela de registro de novos clientes fechada; conexões existentes funcionam.
- wp_list_directory: listagem recursiva passou, retornando 18 arquivos de ferramentas core.
- wp_get_post: rascunho 9 preservado com imagem destacada 10.
- wp_upload_media por URL: sucesso, mídia 13, alteração 8. wp_list_media confirmou 1536x1024, texto alternativo e arquivo PNG.
- wp_http_request HEAD: página inicial e nova mídia responderam HTTP 200.
- wp_list_checkpoints: checkpoint 1 confirmado após migração.

### Teste que falhou e limitação encontrada

Uma imagem mínima fornecida no teste base64 estava corrompida (CRC inválido no bloco IDAT). O servidor retornou HTTP 500 após criar a mídia 12, que ficou sem dimensões e metadados completos. A falha não derrubou a conexão ou o site. O teste foi repetido com imagem válida e passou. O log PHP acessível pelo MCP retornou vazio, portanto não confirmou o ponto exato da falha interna.

Melhoria pendente: validar imagens inválidas e retornar erro claro, com limpeza de arquivos/anexos parciais. Os testes de integração não equivalem à validação individual das 131 ferramentas.

### Conteúdo e recuperação

Nenhum artigo foi publicado, alterado ou excluído. As mídias técnicas 12 e 13 foram mantidas sem vínculo com posts para rastrear os testes; a mídia 10 permanece destacada no rascunho 9.

Recuperação: pela interface WordPress, desativar Ahead e reativar o original em requisições separadas, sem desinstalar nenhum deles. Usar o checkpoint 1 apenas se houver necessidade real de restaurar o banco, pois isso também reverte conteúdo posterior. Nunca ativar ambos simultaneamente. Cópias e hashes da migração foram preservados no workspace local.
