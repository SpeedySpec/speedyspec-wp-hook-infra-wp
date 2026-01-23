<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Entities\ArrayHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\ObjectHookInvoke;
use SpeedySpec\WP\Hook\Domain\Entities\StringHookInvoke;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;
use SpeedySpec\WP\Hook\Infra\WP\Services\WPHookContainer;

covers(WPHookContainer::class);

beforeEach(function () {
    resetWordPressGlobals();
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new WPHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
});

afterEach(function () {
    resetWordPressGlobals();
});

describe('WPHookContainer::add()', function () {
    test('can add a filter with function callback', function () {
        $hookName = new StringHookName('test_hook');
        $callback = new StringHookInvoke('strtoupper', priority: 10);

        $this->container->add($hookName, $callback);

        expect($this->container->hasCallbacks($hookName))->toBeTrue();
    });

    test('can add a filter with object callback', function () {
        $hookName = new StringHookName('test_hook');
        $callback = new ObjectHookInvoke(fn($v) => strtoupper($v), priority: 10);

        $this->container->add($hookName, $callback);

        expect($this->container->hasCallbacks($hookName))->toBeTrue();
    });

    test('can add a filter with array callback', function () {
        $mock = createMockAction();
        $hookName = new StringHookName('test_hook');
        $callback = new ArrayHookInvoke([$mock, 'filter'], priority: 10);

        $this->container->add($hookName, $callback);

        expect($this->container->hasCallbacks($hookName))->toBeTrue();
    });

    test('can add multiple filters to same hook', function () {
        $hookName = new StringHookName('test_hook');
        $callback1 = new ObjectHookInvoke(fn($v) => $v, priority: 10);
        $callback2 = new ObjectHookInvoke(fn($v) => $v, priority: 20);

        $this->container->add($hookName, $callback1);
        $this->container->add($hookName, $callback2);

        expect($this->container->hasCallbacks($hookName))->toBeTrue();
    });

    test('can add filters to different hooks', function () {
        $hookName1 = new StringHookName('hook1');
        $hookName2 = new StringHookName('hook2');
        $callback = new ObjectHookInvoke(fn($v) => $v, priority: 10);

        $this->container->add($hookName1, $callback);
        $this->container->add($hookName2, $callback);

        expect($this->container->hasCallbacks($hookName1))->toBeTrue()
            ->and($this->container->hasCallbacks($hookName2))->toBeTrue();
    });
});

describe('WPHookContainer::remove()', function () {
    test('can remove a filter', function () {
        $hookName = new StringHookName('test_hook');
        $callback = new ObjectHookInvoke(fn($v) => $v, priority: 10);

        $this->container->add($hookName, $callback);
        $this->container->remove($hookName, $callback);

        expect($this->container->hasCallbacks($hookName))->toBeFalse();
    });

    test('removing filter does not affect other filters', function () {
        $hookName = new StringHookName('test_hook');
        $callback1 = new ObjectHookInvoke(fn($v) => $v . '_1', priority: 10);
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_2', priority: 10);

        $this->container->add($hookName, $callback1);
        $this->container->add($hookName, $callback2);
        $this->container->remove($hookName, $callback1);

        $result = $this->container->filter($hookName, 'test');
        expect($result)->toBe('test_2');
    });
});

describe('WPHookContainer::dispatch()', function () {
    test('dispatches action to registered callbacks', function () {
        $hookName = new StringHookName('test_action');
        $callLog = [];

        $callback = new ObjectHookInvoke(function (...$args) use (&$callLog) {
            $callLog[] = $args;
        }, priority: 10);

        $this->container->add($hookName, $callback);
        $this->container->dispatch($hookName, 'arg1', 'arg2');

        expect($callLog)->toHaveCount(1)
            ->and($callLog[0])->toBe(['arg1', 'arg2']);
    });

    test('dispatch does not throw when no callbacks registered', function () {
        $hookName = new StringHookName('non_existent_hook');

        expect(fn() => $this->container->dispatch($hookName))->not->toThrow(Exception::class);
    });
});

