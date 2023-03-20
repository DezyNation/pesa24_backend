<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

class ParentUser extends Model
{
    use HasFactory, HasRoles;

    protected $table = 'users';

    /**
     * The users that belong to the ParentUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function parent(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_parent', 'parent_id')->with('roles');
    }

    public function roles(): BelongsToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            PermissionRegistrar::$pivotRole
        )->withPivot(['fee', 'minimum_balance']);

        if (!PermissionRegistrar::$teams) {
            return $relation;
        }

        return $relation->wherePivot(PermissionRegistrar::$teamsKey, getPermissionsTeamId())
            ->where(function ($q) {
                $teamField = config('permission.table_names.roles') . '.' . PermissionRegistrar::$teamsKey;
                $q->whereNull($teamField)->orWhere($teamField, getPermissionsTeamId());
            });
    }
}
