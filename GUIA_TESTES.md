# 📚 Guia de Testes e Workflow da API

Este documento fornece um passo a passo para testar a API completa do Payment Gateway.

---

## 🚀 Quick Start (5 minutos)

### 1. Setup Rápido
```bash
# Terminal principal do projeto
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### 2. Em outro terminal, inicie os Gateways
```bash
docker run -p 3001:3001 -p 3002:3002 matheusprotzen/gateways-mock
```

### 3. API disponível em
```
http://localhost:8000/api
```

---

## 🔐 1. Autenticação (Login)

### Dados de Teste Disponíveis

**Admin (com role ADMIN):**
```
Email: admin@example.com
Password: admin123
```

**Usuário Comum (com role USER):**
```
Email: test@example.com
Password: password
```

### Login via cURL

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "admin123"
  }'
```

**Resposta esperada (201):**
```json
{
  "token": "1|SomeVeryLongTokenHere..."
}
```

**Salve este token para usar nas próximas requisições:**
```bash
TOKEN="1|SomeVeryLongTokenHere..."
```

---

## 📦 2. Gerenciamento de Produtos

### Listar Produtos
```bash
curl -X GET http://localhost:8000/api/products \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Produto Premium",
      "amount": 5000,
      "created_at": "2026-03-13T10:00:00Z",
      "updated_at": "2026-03-13T10:00:00Z"
    },
    ...
  ]
}
```

### Criar Novo Produto
```bash
curl -X POST http://localhost:8000/api/products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Produto Novo",
    "amount": 7500
  }'
```

**Resposta (201):**
```json
{
  "message": "Product created successfully",
  "data": {
    "id": 4,
    "name": "Produto Novo",
    "amount": 7500,
    "created_at": "2026-03-13T11:30:00Z",
    "updated_at": "2026-03-13T11:30:00Z"
  }
}
```

### Atualizar Produto
```bash
curl -X PATCH http://localhost:8000/api/products/4 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 8500
  }'
```

### Deletar Produto
```bash
curl -X DELETE http://localhost:8000/api/products/4 \
  -H "Authorization: Bearer $TOKEN"
```

---

## 🛒 3. Realizar Compra (O Coração do Sistema)

### Payload da Compra
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

### Requisição
```bash
curl -X POST http://localhost:8000/api/purchase \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "quantity": 2,
    "name": "João Silva",
    "email": "joao@example.com",
    "card_number": "5569000000006063",
    "cvv": "010"
  }'
```

### Caso 1: Sucesso ✅
**Resposta (201):**
```json
{
  "message": "Payment successful",
  "data": {
    "transaction_id": 1,
    "external_id": "gw_123456",
    "status": "success",
    "amount": 10000,
    "card_last_numbers": "6063",
    "created_at": "2026-03-13T12:00:00Z"
  }
}
```

**O que aconteceu:**
- ✅ Produto 1 (R$ 50) × 2 = R$ 100 (10000 centavos)
- ✅ Cliente João Silva foi criado ou encontrado
- ✅ Transação processada no Gateway 1 com sucesso
- ✅ Registro criado em `transactions` table
- ✅ Relacionamento criado em `transaction_products`

### Caso 2: Gateway 1 Falha → Tenta Gateway 2 🔄
**Payload com CVV que causa erro no Gateway 1:**
```json
{
  "product_id": 1,
  "quantity": 1,
  "name": "Cliente Teste",
  "email": "teste@example.com",
  "card_number": "5569000000006063",
  "cvv": "100"
}
```

**O que acontece:**
- ❌ Gateway 1 retorna erro (CVV 100 é inválido)
- ↪️ Sistema tenta Gateway 2 automaticamente
- ✅ Gateway 2 processa com sucesso
- ✅ Transação salva como sucesso do Gateway 2

**Resposta (201):**
```json
{
  "message": "Payment successful",
  "data": {
    "transaction_id": 2,
    "external_id": "gw_987654",
    "status": "success",
    "amount": 5000,
    "card_last_numbers": "6063",
    "created_at": "2026-03-13T12:05:00Z"
  }
}
```

