<?php

namespace App\Classes;

use App\Models\AddOn;
use App\Services\AssistantActivation\AssistantActivationCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class Module
{
    private const CACHE_META_PREFIX = 'module:meta:';
    private const CACHE_ENABLED_KEY = 'module:enabled:list';
    private const CACHE_ENABLED_ADMIN_KEY = 'module:enabled_admin:list';
    private const CACHE_DIRECTORIES_KEY = 'module:directories:list';
    private const CACHE_ALL_MODULES_KEY = 'module:all:list';

    protected $addon;
    public $name;
    public $alias;
    public $monthly_price;
    public $yearly_price;
    public $image;
    public $description;
    public $priority;
    public $child_module;
    public $parent_module;
    public $version;
    public $package_name;
    public $display;
    public $for_admin;
    public $is_enable;
    protected $allEnabled = [];

    public function json($name)
    {
        $path = base_path('packages/workdo/' . $name . '/module.json');
        if (!File::exists($path)) {
            return false;
        }

        $contents = File::get($path);
        return json_decode($contents, true);
    }

    public function find($name)
    {
        return Cache::rememberForever(
            self::CACHE_META_PREFIX . $name,
            function () use ($name) {
                if ($name === 'general') {
                    $this->name =  $name;
                    $this->alias =  $name;
                } else {
                    $this->addon = AddOn::where('module', $name)->orWhere('package_name', $name)->first();

                    $addonJson = $this->json($name);
                    if ($addonJson) {
                        $this->name = $addonJson['name'] ?? $name;
                        $this->alias = $addonJson['alias'] ?? $name;
                        $this->monthly_price = $addonJson['monthly_price'] ?? 0;
                        $this->yearly_price = $addonJson['yearly_price'] ?? 0;
                        $this->image = $this->addon->image ?? url('/packages/workdo/' . $name . '/favicon.png');
                        $this->description = $addonJson['description'] ?? "";
                        $this->priority = $addonJson['priority'] ?? 10;
                        $this->child_module = $addonJson['child_module'] ?? [];
                        $this->parent_module = $addonJson['parent_module'] ?? [];
                        $this->version = $addonJson['version'] ?? 1.0;
                        $this->package_name = $addonJson['package_name'] ?? null;
                        $this->display = $addonJson['display'] ?? true;
                        $this->for_admin = $addonJson['for_admin'] ?? false;
                        $this->is_enable = false;
                    }

                    if ($this->addon) {
                        $this->name = $this->addon->module ?? $name;
                        $this->alias = $this->addon->name ?? $name;
                        $this->monthly_price = $this->addon->monthly_price ?? 0;
                        $this->yearly_price = $this->addon->yearly_price ?? 0;
                        $this->image = $this->addon->image ? getImageUrlPrefix().'/'.$this->addon->image : url('/packages/workdo/' . $name . '/favicon.png');
                        $this->package_name = $this->addon->package_name ?? null;
                        $this->for_admin = $this->addon->for_admin ?? false;
                        $this->is_enable = $this->addon->is_enable ?? false;
                    }
                }

                return $this;
            }
        );
    }

    public function all()
    {
        $modules = $this->allEnabled();
        return $this->moduleArr($modules);
    }

    public function moduleArr($modules)
    {
        $allModulesArr = [];
        foreach ($modules as $module) {
            $moduleInstance = new self();
            $allModulesArr[] = $moduleInstance->find($module);
        }
        return $allModulesArr;
    }

    public function allEnabled(): array
    {
        return Cache::remember(self::CACHE_ENABLED_KEY, now()->addMinutes(10), function () {
            return AddOn::where('is_enable', 1)->orderBy('priority')->pluck('module')->toArray() ?? [];
        });
    }

    public function allEnabledAdmin(): array
    {
        return Cache::remember(self::CACHE_ENABLED_ADMIN_KEY, now()->addMinutes(10), function () {
            return AddOn::where('for_admin', 1)->where('is_enable', 1)->orderBy('priority')->pluck('module')->toArray() ?? [];
        });
    }

    public function getOrdered()
    {
        $modules = $this->all();

        usort($modules, function ($a, $b) {
            return $a->priority - $b->priority;
        });

        return $modules;
    }

    public function has($name)
    {
        return in_array($name, array_column($this->allModules(), 'name'));
    }

    public function isEnabled($module = null)
    {
        static $cache = [];

        if ($module) {
            if (!isset($cache[$module])) {

                $cache[$module] = Addon::where('module', $module)
                    ->where('is_enable', 1)
                    ->exists();
            }

            return $cache[$module];
        }

        return $this->addon && $this->addon->is_enable;
    }

    public function enable()
    {
        if ($this->addon) {
            $this->addon->is_enable = 1;
            $this->addon->save();

            $this->moduleCacheForget();

        }
    }

    public function disable()
    {
        if ($this->addon) {
            $this->addon->is_enable = 0;
            $this->addon->save();

            $this->moduleCacheForget();
        }
    }

    public function getDirectories()
    {
        return Cache::remember(self::CACHE_DIRECTORIES_KEY, now()->addMinutes(10), function () {
            $path = base_path('packages/workdo');
            return File::directories($path);
        });
    }

    public function getPath()
    {
        if (is_null($this->addon)) {
            return $this->getDirectories();
        }
        return base_path('packages/workdo/' . $this->name);
    }

    public function getDevPackagePath()
    {
        if (is_null($this->addon)) {
            $path = base_path('packages/workdo');
            return File::directories($path);
        }
        return base_path('packages/workdo/' . $this->name);
    }

    public function allModules()
    {
        return Cache::remember(self::CACHE_ALL_MODULES_KEY, now()->addMinutes(10), function () {
            $directories = array_map(function ($dir) {
                return basename($dir);
            }, $this->getDirectories());

            return $this->moduleArr($directories);
        });
    }

    public function moduleCacheForget($module = null)
    {
        try {
            app(AssistantActivationCacheService::class)->touchModuleVersion();
            Cache::forget(self::CACHE_ENABLED_KEY);
            Cache::forget(self::CACHE_ENABLED_ADMIN_KEY);
            Cache::forget(self::CACHE_DIRECTORIES_KEY);
            Cache::forget(self::CACHE_ALL_MODULES_KEY);

            if(is_null($module)){
                if ($this->addon) {
                    Cache::forget(self::CACHE_META_PREFIX . $this->addon->module);
                    Cache::forget(self::CACHE_META_PREFIX . $this->addon->package_name);
                }
            }
            else{
                Cache::forget(self::CACHE_META_PREFIX . $module);
            }
        } catch (\Exception $e) {
            \Log::error($module . $e->getMessage());
        }
    }
}
