<?php

declare(strict_types=1);

namespace Stancl\Tenancy\Database\Concerns;

use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Stancl\Tenancy\Tenancy;

trait HasScopedValidationRules
{
    public function unique($table, $column = 'NULL')
    {
        return (new Unique($table, $column))->where(Tenancy::tenantKeyColumn(), $this->getTenantKey());
    }

    public function exists($table, $column = 'NULL')
    {
        return (new Exists($table, $column))->where(Tenancy::tenantKeyColumn(), $this->getTenantKey());
    }
}
