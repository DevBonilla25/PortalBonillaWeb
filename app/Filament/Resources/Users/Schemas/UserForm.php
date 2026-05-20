<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Company;
use App\Models\Employee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cuenta de acceso')
                    ->description('Credenciales para iniciar sesión en el panel o la app.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre para mostrar')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Correo de acceso')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->confirmed()
                            ->rule(Password::defaults())
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        TextInput::make('password_confirmation')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(fn (string $operation): bool => $operation === 'create'),
                            TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(50),
                        Toggle::make('is_active')
                            ->label('Cuenta activa')
                            ->default(true),
                    ]),
                Section::make('Organización')
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
                        Select::make('employee_id')
                            ->label('Empleado vinculado')
                            ->helperText('Opcional. Vincula la cuenta a un empleado sin usuario asignado.')
                            ->relationship(
                                name: 'employee',
                                titleAttribute: 'first_name',
                                modifyQueryUsing: function ($query, Get $get, $livewire) {
                                    $record = $livewire->getRecord();

                                    $query
                                        ->when(
                                            $get('company_id'),
                                            fn ($query, int $companyId) => $query->where('company_id', $companyId),
                                        )
                                        ->where(function ($query) use ($record) {
                                            $query->whereDoesntHave('user');

                                            if ($record?->employee_id) {
                                                $query->orWhere('id', $record->employee_id);
                                            }
                                        });
                                },
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Employee $record): string => trim("{$record->display_name} ({$record->employee_code})"),
                            )
                            ->searchable(['first_name', 'last_name', 'employee_code', 'email'])
                            ->preload()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                if (blank($state)) {
                                    return;
                                }

                                $employee = Employee::query()->find($state);

                                if (! $employee) {
                                    return;
                                }

                                if (blank($get('name'))) {
                                    $set('name', $employee->display_name);
                                }

                                if (blank($get('email')) && filled($employee->email)) {
                                    $set('email', $employee->email);
                                }

                                if (blank($get('phone')) && filled($employee->phone)) {
                                    $set('phone', $employee->phone);
                                }
                            }),
                    ]),
                Section::make('Roles y permisos')
                    ->schema([
                        Select::make('roles')
                            ->label('Roles del sistema')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required(),
                    ]),
            ]);
    }
}
