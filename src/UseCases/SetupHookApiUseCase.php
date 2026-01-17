<?php

declare(strict_types=1);

namespace SpeedySpec\WP\Hook\Infra\WP\UseCases;

use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\SetupHookApiUseCaseInterface;
use WP_Hook;

class SetupHookApiUseCase implements SetupHookApiUseCaseInterface
{

    public function setupHookApi(): void
    {
        /** @var WP_Hook[] $wp_filter */
        global $wp_filter;

        /** @var int[] $wp_actions */
        global $wp_actions;

        /** @var int[] $wp_filters */
        global $wp_filters;

        /** @var string[] $wp_current_filter */
        global $wp_current_filter;

        if ( $wp_filter ) {
            /** @todo Need to get rid of WP_Hook reference */
            $wp_filter = WP_Hook::build_preinitialized_hooks( $wp_filter );
        } else {
            $wp_filter = [];
        }

        if ( ! isset( $wp_actions ) ) {
            $wp_actions = [];
        }

        if ( ! isset( $wp_filters ) ) {
            $wp_filters = [];
        }

        if ( ! isset( $wp_current_filter ) ) {
            $wp_current_filter = [];
        }
    }
}
