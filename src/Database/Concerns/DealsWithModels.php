<?php

declare(strict_types=1);

namespace Stancl\Tenancy\Database\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

trait DealsWithModels
{
    public static array $modelDirectories = ['App/Models'];

    public static Closure|null $modelDiscoveryOverride = null;

    public static function getModels(): Collection
    {
        if (static::$modelDiscoveryOverride) {
            return (static::$modelDiscoveryOverride)();
        }

        $modelFiles = Finder::create()->files()->name('*.php')->in(static::$modelDirectories)->depth('== 0');

        $classes = collect($modelFiles)->map(function (SplFileInfo $file) {
            $fileContents = str($file->getContents());
            $class = $fileContents->after('class ')->before("\n")->explode(' ')->first();

            if ($fileContents->contains('namespace ')) {
                try {
                    return new ($fileContents->after('namespace ')->before(';')->toString() . '\\' . $class);
                } catch (\Throwable $th) {
                    // Skip non-instantiable classes – we only care about models, and those are instantiable
                }
            }

            return null;
        })->filter();

        return $classes->filter(fn ($class) => $class instanceof Model);
    }

    public static function getTenantModels(): Collection
    {
        return static::getModels()->filter(fn (Model $model) => tenancy()->modelBelongsToTenant($model) || tenancy()->modelBelongsToTenantIndirectly($model));
    }

    protected static function getModelFromTable(string $table): Model|null
    {
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, Model::class)) {
                $model = new $class;

                if ($model->getTable() === $table) {
                    return $model;
                }
            }
        }

        return null;
    }

    public static function modelBelongsToTenant(Model $model): bool
    {
        return in_array(BelongsToTenant::class, class_uses_recursive($model::class));
    }

    public static function modelBelongsToTenantIndirectly(Model $model): bool
    {
        return in_array(BelongsToPrimaryModel::class, class_uses_recursive($model::class));
    }
}
