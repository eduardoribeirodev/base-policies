<?php

namespace EduardoRibeiroDev\BasePolicies\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BasePoliciesService
{
    /**
     * Returns the formmated permission string.
     */
    public function getPermissionName(string $action, Model|string|null $model = null): string
    {
        $mappedAction = config("base-policies.permission_mappings.{$action}", $action);
        $permission = $this->convertCase($mappedAction);

        if ($model !== null) {
            $separatorSymbol = $this->getSymbol();
            $permission .= $separatorSymbol . $this->convertCase(class_basename($model));
        }

        return $permission;
    }

    private function convertCase(string $text)
    {
        $case = Str::lower(config('base-policies.permission_format', 'snake'));
        return match ($case) {
            'snake' => Str::snake($text),
            'kebab' => Str::kebab($text),
            'camel' => Str::camel($text),
            'pascal' => Str::pascal($text),
            'lower' => Str::lower($text),
            'upper' => Str::upper($text),
            default => throw new \InvalidArgumentException("Invalid permission case: {$case}"),
        };
    }

    private function getSymbol()
    {
        $separator = config('base-policies.permission_separator', '');
        return match ($separator) {
            'underscore' => "_",
            'dot' => ".",
            'hyphen' => "-",
            'space' => " ",
            'colon' => ":",
            'slash' => "/",
            'pipe' => "|",
            default => throw new \InvalidArgumentException("Invalid permission separator: {$separator}"),
        };
    }

    private function stripPermissionName(string $permissionName)
    {
        $parts = explode($this->getSymbol(), $permissionName);
        
        if (count($parts) < 2) {
            throw new \InvalidArgumentException("Permission name must contain an action and a model separated by the configured symbol.");
        }

        return [
            'ability' => $parts[0],
            'resource' => $parts[1]
        ];
    }

    public function getPermissionLabel(Model|string $permission): string
    {
        $permissionName = ($permission instanceof Model) ? $permission->name : $permission;
        $parts = $this->stripPermissionName($permissionName);

        $abilityName = Str::camel($parts['ability']);
        $modelName = Str::headline(__($parts['resource']));
        $mappedPermissions = array_flip(config("base-policies.permission_mappings"));

        $ability = in_array($abilityName, $mappedPermissions) ? $mappedPermissions[$abilityName] : $abilityName;

        return __("base-policies::labels.{$ability}", ['model' => Str::lower($modelName)]);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(User $user): bool
    {
        if (!config('base-policies.super_admin_bypass.enabled')) {
            return false;
        }

        $roles = config('base-policies.super_admin_bypass.roles', []);
        $permissions = config('base-policies.super_admin_bypass.permissions', []);

        foreach ($roles as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return true;
            }
        }

        foreach ($permissions as $permission) {
            if (method_exists($user, 'hasPermission') && $user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has permission for the given action.
     */
    public function checkPermission(User $user, string $action, Model|string $model): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $modelName = class_basename($model);
        $permission = $this->getPermissionName($action, $modelName);

        $result = $user->hasPermissionTo($permission);

        if (!$result && config('base-policies.defaults.log_failures')) {
            Log::debug("Permission denied: {$permission} for user {$user->id}");
        }

        return $result;
    }

    public function groupPermissionsByResource(Collection|array $permissions): Collection
    {
        $permissionCollection = is_array($permissions) ? collect($permissions) : $permissions;
        return $permissionCollection
            ->mapToGroups(function (string $permission) {
                $parts = $this->stripPermissionName($permission);
                $resource = __($parts['resource']);

                return [$resource => $permission];
            });
    }
}
