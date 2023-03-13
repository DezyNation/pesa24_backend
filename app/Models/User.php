<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Package;
use App\Models\Service;
use App\Models\PackageService;
use App\Models\KYCVerification;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'otp',
        'profile',
        'company_name',
        'phone_number',
        'mpin'
    ];

    /**k
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'updated_at',
        'portal',
        'referal_code',
        'onboard_fee',
        'kyc',
        'otp',
        'mpin'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the KYC associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function kyc(): HasOne
    {
        return $this->hasOne(KYCVerification::class);
    }

    /**
     * Get the package associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class)->select(['id', 'name']);
    }

    /**
     * Get the user associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class);
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

    /**
     * The services that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    // public function services(): BelongsToMany
    // {
    //     return $this->belongsToMany(Service::class);
    // }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class);
    }

    /**
     * Get the user associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    /**
     * The parents that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_parent', 'parent_id', 'user_id');
    }

    /**
     * The services that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_parent', 'user_id', 'parent_id');
    }

    /**
     * Get the organizations that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organizations(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
    
    /**
     * Get all of the services for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function services(): HasManyThrough
    {
        return $this->hasManyThrough(PackageService::class, PackageUser::class, 'user_id', 'package_id');
    }
}
