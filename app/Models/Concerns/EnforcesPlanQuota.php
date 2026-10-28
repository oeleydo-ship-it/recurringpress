<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

trait EnforcesPlanQuota
{
    protected function performInsert(Builder $query)
    {
        return $this->getConnection()->transaction(function () use ($query) {
            if ($this->company_id) {
                $company = Company::whereKey($this->company_id)->lockForUpdate()->firstOrFail();
                if ($company->hasReachedLimit($this->getTable())) {
                    throw ValidationException::withMessages([
                        'plan' => 'Your subscription is expired or the '.$this->getTable().' quota has been reached. Please update your plan.',
                    ]);
                }
            }

            return parent::performInsert($query);
        });
    }
}
