CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE users (
    -- ID será um UUID, que é um identificador único universal.
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

    email VARCHAR(255) UNIQUE NOT NULL,

    name VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,

    -- Role terá um valor padrão 'USER' e só aceitará 'USER' ou 'ADMIN'.
    role VARCHAR(50) NOT NULL DEFAULT 'USER' CHECK (role IN ('USER', 'ADMIN')),

    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id SERIAL PRIMARY KEY, -- No Postgres, SERIAL é um inteiro que se auto-incrementa.
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL CHECK (type IN ('TASK', 'FINANCE')),

    -- A conexão crucial com a tabela de usuários
    user_id UUID NOT NULL,

    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

    -- Define que a coluna user_id é uma chave estrangeira que aponta para o id da tabela users
    CONSTRAINT fk_user
        FOREIGN KEY(user_id) 
        REFERENCES users(id)
        ON DELETE CASCADE, -- Se um usuário for deletado, suas categorias também serão.

    -- Garante que um usuário não pode ter duas categorias com o mesmo nome
    UNIQUE (user_id, name)
);