<?php

namespace App\Models;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    use HasFactory;

    /**
     * Get all of the packages for the Organization
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }
    
    /**
     * The organizations that belong to the Organization
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'packages')->withPivot(['id', 'name']);
    }

    /**
     * Get all of the users for the Organization
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
