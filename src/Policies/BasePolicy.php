<?php

namespace EduardoRibeiroDev\BasePolicies\Policies;

use App\Models\User;
use EduardoRibeiroDev\BasePolicies\Facades\BasePolicies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BasePolicy
{
    public static function getModel()
    {
        $policyName = class_basename(static::class);
        return Str::before($policyName, 'Policy');
    }

    public function viewAny(User $user): bool
    {
        return BasePolicies::checkPermission($user, static::getModel(), "viewAny");
    }

    public function view(User $user): bool
    {
        return BasePolicies::checkPermission($user, static::getModel(), "view");
    }

    public function create(User $user): bool
    {
        return BasePolicies::checkPermission($user, static::getModel(), "create");
    }

    public function update(User $user): bool
    {
        return BasePolicies::checkPermission($user, static::getModel(), "update");
    }

    public function delete(User $user): bool
    {
        return BasePolicies::checkPermission($user, static::getModel(), "delete");
    }

    public function restore(User $user): bool
    {
        return BasePolicies::checkPermission($user, static::getModel(), "restore");
    }

    public function forceDelete(User $user): bool
    {
        return BasePolicies::checkPermission($user, static::getModel(), "forceDelete");
    }

    /**
     * Check if user owns the model.
     */
    protected function owns(User $user, Model $model): bool
    {
        if (isset($model->user_id) && $user->id === $model->user_id) {
            return true;
        }

        $relationMethod = Str::camel($model->getTable());
        if (
            method_exists($user, $relationMethod) &&
            $user->{$relationMethod}()->where($model->getKeyName(), $model->getKey())->exists()
        ) {
            return true;
        }

        return false;
    }
}
