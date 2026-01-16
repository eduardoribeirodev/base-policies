<?php

namespace EduardoRibeiroDev\BasePolicies\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getPermissionName(string $action, Illuminate\Database\Eloquent\Model|string|null $model = null)
 * @method static bool isAdmin(User $user)
 * @method static bool checkPermission(User $user, Illuminate\Database\Eloquent\Model|string $model, string $action)
 * @method static string getPermissionLabel(Illuminate\Database\Eloquent\Model|String $permission)
 * @method static Illuminate\Support\Collection groupPermissionsByResource(Illuminate\Support\Collection|array $permissions)
 */
class BasePolicies extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'base-policies';
    }
}