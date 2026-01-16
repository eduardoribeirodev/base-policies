<?php

namespace EduardoRibeiroDev\BasePolicies\Console\Commands;

use EduardoRibeiroDev\BasePolicies\Facades\BasePolicies;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Finder\Finder;

class GeneratePermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:permissions
                            {--f|force : Overwrite existing permissions}
                            {--dry-run : Show what would be created without actually creating}
                            {--e|except=* : Models to exclude from generation}
                            {--o|only=* : Only generate permissions for these models}
                            {--a|abilities=* : Only generate these abilities (comma-separated)}
                            {--m|model= : The Eloquent Model for permissions}
                            {--c|column=name : The permissions table name column}
                            {--delete-orphans : Delete permissions that no longer have corresponding policies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate permissions in database for all models automatically';

    /**
     * The permission name column
     *
     * @var string
     */
    protected $columnName;

    /**
     * The table name for permissions.
     *
     * @var Model
     */
    protected $permissionModel;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->columnName = $this->option('column');
        $this->permissionModel = $this->resolvePermissionModel();

        if (!$this->permissionModel) {
            return self::FAILURE;
        }

        $policies = $this->getAllPolicies();
        $excluded = $this->option('except');
        $only = $this->option('only');

        if (!empty($only)) {
            $lowerOnly = array_map(fn($model) => Str::lower($model), $only);
            $policies = array_filter($policies, fn($model) => in_array(Str::lower(class_basename($model)), $lowerOnly));
        }

        if (!empty($excluded)) {
            $lowerExcluded = array_map(fn($model) => Str::lower($model), $excluded);
            $policies = array_filter($policies, fn($model) => !in_array(Str::lower(class_basename($model)), $lowerExcluded));
        }

        if (empty($policies)) {
            $this->components->warn('No policies found to generate permissions for.');
            return self::FAILURE;
        }

        $this->components->info('Found ' . count($policies) . ' model(s) to generate permissions for.');
        $this->newLine();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($policies as $police) {
            $result = $this->generatePermissionsForPolice($police);

            $created += $result['created'];
            $updated += $result['updated'];
            $skipped += $result['skipped'];
            $errors += $result['errors'];
        }

        if ($this->option('delete-orphans') && !$this->option('dry-run')) {
            $deleted = $this->deleteOrphanPermissions();
            $this->newLine();
            $this->components->info("Deleted {$deleted} orphan permission(s).");
        }

        $this->newLine();
        $this->displaySummary($created, $updated, $skipped, $errors);

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function resolvePermissionModel()
    {
        $modelsPath = config('base-policies.models_path', app_path('Models'));
        $permissionModelName = $this->option('model')
            ? $modelsPath . '\\' . $this->option('model')
            : config('permission.models.permission', $modelsPath . '\\Permission');

        if (class_exists($permissionModelName)) {
            return $permissionModelName;
        } else {
            $this->components->error("Permission model [{$permissionModelName}] not found.");
            return null;
        }
    }

    /**
     * Generate permissions for a single model.
     */
    protected function generatePermissionsForPolice(string $police): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $abilities = $this->getAbilitiesForPolice($police);

        foreach ($abilities as $ability) {
            $permission = BasePolicies::getPermissionName($ability, $police::getModel());
            $status = $this->createOrUpdatePermission($permission);

            $result[$status]++;
        }

        return $result;
    }

    /**
     * Get abilities for a specific police.
     */
    protected function getAbilitiesForPolice(string $police): array
    {
        $customAbilities = $this->option('abilities');

        if (!empty($customAbilities)) {
            $abilities = [];
            foreach ($customAbilities as $abilityList) {
                $abilities = array_merge($abilities, explode(',', $abilityList));
            }
            return array_map('trim', $abilities);
        }

        $methods = get_class_methods($police);
        return collect($methods)
            ->flip()
            ->except([
                'getModel',
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'forceDelete',
                'restore'
            ])
            ->keys()
            ->merge(array_keys(config('base-policies.permission_mappings')))
            ->toArray();
    }

    /**
     * Create or update a permission in the database.
     */
    protected function createOrUpdatePermission(string $permission): string
    {
        if ($this->option('dry-run')) {
            $this->components->info("Would create/update: {$permission}");
            return 'created';
        }

        try {
            $data = [
                $this->columnName => $permission,
            ];

            $existing = $this->permissionModel::where($this->columnName, $permission)->first();

            if ($existing) {
                if ($this->option('force')) {
                    $existing->update($data);

                    $this->components->info("Updated: {$permission}");
                    return 'updated';
                }

                $this->components->warn("Skipped (already exists): {$permission}");
                return 'skipped';
            }

            $this->permissionModel::create($data);

            $this->components->info("Created: {$permission}");
            return 'created';
        } catch (\Exception $e) {
            $this->components->error("Failed to create {$permission}: {$e->getMessage()}");
            return 'errors';
        }
    }

    /**
     * Generate a description for the permission.
     */
    protected function generateDescription(string $modelName, string $ability): string
    {
        $modelLabel = Str::title(str_replace('_', ' ', Str::snake($modelName)));
        $abilityLabel = Str::title(str_replace('_', ' ', Str::snake($ability)));

        return "{$abilityLabel} {$modelLabel}";
    }

    /**
     * Delete permissions that no longer have corresponding policies.
     */
    protected function deleteOrphanPermissions(): int
    {
        $policies = $this->getAllPolicies();
        $validPermissions = [];

        foreach ($policies as $police) {
            $abilities = $this->getAbilitiesForPolice($police);

            foreach ($abilities as $ability) {
                $validPermissions[] = BasePolicies::getPermissionName($ability, $police::getModel());
            }
        }

        $column = $this->columnName;

        return $this->permissionModel::whereNotIn($column, $validPermissions)->delete();
    }

    /**
     * Get all policies in the application.
     */
    protected function getAllPolicies(): array
    {
        $policesPath = app_path('Policies');

        if (!File::isDirectory($policesPath)) {
            return [];
        }

        $policies = [];

        $finder = new Finder();
        $finder->files()->in($policesPath)->name('*.php');

        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();

            $className = str_replace(
                ['/', '.php'],
                ['\\', ''],
                $relativePath
            );

            $fullClassName = 'App\\Policies\\' . $className;

            if (class_exists($fullClassName)) {
                $policies[] = $fullClassName;
            }
        }

        return $policies;
    }

    /**
     * Display generation summary.
     */
    protected function displaySummary(int $created, int $updated, int $skipped, int $errors): void
    {
        $this->components->info('Generation Summary:');
        $this->components->bulletList([
            "Created: {$created}",
            "Updated: {$updated}",
            "Skipped: {$skipped}",
            "Errors: {$errors}",
        ]);

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->components->warn('This was a dry run. No permissions were actually created.');
            $this->components->info('Run without --dry-run to create the permissions.');
        }
    }
}
