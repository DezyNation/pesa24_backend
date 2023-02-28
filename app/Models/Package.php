<?php

namespace App\Models;

use App\Models\User;
use App\Models\Commission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Package extends Model
{
    protected $fillable = [
        'name',
        'organization_id',
        'role_id',
        'is_default'
    ];
    use HasFactory;

    /**
     * Get the user that owns the Package
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get all of the commissions for the Package
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function commissions(): BelongsToMany
    {
        return $this->belongsToMany(Commission::class)->withPivot(['commission', 'is_surcharge', 'is_flat']);
    }
}
