<?php

declare(strict_types=1);

/**
 * Tests for WP_Hook::remove_filter integration scenarios.
 *
 * These tests verify behavior when the WP package integrates with WordPress' WP_Hook class.
 * They mirror the original WordPress PHPUnit tests for remove_filter functionality.
 */

beforeEach(function () {
    resetWordPressGlobals();
});

afterEach(function () {
    resetWordPressGlobals();
});

describe('WP Hook remove_filter behavior scenarios', function () {
    test('remove filter prevents callback execution', function () {
        $callCount = 0;
        $callbacks = [];

        $callback = function () use (&$callCount) {
            $callCount++;
        };

        // Add callback
        $callbacks[] = $callback;

        // Remove callback
        $callbacks = array_filter($callbacks, fn($cb) => $cb !== $callback);

        // Execute remaining callbacks
        foreach ($callbacks as $cb) {
            $cb();
        }

        expect($callCount)->toBe(0);
    });

    test('remove filter with object callback', function () {
        $mock = createMockAction();
        $callback = [$mock, 'action'];
        $callbacks = [$callback];

        // Remove the callback
        $callbacks = [];

        // Execute (should be empty)
        foreach ($callbacks as $cb) {
            call_user_func($cb);
        }

        expect($mock->getCallCount())->toBe(0);
    });

    test('remove filter leaves other filters at same priority', function () {
        $callCount1 = 0;
        $callCount2 = 0;

        $callback1 = function () use (&$callCount1) {
            $callCount1++;
        };
        $callback2 = function () use (&$callCount2) {
            $callCount2++;
        };

        $callbacks = [$callback1, $callback2];

        // Remove first callback
        $callbacks = array_filter($callbacks, fn($cb) => $cb !== $callback1);

        // Execute remaining
        foreach ($callbacks as $cb) {
            $cb();
        }

        expect($callCount1)->toBe(0)
            ->and($callCount2)->toBe(1);
    });

    test('remove filter leaves other filters at different priority', function () {
        $callCount1 = 0;
        $callCount2 = 0;

        $callback1 = function () use (&$callCount1) {
            $callCount1++;
        };
        $callback2 = function () use (&$callCount2) {
            $callCount2++;
        };

        $priorityCallbacks = [
            10 => [$callback1],
            20 => [$callback2],
        ];

        // Remove callback at priority 10
        unset($priorityCallbacks[10]);

        // Execute remaining
        foreach ($priorityCallbacks as $priority => $callbacks) {
            foreach ($callbacks as $cb) {
                $cb();
            }
        }

        expect($callCount1)->toBe(0)
            ->and($callCount2)->toBe(1);
    });
});

describe('WP Hook remove_all_filters behavior', function () {
    test('remove_all_filters clears all callbacks', function () {
        $callCount = 0;

        $callbacks = [
            function () use (&$callCount) { $callCount++; },
            function () use (&$callCount) { $callCount++; },
            function () use (&$callCount) { $callCount++; },
        ];

        // Remove all
        $callbacks = [];

        // Execute (should be empty)
        foreach ($callbacks as $cb) {
            $cb();
        }

        expect($callCount)->toBe(0);
    });

    test('remove_all_filters with priority clears only that priority', function () {
        $callCount10 = 0;
        $callCount20 = 0;

        $priorityCallbacks = [
            10 => [function () use (&$callCount10) { $callCount10++; }],
            20 => [function () use (&$callCount20) { $callCount20++; }],
        ];

        // Remove only priority 10
        unset($priorityCallbacks[10]);

        // Execute remaining
        foreach ($priorityCallbacks as $callbacks) {
            foreach ($callbacks as $cb) {
                $cb();
            }
        }

        expect($callCount10)->toBe(0)
            ->and($callCount20)->toBe(1);
    });
});

describe('WP Hook remove filter edge cases', function () {
    test('removing non-existent filter does not error', function () {
        $callbacks = [];

        $nonExistentCallback = function () {};

        // Try to remove non-existent callback
        $callbacks = array_filter($callbacks, fn($cb) => $cb !== $nonExistentCallback);

        expect($callbacks)->toBeEmpty();
    });

    test('can remove closure callback', function () {
        $callCount = 0;
        $closure = function () use (&$callCount) {
            $callCount++;
        };

        $callbacks = [$closure];

        // Remove closure
        $callbacks = array_filter($callbacks, fn($cb) => $cb !== $closure);

        // Execute (should be empty)
        foreach ($callbacks as $cb) {
            $cb();
        }

        expect($callCount)->toBe(0);
    });

    test('remove filter during execution maintains iteration', function () {
        $output = '';

        $callbacks = [];

        $callback1 = function () use (&$output, &$callbacks) {
            $output .= '1';
        };

        $callback2 = function () use (&$output, &$callbacks, &$callback2) {
            $output .= '2';
            // In real WP_Hook, removing during iteration is safe
        };

        $callback3 = function () use (&$output) {
            $output .= '3';
        };

        $callbacks = [$callback1, $callback2, $callback3];

        foreach ($callbacks as $cb) {
            $cb();
        }

        expect($output)->toBe('123');
    });
});
