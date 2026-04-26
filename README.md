# ReportaBlu

ReportaBlu e um portal web para moradores abrirem chamados da cidade, acompanhar status e consultar chamados solucionados.

## Tecnologias

- PHP 8+
- MySQL / MariaDB
- PDO
- HTML, CSS e JavaScript

## Funcionalidades

- Cadastro e autenticacao de usuarios
- Registro de chamados com categoria, descricao e localizacao
- Upload de anexos nos chamados
- Geracao de protocolo unico por chamado
- Historico de status por chamado
- Encaminhamento para setor responsavel
- Resposta do atendente para o cidadao
- Filtros e busca de chamados
- Pagina inicial com estatisticas e chamados solucionados
- Interface responsiva

## Arquitetura

O projeto foi refatorado em camadas para maximizar coesao e reduzir acoplamento:

- Domain: regras centrais, catalogos e contratos (interfaces)
- Application: casos de uso/servicos e fachada de operacoes
- Infrastructure: implementacoes concretas com PDO, transacoes e upload local
- UI (paginas PHP): controllers finos + renderizacao

Padroes aplicados:

- Factory para montagem de dependencias da aplicacao
- Facade para simplificar o uso de operacoes de chamado
- Strategy para validacao de anexos no upload
- Repository para acesso a dados desacoplado da regra de negocio

## Instalacao

1. Crie o banco e tabelas executando o arquivo SQL:

   sql/schema.sql

2. Configure o arquivo .env com as credenciais do banco.

3. Garanta permissao de escrita na pasta uploads.

4. Acesse no navegador:

   http://localhost/reportablu/index.php

## Usuarios de teste

- Admin:
  - Email: admin@reportablu.local
  - Senha: password
- Morador:
  - Email: morador@reportablu.local
  - Senha: password

## Estrutura principal

- includes/config.php: loader do .env e conexao PDO
- includes/auth.php: controle de sessao e permissao
- includes/src/Domain: contratos e regras centrais
- includes/src/Application: casos de uso e fachada da aplicacao
- includes/src/Infrastructure: repositorios PDO, transacoes e storage de anexos
- index.php: pagina inicial com chamados solucionados e estatisticas
- dashboard.php: historico com filtros e busca
- new_ticket.php: abertura de chamado com upload
- ticket_detail.php: detalhes, historico, respostas e encaminhamento
- sql/schema.sql: script completo do banco