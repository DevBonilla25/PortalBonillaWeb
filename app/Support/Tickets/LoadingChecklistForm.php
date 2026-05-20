<?php

namespace App\Support\Tickets;

use Closure;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

class LoadingChecklistForm
{
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
                    ->dehydrated(),
                TextInput::make('quantity')
                    ->label('Cantidad ticket')
                    ->disabled()
                    ->dehydrated(),
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
                    ]),
                Toggle::make('is_loaded')
                    ->label('Cargado')
                    ->default(false)
                    ->live(),
                Textarea::make('load_observation')
                    ->label('Observacion')
                    ->live(onBlur: true)
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
                    ->columnSpanFull(),
            ])
            ->columns(4)
            ->reorderable(false)
            ->addable(false)
            ->deletable(false);
    }
}
