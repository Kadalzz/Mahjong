<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const PERMISSIONS = [
        'dashboard'       => 'Lihat dashboard dan jadwal',
        'revenue'         => 'Lihat laporan pendapatan dan okupansi',
        'bookings_view'   => 'Lihat daftar booking',
        'bookings_manage' => 'Ubah status dan batalkan booking',
        'bookings_create' => 'Buat booking admin (Tunai, Owner, Blokir)',
        'tables_power'    => 'Nyalakan/matikan meja manual',
        'pause'           => 'Jeda meja 2/5/10 menit',
        'pricing'         => 'Ubah harga',
        'tables_manage'   => 'Kelola meja (tambah, ubah, hapus)',
    ];

    protected $attributes = [
        'role'      => 'staff',
        'is_active' => true,
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'permissions', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'permissions'       => 'array',
            'is_active'         => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return $this->isAdmin() || in_array($permission, $this->permissions ?? [], true);
    }

    public function hasAnyPermission(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function landingUrl(): string
    {
        if ($this->isAdmin() || $this->hasPermission('dashboard')) {
            return route('dashboard.index');
        }

        return match (true) {
            $this->hasPermission('pause')                                                   => route('dashboard.jeda'),
            $this->hasAnyPermission('bookings_view', 'bookings_manage', 'bookings_create') => route('dashboard.bookings'),
            $this->hasAnyPermission('tables_power', 'tables_manage')                       => route('dashboard.tables.index'),
            $this->hasPermission('pricing')                                                 => route('dashboard.pricing'),
            $this->hasPermission('revenue')                                                 => route('dashboard.revenue'),
            default                                                                         => route('dashboard.noaccess'),
        };
    }
}
