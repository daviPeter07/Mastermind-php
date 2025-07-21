CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Cria a nossa tabela de usuários
CREATE TABLE users (
    -- ID será um UUID, que é um identificador único universal.
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

    -- Email deve ser único
    email VARCHAR(255) UNIQUE NOT NULL,

    name VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,

    -- Role terá um valor padrão 'USER' e só aceitará 'USER' ou 'ADMIN'.
    role VARCHAR(50) NOT NULL DEFAULT 'USER' CHECK (role IN ('USER', 'ADMIN')),

    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);