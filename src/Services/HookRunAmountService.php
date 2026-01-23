<?php

declare(strict_types=1);

namespace SpeedySpec\WP\Hook\Infra\WP\Services;

use SpeedySpec\WP\Hook\Domain\Contracts\HookNameInterface;
use SpeedySpec\WP\Hook\Domain\Contracts\HookRunAmountInterface;

class HookRunAmountService implements HookRunAmountInterface
{

    /**
     * @var array<string, int>
     */
    private array $hooksRunAmount = [];

    public function getRunAmount(HookNameInterface $name): int
    {
        return $this->hooksRunAmount[$name->getName()] ?? 0;
    }

    public function incrementRunAmount(HookNameInterface $name): void
    {
        $this->hooksRunAmount[$name->getName()] ??= 0;
        $this->hooksRunAmount[$name->getName()]++;
    }
}