describe('WPHookContainer::filter()', function () {
    test('filters value through registered callbacks', function () {
        $hookName = new StringHookName('test_filter');
        $callback = new ObjectHookInvoke(fn($v) => strtoupper($v), priority: 10);

        $this->container->add($hookName, $callback);
        $result = $this->container->filter($hookName, 'hello');

        expect($result)->toBe('HELLO');
    });

    test('filter returns original value when no callbacks registered', function () {
        $hookName = new StringHookName('non_existent_hook');

        $result = $this->container->filter($hookName, 'original');

        expect($result)->toBe('original');
    });

    test('filter chains multiple callbacks', function () {
        $hookName = new StringHookName('test_filter');
        $callback1 = new ObjectHookInvoke(fn($v) => $v . '_first', priority: 10);
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_second', priority: 20);

        $this->container->add($hookName, $callback1);
        $this->container->add($hookName, $callback2);
        $result = $this->container->filter($hookName, 'start');

        expect($result)->toBe('start_first_second');
    });

    test('filter handles null value', function () {
        $hookName = new StringHookName('test_filter');
        $callback = new ObjectHookInvoke(fn($v) => $v ?? 'default', priority: 10);

        $this->container->add($hookName, $callback);
        $result = $this->container->filter($hookName, null);

        expect($result)->toBe('default');
    });

    test('filter handles array value', function () {
        $hookName = new StringHookName('test_filter');
        $callback = new ObjectHookInvoke(fn($v) => array_merge($v, ['added']), priority: 10);

        $this->container->add($hookName, $callback);
        $result = $this->container->filter($hookName, ['original']);

        expect($result)->toBe(['original', 'added']);
    });
});

describe('WPHookContainer::removeAll()', function () {
    test('removes all callbacks at specific priority', function () {
        $hookName = new StringHookName('test_hook');
        $callback1 = new ObjectHookInvoke(fn($v) => $v, priority: 10);
        $callback2 = new ObjectHookInvoke(fn($v) => $v, priority: 10);
        $callback3 = new ObjectHookInvoke(fn($v) => $v, priority: 20);

        $this->container->add($hookName, $callback1);
        $this->container->add($hookName, $callback2);
        $this->container->add($hookName, $callback3);

        $this->container->removeAll($hookName, 10);

        $result = $this->container->filter($hookName, 'test');
        expect($result)->toBe('test'); // Only priority 20 callback should run
    });

    test('removes all callbacks when no priority specified', function () {
        $hookName = new StringHookName('test_hook');
        $callback1 = new ObjectHookInvoke(fn($v) => $v . '_1', priority: 10);
        $callback2 = new ObjectHookInvoke(fn($v) => $v . '_2', priority: 20);

        $this->container->add($hookName, $callback1);
        $this->container->add($hookName, $callback2);

        $this->container->removeAll($hookName);

        expect($this->container->hasCallbacks($hookName))->toBeFalse();
    });

    test('does not affect other hooks', function () {
        $hookName1 = new StringHookName('hook1');
        $hookName2 = new StringHookName('hook2');
        $callback = new ObjectHookInvoke(fn($v) => $v, priority: 10);

        $this->container->add($hookName1, $callback);
        $this->container->add($hookName2, $callback);

        $this->container->removeAll($hookName1);

        expect($this->container->hasCallbacks($hookName1))->toBeFalse()
            ->and($this->container->hasCallbacks($hookName2))->toBeTrue();
    });
});

describe('WPHookContainer::hasCallbacks()', function () {
    test('returns false when no callbacks registered', function () {
        $hookName = new StringHookName('non_existent_hook');

        expect($this->container->hasCallbacks($hookName))->toBeFalse();
    });

    test('returns true when callbacks are registered', function () {
        $hookName = new StringHookName('test_hook');
        $callback = new ObjectHookInvoke(fn($v) => $v, priority: 10);

        $this->container->add($hookName, $callback);

        expect($this->container->hasCallbacks($hookName))->toBeTrue();
    });

    test('returns false after all callbacks removed', function () {
        $hookName = new StringHookName('test_hook');
        $callback = new ObjectHookInvoke(fn($v) => $v, priority: 10);

        $this->container->add($hookName, $callback);
        $this->container->removeAll($hookName);

        expect($this->container->hasCallbacks($hookName))->toBeFalse();
    });

    test('returns true for hook with remaining callbacks after partial removal', function () {
        $hookName = new StringHookName('test_hook');
        $callback1 = new ObjectHookInvoke(fn($v) => $v, priority: 10);
        $callback2 = new ObjectHookInvoke(fn($v) => $v, priority: 20);

        $this->container->add($hookName, $callback1);
        $this->container->add($hookName, $callback2);
        $this->container->removeAll($hookName, 10);

        expect($this->container->hasCallbacks($hookName))->toBeTrue();
    });
});
