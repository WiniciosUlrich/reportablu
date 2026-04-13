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
- Historico de status por chamado
- Filtros e busca de chamados
- Pagina inicial com estatisticas e chamados solucionados
- Interface responsiva

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
- index.php: pagina inicial com chamados solucionados e estatisticas
- dashboard.php: historico com filtros e busca
- new_ticket.php: abertura de chamado com upload
- ticket_detail.php: detalhes, anexos e historico
- sql/schema.sql: script completo do banco