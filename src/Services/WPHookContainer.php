<?php

declare(strict_types=1);

namespace SpeedySpec\WP\Hook\Infra\WP\Services;

use SpeedySpec\WP\Hook\Domain\Contracts\CurrentHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookActionInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookFilterInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookInvokableInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookNameInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookPriorityInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookRunAmountInterface;
use WP_Hook;

class WPHookContainer implements HookContainerInterface
{
    private array $hooks = [];

    public function __construct(
        private HookRunAmountInterface $hookRunAmountService,
        private CurrentHookInterface $currentHookService,
    ) {
    }

    public function add(
        HookNameInterface $hook,
        HookInvokableInterface|HookActionInterface|HookFilterInterface $callback
    ): void {
        global $wp_filter;

        $hook_name = $hook->getName();

        if ( ! isset( $wp_filter[ $hook_name ] ) ) {
            $wp_filter[ $hook_name ] = new WP_Hook();
        }

        if ($callback instanceof HookPriorityInterface) {
            $priority = $callback->getPriority();
        } else {
            $priority = 10;
        }

        $wp_filter[ $hook_name ]->add_filter( $hook_name, $callback, $priority, PHP_INT_MAX );
    }

    public function remove(
        HookNameInterface $hook,
        HookInvokableInterface|HookActionInterface|HookFilterInterface $callback
    ): void {
        global $wp_filter;

        $hook_name = $hook->getName();

        if ($callback instanceof HookPriorityInterface) {
            $priority = $callback->getPriority();
        } else {
            $priority = 10;
        }

        if ( isset( $wp_filter[ $hook_name ] ) ) {
            $wp_filter[ $hook_name ]->remove_filter( $hook_name, $callback, $priority );

            if ( ! $wp_filter[ $hook_name ]->callbacks ) {
                unset( $wp_filter[ $hook_name ] );
            }
        }
    }

    public function dispatch(HookNameInterface $hook, ...$args): void
    {
        global $wp_filter, $wp_actions, $wp_current_filter;

        $hook_name = $hook->getName();

        if ( ! isset( $wp_actions[ $hook_name ] ) ) {
            $wp_actions[ $hook_name ] = 1;
        } else {
            ++$wp_actions[ $hook_name ];
        }

        // Do 'all' actions first.
        if ( isset( $wp_filter['all'] ) ) {
            $wp_current_filter[] = $hook_name;
            $all_args            = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
            $wp_filter['all']->do_all_hook( $all_args );
        }

        if ( ! isset( $wp_filter[ $hook_name ] ) ) {
            if ( isset( $wp_filter['all'] ) ) {
                array_pop( $wp_current_filter );
            }

            return;
        }

        if ( ! isset( $wp_filter['all'] ) ) {
            $wp_current_filter[] = $hook_name;
        }

        if ( empty( $args ) ) {
            $args[] = '';
        } elseif ( is_array( $args[0] ) && 1 === count( $args[0] ) && isset( $args[0][0] ) && is_object( $args[0][0] ) ) {
            // Backward compatibility for PHP4-style passing of `array( &$this )` as action `$args`.
            $args[0] = $args[0][0];
        }

        $wp_filter[ $hook_name ]->do_action( $args );

        array_pop( $wp_current_filter );
    }

    public function filter(HookNameInterface $hook, mixed $value, ...$args): mixed
    {
        global $wp_filter, $wp_filters, $wp_current_filter;

        $hook_name = $hook->getName();

        if ( ! isset( $wp_filters[ $hook_name ] ) ) {
            $wp_filters[ $hook_name ] = 1;
        } else {
            ++$wp_filters[ $hook_name ];
        }

        // Do 'all' actions first.
        if ( isset( $wp_filter['all'] ) ) {
            $wp_current_filter[] = $hook_name;

            $all_args = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
            $wp_filter['all']->do_all_hook( $all_args );
        }

        if ( ! isset( $wp_filter[ $hook_name ] ) ) {
            if ( isset( $wp_filter['all'] ) ) {
                array_pop( $wp_current_filter );
            }

            return $value;
        }

        if ( ! isset( $wp_filter['all'] ) ) {
            $wp_current_filter[] = $hook_name;
        }

        // Pass the value to WP_Hook.
        array_unshift( $args, $value );

        $filtered = $wp_filter[ $hook_name ]->apply_filters( $value, $args );

        array_pop( $wp_current_filter );

        return $filtered;
    }

    public function removeAll(HookNameInterface $hook, ?int $priority = null): void
    {
        global $wp_filter;

        $hook_name = $hook->getName();

        if ( isset( $wp_filter[ $hook_name ] ) ) {
            // WP_Hook expects false to remove all priorities, not null
            $wp_priority = $priority === null ? false : $priority;
            $wp_filter[ $hook_name ]->remove_all_filters( $wp_priority );

            if ( ! $wp_filter[ $hook_name ]->has_filters() ) {
                unset( $wp_filter[ $hook_name ] );
            }
        }
    }

    public function hasCallbacks(
        HookNameInterface $hook,
        HookFilterInterface|HookActionInterface|HookInvokableInterface|null $callback = null,
        ?int $priority = null,
    ): bool {
        global $wp_filter;

        $hook_name = $hook->getName();

        if ( ! isset( $wp_filter[ $hook_name ] ) ) {
            return false;
        }

        // WP_Hook expects false when checking for any callbacks, or the actual callable
        $wp_callback = $callback === null ? false : $callback;

        $result = $wp_filter[ $hook_name ]->has_filter( $hook_name, $wp_callback, $priority );

        // WP_Hook::has_filter() returns int (priority) when callback found, false when not
        // Convert to bool as required by HookContainerInterface
        return $result !== false;
    }
}
