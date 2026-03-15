# Payment Gateway API — Nível 2

API RESTful desenvolvida em **Laravel** para gerenciar **compras e pagamentos utilizando múltiplos gateways**, com **fallback automático**, **validação de dados sensíveis** e **monitoramento de transações**.

Este projeto simula um ambiente de pagamento real onde diferentes provedores podem ser utilizados para processar transações.

**Existe um arquivo no repositorio chamado GUIDE_TESTS feito exclusivamente para os testes da API**

---

# Requisitos

Antes de rodar o projeto, certifique-se de possuir:

- PHP 8.2+
- Laravel 12
- Composer 2.x
- Node.js 18+ (para compilar assets se necessário)
- MySQL 8.0+ (ou outro banco compatível configurado no Laravel)
- Docker (necessário para rodar os gateways mockados)
- Postman (opcional) para importar `postman_collection.json`

---

# Como instalar e rodar o projeto

## 1. Clonar o repositório

```bash
git clone https://github.com/Agstgrss/payment-gateway-api.git
cd payment-gateway-api
```

---

## 2. Instalar dependências PHP e Node

```bash
composer install
npm install
```

---

## 3. Configurar o ambiente

Copie o arquivo de ambiente:

```bash
cp .env.example .env
```

Gerar a chave da aplicação:

```bash
php artisan key:generate
```

Ajuste as variáveis do `.env` conforme seu ambiente:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=payment_gateway
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost
```

---


## 4. Rodar os gateways mockados

Para simular provedores de pagamento externos, execute:

```bash
docker run -p 3001:3001 -p 3002:3002 matheusprotzen/gateways-mock
```

Isso iniciará dois gateways simulados:

| Gateway | URL |
|------|------|
| Gateway 1 | http://localhost:3001 |
| Gateway 2 | http://localhost:3002 |

Caso precise rodar **sem autenticação**, utilize a variável:

```
REMOVE_AUTH=true
```

---

## 5. Iniciar o backend Laravel

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

A API ficará disponível em:

```
http://localhost:8000
```

---

## 6. Iniciar tudo com o docker

```bash
docker compose build
docker compose up
docker ps
```

## 7. Migrar e popular o banco de dados

Execute as migrations e seeders:

```bash
php artisan migrate --seed
```

Isso irá criar:

- tabelas necessárias
- usuários de teste
- gateways padrão

---

exemplo, 3 containers rodando (APIfeita, Mysql, Api externa):

```
CONTAINER ID   IMAGE                          COMMAND                  CREATED       STATUS          PORTS
           NAMES
dc753949941f   payment-gateway-api-app        "docker-php-entrypoi…"   5 hours ago   Up 44 minutes   0.0.0.0:8000->8000/tcp, [::]:8000->8000/tcp
           payment_gateway_app
9ffe345f180f   mysql:8.0                      "docker-entrypoint.s…"   5 hours ago   Up 44 minutes   0.0.0.0:3306->3306/tcp, [::]:3306->3306/tcp
           payment_gateway_mysql
