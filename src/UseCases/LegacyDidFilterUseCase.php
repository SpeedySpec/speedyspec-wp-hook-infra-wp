<?php

namespace SpeedySpec\WP\Hook\Infra\WP\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookRunAmountInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDidFilterUseCaseInterface;

class LegacyDidFilterUseCase implements LegacyDidFilterUseCaseInterface
{
    public function __construct(private HookRunAmountInterface $hookRunAmount)
    {
    }

    public function didFilter(string $name): int
    {
        global $wp_filters;

        if ( ! isset( $wp_filters[ $name ] ) ) {
            return 0;
        }

        return $wp_filters[ $name ];
    }
}
