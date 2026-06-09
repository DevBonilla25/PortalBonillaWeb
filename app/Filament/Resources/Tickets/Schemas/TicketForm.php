<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\Contact;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

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
                            ->disabled(fn (Get $get): bool => $get('external_source') === 'morfeus')
                            ->dehydrated()
                            ->maxLength(100)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule, Get $get) => $rule->where('company_id', $get('company_id')),
                            )
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null),
                        TextInput::make('guide_number')
                            ->label('Numero de guia')
                            ->disabled(fn (Get $get): bool => $get('external_source') === 'morfeus')
                            ->dehydrated()
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
                            ->nullable()
                            ->disabled(fn (Get $get): bool => $get('external_source') === 'morfeus')
                            ->dehydrated(),
                        Select::make('warehouse_id')
                            ->label('Bodega')
                            ->relationship(
                                'warehouse',
                                'name',
                                fn ($query, Get $get) => $query->when($get('company_id'), fn ($query, int $companyId) => $query->where('company_id', $companyId)),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->disabled(fn (Get $get): bool => $get('external_source') === 'morfeus')
                            ->dehydrated(),
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
                            ->dehydrated(),
                        Select::make('cashier_id')
                            ->label('Cajero')
                            ->relationship('cashier', 'name')
                            ->default(fn () => Auth::id())
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->disabled(fn (Get $get): bool => $get('external_source') === 'morfeus')
                            ->dehydrated(),
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
                            ->disabled(fn (Get $get): bool => $get('external_source') === 'morfeus')
                            ->dehydrated()
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
                            ->disabled(fn (Get $get): bool => $get('external_source') === 'morfeus')
                            ->dehydrated()
                            ->maxLength(255),
                        TextInput::make('customer_phone')
                            ->label('Telefono')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('customer_phone_2')
                            ->label('Telefono 2')
                            ->tel()
                            ->maxLength(50),
                        Textarea::make('delivery_address')
                            ->label('Direccion de entrega')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('delivery_reference')
                            ->label('Referencia y Observaciones')
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
                    ->hidden(fn (Get $get): bool => $get('external_source') === 'morfeus')
                    ->schema([
                        Repeater::make('items')
                            ->label('Productos')
                            ->relationship()
                            ->schema([
                                TextInput::make('product_code')
                                    ->label('Codigo')
                                    ->maxLength(100)
                                    ->disabled(fn (Get $get): bool => $get('../../external_source') === 'morfeus')
                                    ->dehydrated(),
                                Hidden::make('external_line'),
                                Hidden::make('external_item_id'),
                                Hidden::make('external_unit_id'),
                                Hidden::make('external_snapshot'),
                                TextInput::make('product_name')
                                    ->label('Producto')
                                    ->required()
                                    ->disabled(fn (Get $get): bool => $get('../../external_source') === 'morfeus')
                                    ->dehydrated()
                                    ->maxLength(255),
                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->disabled(fn (Get $get): bool => $get('../../external_source') === 'morfeus')
                                    ->dehydrated(),
                                TextInput::make('unit')
                                    ->label('Unidad')
                                    ->maxLength(50)
                                    ->disabled(fn (Get $get): bool => $get('../../external_source') === 'morfeus')
                                    ->dehydrated(),
                                Textarea::make('observations')
                                    ->label('Observaciones')
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->addable(fn (Get $get): bool => $get('external_source') !== 'morfeus')
                            ->deletable(fn (Get $get): bool => $get('external_source') !== 'morfeus')
                            ->reorderable(fn (Get $get): bool => $get('external_source') !== 'morfeus')
                            ->defaultItems(1),
                    ]),
                Section::make('Productos Morfeus')
                    ->visible(fn (Get $get): bool => $get('external_source') === 'morfeus')
                    ->schema([
                        Placeholder::make('morfeus_items_preview')
                            ->label('')
                            ->content(fn (Get $get): HtmlString => self::morfeusItemsPreview($get('external_snapshot'))),
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
                Hidden::make('external_source'),
                Hidden::make('external_source_type'),
                Hidden::make('external_invoice_id'),
                Hidden::make('external_document_number'),
                Hidden::make('external_warehouse_id'),
                Hidden::make('external_cashier_id'),
                Hidden::make('external_snapshot'),
            ]);
    }

    private static function morfeusItemsPreview(mixed $snapshot): HtmlString
    {
        $items = is_array($snapshot) ? ($snapshot['items'] ?? []) : [];

        if ($items === []) {
            return new HtmlString('<p class="text-sm text-gray-500">No se recibieron productos desde Morfeus.</p>');
        }

        $rows = collect($items)
            ->map(function (array $item): string {
                $code = e($item['barcode'] ?? $item['alternative_code'] ?? $item['external_item_id'] ?? '-');
                $product = e($item['description'] ?? '-');
                $quantity = e((string) ($item['pending_quantity'] ?? '-'));
                $unit = e($item['unit']['name'] ?? '-');

                return <<<HTML
                    <tr>
                        <td style="padding: 0.625rem; border-top: 1px solid rgb(229 231 235);">{$code}</td>
                        <td style="padding: 0.625rem; border-top: 1px solid rgb(229 231 235);">{$product}</td>
                        <td style="padding: 0.625rem; border-top: 1px solid rgb(229 231 235); text-align: right;">{$quantity}</td>
                        <td style="padding: 0.625rem; border-top: 1px solid rgb(229 231 235);">{$unit}</td>
                    </tr>
                HTML;
            })
            ->implode('');

        return new HtmlString(<<<HTML
            <div style="overflow-x: auto;">
                <table style="width: 100%; min-width: 40rem; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr>
                            <th style="padding: 0.625rem; text-align: left;">Codigo</th>
                            <th style="padding: 0.625rem; text-align: left;">Producto</th>
                            <th style="padding: 0.625rem; text-align: right;">Pendiente</th>
                            <th style="padding: 0.625rem; text-align: left;">Unidad</th>
                        </tr>
                    </thead>
                    <tbody>{$rows}</tbody>
                </table>
            </div>
        HTML);
    }
}
