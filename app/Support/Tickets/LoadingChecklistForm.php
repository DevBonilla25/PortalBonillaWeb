<?php

namespace App\Support\Tickets;

use Closure;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;

class LoadingChecklistForm
{
    public static function palletGrid(): Grid
    {
        return Grid::make([
            'default' => 1,
            'md' => 2,
        ])->schema(self::palletFields());
    }

    /**
     * @return array<int, Radio|TextInput>
     */
    public static function palletFields(): array
    {
        return [
            Radio::make('uses_pallets')
                ->label('¿Esta entrega lleva palets?')
                ->options([
                    '1' => 'Sí, lleva palets',
                    '0' => 'No lleva palets',
                ])
                ->required()
                ->live()
                ->inline(),
            TextInput::make('pallet_count')
                ->label('Cantidad de palets')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(65535)
                ->required(fn (Get $get): bool => (string) $get('uses_pallets') === '1')
                ->visible(fn (Get $get): bool => (string) $get('uses_pallets') === '1'),
        ];
    }

    public static function repeater(): Repeater
    {
        return Repeater::make('items')
            ->label('Productos cargados')
            ->rules([
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    $items = is_array($value) ? $value : [];

                    if ($items !== [] && ! LoadingChecklistValidation::allItemsMarkedLoaded($items)) {
                        $fail('Marca todos los productos como cargados para guardar y despachar.');
                    }
                },
            ])
            ->schema([
                Hidden::make('id'),
                TextInput::make('product_name')
                    ->label('Producto')
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(['default' => 12, 'xl' => 4]),
                TextInput::make('quantity')
                    ->label('Cantidad ticket')
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(['default' => 6, 'xl' => 2]),
                TextInput::make('loaded_quantity')
                    ->label('Cantidad cargada')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->live(onBlur: true)
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            $ticketQuantity = (float) ($get('quantity') ?? 0);
                            $loadedQuantity = (float) ($value ?? 0);

                            if ($loadedQuantity > $ticketQuantity) {
                                $fail('La cantidad cargada no puede superar la cantidad del ticket.');
                            }
                        },
                    ])
                    ->columnSpan(['default' => 6, 'xl' => 2]),
                TextInput::make('load_observation')
                    ->label('Observación')
                    ->live(onBlur: true)
                    ->visible(fn (Get $get): bool => LoadingChecklistValidation::requiresObservation(
                        (float) ($get('quantity') ?? 0),
                        (float) ($get('loaded_quantity') ?? 0),
                    ))
                    ->required(fn (Get $get): bool => LoadingChecklistValidation::requiresObservation(
                        (float) ($get('quantity') ?? 0),
                        (float) ($get('loaded_quantity') ?? 0),
                    ))
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if (! LoadingChecklistValidation::requiresObservation(
                                (float) ($get('quantity') ?? 0),
                                (float) ($get('loaded_quantity') ?? 0),
                            )) {
                                return;
                            }

                            if (trim((string) $value) === '') {
                                $fail('La observacion es obligatoria cuando la carga es parcial o cero.');
                            }
                        },
                    ])
                    ->columnSpan(['default' => 12, 'xl' => 3]),
                Toggle::make('is_loaded')
                    ->label('Cargado')
                    ->default(false)
                    ->live()
                    ->columnSpan(['default' => 12, 'xl' => 1]),
            ])
            ->columns(12)
            ->reorderable(false)
            ->addable(false)
            ->deletable(false);
    }
}
