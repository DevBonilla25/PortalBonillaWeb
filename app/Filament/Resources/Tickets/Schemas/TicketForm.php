<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\Contact;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos operativos')
                    ->columns(3)
                    ->schema([
                        Select::make('company_id')
                            ->label('Empresa')
                            ->relationship('company', 'name')
                            ->default(fn () => Auth::user()?->company_id)
                            ->required()
                            ->live()
                            ->disabled(fn () => Company::query()->count() === 1)
                            ->dehydrated(),
                        TextInput::make('ticket_code')
                            ->label('Codigo de ticket')
                            ->required()
                            ->maxLength(100)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule, Get $get) => $rule->where('company_id', $get('company_id')),
                            )
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null),
                        TextInput::make('guide_number')
                            ->label('Numero de guia')
                            ->maxLength(100),
                        Select::make('branch_id')
                            ->label('Sucursal')
                            ->relationship(
                                'branch',
                                'name',
                                fn ($query, Get $get) => $query->when($get('company_id'), fn ($query, int $companyId) => $query->where('company_id', $companyId)),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('warehouse_id')
                            ->label('Bodega')
                            ->relationship(
                                'warehouse',
                                'name',
                                fn ($query, Get $get) => $query->when($get('company_id'), fn ($query, int $companyId) => $query->where('company_id', $companyId)),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('zone_id')
                            ->label('Zona')
                            ->relationship(
                                'zone',
                                'name',
                                fn ($query, Get $get) => $query->when($get('company_id'), fn ($query, int $companyId) => $query->where('company_id', $companyId)),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('priority')
                            ->label('Prioridad')
                            ->options(TicketPriority::class)
                            ->required()
                            ->default(TicketPriority::Normal->value),
                        Select::make('status')
                            ->label('Estado')
                            ->options(TicketStatus::class)
                            ->required()
                            ->default(TicketStatus::Created->value)
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('cashier_id')
                            ->label('Cajero')
                            ->relationship('cashier', 'name')
                            ->default(fn () => Auth::id())
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),
                Section::make('Cliente y destino')
                    ->columns(2)
                    ->schema([
                        Select::make('contact_id')
                            ->label('Contacto vinculado')
                            ->relationship(
                                'contact',
                                'first_name',
                                fn ($query, Get $get) => $query->when($get('company_id'), fn ($query, int $companyId) => $query->where('company_id', $companyId)),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->display_name)
                            ->searchable(['first_name', 'last_name', 'business_name', 'identification_number'])
                            ->preload()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (blank($state)) {
                                    return;
                                }

                                $contact = Contact::query()->find($state);

                                if (! $contact) {
                                    return;
                                }

                                $set('customer_name', $contact->display_name);
                                $set('customer_phone', $contact->phone);
                                $set('delivery_address', $contact->address);
                            }),
                        TextInput::make('customer_name')
                            ->label('Cliente')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('customer_phone')
                            ->label('Telefono')
                            ->tel()
                            ->maxLength(50),
                        Textarea::make('delivery_address')
                            ->label('Direccion de entrega')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('delivery_reference')
                            ->label('Referencia')
                            ->columnSpanFull(),
                    ]),
                Section::make('Asignacion inicial')
                    ->columns(2)
                    ->schema([
                        Select::make('current_driver_id')
                            ->label('Chofer')
                            ->relationship('currentDriver', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Chofer #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('current_vehicle_id')
                            ->label('Vehiculo')
                            ->relationship('currentVehicle', 'plate')
                            ->searchable(['plate', 'code', 'brand', 'model'])
                            ->preload()
                            ->nullable()
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                Section::make('Productos')
                    ->schema([
                        Repeater::make('items')
                            ->label('Productos')
                            ->relationship()
                            ->schema([
                                TextInput::make('product_code')
                                    ->label('Codigo')
                                    ->maxLength(100),
                                TextInput::make('product_name')
                                    ->label('Producto')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                TextInput::make('unit')
                                    ->label('Unidad')
                                    ->maxLength(50),
                                Textarea::make('observations')
                                    ->label('Observaciones')
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->defaultItems(1),
                    ]),
                Section::make('Documento y observaciones')
                    ->schema([
                        FileUpload::make('source_image_path')
                            ->label('Imagen de guia')
                            ->directory('ticket-guides')
                            ->image()
                            ->nullable(),
                        Textarea::make('observations')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
