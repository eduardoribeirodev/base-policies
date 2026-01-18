# Base Policies

Um pacote Laravel poderoso para gerenciar políticas de autorização e permissões de forma simplificada e padronizada, integrando-se perfeitamente com a estrutura de autorização nativa do Laravel.

## ✨ Características

- 🔐 **Política Base Abstrata**: Implemente políticas de autorização de forma consistente
- 🎯 **Permissões Inteligentes**: Mapeamento automático de ações para permissões
- 🛠️ **Comandos Artisan**: Gere políticas e permissões automaticamente
- 🌐 **Formatação Flexível**: Suporte para múltiplos formatos de permissões (snake_case, kebab-case, camelCase, PascalCase, etc.)
- 📍 **Separadores Customizáveis**: Configure como as permissões são separadas (underscore, dot, hyphen, colon, etc.)
- 🌍 **Internacionalização**: Suporte multilíngue para rótulos de permissões
- 🔗 **Integração Spatie**: Funciona perfeitamente com o pacote `laravel-permission`

## 📦 Instalação

Instale o pacote via Composer:

```bash
composer require eduardoribeirodev/base-policies
```

Publique o arquivo de configuração:

```bash
php artisan vendor:publish --tag=base-policies-config
```

Opcionalmente, publique os stubs para customização:

```bash
php artisan vendor:publish --tag=base-policies-stubs
```

## ⚙️ Configuração

Após publicar, edite `config/base-policies.php` para suas necessidades:

```php
return [
    // Namespace onde suas políticas serão criadas
    'namespace' => env('BASE_POLICIES_NAMESPACE', 'Policies'),

    // Caminho onde seus modelos estão localizados
    'models_path' => app_path('Models'),

    // Formato de permissões: snake, kebab, camel, pascal, lower, upper
    'permission_format' => env('BASE_POLICIES_PERMISSION_FORMAT', 'snake'),

    // Separador de permissões: underscore, dot, hyphen, space, colon, slash, pipe
    'permission_separator' => env('BASE_POLICIES_PERMISSION_SEPARATOR', 'colon'),

    // Mapeamento de ações para permissões (customizável)
    'permission_mappings' => [
        'viewAny' => 'view',
        'view' => 'view',
        'create' => 'create',
        'update' => 'update',
        'delete' => 'delete',
        'restore' => 'restore',
        'forceDelete' => 'force_delete',
    ],
];
```

## 🚀 Uso

### Criar uma Política Base

Use o comando Artisan para criar uma nova política:

```bash
php artisan make:base-policy PostPolicy
```

Isso criará uma política que estende `BasePolicy`:

```php
<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use EduardoRibeiroDev\BasePolicies\Policies\BasePolicy;

class PostPolicy extends BasePolicy
{
    // Herda automaticamente: viewAny, view, create, update, delete, restore, forceDelete
}
```

### Gerar Permissões

Crie permissões automaticamente a partir de seus modelos:

```bash
php artisan generate:permissions
```

Isso criará permissões no formato configurado, por exemplo:
- `view:post`
- `create:post`
- `update:post`
- `delete:post`
- `restore:post`
- `force_delete:post`

### Gerar Políticas em Massa

Crie políticas para todos os seus modelos de uma vez:

```bash
php artisan generate:policies
```

### Usar no Seu Código

As políticas funcionam automaticamente com as permissões:

```php
// Autorizar visualização
if ($user->can('view', $post)) {
    // Usuário pode visualizar este post
}

// Autorizar criação
if ($user->can('create', Post::class)) {
    // Usuário pode criar posts
}

// Usar no controller
$this->authorize('view', $post);
$this->authorize('delete', $post);
```

### Usar a Facade

A facade `BasePolicies` fornece utilitários adicionais:

```php
use EduardoRibeiroDev\BasePolicies\Facades\BasePolicies;

// Obter o nome formatado da permissão
$permissionName = BasePolicies::getPermissionName('view', 'Post');
// Resultado: 'view:post'

// Verificar permissão
$allowed = BasePolicies::checkPermission($user, 'view', 'Post');

// Obter rótulo formatado da permissão
$label = BasePolicies::getPermissionLabel('view:post');
// Resultado: 'Ver Post'
```

## 🔧 Personalização

### Customizar Formatos

Configure o formato de permissões via `.env`:

```env
BASE_POLICIES_NAMESPACE=Authorization
BASE_POLICIES_PERMISSION_FORMAT=kebab
BASE_POLICIES_PERMISSION_SEPARATOR=colon
```

**Formatos Suportados:**
- `snake`: `view_any`
- `kebab`: `view-any`
- `camel`: `viewAny`
- `pascal`: `ViewAny`
- `lower`: `viewany`
- `upper`: `VIEWANY`

**Separadores Suportados:**
- `underscore`: `_`
- `dot`: `.`
- `hyphen`: `-`
- `space`: ` `
- `colon`: `:`
- `slash`: `/`
- `pipe`: `|`

### Adicionar Permissões Customizadas

Na sua política, sobrescreva métodos conforme necessário:

```php
class PostPolicy extends BasePolicy
{
    public function publish(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('publish:post');
    }

    public function archive(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('archive:post');
    }
}
```

## 📋 Estrutura do Projeto

```
laravel-policies/
├── config/
│   └── base-policies.php          # Configurações principais
├── src/
│   ├── BasePoliciesServiceProvider.php
│   ├── Console/
│   │   └── Commands/              # Comandos Artisan
│   ├── Facades/
│   │   └── BasePolicies.php       # Facade da aplicação
│   ├── Policies/
│   │   └── BasePolicy.php         # Classe base para políticas
│   └── Services/
│       └── BasePoliciesService.php # Lógica principal
├── resources/
│   └── lang/
│       └── pt_BR/                 # Traduções em Português
├── stubs/
│   ├── policy.base.stub           # Template para políticas
│   └── test.php                   # Template para testes
└── composer.json
```

## 🤝 Integração com Spatie Permission

Este pacote foi desenvolvido para funcionar com o `spatie/laravel-permission`:

```bash
composer require spatie/laravel-permission
```

Após instalar, execute as migrações:

```bash
php artisan migrate
```

As permissões geradas por este pacote funcionam automaticamente com as funções do Spatie:

```php
$user->givePermissionTo('view:post');
$user->syncPermissions(['view:post', 'create:post']);
$user->hasPermissionTo('delete:post');
```

## 📚 Exemplos Práticos

### Exemplo 1: Autorização em Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;

class PostController extends Controller
{
    public function show(Post $post)
    {
        $this->authorize('view', $post);
        return view('posts.show', compact('post'));
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);
        return view('posts.edit', compact('post'));
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);
        $post->delete();
        return redirect()->route('posts.index');
    }
}
```

### Exemplo 2: Verificação em Blade

```blade
@can('view', $post)
    <a href="{{ route('posts.show', $post) }}">Visualizar</a>
@endcan

@can('update', $post)
    <a href="{{ route('posts.edit', $post) }}">Editar</a>
@endcan

@can('delete', $post)
    <form action="{{ route('posts.destroy', $post) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit">Deletar</button>
    </form>
@endcan
```

### Exemplo 3: Atribuir Permissões a Usuários

```php
$user = User::find(1);

// Dar permissão individual
$user->givePermissionTo('view:post');

// Sincronizar múltiplas permissões
$user->syncPermissions([
    'view:post',
    'create:post',
    'update:post',
]);

// Atribuir role
$user->assignRole('editor');
```

## 🔐 Boas Práticas

1. **Use Políticas para Lógica Complexa**: Mantenha toda a lógica de autorização centralizada em políticas
2. **Nomeação Consistente**: Use os nomes de ação padrão (view, create, update, delete, etc.)
3. **Permissões Granulares**: Crie permissões específicas para cada recurso
4. **Roles e Permissões**: Use roles para grupos de permissões relacionadas
5. **Teste Suas Políticas**: Sempre teste suas políticas de autorização

## 📝 Licença

Este pacote é open-source e licenciado sob a Licença MIT.

## 👤 Autor

**Eduardo Ribeiro**
- Email: eduribeiro.films@gmail.com
- GitHub: [eduardoribeiromagalhaes](https://github.com/eduardoribeirodev)

## 🙏 Contribuições

Contribuições são bem-vindas! Sinta-se livre para abrir issues ou enviar pull requests para melhorar este pacote.

## 📖 Documentação Relacionada

- [Laravel Authorization](https://laravel.com/docs/authorization)
- [Spatie Laravel Permission](https://github.com/spatie/laravel-permission)
- [Laravel Policies](https://laravel.com/docs/authorization#creating-policies)

---

**Desenvolvido com ❤️ para a comunidade Laravel**
