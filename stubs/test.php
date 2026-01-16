<?php

namespace {{ namespace }};

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

{{ docstring }}
abstract class BasePolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admin bypass
        if (config('base-policies.super_admin_bypass.enabled')) {
            $method = config('base-policies.super_admin_bypass.method', 'hasRole');
            $roles = config('base-policies.super_admin_bypass.roles', []);

            foreach ($roles as $role) {
                if (method_exists($user, $method) && $user->{$method}($role)) {
                    return true;
                }
            }
        }

        return null;
    }

    public function call($command, array $arguments = [])
    {
        $user = $arguments[0];

        if (!$user instanceof User) {
            throw new \InvalidArgumentException('First argument must be an instance of App\Models\User.');
        }

        $ability = $arguments[1] ?? null;

        if (method_exists($this, $ability)) {
            return $this->{$ability}($user, $ability);
        }

        return $this->checkPermission($user, $ability);
    }

    /**
     * Check if user has permission for the given action.
     */
    protected function checkPermission(User $user, string $action): bool
    {
        $modelName = $this->getModelName();
        $permission = $this->formatPermission($action, $modelName);

        $result = $user->can($permission);

        // Log failures if enabled
        if (!$result && config('base-policies.defaults.log_failures')) {
            \Log::debug("Permission denied: {$permission} for user {$user->id}");
        }

        return $result;
    }

    /**
     * Get the model name for permission checking.
     */
    protected function getModelName(): string
    {
        return Str::of(class_basename(static::class))
            ->before('Policy')
            ->snake()
            ->toString();
    }

    /**
     * Format the permission string.
     */
    protected function formatPermission(string $action, string $model): string
    {
        // Get mapped action name
        $mappedAction = config("base-policies.permission_mappings.{$action}", $action);

        // Format based on config
        $format = config('base-policies.permission_format', 'dot');

        return match ($format) {
            'underscore' => "{$mappedAction}_{$model}",
            'dot' => "{$mappedAction}.{$model}",
            'hyphen' => "{$mappedAction}-{$model}",
            'camel' => Str::camel("{$mappedAction} {$model}"),
            'colon' => "{$mappedAction}:{$model}",
            default => throw new \InvalidArgumentException("Invalid permission format: {$format}"),
        };
    }

    /**
     * Check if user owns the model.
     */
    protected function owns(User $user, Model $model): bool
    {
        return isset($model->user_id) && $user->id === $model->user_id;
    }

    /**
     * Check if user is admin.
     */
    protected function isAdmin(User $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('admin');
    }
}