### Caso 3: Validação Inválida ❌
**Payload sem card_number:**
```json
{
  "product_id": 1,
  "quantity": 1,
  "name": "Cliente",
  "email": "cliente@example.com",
  "cvv": "010"
}
```

**Resposta (422):**
```json
{
  "message": "Validation error",
  "errors": {
    "card_number": ["The card number field is required."]
  }
}
```

### Caso 4: Produto Não Existe ❌
**Payload com product_id inválido:**
```bash
curl -X POST http://localhost:8000/api/purchase \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 999,
    "quantity": 1,
    "name": "Cliente",
    "email": "cliente@example.com",
    "card_number": "5569000000006063",
    "cvv": "010"
  }'
```

**Resposta (404):**
```json
{
  "message": "Product not found"
}
```

---

## 💳 4. Gerenciamento de Transações (Autenticado)

### Listar Todas as Transações
```bash
curl -X GET http://localhost:8000/api/transactions \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta (200):**
```json
{
  "data": [
    {
      "id": 1,
      "client_id": 1,
      "gateway_id": 1,
      "external_id": "gw_123456",
      "status": "success",
      "amount": 10000,
      "card_last_numbers": "6063",
      "created_at": "2026-03-13T12:00:00Z",
      "updated_at": "2026-03-13T12:00:00Z",
      "client": {
        "id": 1,
        "name": "João Silva",
        "email": "joao@example.com"
      },
      "gateway": {
        "id": 1,
        "name": "Gateway1",
        "is_active": true,
        "priority": 1
      },
      "products": [
        {
          "id": 1,
          "transaction_id": 1,
          "product_id": 1,
          "quantity": 2
        }
      ]
    }
  ]
}
```

### Detalhe de Uma Transação
```bash
curl -X GET http://localhost:8000/api/transactions/1 \
  -H "Authorization: Bearer $TOKEN"
```

### Reembolsar Transação
```bash
curl -X POST http://localhost:8000/api/transactions/1/refund \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta (200):**
```json
{
  "message": "Transaction refunded successfully",
  "data": {
    "id": 1,
    "status": "refunded",
    ...
  }
}
```

**Casos de Erro:**

Transação já foi reembolsada (400):
```json
{
  "message": "This transaction has already been refunded"
}
```

Transação não existe (404):
```json
{
  "message": "Transaction not found"
}
```

---

## 👥 5. Gerenciamento de Clientes (Autenticado)

### Listar Todos os Clientes
```bash
curl -X GET http://localhost:8000/api/clients \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "transactions": [
        {
          "id": 1,
          "client_id": 1,
          "gateway_id": 1,
          "status": "success",
          "amount": 10000
        }
      ]
    }
  ]
}
```

### Detalhe do Cliente com Histórico de Compras
```bash
curl -X GET http://localhost:8000/api/clients/1 \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta (200):**
```json
{
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "transactions": [
      {
        "id": 1,
        "gateway": { "name": "Gateway1" },
        "status": "success",
        "amount": 10000,
        "products": [
          {
            "product_id": 1,
            "quantity": 2
          }
        ]
      }
    ]
  }
}
```

---

## 🔧 6. Gerenciamento de Gateways (Autenticado)

### Listar Gateways (via produtos)

### Desativar um Gateway
```bash
curl -X PATCH http://localhost:8000/api/gateways/1/toggle \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta (200):**
```json
{
  "message": "Gateway status updated successfully",
  "data": {
    "id": 1,
    "name": "Gateway1",
    "is_active": false,
    "priority": 1
  }
}
```

**Efeito:** Se Gateway 1 está desativado, compras tentarão apenas Gateway 2

### Ativar novamente
```bash
curl -X PATCH http://localhost:8000/api/gateways/1/toggle \
  -H "Authorization: Bearer $TOKEN"
```

### Alterar Prioridade
```bash
curl -X PATCH http://localhost:8000/api/gateways/1/priority \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "priority": 2
  }'
```

