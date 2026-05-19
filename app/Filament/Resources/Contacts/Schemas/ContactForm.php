<?php

namespace App\Filament\Resources\Contacts\Schemas;

use App\Enums\ContactType;
use App\Enums\IdentificationType;
use App\Enums\PersonType;
use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Clasificación')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('Empresa')
                            ->relationship('company', 'name')
                            ->default(fn () => Auth::user()?->company_id)
                            ->required()
                            ->disabled(fn () => Company::query()->count() === 1)
                            ->dehydrated(),
                        Select::make('contact_type')
                            ->label('Tipo de contacto')
                            ->options(ContactType::class)
                            ->required()
                            ->live(),
                        Select::make('person_type')
                            ->label('Tipo de persona')
                            ->options(PersonType::class)
                            ->required(),
                        Select::make('identification_type')
                            ->label('Tipo de identificación')
                            ->options(IdentificationType::class)
                            ->nullable(),
                        TextInput::make('identification_number')
                            ->label('Número de identificación')
                            ->maxLength(50),
                    ]),
                Section::make('Datos de persona')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get->enum('contact_type', ContactType::class) === ContactType::Person)
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Nombres')
                            ->required(fn (Get $get): bool => $get->enum('contact_type', ContactType::class) === ContactType::Person)
                            ->maxLength(255)
                            ->dehydrated(),
                        TextInput::make('last_name')
                            ->label('Apellidos')
                            ->maxLength(255)
                            ->dehydrated(),
                    ]),
                Section::make('Datos de empresa')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get->enum('contact_type', ContactType::class) === ContactType::Company)
                    ->schema([
                        TextInput::make('business_name')
                            ->label('Razón social')
                            ->required(fn (Get $get): bool => $get->enum('contact_type', ContactType::class) === ContactType::Company)
                            ->maxLength(255)
                            ->dehydrated(),
                        TextInput::make('commercial_name')
                            ->label('Nombre comercial')
                            ->maxLength(255)
                            ->dehydrated(),
                    ]),
                Section::make('Contacto y ubicación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('secondary_phone')
                            ->label('Teléfono secundario')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('city')
                            ->label('Ciudad')
                            ->maxLength(255),
                        TextInput::make('province')
                            ->label('Provincia')
                            ->maxLength(255),
                        TextInput::make('country')
                            ->label('País')
                            ->default('Ecuador')
                            ->maxLength(100),
                        Textarea::make('address')
                            ->label('Dirección')
                            ->columnSpanFull(),
                    ]),
                Section::make('Roles del contacto')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_customer')
                            ->label('Cliente')
                            ->default(false),
                        Toggle::make('is_supplier')
                            ->label('Proveedor')
                            ->default(false),
                        Toggle::make('is_employee_related')
                            ->label('Relacionado a empleado')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
