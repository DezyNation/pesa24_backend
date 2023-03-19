<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Ticket;
use App\Models\Package;
use App\Models\Service;
use App\Models\PackageService;
use App\Models\KYCVerification;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class User extends Authenticatable implements JWTSubject
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
        'mpin',
        'alternate_phone',
        'user_code',
        'company_name',
        'firm_type',
        'gst_number',
        'wallet',
        'minimum_balance',
        'dob',
        'portal',
        'pan_number',
        'aadhaar',
        'pan_photo',
        'aadhar_front',
        'aadhar_back',
        'profile_pic',
        'onboard_fee',
        'referal_code',
        'password',
        'mpin',
        'otp',
        'kyc',
        'line',
        'city',
        'state',
        'pincode',
        'profile',
        'organization_id',
        'paysprint_onboard',
        'device_number',
        'model_name'
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
        'otp',
        'mpin',
        'phone_number',
        'pan_photo',
        'aadhar_front',
        'aadhar_back',
        'profile_pic',
        'line',
        'city',
        'state',
        'pincode',
        'profile',
        'created_at',
        'email',
        'has_parent',
        'gst_number',
        'firm_type',
        'company_name',
        'user_code',
        'alternate_phone',
        'pan_number',
        'aadhaar',
        'kyc',
        'organization_id',
        'wallet',
        'minimum_balance',
        'dob'
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
     * The roles that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function parentsRoles(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_parent', 'user_id', 'parent_id')->with(['roles' => function ($q) {
            $q->select('role_id', 'model_id', 'name');
        }]);
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

    /**
     * Get all of the tickets for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * The funds that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function funds(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'funds', 'parent_id', 'user_id')->withPivot(['amount', 'bank_name', 'transaction_type', 'transaction_id', 'transaction_date', 'receipt', 'approved', 'status', 'remarks', 'admin_remarks', 'created_at', 'updated_at']);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
