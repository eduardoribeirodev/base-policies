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
        return BasePolicies::checkPermission($user, "viewAny", static::getModel());
    }

    public function view(User $user): bool
    {
        return BasePolicies::checkPermission($user, "view", static::getModel());
    }

    public function create(User $user): bool
    {
        return BasePolicies::checkPermission($user, "create", static::getModel());
    }

    public function update(User $user): bool
    {
        return BasePolicies::checkPermission($user, "update", static::getModel());
    }

    public function delete(User $user): bool
    {
        return BasePolicies::checkPermission($user, "delete", static::getModel());
    }

    public function restore(User $user): bool
    {
        return BasePolicies::checkPermission($user, "restore", static::getModel());
    }

    public function forceDelete(User $user): bool
    {
        return BasePolicies::checkPermission($user, "forceDelete", static::getModel());
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
