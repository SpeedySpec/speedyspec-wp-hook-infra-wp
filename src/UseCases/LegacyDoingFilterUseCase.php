<?php

namespace SpeedySpec\WP\Hook\Infra\WP\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDoingFilterUseCaseInterface;

class LegacyDoingFilterUseCase implements LegacyDoingFilterUseCaseInterface
{
    public function isDoingFilter(?string $name = null): bool
    {
        global $wp_current_filter;

        if ( null === $name ) {
            return ! empty( $wp_current_filter );
        }

        return in_array( $name, $wp_current_filter, true );
    }
}
