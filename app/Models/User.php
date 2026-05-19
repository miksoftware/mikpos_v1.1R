<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'phone',
        'is_active',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot('branch_id')
            ->withTimestamps();
    }

    /**
     * Preparation stations this user is assigned to (kitchen, bar, etc.).
     * Used to scope the Kitchen Panel so staff only see their own station(s).
     */
    public function preparationStations(): BelongsToMany
    {
        return $this->belongsToMany(
            PreparationStation::class,
            'preparation_station_user',
            'user_id',
            'preparation_station_id'
        )->withTimestamps();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Employee::class);
    }


    // Get user's role (global or for specific branch)
    public function getRole(?int $branchId = null): ?Role
    {
        $query = $this->roles();
        
        if ($branchId) {
            $query->wherePivot('branch_id', $branchId);
        } else {
            $query->wherePivotNull('branch_id');
        }
        
        return $query->first();
    }

    // Check if user has a specific permission
    public function hasPermission(string $permission, ?int $branchId = null): bool
    {
        // Check ALL user roles (both global and branch-specific)
        foreach ($this->roles as $role) {
            // Super admin has all permissions
            if ($role->name === 'super_admin') {
                return true;
            }

            // Check if this role has the permission
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    // Check if user is super admin
    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('name', 'super_admin')->exists();
    }

    // Check if user can access a specific branch
    public function canAccessBranch(int $branchId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->branch_id === $branchId;
    }

    // Get all permissions for user
    public function getAllPermissions(): array
    {
        $permissions = [];
        
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[$permission->name] = true;
            }
        }
        
        return array_keys($permissions);
    }

    /**
     * Determine the best landing route for this user based on the permissions
     * they actually have. Useful right after login so a user without
     * dashboard.view (e.g. a cook with only kitchen_panel.view) doesn't crash
     * with a 403.
     *
     * Order is from most-restricted (single-purpose roles) to most-broad.
     */
    public function landingRoute(): string
    {
        // Single-purpose role: kitchen panel staff
        if ($this->hasPermission('kitchen_panel.view') && !$this->hasPermission('dashboard.view')) {
            return route('kitchen-panel');
        }

        // Cashier-style accounts that go straight to POS
        if ($this->hasPermission('pos.access') && !$this->hasPermission('dashboard.view')) {
            return route('pos');
        }

        // Mostrador / waitstaff
        if ($this->hasPermission('mostrador.view') && !$this->hasPermission('dashboard.view')) {
            return route('mostrador');
        }

        // Kitchen orders panel (full visibility)
        if ($this->hasPermission('kitchen.view') && !$this->hasPermission('dashboard.view')) {
            return route('kitchen-orders');
        }

        // Default: dashboard for users who can see it
        if ($this->hasPermission('dashboard.view')) {
            return route('dashboard');
        }

        // Fallback for anyone else with at least one panel permission
        if ($this->hasPermission('kitchen_panel.view'))   return route('kitchen-panel');
        if ($this->hasPermission('mostrador.view'))       return route('mostrador');
        if ($this->hasPermission('pos.access'))           return route('pos');
        if ($this->hasPermission('kitchen.view'))         return route('kitchen-orders');
        if ($this->hasPermission('sales.view'))           return route('sales');

        // Truly nothing assigned — log them out gracefully on the login page.
        return route('login');
    }
}
