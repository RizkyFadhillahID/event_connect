<?php

namespace App\Traits;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization()
    {
        static::addGlobalScope('organization', function (Builder $query) {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user instanceof User && $user->organization_id) {
                    $query->where($query->getModel()->getTable() . '.organization_id', $user->organization_id);
                }
            }
        });

        static::creating(function ($model) {
            if (Auth::check() && !$model->organization_id) {
                $user = Auth::user();
                if ($user instanceof User && $user->organization_id) {
                    $model->organization_id = $user->organization_id;
                }
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
