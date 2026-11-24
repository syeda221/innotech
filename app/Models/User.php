<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

// use Spatie\Permission\Models\Role;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
     * Get the employee profile associated with this user
     */
    public function employee()
    {
        return $this->hasOne(\App\Models\Hr\Employee::class);
    }

    /**
     * Check if user's employee profile is active (can login)
     * Returns true if user has no employee profile (admin/non-employee users)
     * Returns false if employee status is non-active or terminated
     */
    public function isEmployeeActive()
    {
        // Super Admin users can always login and should never be deactivated by employee status
        if ($this->isSuperAdmin()) {
            return true;
        }

        $employee = $this->employee;

        // If no employee profile, user can login (admin users, etc.)
        if (! $employee) {
            return true;
        }

        // Only active employees can login (case-insensitive check)
        $status = strtolower(trim((string) ($employee->status ?? '')));
        return in_array($status, ['active', '1', 'true', 'approved']);
    }

    //    public function roles()
    //     {
    //         return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id')
    //                     ->where('model_type', User::class);
    //     }

    /**
     * Check if user is a Super Admin / unrestricted system owner
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin')
            || $this->hasRole('superAdmin')
            || $this->hasRole('admin')
            || $this->hasRole('superadmin')
            || $this->email === 'superadmin@example.com';
    }

    /**
     * Get all permissions this user is allowed to manage/delegate to other roles.
     * Super admins get all permissions in the system.
     * Delegated users only get the permissions they themselves possess.
     */
    public function getManageablePermissions()
    {
        if ($this->isSuperAdmin()) {
            return \Spatie\Permission\Models\Permission::orderBy('name', 'asc')->get();
        }

        return $this->getAllPermissions()->sortBy('name')->values();
    }

    /**
     * Get all roles this user is allowed to assign to other users.
     * Super admins get all roles.
     * Delegated users only get roles whose permissions are a subset of what the user has.
     */
    public function getManageableRoles()
    {
        if ($this->isSuperAdmin()) {
            return \Spatie\Permission\Models\Role::with('permissions')->orderBy('name', 'asc')->get();
        }

        $myPermNames = $this->getAllPermissions()->pluck('name')->toArray();

        return \Spatie\Permission\Models\Role::with('permissions')
            ->orderBy('name', 'asc')
            ->get()
            ->filter(function ($role) use ($myPermNames) {
                // Do not allow assigning superadmin roles
                if (in_array(strtolower($role->name), ['super admin', 'superadmin', 'admin'])) {
                    return false;
                }

                $rolePermNames = $role->permissions->pluck('name')->toArray();
                // A role is assignable only if all of its permissions are possessed by this user
                return empty(array_diff($rolePermNames, $myPermNames));
            })
            ->values();
    }

}
