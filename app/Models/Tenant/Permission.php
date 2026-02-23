<?php
namespace App\Models\Tenant;

use Spatie\Permission\Models\Permission as BasePermission;

class Permission extends BasePermission
{
    protected $connection = 'tenant';
}
