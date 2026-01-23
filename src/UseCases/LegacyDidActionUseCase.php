<?php

namespace SpeedySpec\WP\Hook\Infra\WP\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\HookRunAmountInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDidActionUseCaseInterface;

class LegacyDidActionUseCase implements LegacyDidActionUseCaseInterface
{
    public function __construct(private HookRunAmountInterface $hookRunAmount)
    {
    }

    public function didAction(string $name): int
    {
        global $wp_actions;

        if ( ! isset( $wp_actions[ $name ] ) ) {
            return 0;
        }

        return $wp_actions[ $name ];
    }
}
