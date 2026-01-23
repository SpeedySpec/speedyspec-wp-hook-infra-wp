<?php

declare(strict_types=1);

use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\WP\Services\WPHookContainer;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyAddFilterUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyDispatchFilterHookUseCase;

covers(LegacyDispatchFilterHookUseCase::class);

beforeEach(function () {
    resetWordPressGlobals();
    $this->hookRunAmountService = new HookRunAmountService();
    $this->currentHookService = new CurrentHookService();
    $this->container = new WPHookContainer(
        $this->hookRunAmountService,
        $this->currentHookService
    );
    $this->addFilterUseCase = new LegacyAddFilterUseCase($this->container);
    $this->useCase = new LegacyDispatchFilterHookUseCase($this->container);
});

afterEach(function () {
    resetWordPressGlobals();
});

describe('LegacyDispatchFilterHookUseCase::filter()', function () {
    test('filters value through callback', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => strtoupper($v), 10, 1);

        $result = $this->useCase->filter('test_filter', 'hello');

        expect($result)->toBe('HELLO');
    });

    test('returns original value when no filters registered', function () {
        $result = $this->useCase->filter('non_existent_filter', 'original');

        expect($result)->toBe('original');
    });

    test('chains multiple filters', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v . '_first', 10, 1);
        $this->addFilterUseCase->add('test_filter', fn($v) => $v . '_second', 20, 1);

        $result = $this->useCase->filter('test_filter', 'start');

        expect($result)->toBe('start_first_second');
    });

    test('filters with additional arguments', function () {
        $this->addFilterUseCase->add('test_filter', fn($v, $arg) => $v . '_' . $arg, 10, 2);

        $result = $this->useCase->filter('test_filter', 'hello', 'world');

        expect($result)->toBe('hello_world');
    });

    test('filters in priority order', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v . '_last', 100, 1);
        $this->addFilterUseCase->add('test_filter', fn($v) => $v . '_first', 1, 1);
        $this->addFilterUseCase->add('test_filter', fn($v) => $v . '_middle', 10, 1);

        $result = $this->useCase->filter('test_filter', 'start');

        expect($result)->toBe('start_first_middle_last');
    });

    test('handles null value', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => $v ?? 'default', 10, 1);

        $result = $this->useCase->filter('test_filter', null);

        expect($result)->toBe('default');
    });

    test('handles array value', function () {
        $this->addFilterUseCase->add('test_filter', fn($v) => array_merge($v, ['added']), 10, 1);

        $result = $this->useCase->filter('test_filter', ['original']);

        expect($result)->toBe(['original', 'added']);
    });
});
