# Comandos do Laravel Base Policies

Esta documentação descreve todos os comandos artisan disponíveis neste pacote.

## Índice

- [generate:permissions](#generatepermissions)
- [generate:policies](#generatepolicies)
- [make:base-policy](#makebase-policy)
- [give:permission](#givepermission)

---

## generate:permissions

Gera automaticamente permissões no banco de dados para todos os modelos.

### Descrição

Este comando varre o diretório de policies da aplicação e cria permissões correspondentes no banco de dados para cada modelo com sua respectiva política. É útil para inicializar o sistema de permissões ou adicionar permissões para novos modelos.

### Assinatura

```bash
php artisan generate:permissions [options]
```

### Opções

| Opção | Descrição |
|-------|-----------|
| `--force, -f` | Sobrescreve permissões existentes |
| `--dry-run` | Mostra o que seria criado sem realmente criar |
| `--except=MODEL` | Exclui modelos específicos da geração (pode ser usado múltiplas vezes) |
| `--only=MODEL` | Gera permissões apenas para estes modelos (pode ser usado múltiplas vezes) |
| `--abilities=ABILITIES` | Gera apenas estas habilidades (separadas por vírgula) |
| `--model=CLASS` | Especifica a classe do modelo de permissão |
| `--column=NAME` | Especifica a coluna de nome da tabela de permissões (padrão: name) |
| `--delete-orphans` | Deleta permissões que não têm policies correspondentes |

### Exemplos

```bash
# Gerar todas as permissões
php artisan generate:permissions

# Gerar com confirmação antes de criar
php artisan generate:permissions --dry-run

# Gerar apenas para modelos específicos
php artisan generate:permissions --only=User --only=Post

# Gerar excluindo modelos específicos
php artisan generate:permissions --except=Audit

# Gerar apenas habilidades específicas
php artisan generate:permissions --abilities=create,view

# Limpar permissões órfãs
php artisan generate:permissions --delete-orphans
```

---

## generate:policies

Gera automaticamente policies para todos os modelos da aplicação.

### Descrição

Varre os modelos da aplicação e gera policies correspondentes baseadas na classe `BasePolicy`, permitindo que você customize as regras de autorização para cada modelo.

### Assinatura

```bash
php artisan generate:policies [options]
```

### Opções

| Opção | Descrição |
|-------|-----------|
| `--force, -f` | Sobrescreve policies existentes |
| `--dry-run` | Mostra o que seria criado sem realmente criar |
| `--except=MODEL` | Exclui modelos específicos (pode ser usado múltiplas vezes) |
| `--only=MODEL` | Gera apenas para estes modelos (pode ser usado múltiplas vezes) |
| `--model-path=PATH` | Caminho dos modelos (padrão: app/Models) |
| `--policy-path=PATH` | Caminho das policies (padrão: app/Policies) |

### Exemplos

```bash
# Gerar todas as policies
php artisan generate:policies

# Simulação antes de criar
php artisan generate:policies --dry-run

# Gerar apenas para modelos específicos
php artisan generate:policies --only=User --only=Post

# Sobrescrever policies existentes
php artisan generate:policies --force
```

---

## make:base-policy

Cria uma nova policy baseada na classe `BasePolicy`.

### Descrição

Cria um arquivo de policy customizado para um modelo específico, permitindo que você defina regras de autorização específicas. A nova policy herda todas as funcionalidades da `BasePolicy`.

### Assinatura

```bash
php artisan make:base-policy {name} [options]
```

### Argumentos

| Argumento | Descrição |
|-----------|-----------|
| `name` | Nome da policy a ser criada |

### Opções

| Opção | Descrição |
|-------|-----------|
| `--model=CLASS` | Especifica o modelo para a policy |
| `--force, -f` | Sobrescreve a policy se já existir |

### Exemplos

```bash
# Criar uma policy básica
php artisan make:base-policy UserPolicy

# Criar uma policy para um modelo específico
php artisan make:base-policy PostPolicy --model=Post

# Sobrescrever uma policy existente
php artisan make:base-policy UserPolicy --force
```

---

## give:permission

Atribui uma permissão específica a um usuário pelo ID.

### Descrição

Este comando permite atribuir manualmente uma permissão a um usuário. É útil para:
- Dar permissões iniciais a usuários específicos
- Corrigir problemas de permissões
- Atribuições pontuais de permissões administrativas

Quando uma permissão não existe no banco de dados, o comando oferece a opção de criá-la automaticamente.

### Assinatura

```bash
php artisan give:permission {user_id} {permission} [options]
```

### Argumentos

| Argumento | Descrição |
|-----------|-----------|
| `user_id` | ID do usuário que receberá a permissão |
| `permission` | Nome da permissão a ser atribuída |

### Opções

| Opção | Descrição |
|-------|-----------|
| `--model=CLASS` | Especifica a classe do modelo de permissão (padrão: Spatie\Permission\Models\Permission) |

### Exemplos

```bash
# Dar permissão a um usuário
php artisan give:permission 1 view_user

# Dar permissão de criar posts
php artisan give:permission 5 create_post

# Dar permissão com modelo customizado
php artisan give:permission 1 view_user --model=App\\Models\\Permission
```

### Comportamento

- **Validação de Usuário**: Verifica se o usuário com o ID especificado existe
- **Compatibilidade**: Confirma se o modelo User suporta permissões (Spatie Laravel Permission)
- **Criação de Permissão**: Se a permissão não existir, oferece criar automaticamente
- **Prevenção de Duplicatas**: Alerta se o usuário já possui a permissão
- **Cache**: Limpa automaticamente o cache de permissões após a atribuição

### Codes de Saída

| Código | Significado |
|--------|------------|
| `0` | Sucesso |
| `1` | Falha (usuário não encontrado, erro ao atribuir permissão, etc.) |

### Mensagens Esperadas

```
✓ Permission 'view_user' successfully given to user John Doe (ID: 1).
```

```
⚠ User with ID 999 not found.
```

```
⚠ User Admin already has the permission 'create_post'.
```

---

## Fluxo Recomendado de Uso

Para configurar um novo sistema de permissões, siga esta sequência:

### 1. Gerar Policies (Inicial)
```bash
php artisan generate:policies
```

### 2. Gerar Permissões
```bash
php artisan generate:permissions
```

### 3. Atribuir Permissões a Usuários Específicos (Opcional)
```bash
php artisan give:permission 1 view_user
php artisan give:permission 1 create_post
```

---

## Boas Práticas

### ✅ Recomendado

- Use `--dry-run` antes de gerar permissões em produção
- Use `generate:permissions --only` quando adicionar novos modelos
- Use `give:permission` para atribuições pontuais
- Use roles (Spatie) em combinação com permissões para melhor gerenciamento em escala

### ❌ Evitar

- Não use `--force` sem revisar as mudanças primeiro
- Não atribua todas as permissões a todos os usuários
- Não confunda permissões com roles (use roles para grupos de permissões)

---

## Resolução de Problemas

### Permissão não criada automaticamente

Se o comando `give:permission` não conseguir criar a permissão automaticamente, você pode criar manualmente usando Tinker:

```bash
php artisan tinker
>>> Permission::create(['name' => 'create_post', 'guard_name' => 'web'])
```

### Usuário não encontrado

Certifique-se de que:
- O ID do usuário está correto
- O usuário existe no banco de dados
- Você está usando o modelo User correto

### Permissões não aparecem

Se as permissões não aparecerem para o usuário, limpe o cache:

```bash
php artisan cache:clear
```

---

## Configuração

Os comandos respeitam as configurações definidas em `config/base-policies.php`:

- `permission_format`: Formato das permissões (snake_case, camelCase, etc.)
- `permission_separator`: Separador entre ação e modelo (_. -, espaço, etc.)
- `super_admin_bypass`: Configuração de bypass para super admins

Consulte o arquivo de configuração para mais detalhes.
