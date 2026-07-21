<?php

namespace App\Support\Tickets;

use DomainException;
use Illuminate\Validation\ValidationException;

class LoadingChecklistValidation
{
    public static function requiresObservation(float $ticketQuantity, float $loadedQuantity): bool
    {
        return $loadedQuantity <= 0 || $loadedQuantity < $ticketQuantity;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public static function allItemsMarkedLoaded(array $items): bool
    {
        if ($items === []) {
            return false;
        }

        foreach ($items as $item) {
            if (! ($item['is_loaded'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, string>
     */
    public static function itemErrors(int $index, array $item): array
    {
        $errors = [];
        $ticketQuantity = (float) ($item['quantity'] ?? 0);
        $loadedQuantity = (float) ($item['loaded_quantity'] ?? 0);
        $observation = trim((string) ($item['load_observation'] ?? ''));
        $productName = (string) ($item['product_name'] ?? "producto #{$index}");

        if ($loadedQuantity > $ticketQuantity) {
            $errors["items.{$index}.loaded_quantity"] = "La cantidad cargada de {$productName} no puede superar la cantidad del ticket.";
        }

        if (self::requiresObservation($ticketQuantity, $loadedQuantity) && $observation === '') {
            $errors["items.{$index}.load_observation"] = "La observacion es obligatoria para {$productName} cuando la carga es parcial o cero.";
        }

        return $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, string>
     */
    public static function formErrors(array $items): array
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $errors = array_merge($errors, self::itemErrors($index, $item));
        }

        if (! self::allItemsMarkedLoaded($items)) {
            $message = 'Marca todos los productos como cargados para guardar y despachar.';

            foreach ($items as $index => $item) {
                if (! ($item['is_loaded'] ?? false)) {
                    $errors["items.{$index}.is_loaded"] = $message;
                }
            }
        }

        return $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public static function validateOrThrow(array $items): void
    {
        $errors = self::formErrors($items);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public static function validateOrFailDomain(array $items): void
    {
        $errors = self::formErrors($items);

        if ($errors !== []) {
            throw new DomainException(reset($errors));
        }
    }
}
