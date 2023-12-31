<?php

declare(strict_types=1);

namespace Stancl\Tenancy\Concerns;

use Illuminate\Support\Collection;
use Stancl\Tenancy\Contracts\Tenant;

trait DealsWithTenantSymlinks
{
    /**
     * Get all possible tenant symlinks, existing or not (array of ['public path' => 'storage path']).
     *
     * Tenants can have a symlink for each disk registered in the tenancy.filesystem.url_override config.
     *
     * This is used for creating all possible tenant symlinks and removing all existing tenant symlinks.
     *
     * @return Collection<string, string>
     */
    protected static function possibleTenantSymlinks(Tenant $tenant): Collection
    {
        $diskUrls = config('tenancy.filesystem.url_override');
        $disks = config('tenancy.filesystem.root_override');
        $suffixBase = config('tenancy.filesystem.suffix_base');
        $tenantKey = $tenant->getTenantKey();

        /** @var Collection<array<string, string>> $symlinks */
        $symlinks = collect([]);

        foreach ($diskUrls as $disk => $publicPath) {
            $storagePath = str_replace('%storage_path%', $suffixBase . $tenantKey, $disks[$disk]);
            $publicPath = str_replace('%tenant_id%', (string) $tenantKey, $publicPath);

            tenancy()->central(function () use ($symlinks, $publicPath, $storagePath) {
                $symlinks->push([public_path($publicPath) => storage_path($storagePath)]);
            });
        }

        return $symlinks->mapWithKeys(fn ($item) => $item); // [[a => b], [c => d]] -> [a => b, c => d]
    }

    /** Determine if the provided path is an existing symlink. */
    protected static function symlinkExists(string $link): bool
    {
        return file_exists($link) && is_link($link);
    }
}
