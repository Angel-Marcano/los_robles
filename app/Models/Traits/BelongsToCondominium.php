<?php
namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCondominium
{
    protected static function bootBelongsToCondominium(): void
    {
        static::addGlobalScope('condominium', function (Builder $builder) {
            $id = app()->bound('currentCondominiumId') ? app('currentCondominiumId') : null;
            if ($id && \Schema::hasColumn($builder->getModel()->getTable(), 'condominium_id')) {
                $builder->where($builder->getModel()->getTable() . '.condominium_id', $id);
            }
        });
    }

    public function condominium()
    {
        return $this->belongsTo(\App\Models\Condominium::class);
    }
}
