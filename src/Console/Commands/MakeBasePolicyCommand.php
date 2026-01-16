<?php

namespace EduardoRibeiroDev\BasePolicies\Console\Commands;

use EduardoRibeiroDev\BasePolicies\Facades\BasePolicies;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class MakeBasePolicyCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:base-policy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new policy class extending BasePolicy';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Policy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->resolveModelOption();

        if (!$this->option('force') && $this->alreadyExists($this->getNameInput())) {
            if ($this->confirm('Policy already exists. Do you want to overwrite it?')) {
                $this->input->setOption('force', true);
            }
        }

        return parent::handle();
    }

    /**
     * Resolve the model option if not provided.
     */
    protected function resolveModelOption()
    {
        if ($this->option('model')) {
            return;
        }

        $name = $this->getNameInput();
        $modelName = Str::studly(class_basename($name));

        if (Str::endsWith($modelName, 'Policy')) {
            $modelName = substr($modelName, 0, -6);
        }
        
        if (!empty($modelName)) {
            $this->input->setOption('model', $modelName);
        }
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');
        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        $name = str_replace('/', '\\', $name);

        if (!Str::endsWith($name, 'Policy')) {
            $name .= 'Policy';
        }

        $namespace = $this->getDefaultNamespace(trim($rootNamespace, '\\'));

        return $namespace . '\\' . $name;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/policy.base.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath($stub)
    {
        // Check for published stubs first
        $customPath = base_path('stubs/vendor/base-policies' . Str::after($stub, '/stubs'));

        if (file_exists($customPath)) {
            return $customPath;
        }

        // Fall back to package stubs
        return file_exists($packagePath = __DIR__ . '/../../' . $stub)
            ? $packagePath
            : __DIR__ . $stub;
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\' . config('base-policies.namespace', 'Policies');
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);
        $model = $this->option('model');

        return $this->replaceModel($stub, $model);
    }

    /**
     * Replace the model for the given stub.
     */
    protected function replaceModel($stub, $model)
    {
        $modelClass = $this->parseModel($model);
        $modelName = class_basename($modelClass);
        $namespaceModel = $modelClass;

        if ($modelClass != $this->getUserModelClass()) {
            $namespaceModel .= ";\nuse " . $this->getUserModelClass();
        }
        
        if (!$this->validateModel($modelClass)) {
            $this->components->warn("Model [{$modelClass}] not found. Policy will be created anyway.");
        }

        $abilitiesMethods = $this->hasOption('abilities') && $this->option('abilities')
            ? $this->generateAbilitiesMethods($this->option('abilities'))
            : "\t//";

        $replace = [
            '{{ abilitiesMethods }}' => $abilitiesMethods,
            'DummyFullModelClass' => $namespaceModel,
            '{{ namespacedModel }}' => $namespaceModel,
            'DummyModelClass' => $modelName,
            '{{ model }}' => $modelName,
            'DummyModelVariable' => $modelClass != $this->getUserModelClass() ? Str::camel($modelName) : 'model',
            '{{ modelVariable }}' => $modelClass != $this->getUserModelClass() ? Str::camel($modelName) : 'model',
        ];

        return str_replace(array_keys($replace), array_values($replace), $stub);
    }

    /**
     * Generate ability methods from comma-separated string.
     *
     * @param string $abilities
     * @return string
     */
    protected function generateAbilitiesMethods($abilities)
    {
        $userModel = $this->getUserModelClass();

        $abilitiesArray = [];
        if (in_array($abilities, ['all', '=all'])) {
            $abilitiesArray = array_keys(config('base-policies.permission_mappings', []));
        } else {
            $abilitiesArray = explode(',', $abilities);
        }

        return collect($abilitiesArray)
            ->map(fn($ability) => trim($ability, " \n\r\t\v\0="))
            ->filter()
            ->map(function ($ability) use ($userModel) {
                $hasModel = !in_array($ability, ['viewAny', 'create']);
                
                $parameters = $this->buildMethodParameters($userModel, $hasModel);
                $methodBody = $this->buildMethodBody($ability, $hasModel);

                return "\tpublic function {$ability}({$parameters}): bool\n\t{\n{$methodBody}\n\t}";
            })
            ->join("\n\n");
    }

    /**
     * Build method parameters string.
     *
     * @param string $userModel
     * @param bool $hasModel
     * @return string
     */
    protected function buildMethodParameters($userModel, $hasModel)
    {
        $parameters = [
            'user' => class_basename($userModel)
        ];

        if ($hasModel) {
            $parameters['{{ modelVariable }} = null'] = '?{{ model }}';
        }

        return collect($parameters)
            ->map(fn($type, $name) => "{$type} \${$name}")
            ->join(', ');
    }

    /**
     * Build method body.
     *
     * @param string $ability
     * @param bool $hasModel
     * @return string
     */
    protected function buildMethodBody($ability, $hasModel)
    {
        $body = '';
        if ($hasModel) {
            $body .= "\t\tif (\$this->owns(\$user, \${{ modelVariable }})) {\n\t\t\treturn true;\n\t\t}\n";
        }

        $body .= "\t\treturn \$this->checkPermission(\$user, '{$ability}');";
        return $body;
    }

    /**
     * Get the configured user model class.
     *
     * @return string
     */
    protected function getUserModelClass()
    {
        $authProvider = config('auth.guards.' . config('auth.defaults.guard') . '.provider');
        $userModel = config("auth.providers.{$authProvider}.model");

        return $userModel ?: 'App\\Models\\User';
    }

    /**
     * Validate if the model class exists.
     *
     * @param string $modelClass
     * @return bool
     */
    protected function validateModel($modelClass)
    {
        return class_exists($modelClass);
    }

    /**
     * Get the fully-qualified model class name.
     */
    protected function parseModel($model)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new \InvalidArgumentException('Model name contains invalid characters.');
        }

        return $this->qualifyModel($model);
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the policy applies to'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the policy already exists'],
            ['abilities', 'a', InputOption::VALUE_OPTIONAL, 'The default abilities methods implemented in the policy (comma-separated)'],
        ];
    }
}