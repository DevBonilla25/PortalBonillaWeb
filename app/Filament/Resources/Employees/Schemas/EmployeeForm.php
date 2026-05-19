<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\EmploymentStatus;
use App\Models\Company;
use App\Models\Contact;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos laborales')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('Empresa')
                            ->relationship('company', 'name')
                            ->default(fn () => Auth::user()?->company_id)
                            ->required()
                            ->live()
                            ->disabled(fn () => Company::query()->count() === 1)
                            ->dehydrated(),
                        TextInput::make('employee_code')
                            ->label('Código de empleado')
                            ->required()
                            ->maxLength(50)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule, Get $get) => $rule->where('company_id', $get('company_id')),
                            )
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null),
                        Select::make('contact_id')
                            ->label('Contacto vinculado')
                            ->helperText('Opcional. Si la persona ya existe como contacto, selecciónala aquí; los datos personales se completarán automáticamente.')
                            ->relationship(
                                name: 'contact',
                                titleAttribute: 'first_name',
                                modifyQueryUsing: fn ($query, Get $get) => $query->when(
                                    $get('company_id'),
                                    fn ($query, int $companyId) => $query->where('company_id', $companyId),
                                ),
                            )
                            ->searchable(['first_name', 'last_name', 'business_name', 'identification_number'])
                            ->preload()
                            ->nullable()
                            ->live()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->display_name)
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (blank($state)) {
                                    return;
                                }

                                $contact = Contact::query()->find($state);

                                if (! $contact) {
                                    return;
                                }

                                $set('first_name', $contact->first_name);
                                $set('last_name', $contact->last_name);
                                $set('identification_number', $contact->identification_number);
                                $set('phone', $contact->phone);
                                $set('email', $contact->email);
                            }),
                        Select::make('employment_status')
                            ->label('Estado laboral')
                            ->options(EmploymentStatus::class)
                            ->required()
                            ->default(EmploymentStatus::Active->value),
                        TextInput::make('job_position')
                            ->label('Cargo')
                            ->maxLength(255),
                        DatePicker::make('hire_date')
                            ->label('Fecha de ingreso')
                            ->native(false),
                        DatePicker::make('termination_date')
                            ->label('Fecha de salida')
                            ->native(false),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ]),
                Section::make('Datos personales')
                    ->description(fn (Get $get): ?string => filled($get('contact_id'))
                        ? 'Copiados del contacto vinculado. Puedes corregirlos si el teléfono o correo laboral es distinto.'
                        : 'Obligatorios si no vinculas un contacto. El empleado guarda su propia copia para reportes y operación.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Nombres')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->label('Apellidos')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('identification_number')
                            ->label('Identificación')
                            ->maxLength(50),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->maxLength(255),
                    ]),
                Section::make('Asignación')
                    ->columns(2)
                    ->schema([
                        Select::make('branch_id')
                            ->label('Sucursal')
                            ->relationship(
                                'branch',
                                'name',
                                fn ($query, Get $get) => $query->when(
                                    $get('company_id'),
                                    fn ($query, int $companyId) => $query->where('company_id', $companyId),
                                ),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('warehouse_id')
                            ->label('Bodega')
                            ->relationship(
                                'warehouse',
                                'name',
                                fn ($query, Get $get) => $query->when(
                                    $get('company_id'),
                                    fn ($query, int $companyId) => $query->where('company_id', $companyId),
                                ),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),
            ]);
    }
}
