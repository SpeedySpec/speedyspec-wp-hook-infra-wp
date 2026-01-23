<?php

namespace SpeedySpec\WP\Hook\Infra\WP\Services;

use SpeedySpec\WP\Hook\Domain\Contracts\CurrentHookInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookNameInterface;
use SpeedySpec\WP\Hook\Domain\ValueObject\StringHookName;

class CurrentHookService implements CurrentHookInterface
{
    /**
     * @param string[] $hooks
     * @param array<string, string[]> $callbacks
     */
    public function __construct(
        private array $hooks = [],
        private array $callbacks = [],
    ) {
    }

    public function addHook(string $name): void
    {
        $this->hooks[] = $name;
    }

    public function removeHook(): void
    {
        if ( empty( $this->hooks ) ) {
            return;
        }
        array_pop( $this->hooks );
    }

    public function getCurrentHook(): ?HookNameInterface
    {
        global $wp_current_filter;

        return new StringHookName( end( $wp_current_filter ) );
    }

    public function hookTraceback(): array
    {
        return $this->hooks;
    }

    public function addCallback(string $name): void
    {
        $this->callbacks[ end( $this->hooks ) ?: 'unknown' ][] = $name;
    }

    public function removeCallback(): void
    {
        array_pop($this->callbacks[ end( $this->hooks ) ?: 'unknown' ]);
    }

    public function getCurrentCallback(): ?string
    {
        $callbacks = $this->callbacks[ end( $this->hooks ) ?: 'unknown' ] ?? [];

        if (empty($callbacks)) {
            return null;
        }

        return end( $callbacks );
    }

    public function callbackTraceback(): array
    {
        return $this->callbacks[ end( $this->hooks ) ?: 'unknown' ];
    }

    public function entireCallbackTraceback(): array
    {
        return $this->callbacks;
    }
}
