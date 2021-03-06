<?php

namespace Packages\Cms\Support\Abstracts;

use Illuminate\Console\GeneratorCommand;

abstract class Generator extends GeneratorCommand
{
    /**
     * @var array
     */
    protected $moduleInformation;

    /**
     * Get root folder of every modules by module type
     * @param array $type
     * @return string
     */
    protected function resolveModuleRootFolder($module)
    {
        $path = package_path();
        if (!ends_with('/', $path)) {
            $path .= '/';
        }

        return $path;
    }

    /**
     * Current module information
     * @return array
     */
    protected function getCurrentModule()
    {
        $alias = $this->argument('alias');

        $module = \Module::where('alias', $alias)->first();

        if (!$module) {
            $this->error('Module not exists');
            die();
        }

        $moduleRootFolder = $this->resolveModuleRootFolder($module);

        return $this->moduleInformation = array_merge((array) $module, [
            'module-path' => $moduleRootFolder . basename(str_replace(DIRECTORY_SEPARATOR.$module->type.'.json', '', $module->file)).DIRECTORY_SEPARATOR
        ]);
    }

    /**
     * Get module information by key
     * @param $key
     * @return array|mixed
     */
    protected function getModuleInfo($key = null)
    {
        if (!$this->moduleInformation) {
            $this->getCurrentModule();
        }
        if (!$key) {
            return $this->moduleInformation;
        }
        return array_get($this->moduleInformation, $key, null);
    }

    /**
     * Parse the name and format according to the root namespace.
     *
     * @param  string $name
     * @return string
     */
    protected function parseName($name)
    {
        if (str_contains($name, '/')) {
            $name = str_replace('/', '\\', $name);
        }

        $name = studly_case($name);
        return $this->getDefaultNamespace($name);
    }

    /**
     * Get the destination class path.
     *
     * @param  string $name
     * @return string
     */
    protected function getPath($name)
    {
        return package_path($this->getModuleInfo('alias')) . '/src/' . str_replace('\\', '/', std_namespace($name)) . '.php';
    }

    /**
     * Get the full namespace name for a given class.
     *
     * @param  string $name
     * @return string
     */
    protected function getNamespace($name)
    {
        $name = trim(implode('\\' ,array_slice(explode('\\', $name), 0, -1)), '\\');
        return module_namespace($this->getModuleInfo('alias'), $name);
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string $stub
     * @param  string $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $class = ucfirst(class_basename($name));

        return str_replace('DummyClass', $class, $stub);
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string $stub
     * @param  string $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace('DummyNamespace', $this->getNamespace($name), $stub);

        $stub = str_replace('DummyRootNamespace', $this->laravel->getNamespace(), $stub);

        if (method_exists($this, 'replaceParameters')) {
            $this->replaceParameters($stub);
        }

        return $this;
    }
}
