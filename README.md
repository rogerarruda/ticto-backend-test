# Ticto Backend Test — API

Este repositório contém uma aplicação Laravel que expõe uma API REST para gestão de funcionários e seus registros de ponto.

Abaixo você encontra instruções completas para rodar com Laravel Sail (Docker), exemplos de uso dos endpoints e como executar os testes automatizados. Há também uma coleção Postman pronta para importação.


## Sumário
- Visão geral da API
- Como rodar com Laravel Sail (Docker)
- Configuração de ambiente (.env)
- Exemplos de endpoints (cURL)
- Como rodar os testes
- Postman Collection
- O que foi feito e por que (decisões técnicas)
- Solução de problemas


## Visão geral da API
Autenticação via token (Laravel Sanctum):
- POST /api/login → retorna { access_token, token_type: "Bearer", user }
- Demais endpoints → requerem header Authorization: Bearer <token>

Endpoints autenticados comuns:
- GET /api/user → retorna o usuário autenticado
- POST /api/logout → invalida o token atual
- POST /api/password/change → altera a senha (body: current_password, password, password_confirmation)

Funcionário:
- POST /api/time-records → cria um registro de ponto no horário atual

Admin — Funcionários:
- GET /api/admin/employees?name=&cpf=&email=&supervisor_id=
- POST /api/admin/employees (criar)
- GET /api/admin/employees/{id} (detalhes)
- PUT/PATCH /api/admin/employees/{id} (atualizar)
- DELETE /api/admin/employees/{id} (excluir)
- GET /api/admin/employees/{id}/time-records (registros de ponto do funcionário)

Admin — Registros de ponto:
- GET /api/admin/time-records?start_date=&end_date=&employee_name=&supervisor_name= (paginado)
- GET /api/admin/time-records/report?start_date=&end_date=&employee_name=&limit= (relatório resumido)


## Como rodar com Laravel Sail (Docker)
Pré-requisitos:
- Docker (Desktop no macOS/Windows ou Engine no Linux)

Passo a passo:
1) Clonar o repositório
- git clone https://github.com/rogerarruda/ticto-backend-test.git
- cd ticto-backend-test

2) Instalar dependências PHP dentro de container (não precisa PHP local)
- docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd)":/var/www/html -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs

3) Configurar o .env
- Copie .env.example para .env (se necessário) e ajuste conforme a seção “Configuração de ambiente”.

4) Subir os serviços com Sail
- ./vendor/bin/sail up -d

5) Gerar a chave da aplicação
- ./vendor/bin/sail artisan key:generate

6) Executar migrações e seeds
- ./vendor/bin/sail artisan migrate --seed

7) URL base
- API base: http://localhost (ajuste APP_URL/.env se usar outra porta/host)


## Configuração de ambiente (.env)
Ajuste os valores para o ambiente Docker do Sail (exemplos):
- APP_URL=http://localhost
- DB_CONNECTION=mysql
- DB_HOST=mysql
- DB_PORT=3306
- DB_DATABASE=ticto_backend_test
- DB_USERNAME=sail
- DB_PASSWORD=password
- REDIS_CLIENT=phpredis
- REDIS_HOST=redis
- REDIS_PORT=6379

Se alterar portas (APP_PORT, DB, etc.), atualize também o docker-compose e o .env.


## Exemplos de endpoints (cURL)
Login (obter token):
- curl -X POST http://localhost/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin1@email.com","password":"sua-senha"}'

Usando o token nas próximas requisições:
- curl http://localhost/api/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"

Alterar senha:
- curl -X POST http://localhost/api/password/change \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{"current_password":"antiga","password":"nova123456","password_confirmation":"nova123456"}'

Criar registro de ponto (funcionário):
- curl -X POST http://localhost/api/time-records \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"

Listar funcionários (admin):
- curl "http://localhost/api/admin/employees?name=&cpf=&email=&supervisor_id=" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"

Criar funcionário (admin):
- curl -X POST http://localhost/api/admin/employees \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "name":"Funcionário Teste",
    "email":"funcionario.teste@email.com",
    "cpf":"12345678909",
    "birth_date":"1990-05-20",
    "position":"Analista",
    "zipcode":"01001000",
    "number":"123",
    "complement":"Apto 10",
    "password":"secret1234"
  }'

Listar registros de ponto (admin, paginado):
- curl "http://localhost/api/admin/time-records?start_date=&end_date=&employee_name=&supervisor_name=" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"

Relatório de registros de ponto (admin):
- curl "http://localhost/api/admin/time-records/report?start_date=&end_date=&employee_name=&limit=1000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"


## Como rodar os testes
- Rode todos os testes:
  - ./vendor/bin/sail artisan test

- Opcional: executar um arquivo ou diretório específico (PHPUnit/Pest):
  - ./vendor/bin/sail artisan test --testsuite=Feature
  - ./vendor/bin/sail artisan test tests/Feature/Admin/TimeRecordsControllerTest.php

- Dicas:
  - O docker-compose do Sail cria um banco de testes automaticamente (via script de init). Caso necessário, revise phpunit.xml para o nome do database de teste.
  - Se mudar credenciais/host, alinhe com o .env.testing e variáveis do container.


## Postman Collection
- Há uma coleção Postman pronta neste repositório: postman-collection.json
- Como usar:
  1. Importe o arquivo no Postman (File > Import > selecionar o JSON).
  2. Verifique a variável de coleção baseUrl (padrão http://localhost). Ajuste se usar outra porta/host.
  3. Execute a requisição Auth > Login com credenciais válidas. Um script de teste salvará o access_token em {{token}}.
  4. As demais requisições já usam Authorization: Bearer {{token}}.


## O que foi feito e por que (decisões técnicas)
- Laravel Sanctum para autenticação por token
  - Simples de integrar e seguro para APIs first‑party.
- Policies (Gate/Policies) para autorização
  - Centraliza regras de acesso por perfil (Admin/Funcionário).
- Form Requests para validação
  - Mantém controladores enxutos e mensagens de erro padronizadas.
- ViaCEP para busca de endereço a partir do CEP
  - Melhora a UX e evita dados inconsistentes de endereço.
- Resources (API Resources) e paginação nativa
  - Saída consistente de JSON e melhor performance em listagens.
- Throttle no endpoint de registro de ponto
  - Evita batidas múltiplas acidentais em curto intervalo.
- Seeds de dados (admins, funcionários, registros)
  - Facilita testes locais e demonstração.
- Laravel Sail (Docker) para ambiente de desenvolvimento
  - Padroniza setup entre máquinas e reduz atrito de onboarding.


## Solução de problemas
- "SQLSTATE[HY000] [2002] Connection refused" ou erro ao conectar no MySQL
  - Confirme DB_HOST=mysql, DB_USERNAME=sail, DB_PASSWORD=password e containers ativos (./vendor/bin/sail up -d).
- Erro 500 na primeira execução
  - Garanta que a APP_KEY foi gerada e que as migrações/seeds foram executadas.
- Senhas dos usuários seedados
  - Se precisar de uma senha conhecida, crie um novo usuário via endpoint de Admin ou ajuste as factories/seeds e rode novamente.
