<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait BuildsTenantScopedRules
{
    protected function tenantOwnedExistsRule(string $table, string $column = 'id', array $extra = [])
    {
        return Rule::exists($table, $column)->where(function ($query) use ($extra) {
            $query->where('created_by', creatorId());

            foreach ($extra as $field => $value) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        });
    }

    protected function companyUserExistsRule(string $type)
    {
        return $this->tenantOwnedExistsRule('users', 'id', [
            'type' => $type,
        ]);
    }

    protected function companyClientExistsRule()
    {
        return $this->companyUserExistsRule('client');
    }

    protected function companyVendorExistsRule()
    {
        return $this->companyUserExistsRule('vendor');
    }

    protected function companyWarehouseExistsRule(bool $activeOnly = true)
    {
        $extra = [];

        if ($activeOnly) {
            $extra['is_active'] = true;
        }

        return $this->tenantOwnedExistsRule('warehouses', 'id', $extra);
    }

    protected function companyProductExistsRule(?string $type = null, bool $activeOnly = true)
    {
        $extra = [];

        if ($activeOnly) {
            $extra['is_active'] = true;
        }

        if ($type !== null) {
            $extra['type'] = $type;
        }

        return $this->tenantOwnedExistsRule('product_service_items', 'id', $extra);
    }
}
