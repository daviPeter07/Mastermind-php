# Mastermind API 🧠

API RESTful para o assistente pessoal Mastermind, construída com PHP moderno, PostgreSQL e um ambiente de desenvolvimento 100% containerizado com Docker.

---

## ✨ Funcionalidades Principais

* **Autenticação Segura:** Sistema completo de registro e login utilizando hash de senhas (`bcrypt`) e Tokens de Acesso (`JWT`).
* **Gerenciamento de Usuários:** CRUD completo para usuários com sistema de permissões (`Roles`) para `ADMIN` e `USER`.
* **CRUD de Categorias:** Usuários autenticados podem gerenciar suas próprias categorias de tarefas e finanças.
* **Arquitetura em Camadas:** Código organizado com uma separação clara de responsabilidades (Router, Controllers, Services).

## 🛠️ Stack de Tecnologias

* **Linguagem:** PHP 8.3
* **Banco de Dados:** PostgreSQL 15
* **Ambiente de Desenvolvimento:** Docker & Docker Compose
* **Gerenciador de Pacotes:** Composer
* **Autenticação:** `firebase/php-jwt` para JSON Web Tokens
* **Variáveis de Ambiente:** `vlucas/phpdotenv`

---

## 🚀 Como Rodar o Projeto

Siga os passos abaixo para configurar e rodar o ambiente de desenvolvimento localmente.

### Pré-requisitos

* [Docker](https://www.docker.com/products/docker-desktop/)
* [Composer](https://getcomposer.org/)
* PHP (necessário para o Composer, a versão não precisa ser a mesma do contêiner)

### Passos de Instalação

1.  **Clone o Repositório**
    ```bash
    git clone https://github.com/daviPeter07/Mastermind-php
    cd Mastermind-php
    ```

2.  **Configure as Variáveis de Ambiente**
    Copie o arquivo de exemplo `.env.example` para um novo arquivo chamado `.env`.
    ```bash
    cp .env.example .env
    ```
    Em seguida, abra o arquivo `.env` e preencha com suas chaves e senhas.

3.  **Instale as Dependências do PHP**
    O Composer vai ler o `composer.json` e instalar as bibliotecas necessárias.
    ```bash
    composer install
    ```

4.  **Inicie o Ambiente Docker**
    Este comando vai construir as imagens e iniciar os contêineres da API e do Banco de Dados.
    ```bash
    docker compose up --build -d
    ```


Pronto! Sua API estará rodando em `http://localhost:8000`.

---

## 🔌 Documentação dos Endpoints da API

### Autenticação

#### `POST /api/auth/register`
Registra um novo usuário no sistema.

* **Body (JSON):**
    ```json
    {
        "name": "Alice Dev",
        "email": "alice@email.com",
        "password": "senhaForte123"
    }
    ```
* **Resposta de Sucesso (201 Created):**
    ```json
    {
        "user": {
            "id": "uuid-da-alice",
            "name": "Alice Dev",
            "email": "alice@email.com"
        },
        "token": "seu.token.jwt.aqui"
    }
    ```
* **Resposta de Erro (409 Conflict):**
    ```json
    {
        "error": "Este email já está em uso."
    }
    ```

#### `POST /api/auth/login`
Autentica um usuário e retorna um token JWT.

* **Body (JSON):**
    ```json
    {
        "email": "alice@email.com",
        "password": "senhaForte123"
    }
    ```
* **Resposta de Sucesso (200 OK):**
    ```json
    {
        "token": "seu.token.jwt.aqui",
        "user": {
            "id": "uuid-da-alice",
            "name": "Alice Dev",
            "email": "alice@email.com",
            "role": "USER"
        }
    }
    ```

### Categorias (Requer Autenticação)

Para todas as rotas abaixo, é necessário enviar o token no cabeçalho:
`Authorization: Bearer <SEU_TOKEN_JWT>`

#### `POST /api/categories`
Cria uma nova categoria para o usuário autenticado.

* **Body (JSON):**
    ```json
    {
        "name": "Faculdade",
        "type": "TASK"
    }
    ```
* **Resposta de Sucesso (201 Created):**
    ```json
    {
        "id": 1,
        "name": "Faculdade",
        "type": "TASK"
    }
    ```

#### `GET /api/categories`
Lista todas as categorias do usuário autenticado.

* **Resposta de Sucesso (200 OK):**
    ```json
    [
        {
            "id": 1,
            "name": "Faculdade",
            "type": "TASK",
            "created_at": "..."
        }
    ]
    ```

### Usuários (Requer Role de ADMIN)

#### `GET /api/users`
Lista todos os usuários do sistema. Requer um token de um usuário com `role` de `ADMIN`.

* **Resposta de Sucesso (200 OK):**
    ```json
    [
        {
            "id": "uuid-do-davi",
            "name": "Davi Peterson",
            "email": "davi@email.com",
            "role": "ADMIN",
            "created_at": "..."
        }
    ]
    ```
* **Resposta de Erro (403 Forbidden):**
    ```json
    {
        "error": "Acesso negado. Permissões de administrador necessárias."
    }
    ```

*(CRUDs de `User` e `Category` para `GET por ID`, `PUT` e `DELETE` seguem um padrão similar).*