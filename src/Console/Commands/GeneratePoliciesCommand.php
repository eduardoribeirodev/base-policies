<?php

namespace EduardoRibeiroDev\BasePolicies\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class GeneratePoliciesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:policies
                            {--f|force : Overwrite existing policies}
                            {--dry-run : Show what would be created without actually creating}
                            {--e|except=* : Models to exclude from generation}
                            {--o|only=* : Only generate policies for these models}
                            {--a|abilities= : The default abilities methods implemented in the policy (comma-separated)}
                            {--s|seed : Seed the database with permissions after generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate policies for all models automatically';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $models = $this->getAllModels();
        $excluded = $this->option('except');
        $only = $this->option('only');

        // Filter models
        if (!empty($only)) {
            $lowerOnly = array_map(fn($model) => Str::lower($model), $only);
            $models = array_filter($models, fn($model) => in_array(Str::lower(class_basename($model)), $lowerOnly));
        }

        if (!empty($excluded)) {
            $lowerExcluded = array_map(fn($model) => Str::lower($model), $excluded);
            $models = array_filter($models, fn($model) => !in_array(Str::lower(class_basename($model)), $lowerExcluded));
        }

        if (empty($models)) {
            $this->components->warn('No models found to generate policies for.');
            return self::FAILURE;
        }

        $this->components->info('Found '.count($models).' model(s) to generate policies for.');
        $this->newLine();

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($models as $model) {
            $modelName = class_basename($model);
            $result = $this->generatePolicy($modelName);

            switch ($result) {
                case 'created':
                    $created++;
                    break;
                case 'skipped':
                    $skipped++;
                    break;
                case 'error':
                    $errors++;
                    break;
            }
        }

        $this->newLine();
        $this->displaySummary($created, $skipped, $errors);

        if ($this->option('seed')) {
            $this->call('generate:permissions', [
                '--force' => $this->option('force'),
                '--dry-run' => $this->option('dry-run'),
                '--except' => $excluded,
                '--only' => $only,
                '--abilities' => $this->option('abilities'),
            ]);

            $this->newLine();
            $this->components->info('Seeding of permissions completed.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Generate a single policy.
     */
    protected function generatePolicy(string $modelName): string
    {
        $namespace = config('base-policies.namespace', 'Policies');
        $policyPath = app_path("{$namespace}/{$modelName}Policy.php");

        // Check if policy already exists
        if (File::exists($policyPath) && !$this->option('force')) {
            $this->components->warn("Policy already exists at: {$policyPath}");
            return 'skipped';
        }

        if ($this->option('dry-run')) {
            $this->components->info("Would create: {$policyPath}");
            return 'created';
        }

        try {
            // Call make:base-policy command
            $this->call('make:base-policy', [
                'name' => $modelName,
                '--force' => $this->option('force'),
                '--abilities' => $this->option('abilities')
            ]);

            return 'created';
        } catch (\Exception $e) {
            $this->components->error("Failed to create {$policyName}: {$e->getMessage()}");
            return 'error';
        }
    }

    /**
     * Get all models in the application.
     */
    protected function getAllModels(): array
    {
        $modelsPath = config('base-policies.models_path', app_path('Models'));
        
        if (!File::isDirectory($modelsPath)) {
            return [];
        }

        $models = [];
        $namespace = $this->getAppNamespace() . 'Models';

        $finder = new Finder();
        $finder->files()->in($modelsPath)->name('*.php');

        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            $class = $namespace . '\\' . str_replace(
                ['/', '.php'],
                ['\\', ''],
                $relativePath
            );

            if ($this->isValidModel($class)) {
                $models[] = $class;
            }
        }

        return $models;
    }

    /**
     * Check if a class is a valid Eloquent model.
     */
    protected function isValidModel(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        $reflection = new ReflectionClass($class);

        // Skip abstract classes, traits, and interfaces
        if ($reflection->isAbstract() || $reflection->isTrait() || $reflection->isInterface()) {
            return false;
        }

        // Check if it extends Illuminate\Database\Eloquent\Model
        return is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class);
    }

    /**
     * Get the application namespace.
     */
    protected function getAppNamespace(): string
    {
        return $this->laravel->getNamespace();
    }

    /**
     * Display generation summary.
     */
    protected function displaySummary(int $created, int $skipped, int $errors): void
    {
        $this->components->info('Generation Summary:');
        $this->components->bulletList([
            "Created: {$created}",
            "Skipped: {$skipped}",
            "Errors: {$errors}",
        ]);

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->components->warn('This was a dry run. No files were actually created.');
            $this->components->info('Run without --dry-run to create the policies.');
        }
    }
}