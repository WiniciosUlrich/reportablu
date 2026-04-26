# Mapeamento dos criterios de design no ReportaBlu

Este documento aponta onde cada criterio foi aplicado no codigo.

## 1) Design de software: modularizacao

Problema: o sistema web cresceu e precisava reduzir complexidade.
Solucao: divisao em camadas e modulos com contrato + implementacao.

Exemplos de modulo com interface + implementacao:
- TicketReadRepositoryInterface -> PdoTicketRepository
- TicketWriteRepositoryInterface -> PdoTicketRepository
- AttachmentStorageInterface -> LocalAttachmentStorage
- UploadValidationStrategyInterface -> DefaultUploadValidationStrategy
- TransactionManagerInterface -> PdoTransactionManager

Arquivos:
- includes/src/Domain/Contracts/
- includes/src/Infrastructure/Repository/
- includes/src/Infrastructure/Storage/
- includes/src/Infrastructure/Database/PdoTransactionManager.php

## 2) Propriedades de um bom projeto

### Integridade conceitual
- Nomes e fluxo consistentes: TicketCreationService, TicketQueryService, TicketWorkflowService.
- Controllers finos chamando Facade em index.php, dashboard.php, new_ticket.php, ticket_detail.php.

### Ocultamento de informacao (encapsulamento)
- SQL encapsulado em repositories PDO.
- Regras de upload encapsuladas em AttachmentService e LocalAttachmentStorage.
- UI nao conhece detalhes de SQL, transacao ou filesystem.

### Coesao
- TicketCreationService: criar chamado.
- TicketQueryService: consultar dados.
- TicketWorkflowService: atualizar status/encaminhar/responder.
- DefaultUploadValidationStrategy: somente validacao de anexos.

### Acoplamento
- Services dependem de contratos (Domain/Contracts), nao de classes concretas.
- Concretos sao montados apenas em AppFactory.

## 3) SOLID + extras

### SRP (responsabilidade unica)
- Application services separados por responsabilidade.
- Repositories separados por contexto de dados.

### Interface Segregation (ISP)
- Contratos pequenos e especificos em Domain/Contracts.
- Exemplo: TicketReadRepositoryInterface separado de TicketWriteRepositoryInterface.

### DIP (inversao de dependencia)
- Constructors de services recebem interfaces.
- AppFactory decide quais implementacoes concretas injetar.

### Prefira composicao a heranca
- LocalAttachmentStorage tem uma estrategia de validacao injetada.
- TicketFacade compoe services especializados.

### Demeter
- Controllers falam com TicketFacade, sem navegar por cadeias longas de objetos.

### OCP (aberto para extensao, fechado para modificacao)
- Nova estrategia de validacao pode ser adicionada sem alterar LocalAttachmentStorage.
- Novos repositories podem implementar os contratos existentes.

### LSP
- Implementacoes concretas de contratos podem ser substituidas sem quebrar services.
- Exemplo: qualquer classe que implemente AttachmentStorageInterface pode substituir LocalAttachmentStorage.

## 4) Padroes de projeto aplicados

### Factory
Contexto: muitas dependencias para montar a aplicacao.
Problema: controllers com new em excesso e alto acoplamento.
Solucao: AppFactory centraliza a composicao.
Arquivo: includes/src/Application/AppFactory.php

### Facade
Contexto: UI precisava usar varios casos de uso.
Problema: controller complexo e com muitas dependencias.
Solucao: TicketFacade oferece API unica para a camada de UI.
Arquivo: includes/src/Application/TicketFacade.php

### Strategy
Contexto: regras de validacao de anexos podem mudar.
Problema: validacao acoplada ao storage.
Solucao: UploadValidationStrategyInterface + DefaultUploadValidationStrategy.
Arquivos: includes/src/Domain/Contracts/UploadValidationStrategyInterface.php, includes/src/Infrastructure/Storage/DefaultUploadValidationStrategy.php

### Repository
Contexto: acesso a banco espalhado pela UI.
Problema: SQL misturado com regra de negocio.
Solucao: repositories encapsulam consultas e escrita.
Arquivos: includes/src/Infrastructure/Repository/

### Singleton (parcial, por request)
- A funcao db() usa cache estatico de PDO na request atual.
Arquivo: includes/config.php

### Proxy / Adapter / Decorator / Observer / Template Method / Visitor
- Nao foram aplicados explicitamente neste momento para evitar overengineering.
- A arquitetura atual permite adicionar quando houver necessidade real.

## 5) Arquitetura

### Camadas
- UI (paginas PHP)
- Application (casos de uso / orquestracao)
- Domain (contratos e regras centrais)
- Infrastructure (PDO, filesystem, transacao)

### MVC web (adaptacao)
- Controller: paginas index.php, dashboard.php, new_ticket.php, ticket_detail.php
- Model: services + repositorios + contratos
- View: HTML/PHP renderizado nas paginas e layout

### Monolito web
- Frontend e backend no mesmo projeto PHP (MPA).
- Estrutura preparada para evolucao por modulos/camadas.
