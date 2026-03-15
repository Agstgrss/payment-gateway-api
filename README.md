# Payment Gateway API - Nível 2

Uma API RESTful em Laravel para gerenciar pagamentos com múltiplos gateways. Este projeto implementa o **Nível 2** dos requisitos, incluindo:

- ✅ Cálculo de valor da compra via backend (produto + quantidade)
- ✅ Autenticação com os gateways
- ✅ Sistema multi-gateway com fallback automático
- ✅ RESTful API completa com validações

## 📋 Requisitos

- PHP 8.2+
- Laravel 12
- MySQL 8.0+
- Composer
- Node.js (para build assets)

## 🚀 Instalação

### 1. Clone o repositório
```bash
git clone <repository-url>
cd payment-gateway-api
```

### 2. Instale as dependências
```bash
composer install
npm install
```

### 3. Configure o ambiente
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure o banco de dados
Edite o arquivo `.env` com suas credenciais MySQL:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=payment_gateway
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Execute as migrations
```bash
php artisan migrate --seed
```

### 6. Start dos servidores

**Terminal 1 - Backend (Laravel):**
```bash
php artisan serve
```

**Terminal 2 - Gateways Mock (Docker):**
```bash
docker run -p 3001:3001 -p 3002:3002 matheusprotzen/gateways-mock
```

Ou sem autenticação (para testes):
```bash
docker run -p 3001:3001 -p 3002:3002 -e REMOVE_AUTH='true' matheusprotzen/gateways-mock
```

A API estará disponível em: `http://localhost:8000`

## 📊 Banco de Dados

### Estrutura

**users**
- id, name, email, password, role, timestamps

**gateways**
- id, name, is_active, priority, timestamps

**clients**
- id, name, email, timestamps

**products**
- id, name, amount (em centavos), timestamps

**transactions**
- id, client_id, gateway_id, external_id, status, amount, card_last_numbers, timestamps

**transaction_products**
- id, transaction_id, product_id, quantity

## 🛣️ Rotas da API

### Rotas Públicas

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Resposta (201):**
```json
{
  "token": "Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

#### Realizar uma Compra
```http
POST /api/purchase
Content-Type: application/json

{
  "product_id": 1,
  "quantity": 2,
  "name": "João Silva",
  "email": "joao@example.com",
  "card_number": "5569000000006063",
  "cvv": "010"
}
```

**Resposta (201):**
```json
{
  "message": "Payment successful",
  "data": {
    "transaction_id": 1,
    "external_id": "gw_12345",
    "status": "success",
    "amount": 2000,
    "card_last_numbers": "6063",
    "created_at": "2026-03-13T10:30:00Z"
  }
}
```

### Rotas Autenticadas (Requer Bearer Token)

#### Gerenciamento de Produtos

**Listar produtos:**
```http
GET /api/products
Authorization: Bearer {token}
```

**Criar produto:**
```http
POST /api/products
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Produto A",
  "amount": 1000
}
```

**Atualizar produto:**
```http
PATCH /api/products/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Produto A Atualizado",
  "amount": 1500
}
```

**Deletar produto:**
```http
DELETE /api/products/{id}
Authorization: Bearer {token}
```

#### Gerenciamento de Transactions

**Listar todas as transações:**
```http
GET /api/transactions
Authorization: Bearer {token}
```

**Detalhe de uma transação:**
```http
GET /api/transactions/{id}
Authorization: Bearer {token}
```

**Reembolsar uma transação:**
```http
POST /api/transactions/{id}/refund
Authorization: Bearer {token}
```

#### Gerenciamento de Clientes

**Listar clientes:**
```http
GET /api/clients
Authorization: Bearer {token}
```

**Detalhe do cliente com compras:**
```http
GET /api/clients/{id}
Authorization: Bearer {token}
```

#### Gerenciamento de Gateways

**Ativar/Desativar gateway:**
```http
PATCH /api/gateways/{id}/toggle
Authorization: Bearer {token}
```

**Alterar prioridade:**
```http
PATCH /api/gateways/{id}/priority
Authorization: Bearer {token}
Content-Type: application/json

