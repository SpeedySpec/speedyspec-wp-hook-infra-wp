<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Infra\WP\ServiceProvider;

covers(ServiceProvider::class);

beforeEach(function () {
    resetWordPressGlobals();
});

afterEach(function () {
    resetWordPressGlobals();
});

describe('ServiceProvider', function () {
    test('can be instantiated', function () {
        $provider = new ServiceProvider();

        expect($provider)->toBeInstanceOf(ServiceProvider::class);
    });

    test('boot method can be called', function () {
        $provider = new ServiceProvider();

        expect(fn() => $provider->boot())->not->toThrow(Exception::class);
    });

    test('register method can be called', function () {
        $provider = new ServiceProvider();

        expect(fn() => $provider->register())->not->toThrow(Exception::class);
    });

    test('can be invoked as callable', function () {
        $provider = new ServiceProvider();

        expect(fn() => $provider())->not->toThrow(Exception::class);
    });
});