f4f43673fc0c   matheusprotzen/gateways-mock   "/app/cli"               5 hours ago   Up 44 minutes   0.0.0.0:3001-3002->3001-3002/tcp, [::]:3001-3002->3001-3002/tcp   payment_gateway_gateways
```

---

# Rotas da API

Todas as rotas utilizam o prefixo:

```
/api
```

---

# Rotas Públicas

## Login

### POST `/api/login`

Realiza autenticação e retorna token **Sanctum**.

### Body

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

### Resposta

Retorna token **Bearer** necessário para rotas protegidas.

---

## Realizar Compra

### POST `/api/purchase`

Processa pagamento utilizando os gateways disponíveis.

### Body

```json
{
  "product_id": 1,
  "quantity": 2,
  "name": "João Silva",
  "email": "joao@example.com",
  "card_number": "5569000000006063",
  "cvv": "010"
}
```

### Resposta

```json
{
  "transaction_id": 1,
  "status": "approved",
  "amount": 2000,
  "card_last_numbers": "6063"
}
```

---

# Rotas Autenticadas

Todas exigem header:

```
Authorization: Bearer {token}
```

---

# Produtos

## Listar produtos

```
GET /api/products
```

---

## Criar produto

```
POST /api/products
```

Body:

```json
{
  "name": "Produto A",
  "amount": 1000
}
```

Valor em **centavos**.

---

## Atualizar produto

```
PATCH /api/products/{id}
```

---

## Deletar produto

```
DELETE /api/products/{id}
```

---

# Transações

## Listar transações

```
GET /api/transactions
```

---

## Detalhar transação

```
GET /api/transactions/{id}
```

Retorna:

- status
- gateway
- amount

---

## Solicitar estorno

```
POST /api/transactions/{id}/refund
```

O gateway mock retorna resposta conforme regras configuradas.

---

# Clientes

## Listar clientes

```
GET /api/clients
```

---

## Detalhar cliente

```
GET /api/clients/{id}
```

Retorna:

- dados do cliente
- compras associadas

---

# Gateways

## Ativar / desativar gateway

```
PATCH /api/gateways/{id}/toggle
```

Controla campo:

```
is_active
```

---

## Alterar prioridade do gateway

```
PATCH /api/gateways/{id}/priority
```

Body:

```json
{
  "priority": 1
}
```

Define a ordem de tentativa.

---

# Arquitetura do Sistema

## Fluxo Multi-Gateway

O `PaymentService` executa:

1. Ordena gateways por **priority**
2. Tenta processar no gateway principal
3. Em caso de erro ou timeout
4. Tenta o próximo gateway disponível
5. Continua até:

- sucesso
- todos falharem

---

# Gateways Mockados

### Gateway 1

```
http://localhost:3001
```

Token:

```
Bearer FEC9BB078BF338F464F96B48089EB498
```

---

### Gateway 2

```
http://localhost:3002
```

Headers necessários:

```
Gateway-Auth-Token
Gateway-Auth-Secret
```

---

## Simulação de erro

Se o **CVV for 100 ou 200**, o gateway retorna erro para simular falha.

---

# Segurança

O sistema implementa boas práticas.

### Senhas

- criptografadas com **Bcrypt**

### Autenticação

- **Laravel Sanctum**

### Dados sensíveis

- número do cartão **não é armazenado**
- apenas os **últimos 4 dígitos**

Exemplo armazenado:

```
card_last_numbers
```

### Validações

Cartão:

```
16 dígitos
```

CVV:

```
3 dígitos
```

---

# Testes

Rodar testes:

```bash
php artisan test
```

Rodar com coverage:

```bash
php artisan test --coverage
```

Requer:

- **Xdebug**
- ou **PCOV**

---

# Postman

Existe uma coleção disponível:

```
postman_collection.json
```

Importe no Postman para testar rapidamente todas as rotas.

---

# Estrutura do Projeto

```
app/
 ├── Gateways/
 │   ├── GatewayInterface
 │   ├── Gateway implementations
 │
 ├── Services/
 │   └── PaymentService.php
 │
routes/
 └── api.php
```

### Principais componentes

| Caminho | Função |
|------|------|
| `app/Gateways` | Integrações com gateways |
| `app/Services/PaymentService.php` | Lógica de fallback |
| `routes/api.php` | Endpoints da API |

---

# Variáveis de Ambiente Extras

Opcional:

```
DEFAULT_GATEWAY_PRIORITY
```

Define prioridade inicial no seeder de gateways.

---

# Monitoramento e Troubleshooting

### Logs

```
storage/logs/laravel.log
```

---

### Verificar containers

```bash
docker ps
```

Confirme portas:

```
3001
3002
```

---

### Erro 401

Verifique:

- Header correto

```
Authorization: Bearer {token}
```

- token válido

---

# Referências Úteis

Arquivos importantes no projeto:

```
GUIA_TESTES.md
database/seeders
routes/api.php
```

Seeders criam usuário de teste:

```
user@example.com
password
```

---

# Objetivo do Projeto

Este projeto foi estruturado para servir como **base técnica de um sistema de pagamentos com múltiplos gateways**, incluindo:

- fallback automático
- segurança de dados sensíveis
- controle de transações
- arquitetura extensível

Ideal para **testes técnicos, estudos de arquitetura de pagamento e APIs REST robustas**.