{
  "priority": 1
}
```

## 🔌 Integração com Gateways

### Gateway 1 (http://localhost:3001)

Usa **Bearer Token** para autenticação.

**Login:**
- Email: `dev@betalent.tech`
- Token: `FEC9BB078BF338F464F96B48089EB498`

**Transação Criada com CVV 100 ou 200:** Erro (dados de cartão inválidos)

### Gateway 2 (http://localhost:3002)

Usa **Headers customizados** para autenticação:
- `Gateway-Auth-Token: tk_f2198cc671b5289fa856`
- `Gateway-Auth-Secret: 3d15e8ed6131446ea7e3456728b1211f`

**Transação Criada com CVV 200 ou 300:** Erro (dados de cartão inválidos)

### Fluxo Multi-Gateway

1. O sistema tenta processar a compra no Gateway 1 (prioridade 1)
2. Se o Gateway 1 falhar, tenta no Gateway 2 (prioridade 2)
3. Se algum gateway retornar sucesso, a transação é salva
4. Se todos os gateways falharem, um erro genérico é retornado

## 🧪 Testes

Executar testes:
```bash
php artisan test
```

Testes com coverage:
```bash
php artisan test --coverage
```

## 📁 Estrutura do Projeto

```
app/
├── Gateways/
│   ├── Gateway1Service.php    # Integração com Gateway 1
│   ├── Gateway2Service.php    # Integração com Gateway 2
│   └── GatewayInterface.php   # Interface para gateways
├── Http/
│   └── Controllers/
│       ├── AuthController.php
│       ├── ProductController.php
│       ├── TransactionController.php
│       ├── ClientController.php
│       ├── GatewayController.php
│       └── PurchaseController.php
├── Models/
│   ├── User.php
│   ├── Product.php
│   ├── Client.php
│   ├── Gateway.php
│   ├── Transaction.php
│   └── TransactionProduct.php
└── Services/
    └── PaymentService.php     # Lógica de pagamento e multi-gateway

database/
├── migrations/               # Estrutura do banco
└── seeders/                  # Dados iniciais

routes/
├── api.php                   # Rotas da API REST
└── web.php
```

## 🔒 Segurança

- Senhas hasheadas com Bcrypt
- Tokens API via Laravel Sanctum
- Validação de CVV (apenas 3 dígitos)
- Validação de número de cartão (16 dígitos)
- Dados sensíveis (últimos 4 dígitos) armazenados
- Bearer Token para autenticação nas rotas privadas

## ⚙️ Variáveis de Ambiente

```env
APP_NAME=PaymentGatewayAPI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=payment_gateway
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost
```

## 📝 Exemplo de Fluxo Completo

### 1. Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

### 2. Criar Produto
```bash
curl -X POST http://localhost:8000/api/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Produto Premium",
    "amount": 5000
  }'
```

### 3. Realizar Compra
```bash
curl -X POST http://localhost:8000/api/purchase \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "quantity": 1,
    "name": "Cliente Teste",
    "email": "cliente@example.com",
    "card_number": "5569000000006063",
    "cvv": "010"
  }'
```

### 4. Listar Transações
```bash
curl -X GET http://localhost:8000/api/transactions \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🐛 Troubleshooting

### Erro de conexão com banco
- Verifique credenciais em `.env`
- Certifique-se de que MySQL está rodando

### Erro na integração com gateways
- Verifique se Docker está rodando: `docker ps`
- Confirme as portas (3001 e 3002) estão livres
- Teste a conexão: `curl http://localhost:3001/login`

### Erro 401 em rotas autenticadas
- Verifique o formato do token
- Certifique-se de usar `Bearer {token}`

## 📦 Dependências Principais

- Laravel Framework 12
- Laravel Sanctum (Autenticação)
- Eloquent ORM
- HTTP Client (Guzzle)
- Pest (Testes)

## 📄 Licença

MIT

## 👨‍💻 Autor

Payment Gateway API - Teste técnico Nível 2

