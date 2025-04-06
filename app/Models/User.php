<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Dieses Model repräsentiert einen Nutzer in der Anwendung.
 * Ein Nutzer kann mehrere Rollen besitzen, ein Wallet haben, Bestellungen tätigen, Druckausschreibungen stellen, als Designer Designs erstellen und als Provider Druckausschreibungen annehmen.
 */
class User extends Model implements Authenticatable
{
    protected $table = 'User';
    protected $primaryKey = 'User_ID';

    protected $fillable = [
        'First_Name',
        'Last_Name',
        'Street',
        'House_Number',
        'Country',
        'City',
        'Postal_Code',
        'Email',
        'google_id',
        'Remember_Token'
    ];
    protected $hidden = ['Password_Hash'];
    public $timestamps = false;

    public function roles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserRole::class, 'User_ID');
    }

    public function wallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Wallet::class, 'User_ID');
    }

    public function designs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Design::class, 'Designer_ID');
    }

    public function getAuthIdentifierName()
    {
        return "User_ID";
    }

    public function getAuthIdentifier() {
        return $this->getKey();
    }

    public function getAuthPasswordName()
    {
        return "Password_Hash";
    }

    public function getAuthPassword()
    {
        return $this->Password_Hash;
    }

    public function getRememberToken()
    {
        return $this->Remember_Token;
    }

    public function setRememberToken($value)
    {
        $this->Remember_Token = $value;
        $this->save();
    }

    public function getRememberTokenName()
    {
        return "Remember_Token";
    }
}
