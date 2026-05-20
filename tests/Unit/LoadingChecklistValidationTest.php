<?php

use App\Support\Tickets\LoadingChecklistValidation;

it('requires observation when loaded quantity is partial or zero', function () {
    expect(LoadingChecklistValidation::requiresObservation(30, 3))->toBeTrue();
    expect(LoadingChecklistValidation::requiresObservation(6, 0))->toBeTrue();
    expect(LoadingChecklistValidation::requiresObservation(6, 6))->toBeFalse();
});

it('allows marking loaded with partial quantity when observation is provided', function () {
    $errors = LoadingChecklistValidation::itemErrors(0, [
        'product_name' => 'Clavos',
        'quantity' => 30,
        'loaded_quantity' => 3,
        'is_loaded' => true,
        'load_observation' => 'Faltante por stock',
    ]);

    expect($errors)->toBeEmpty();
});

it('requires all items marked loaded before dispatch', function () {
    $errors = LoadingChecklistValidation::formErrors([
        [
            'product_name' => 'Clavos',
            'quantity' => 30,
            'loaded_quantity' => 30,
            'is_loaded' => true,
            'load_observation' => '',
        ],
        [
            'product_name' => 'Martillos',
            'quantity' => 6,
            'loaded_quantity' => 6,
            'is_loaded' => false,
            'load_observation' => '',
        ],
    ]);

    expect($errors)->toHaveKey('items.1.is_loaded')
        ->and($errors['items.1.is_loaded'])
        ->toBe('Marca todos los productos como cargados para guardar y despachar.');
});

it('passes when all items are marked loaded and observations are complete', function () {
    $errors = LoadingChecklistValidation::formErrors([
        [
            'product_name' => 'Clavos',
            'quantity' => 30,
            'loaded_quantity' => 3,
            'is_loaded' => true,
            'load_observation' => 'Parcial',
        ],
        [
            'product_name' => 'Martillos',
            'quantity' => 6,
            'loaded_quantity' => 6,
            'is_loaded' => true,
            'load_observation' => '',
        ],
    ]);

    expect($errors)->toBeEmpty();
});
