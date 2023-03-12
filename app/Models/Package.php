<?php

namespace App\Models;

use App\Models\User;
use App\Models\Commission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Package extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'organization_id',
        'role_id',
        'is_default'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Get the user that owns the Package
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
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

    /**
     * Get all of the services for the Package
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withPivot(['is_flat', 'commission', 'from', 'to', 'is_surcharge']);
    }

    /**
     * Get the organizations that owns the Package
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}