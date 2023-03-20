<?php

namespace App\Models;

use App\Models\Package;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Commission extends Model
{
    use HasFactory;

    /**
     * The package that belong to the Commission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function package(): BelongsToMany
    {
        return $this->belongsToMany(Package::class);
    }
}
