<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Policies Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where your policies will be created.
    | Default: 'Policies' (App\Policies)
    |
    */

    'namespace' => env('BASE_POLICIES_NAMESPACE', 'Policies'),

    /*
    |--------------------------------------------------------------------------
    | Models Path
    |--------------------------------------------------------------------------
    |
    | The path where your models are located.
    | This is used by the generate:policies command.
    |
    */

    'models_path' => app_path('Models'),

    /*
    |--------------------------------------------------------------------------
    | Permission Format
    |--------------------------------------------------------------------------
    |
    | Define how permissions are formatted by default.
    | Avaliable: snake, kebab, camel, pascal, lower, upper
    |
    */

    'permission_format' => env('BASE_POLICIES_PERMISSION_FORMAT', 'snake'),

    /*
    |--------------------------------------------------------------------------
    | Permission Separator
    |--------------------------------------------------------------------------
    |
    | Define how permissions are separated by default.
    | Avaliable: underscore, dot, hyphen, space, colon, slash, pipe
    |
    */
    'permission_separator' => env('BASE_POLICIES_PERMISSION_SEPARATOR', 'colon'),

    /*
    |--------------------------------------------------------------------------
    | Permission Method Mappings
    |--------------------------------------------------------------------------
    |
    | Map policy methods to permission names if you need custom naming.
    | Leave empty to use the method names as-is.
    |
    */

    'permission_mappings' => [
        'viewAny' => 'viewAny',
        'view' => 'view',
        'create' => 'create',
        'update' => 'update',
        'delete' => 'delete',
        'restore' => 'restore',
        // 'forceDelete' => 'forceDelete',
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin Bypass
    |--------------------------------------------------------------------------
    |
    | If enabled, users with these roles will automatically pass all
    | policy checks. Set to null or empty array to disable.
    |
    */

    'super_admin_bypass' => [
        'enabled' => env('BASE_POLICIES_SUPER_ADMIN_BYPASS', true),
        'roles' => ['super-admin', 'super_admin', 'admin', 'administrator'],
        'permissions' => ['all-access', 'all_access', 'full-access', 'full_access'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Policy Behavior
    |--------------------------------------------------------------------------
    |
    | Configure the default behavior for policy checks.
    |
    */

    'defaults' => [
        // Return false by default if permission check fails
        'deny_by_default' => true,
        
        // Log failed permission checks
        'log_failures' => env('BASE_POLICIES_LOG_FAILURES', false),
    ],

];