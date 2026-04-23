<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'access_permissions',
        'status',
        'last_login_at',
        'last_login_ip'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'access_permissions' => 'array',
    ];

    // Role constants
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_CAMPUS_ADMIN = 'campus_administrator';
    const ROLE_OFFICE_HEAD = 'office_head';
    const ROLE_DIRECTOR = 'director';
    const ROLE_VPAA = 'vpaa';
    const ROLE_VPAF = 'vpaf';
    const ROLE_PRESIDENT = 'president';
    const ROLE_QA = 'quality_assurance';

    // Available pages for access control
    const AVAILABLE_PAGES = [
        'dashboard' => 'Dashboard',
        'feedbacks' => 'Feedbacks',
        'reports' => 'Reports',
        'ai_analysis' => 'AI Analysis',
        'offices' => 'All Offices',
        'user_management' => 'User Management',
    ];

    public static function getRoles()
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Administrator',
            self::ROLE_CAMPUS_ADMIN => 'Campus Administrator',
            self::ROLE_OFFICE_HEAD => 'Office Head',
            self::ROLE_DIRECTOR => 'Director',
            self::ROLE_VPAA => 'VPAA',
            self::ROLE_VPAF => 'VPAF',
            self::ROLE_PRESIDENT => 'President',
            self::ROLE_QA => 'Quality Assurance',
        ];
    }

    public static function getRoleBadgeColors()
    {
        return [
            self::ROLE_SUPER_ADMIN => 'bg-red-100 text-red-700',
            self::ROLE_CAMPUS_ADMIN => 'bg-purple-100 text-purple-700',
            self::ROLE_OFFICE_HEAD => 'bg-blue-100 text-blue-700',
            self::ROLE_DIRECTOR => 'bg-indigo-100 text-indigo-700',
            self::ROLE_VPAA => 'bg-yellow-100 text-yellow-700',
            self::ROLE_VPAF => 'bg-orange-100 text-orange-700',
            self::ROLE_PRESIDENT => 'bg-green-100 text-green-700',
            self::ROLE_QA => 'bg-teal-100 text-teal-700',
        ];
    }

    public function getRoleDisplayAttribute()
    {
        return self::getRoles()[$this->role] ?? ucfirst(str_replace('_', ' ', $this->role));
    }

    public function getRoleBadgeColorAttribute()
    {
        return self::getRoleBadgeColors()[$this->role] ?? 'bg-gray-100 text-gray-700';
    }

    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin()
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_CAMPUS_ADMIN,
            self::ROLE_DIRECTOR,
            self::ROLE_VPAA,
            self::ROLE_VPAF,
            self::ROLE_PRESIDENT,
            self::ROLE_QA
        ]);
    }

    public function canAccess($page)
    {
        // Super admin can access everything
        if ($this->role === self::ROLE_SUPER_ADMIN) {
            return true;
        }

        // Check specific permissions
        $permissions = $this->access_permissions ?? [];
        return in_array($page, $permissions);
    }

    public function getAllowedPages()
    {
        if ($this->role === self::ROLE_SUPER_ADMIN) {
            return array_keys(self::AVAILABLE_PAGES);
        }

        return $this->access_permissions ?? [];
    }
}