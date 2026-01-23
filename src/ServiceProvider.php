<?php

namespace SpeedySpec\WP\Hook\Infra\WP;

use SpeedySpec\WP\Hook\Domain\Contracts\CurrentHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookContainerInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookRunAmountInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyAddActionUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyAddFilterUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyCurrentActionUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyCurrentFilterUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDidActionUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDidFilterUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDispatchActionHookUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDispatchDeprecatedActionHookUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDispatchDeprecatedFilterHookUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDispatchFilterHookUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDoingActionUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyDoingFilterUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyHasActionUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyHasFilterUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyRemoveActionUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyRemoveAllActionsUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyRemoveAllFiltersUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\UseCases\LegacyRemoveFilterUseCaseInterface;
use SpeedySpec\WP\Hook\Domain\HookServiceContainer;
use SpeedySpec\WP\Hook\Domain\Services\CurrentHookService;
use SpeedySpec\WP\Hook\Domain\Services\HookRunAmountService;
use SpeedySpec\WP\Hook\Infra\Memory\Services\WPHookContainer;
use SpeedySpec\WP\Hook\Infra\Memory\Services\WPHookSubject;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyAddActionUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyCurrentActionUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyCurrentFilterUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyDidActionUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyDidFilterUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyDispatchActionHookUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyDispatchDeprecatedActionHookUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyDispatchDeprecatedFilterHookUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyDispatchFilterHookUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyDoingActionUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyDoingFilterUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyHasActionUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyHasFilterUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyRemoveActionUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyRemoveAllActionsUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyRemoveAllFiltersUseCase;
use SpeedySpec\WP\Hook\Infra\WP\UseCases\LegacyRemoveFilterUseCase;

class ServiceProvider
{
    public function boot(): void
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

    public function register(): void
    {
        $container = HookServiceContainer::getInstance();
        $container->add(HookRunAmountService::class, fn() => new HookRunAmountService());
        $container->add(CurrentHookService::class, fn() => new CurrentHookService());
        $container->add(
            HookContainerInterface::class,
            fn($c) => new WPHookContainer($c->get(HookRunAmountService::class), $c->get(CurrentHookService::class))
        );
        $container->add(
            reference: LegacyAddActionUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyAddActionUseCase($c->get(HookContainerInterface::class))
        );
        $container->add(
            reference: LegacyAddFilterUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyAddFilterUseCase($c->get(HookContainerInterface::class))
        );
        $container->add(
            reference: LegacyCurrentActionUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyCurrentActionUseCase($c->get(CurrentHookService::class))
        );
        $container->add(
            reference: LegacyCurrentFilterUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyCurrentFilterUseCase($c->get(CurrentHookService::class))
        );
        $container->add(
            reference: LegacyDidActionUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyDidActionUseCase($c->get(HookRunAmountInterface::class))
        );
        $container->add(
            reference: LegacyDidFilterUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyDidFilterUseCase($c->get(HookRunAmountInterface::class))
        );
        $container->add(
            reference: LegacyDispatchActionHookUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyDispatchActionHookUseCase($c->get(HookContainerInterface::class))
        );
        $container->add(
            reference: LegacyDispatchFilterHookUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyDispatchFilterHookUseCase($c->get(HookContainerInterface::class))
        );
        $container->add(
            reference: LegacyDispatchDeprecatedActionHookUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyDispatchDeprecatedActionHookUseCase(
                $c->get(HookContainerInterface::class),
                $c->get(CalledDeprecatedHookInterface::class),
            )
        );
        $container->add(
            reference: LegacyDispatchDeprecatedFilterHookUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyDispatchDeprecatedFilterHookUseCase(
                $c->get(HookContainerInterface::class),
                $c->get(CalledDeprecatedHookInterface::class),
            )
        );
        $container->add(
            reference: LegacyDoingActionUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyDoingActionUseCase($c->get(CurrentHookInterface::class))
        );
        $container->add(
            reference: LegacyDoingFilterUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyDoingFilterUseCase($c->get(CurrentHookInterface::class))
        );
        $container->add(
            reference: LegacyHasActionUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyHasActionUseCase($c->get(HookContainerInterface::class))
        );
        $container->add(
            reference: LegacyHasFilterUseCaseInterface::class,
            registerCallback: fn ($c) => new LEgacyHasFilterUseCase($c->get(HookContainerInterface::class))
        );
        $container->add(
            reference: LegacyRemoveActionUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyRemoveActionUseCase($c->get(HookContainerInterface::class))
        );
        $container->add(
            reference: LegacyRemoveFilterUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyRemoveFilterUseCase($c->get(HookContainerInterface::class))
        );
        $container->add(
            reference: LegacyRemoveAllActionsUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyRemoveAllActionsUseCase($c->get(HookContainerInterface::class))
        );
        $container->add(
            reference: LegacyRemoveAllFiltersUseCaseInterface::class,
            registerCallback: fn ($c) => new LegacyRemoveAllFiltersUseCase($c->get(HookContainerInterface::class))
        );
    }

    public function __invoke(): void
    {
        $this->boot();
        $this->register();
    }
}
