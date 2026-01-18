<?php

namespace EduardoRibeiroDev\BasePolicies\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GivePermissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:permission
                            {user_id : The user ID to give permission to}
                            {permissions : The permissions name to give (comma-separated)}
                            {--model= : The Eloquent Model class for permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give specific permissions to a user by their ID';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $permissions = explode(',', $this->argument('permissions'));

        // Find the user
        $user = User::find($userId);

        if (!$user) {
            $this->components->error("User with ID {$userId} not found.");
            return self::FAILURE;
        }

        // Check if user has the permission method
        if (!method_exists($user, 'givePermissionTo')) {
            $this->components->error("User model does not support permissions. Install 'spatie/laravel-permission' package.");
            return self::FAILURE;
        }

        try {
            // Check if permission exists
            $permissionModel = $this->option('model') ?? config('permission.models.permission', 'Spatie\Permission\Models\Permission');
            $permissions = $permissionModel::whereIn('name', $permissions)->get();

            foreach ($permissions as $permission) {
                if (!$permission) {
                    $this->components->warn("Permission '{$permission}' does not exist in the database.");

                    if ($this->confirm('Do you want to create this permission?')) {
                        $permissionModel::create(['name' => $permission, 'guard_name' => 'web']);
                        $this->components->info("Permission '{$permission}' created successfully.");
                    } else {
                        return self::FAILURE;
                    }
                }

                // Check if user already has the permission
                if ($user->hasPermissionTo($permission)) {
                    $this->components->warn("User {$user->name} (ID: {$userId}) already has the permission '{$permission}'.");
                    return self::SUCCESS;
                }

                // Give permission to user
                $user->givePermissionTo($permission);

                // Clear cache if using caching
                if (method_exists($user, 'forgetCachedPermissions')) {
                    $user->forgetCachedPermissions();
                }

                $this->components->info("Permission '{$permission}' successfully given to user {$user->name} (ID: {$userId}).");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error("Error giving permission: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
