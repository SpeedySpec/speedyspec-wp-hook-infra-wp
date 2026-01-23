<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;
use SpeedySpec\WP\Hook\Infra\WP\Services\WPHookContainer;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyAddFilterUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyRemoveFilterUseCase;

covers(LegacyRemoveFilterUseCase::class);

beforeEach(function () {
    resetWordPressGlobals();
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new WPHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addFilterUseCase = new LegacyAddFilterUseCase($this->container);
    $this->useCase = new LegacyRemoveFilterUseCase($this->container);
});

afterEach(function () {
    resetWordPressGlobals();
});

describe('LegacyRemoveFilterUseCase::removeHook()', function () {
    test('removes filter with closure callback', function () {
        $callback = fn($v) => $v;
        $this->addFilterUseCase->add('test_hook', $callback, 10, 1);

        // Verify callback was added
        expect($this->container->hasCallbacks(new StringHookName('test_hook')))->toBeTrue();

        $result = $this->useCase->removeHook('test_hook', $callback, 10);

        // Verify removal worked
        expect($result)->toBeTrue()
            ->and($this->container->hasCallbacks(new StringHookName('test_hook')))->toBeFalse();
    });

    test('removes filter with string callback', function () {
        $this->addFilterUseCase->add('test_hook', 'strtoupper', 10, 1);

        $result = $this->useCase->removeHook('test_hook', 'strtoupper', 10);

        expect($result)->toBeTrue();
    });

    test('removes filter with array callback', function () {
        $mock = createMockAction();
        $this->addFilterUseCase->add('test_hook', [$mock, 'filter'], 10, 1);

        $result = $this->useCase->removeHook('test_hook', [$mock, 'filter'], 10);

        expect($result)->toBeTrue();
    });

    test('returns true even when removing non-existent filter', function () {
        $result = $this->useCase->removeHook('non_existent', fn($v) => $v, 10);

        expect($result)->toBeTrue();
    });

    test('does not affect other filters on same hook', function () {
        $callback1 = fn($v) => $v . '_1';
        $callback2 = fn($v) => $v . '_2';

        $this->addFilterUseCase->add('test_hook', $callback1, 10, 1);
        $this->addFilterUseCase->add('test_hook', $callback2, 10, 1);

        $this->useCase->removeHook('test_hook', $callback1, 10);

        $result = $this->container->filter(new StringHookName('test_hook'), 'test');

        // After removing callback1, only callback2 should run
        expect($result)->toBe('test_2');
    });

    test('does not affect filters on different hooks', function () {
        $callback = fn($v) => $v;
        $this->addFilterUseCase->add('hook1', $callback, 10, 1);
        $this->addFilterUseCase->add('hook2', $callback, 10, 1);

        $this->useCase->removeHook('hook1', $callback, 10);

        // hook1 should have no callbacks, hook2 should still have the callback
        expect($this->container->hasCallbacks(new StringHookName('hook1')))->toBeFalse()
            ->and($this->container->hasCallbacks(new StringHookName('hook2')))->toBeTrue();
    });
});
