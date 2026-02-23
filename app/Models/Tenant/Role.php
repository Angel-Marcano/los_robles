<?php
namespace App\Models\Tenant;

use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole
{
    protected $connection = 'tenant';
}
