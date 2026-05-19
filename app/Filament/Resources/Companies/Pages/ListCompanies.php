<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    public function mount(): void
    {
        parent::mount();

        $company = Company::query()->first();

        if ($company && Company::query()->count() === 1) {
            $this->redirect(CompanyResource::getUrl('edit', ['record' => $company]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
