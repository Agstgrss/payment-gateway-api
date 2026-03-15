# Guia de Testes (fluxo completo)
Este guia descreve um fluxo manual que pode ser executado no Postman, Thunder Client ou similar para validar os principais recursos expostos pela API `payment-gateway-api`.

### 1️⃣ Login (token do Sanctum)
- **Endpoint:** `POST http://localhost:8000/api/login`
- **Body JSON:**
  ```json
  {
    "email": "admin@example.com",
    "password": "password"
  }
  ```
- **Resposta esperada:** `{ "token": "1|xxxxxx" }`
- Copie o `token` retornado para usar nos próximos passos.

### 2️⃣ Configurar Authorization
- Em todas as rotas privadas (rotas dentro de `auth:sanctum`) adicione **header**:
  - `Authorization: Bearer SEU_TOKEN`
- No Postman:
  ```
  Authorization → Bearer Token → cole o token gerado
  ```

### 3️⃣ Criar um produto (miolo do CRUD)
- **Endpoint:** `POST http://localhost:8000/api/products`
- **Body JSON:**
  ```json
  {
    "name": "Notebook Gamer",
    "amount": 500000
  }
  ```
- `amount` representa centavos (R$5.000,00).
- **Resposta esperada:** Objeto do produto criado com `id`, `name`, `amount`.

### 4️⃣ Listar produtos
- **Endpoint:** `GET http://localhost:8000/api/products`
- Confirma retorno da lista (mínimo o produto criado).

### 5️⃣ Realizar compra (rota pública)
- **Endpoint:** `POST http://localhost:8000/api/purchase`
- **Body JSON:**
  ```json
  {
    "product_id": 1,
    "quantity": 1,
    "name": "Augusto",
    "email": "augusto@email.com",
    "card_number": "5569000000006063",
    "cvv": "010"
  }
  ```
- Fluxo esperado:
  1. API seleciona gateway ativo de menor prioridade (`Gateway1`).
  2. Se `Gateway1` falhar, tenta `Gateway2`.
  3. Cria `Transaction` + `TransactionProduct` com os dados retornados.
- **Resultado esperado:** resposta com `transaction_id`, `external_id`, `status`, `amount`, `card_last_numbers`, `created_at`.

### 6️⃣ Listar transações
- **Endpoint:** `GET http://localhost:8000/api/transactions`
- Deve listar todas as transações com cliente, gateway e produtos.
- **Resposta esperada (exemplo):**
  ```json
  [
    {
      "id": 1,
      "status": "success",
      "amount": 500000
    }
  ]
  ```

### 7️⃣ Ver detalhe da compra
- **Endpoint:** `GET http://localhost:8000/api/transactions/1`
- Confirme:
  - Cliente associado
  - Gateway utilizado
  - Produtos e quantidades
  - Valor e status

### 8️⃣ Listar clientes
- **Endpoint:** `GET http://localhost:8000/api/clients`
- Retorna todos os clientes com suas transações (mínimo o cliente criado).

### 9️⃣ Detalhes de um cliente
- **Endpoint:** `GET http://localhost:8000/api/clients/1`
- Deve trazer informações do cliente + todas as compras relacionadas (com gateway e produtos).

### 🔟 Testar fallback de gateway
- Faça outra compra com **CVV `200`** para simular erro no Gateway 1:
  ```json
  {
    "product_id": 1,
    "quantity": 1,
    "name": "Tester",
    "email": "tester@email.com",
    "card_number": "5569000000006063",
    "cvv": "200"
  }
  ```
- **Resultado esperado:**
  1. Gateway 1 responde erro (`cvv` 200).
  2. Gateway 2 é acionado e conclui a transação.
  3. Nova transação com status `success` salva no banco.

### 1️⃣1️⃣ Reembolso
- **Endpoint:** `POST http://localhost:8000/api/transactions/1/refund`
- Deve chamar o gateway correto (pelo nome registrado) e atualizar o status da transação para `refunded`.
- **Resultado esperado:** retorno com mensagem de sucesso e dados atualizados da transação.

### Checklist geral antes de finalizar
- [ ] Containers estão rodando (`docker compose ps`).
- [ ] `.env` copiado de `.env.example` e ajustado.
- [ ] `php artisan migrate --seed` executado.
- [ ] Token válido (role ADMIN) usado nos headers das rotas privadas.
- [ ] Mock dos gateways (`http://gateways:3001` e `http://gateways:3002`) respondendo.
