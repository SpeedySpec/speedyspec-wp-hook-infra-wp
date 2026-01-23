<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;
use SpeedySpec\WP\Hook\Infra\WP\Services\WPHookContainer;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyAddFilterUseCase;

covers(LegacyAddFilterUseCase::class);

beforeEach(function () {
    resetWordPressGlobals();
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new WPHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->useCase = new LegacyAddFilterUseCase($this->container);
});

afterEach(function () {
    resetWordPressGlobals();
});

describe('LegacyAddFilterUseCase::add()', function () {
    test('adds filter with string callback', function () {
        $result = $this->useCase->add('test_hook', 'strtoupper', 10, 1);

        expect($result)->toBeTrue()
            ->and($this->container->hasCallbacks(new StringHookName('test_hook')))->toBeTrue();
    });

    test('adds filter with array callback', function () {
        $mock = createMockAction();
        $result = $this->useCase->add('test_hook', [$mock, 'filter'], 10, 1);

        expect($result)->toBeTrue()
            ->and($this->container->hasCallbacks(new StringHookName('test_hook')))->toBeTrue();
    });

    test('adds filter with closure callback', function () {
        $result = $this->useCase->add('test_hook', fn($v) => $v, 10, 1);

        expect($result)->toBeTrue()
            ->and($this->container->hasCallbacks(new StringHookName('test_hook')))->toBeTrue();
    });

    test('adds filter with custom priority', function () {
        $this->useCase->add('test_hook', fn($v) => $v . '_first', 5, 1);
        $this->useCase->add('test_hook', fn($v) => $v . '_second', 10, 1);

        $result = $this->container->filter(new StringHookName('test_hook'), 'test');

        expect($result)->toBe('test_first_second');
    });

    test('returns true on successful add', function () {
        $result = $this->useCase->add('test_hook', fn($v) => $v, 10, 1);

        expect($result)->toBeTrue();
    });

    test('adds multiple filters to same hook', function () {
        $this->useCase->add('test_hook', fn($v) => $v . '_1', 10, 1);
        $this->useCase->add('test_hook', fn($v) => $v . '_2', 20, 1);

        $result = $this->container->filter(new StringHookName('test_hook'), 'test');

        expect($result)->toBe('test_1_2');
    });

    test('adds filters to different hooks', function () {
        $this->useCase->add('hook1', fn($v) => $v . '_1', 10, 1);
        $this->useCase->add('hook2', fn($v) => $v . '_2', 10, 1);

        expect($this->container->hasCallbacks(new StringHookName('hook1')))->toBeTrue()
            ->and($this->container->hasCallbacks(new StringHookName('hook2')))->toBeTrue();
    });
});
