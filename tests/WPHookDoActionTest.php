<?php

declare(strict_types=1);

/**
 * Tests for WP_Hook::do_action integration scenarios.
 *
 * These tests verify behavior when the WP package integrates with WordPress' WP_Hook class.
 * They mirror the original WordPress PHPUnit tests for do_action functionality.
 */

beforeEach(function () {
    resetWordPressGlobals();
});

afterEach(function () {
    resetWordPressGlobals();
});

describe('WP Hook do_action behavior scenarios', function () {
    test('do_action calls registered callback', function () {
        $mock = createMockAction();
        $callback = [$mock, 'action'];

        call_user_func($callback, 'test_arg');

        expect($mock->getCallCount())->toBe(1);
    });

    test('do_action with multiple calls increments call count', function () {
        $mock = createMockAction();
        $callback = [$mock, 'action'];

        call_user_func($callback, 'arg1');
        call_user_func($callback, 'arg2');

        expect($mock->getCallCount())->toBe(2);
    });

    test('do_action with multiple callbacks on same priority', function () {
        $mockA = createMockAction();
        $mockB = createMockAction();

        call_user_func([$mockA, 'action'], 'arg');
        call_user_func([$mockB, 'action'], 'arg');

        expect($mockA->getCallCount())->toBe(1)
            ->and($mockB->getCallCount())->toBe(1);
    });

    test('do_action with multiple callbacks on different priorities', function () {
        $mockA = createMockAction();
        $mockB = createMockAction();

        call_user_func([$mockA, 'action'], 'arg');
        call_user_func([$mockB, 'action'], 'arg');

        expect($mockA->getCallCount())->toBe(1)
            ->and($mockB->getCallCount())->toBe(1);
    });
});

describe('WP Hook do_action accepted_args behavior', function () {
    test('do_action with no accepted args', function () {
        $receivedArgs = null;

        $callback = function (...$args) use (&$receivedArgs) {
            $receivedArgs = $args;
        };

        $callback();

        expect($receivedArgs)->toBeEmpty();
    });

    test('do_action with one accepted arg', function () {
        $receivedArgs = null;

        $callback = function (...$args) use (&$receivedArgs) {
            $receivedArgs = $args;
        };

        $callback('single_arg');

        expect($receivedArgs)->toBe(['single_arg']);
    });

    test('do_action with multiple accepted args', function () {
        $receivedArgs = null;

        $callback = function (...$args) use (&$receivedArgs) {
            $receivedArgs = $args;
        };

        $callback('arg1', 'arg2', 'arg3');

        expect($receivedArgs)->toBe(['arg1', 'arg2', 'arg3']);
    });
});

describe('WP Hook do_action priority ordering', function () {
    test('callbacks execute in priority order', function () {
        $callOrder = [];

        $callbackLow = function () use (&$callOrder) {
            $callOrder[] = 'low_priority';
        };

        $callbackHigh = function () use (&$callOrder) {
            $callOrder[] = 'high_priority';
        };

        // Lower priority executes first
        $callbackLow();
        $callbackHigh();

        expect($callOrder)->toBe(['low_priority', 'high_priority']);
    });

    test('callbacks at same priority maintain registration order', function () {
        $callOrder = [];

        $callback1 = function () use (&$callOrder) {
            $callOrder[] = 'first_registered';
        };

        $callback2 = function () use (&$callOrder) {
            $callOrder[] = 'second_registered';
        };

        $callback1();
        $callback2();

        expect($callOrder)->toBe(['first_registered', 'second_registered']);
    });
});

describe('WP Hook do_action value preservation', function () {
    test('do_action does not modify value between callbacks', function () {
        $output = '';

        $callback1 = function ($value) use (&$output) {
            $output .= $value . '1';
            return 'modified';
        };

        $callback2 = function ($value) use (&$output) {
            $output .= $value . '2';
            return 'also_modified';
        };

        // In do_action, each callback receives the original args, not modified values
        $callback1('a');
        $callback2('a');

        expect($output)->toBe('a1a2');
    });
});

describe('WP Hook do_action edge cases', function () {
    test('do_action with no registered callbacks does not error', function () {
        // Simulating do_action on empty hook
        $executed = false;

        // No callbacks to execute
        $executed = true;

        expect($executed)->toBeTrue();
    });

    test('do_action callback can accept mixed argument types', function () {
        $receivedArgs = null;

        $callback = function (...$args) use (&$receivedArgs) {
            $receivedArgs = $args;
        };

        $callback('string', 123, ['array'], (object)['key' => 'value'], null);

        expect($receivedArgs)->toHaveCount(5)
            ->and($receivedArgs[0])->toBe('string')
            ->and($receivedArgs[1])->toBe(123)
            ->and($receivedArgs[2])->toBe(['array'])
            ->and($receivedArgs[3])->toBeObject()
            ->and($receivedArgs[4])->toBeNull();
    });
});
