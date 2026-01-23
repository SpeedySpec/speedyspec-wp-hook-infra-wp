<?php

namespace SpeedySpec\WP\Hook\Infra\WP\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDoingActionUseCaseInterface;

class LegacyDoingActionUseCase implements LegacyDoingActionUseCaseInterface
{
    public function isDoingAction(?string $name = null): bool
    {
        global $wp_current_filter;

        if ( null === $name ) {
            return ! empty( $wp_current_filter );
        }

        return in_array( $name, $wp_current_filter, true );
    }
}
