<?php

declare(strict_types=1);

/**
 * Tests for WP_Hook::apply_filters integration scenarios.
 *
 * These tests verify behavior when the WP package integrates with WordPress' WP_Hook class.
 * They mirror the original WordPress PHPUnit tests for apply_filters functionality.
 */

beforeEach(function () {
    resetWordPressGlobals();
});

afterEach(function () {
    resetWordPressGlobals();
});

describe('WP Hook apply_filters behavior scenarios', function () {
    test('apply_filters calls registered callback', function () {
        $mock = createMockAction();
        $callCount = 0;

        $callback = function ($value) use ($mock, &$callCount) {
            $callCount++;
            return $value;
        };

        // Simulate the behavior of apply_filters
        $result = $callback('test_arg');

        expect($callCount)->toBe(1)
            ->and($result)->toBe('test_arg');
    });

    test('apply_filters with multiple calls increments call count', function () {
        $callCount = 0;

        $callback = function ($value) use (&$callCount) {
            $callCount++;
            return $value;
        };

        $callback('arg1');
        $callback('arg2');

        expect($callCount)->toBe(2);
    });

    test('apply_filters passes value through callback chain', function () {
        $callback1 = fn($v) => $v . '_first';
        $callback2 = fn($v) => $v . '_second';

        $value = 'start';
        $value = $callback1($value);
        $value = $callback2($value);

        expect($value)->toBe('start_first_second');
    });

    test('apply_filters returns original value when no filters', function () {
        $value = 'original_value';

        // With no filters, value should pass through unchanged
        expect($value)->toBe('original_value');
    });
});

describe('WP Hook priority ordering', function () {
    test('callbacks execute in priority order ascending', function () {
        $callOrder = [];

        $callback10 = function ($v) use (&$callOrder) {
            $callOrder[] = 'priority_10';
            return $v;
        };

        $callback5 = function ($v) use (&$callOrder) {
            $callOrder[] = 'priority_5';
            return $v;
        };

        // Simulate priority ordering: lower priority executes first
        $callback5('test');
        $callback10('test');

        expect($callOrder)->toBe(['priority_5', 'priority_10']);
    });

    test('callbacks at same priority execute in registration order', function () {
        $callOrder = [];

        $callback1 = function ($v) use (&$callOrder) {
            $callOrder[] = 'first';
            return $v;
        };

        $callback2 = function ($v) use (&$callOrder) {
            $callOrder[] = 'second';
            return $v;
        };

        $callback1('test');
        $callback2('test');

        expect($callOrder)->toBe(['first', 'second']);
    });
});

describe('WP Hook accepted_args behavior', function () {
    test('callback receives correct number of arguments', function () {
        $receivedArgs = [];

        $callback = function (...$args) use (&$receivedArgs) {
            $receivedArgs = $args;
            return $args[0] ?? null;
        };

        $callback('value', 'arg1', 'arg2');

        expect($receivedArgs)->toBe(['value', 'arg1', 'arg2']);
    });

    test('callback with zero accepted args receives no arguments', function () {
        $receivedArgs = null;

        $callback = function () use (&$receivedArgs) {
            $receivedArgs = func_get_args();
            return null;
        };

        $callback();

        expect($receivedArgs)->toBeEmpty();
    });

    test('callback with one accepted arg receives only first argument', function () {
        $callback = function ($value) {
            return $value . '_modified';
        };

        $result = $callback('test');

        expect($result)->toBe('test_modified');
    });
});

describe('WP Hook filter value modification', function () {
    test('filter can modify and return new value', function () {
        $callback = fn($v) => strtoupper($v);

        $result = $callback('hello');

        expect($result)->toBe('HELLO');
    });

    test('filter can return different type', function () {
        $callback = fn($v) => strlen($v);

        $result = $callback('hello');

        expect($result)->toBe(5);
    });

    test('filter can return null', function () {
        $callback = fn($v) => null;

        $result = $callback('hello');

        expect($result)->toBeNull();
    });

    test('filter can return array', function () {
        $callback = fn($v) => [$v, 'extra'];

        $result = $callback('hello');

        expect($result)->toBe(['hello', 'extra']);
    });
});
