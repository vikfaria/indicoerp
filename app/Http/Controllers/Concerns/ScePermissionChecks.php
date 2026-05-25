<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;

trait ScePermissionChecks
{
    protected function canViewSceSuite(): bool
    {
        return $this->userCanAny([
            'manage-account',
            'manage-account-reports',
            'view-tax-summary',
        ]);
    }

    protected function canManageSceFiscal(): bool
    {
        return $this->userCanAny([
            'manage-account',
            'manage-account-reports',
        ]);
    }

    protected function canManageSceAccounting(): bool
    {
        return $this->userCanAny([
            'manage-account',
        ]);
    }

    private function userCanAny(array $permissions): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
