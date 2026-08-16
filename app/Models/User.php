<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, \App\Models\Traits\UsesTenantConnection;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 'name','first_name','last_name','document_type','document_number','email','password','active','accepted_privacy_at','accepted_terms_at','legal_version' ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
        'two_factor_code_expires_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'accepted_privacy_at' => 'datetime',
        'accepted_terms_at' => 'datetime',
    ];

    public function scopeActive($q){ return $q->where('active',true); }

    // ── Verificación en dos pasos ─────────────────────────────────────────

    public function twoFactorEnabled(): bool
    {
        return $this->two_factor_method !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Genera un código de 6 dígitos, lo guarda hasheado con expiración de 10 min
     * y devuelve el código en claro para enviarlo por correo.
     */
    public function generateTwoFactorCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->forceFill([
            'two_factor_code' => \Illuminate\Support\Facades\Hash::make($code),
            'two_factor_code_expires_at' => now()->addMinutes(10),
        ])->save();
        return $code;
    }

    /**
     * Verifica el código de correo; si es válido lo invalida (un solo uso).
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (!$this->two_factor_code || !$this->two_factor_code_expires_at || now()->gt($this->two_factor_code_expires_at)) {
            return false;
        }
        if (!\Illuminate\Support\Facades\Hash::check($code, $this->two_factor_code)) {
            return false;
        }
        $this->forceFill([
            'two_factor_code' => null,
            'two_factor_code_expires_at' => null,
        ])->save();
        return true;
    }

    /**
     * Verifica un código TOTP contra el secreto guardado (encriptado).
     */
    public function verifyTotpCode(string $code): bool
    {
        if (!$this->two_factor_secret) { return false; }
        try {
            $secret = \Illuminate\Support\Facades\Crypt::decryptString($this->two_factor_secret);
        } catch (\Throwable $e) {
            return false;
        }
        return (bool) app(\PragmaRX\Google2FA\Google2FA::class)->verifyKey($secret, $code, 1);
    }

}
