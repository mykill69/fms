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
        'department',
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

    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_CAMPUS_ADMIN = 'campus_administrator';
    const ROLE_OFFICE_HEAD = 'office_head';
    const ROLE_DIRECTOR = 'director';
    const ROLE_VPAA = 'vpaa';
    const ROLE_VPAF = 'vpaf';
    const ROLE_PRESIDENT = 'president';
    const ROLE_QA = 'quality_assurance';

    const AVAILABLE_PAGES = [
        'dashboard' => 'Dashboard',
        'feedbacks' => 'Feedbacks',
        'reports' => 'Reports',
        'flagged' => 'Flagged',
        'user_management' => 'User Management',
        'settings' => 'Settings',
    ];

    public static function getRoles()
    {
        return [
            // self::ROLE_SUPER_ADMIN => 'Super Administrator',
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
        $roles = self::getRoles();
        return $roles[$this->role] ?? ucfirst(str_replace('_', ' ', $this->role));
    }

    public function getRoleBadgeColorAttribute()
    {
        $colors = self::getRoleBadgeColors();
        return $colors[$this->role] ?? 'bg-gray-100 text-gray-700';
    }

    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isQualityAssurance()
    {
        return $this->role === self::ROLE_QA;
    }

    public function isAdmin()
    {
        $adminRoles = [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_CAMPUS_ADMIN,
            self::ROLE_DIRECTOR,
            self::ROLE_VPAA,
            self::ROLE_VPAF,
            self::ROLE_PRESIDENT,
            self::ROLE_QA
        ];
        return in_array($this->role, $adminRoles);
    }

    public function hasFullDashboardAccess()
    {
        return $this->role === self::ROLE_SUPER_ADMIN || $this->role === self::ROLE_QA;
    }

    public function canAccess($page)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        if ($this->isQualityAssurance() && $page !== 'user_management') {
            return true;
        }
        $permissions = $this->access_permissions;
        if (!is_array($permissions)) {
            $permissions = [];
        }
        return in_array($page, $permissions);
    }

    public function getAllowedPages()
    {
        if ($this->isSuperAdmin()) {
            return array_keys(self::AVAILABLE_PAGES);
        }
        if ($this->isQualityAssurance()) {
            $pages = array_keys(self::AVAILABLE_PAGES);
            $filtered = [];
            foreach ($pages as $page) {
                if ($page !== 'user_management') {
                    $filtered[] = $page;
                }
            }
            return $filtered;
        }
        $permissions = $this->access_permissions;
        if (!is_array($permissions)) {
            return [];
        }
        return $permissions;
    }
}