**Resposta (200):**
```json
{
  "message": "Gateway priority updated successfully",
  "data": {
    "id": 1,
    "name": "Gateway1",
    "priority": 2,
    "is_active": true
  }
}
```

**Efeito:** Agora Gateway 2 tenta primeiro (prioridade 1), depois Gateway 1 (prioridade 2)

---

## 🧪 Cenários de Teste Recomendados

### Teste 1: Fluxo Básico de Sucesso
```bash
1. Login com admin@example.com / admin123
2. GET /products (listar produtos)
3. POST /purchase com produto ID 1, quantidade 1
4. GET /transactions (verificar que transação foi criada)
5. GET /transactions/1 (detalhe da transação)
```

### Teste 2: Failover Multi-Gateway
```bash
1. POST /purchase com CVV 100 (falha no Gateway 1)
   → Sistema automaticamente tenta Gateway 2
   → Transação processada com sucesso
2. Verificar em /transactions que usou Gateway 2
```

### Teste 3: Validações
```bash
1. POST /purchase SEM card_number → 422
2. POST /purchase com product_id 999 → 404
3. POST /purchase com CVV "abc" → 422
4. POST /purchase com card_number com 15 dígitos → 422
```

### Teste 4: Reembolso
```bash
1. POST /purchase (criar transação com ID X)
2. POST /transactions/X/refund (reembolsar)
3. GET /transactions/X (verificar status = "refunded")
4. POST /transactions/X/refund novamente → 400 (já foi reembolsado)
```

### Teste 5: Gerenciamento de Gateways
```bash
1. PATCH /gateways/1/toggle (desativar Gateway 1)
2. POST /purchase com CVV 010 
   → Só Gateway 2 está ativo, então usa Gateway 2
3. PATCH /gateways/1/toggle (reativar Gateway 1)
4. PATCH /gateways/2/priority com priority 1 (Gateway 2 agora é prioridade)
5. POST /purchase com CVV 100
   → Tenta Gateway 2 primeiro (sucesso)
```

---

## 📊 Verificando Dados no Banco

### Ver todas as transações
```bash
php artisan tinker
# Dentro do tinker:
>>> App\Models\Transaction::with(['client', 'gateway', 'products'])->get()
```

### Ver cliente com compras
```bash
>>> App\Models\Client::with('transactions')->find(1)
```

### Ver produtos
```bash
>>> App\Models\Product::all()
```

---

## 🐛 Troubleshooting

### Erro: "No active gateways available"
```
Causa: Todos os gateways estão com is_active = false
Solução: PATCH /gateways/{id}/toggle para reativar
```

### Erro: "All gateways failed"
```
Causa: Ambos os gateways retornaram erro
Solução: Verificar Docker (gateways rodando?), testar com CVV válido (010)
```

### Erro: 401 Unauthorized
```
Causa: Token expirado ou inválido
Solução: Fazer login novamente e obter novo token
```

### Erro: Connection refused no gateway
```
Causa: Docker não está rodando
Solução: docker run -p 3001:3001 -p 3002:3002 matheusprotzen/gateways-mock
```

---

## ✅ Checklist de Testes Completos

- [ ] Login funciona
- [ ] Token retornado
- [ ] Listar produtos funciona
- [ ] Criar produto funciona
- [ ] Atualizar produto funciona
- [ ] Deletar produto funciona
- [ ] Compra com sucesso (Gateway 1)
- [ ] Compra com sucesso (Failover para Gateway 2)
- [ ] Falha de validação retorna 422
- [ ] Produto não encontrado retorna 404
- [ ] Listar transações funciona
- [ ] Detalhe de transação funciona
- [ ] Reembolso funciona
- [ ] Reembolso duplicado retorna erro
- [ ] Listar clientes funciona
- [ ] Detalhe cliente com transações funciona
- [ ] Toggle gateway funciona
- [ ] Alterar prioridade funciona

---

**Pronto para começar os testes! 🚀**
