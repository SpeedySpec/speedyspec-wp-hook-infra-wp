<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()
    ->group('hooks', 'wp')
    ->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function resetWordPressGlobals(): void
{
    global $wp_filter, $wp_actions, $wp_filters, $wp_current_filter;
    $wp_filter = null;
    $wp_actions = null;
    $wp_filters = null;
    $wp_current_filter = null;
}

function createMockWPHook(): object
{
    return new class {
        public array $callbacks = [];

        public static function build_preinitialized_hooks(array $filters): array
        {
            return $filters;
        }

        public function add_filter(string $hook_name, callable $callback, int $priority, int $accepted_args): void
        {
            $this->callbacks[$priority][] = [
                'function' => $callback,
                'accepted_args' => $accepted_args,
            ];
        }

        public function has_filter(string $hook_name = '', callable|false $callback = false): bool|int
        {
            if ($callback === false) {
                return !empty($this->callbacks);
            }
            foreach ($this->callbacks as $priority => $funcs) {
                foreach ($funcs as $func) {
                    if ($func['function'] === $callback) {
                        return $priority;
                    }
                }
            }
            return false;
        }
    };
}

function createMockAction(): object
{
    return new class {
        private int $callCount = 0;
        private array $events = [];

        public function action(...$args): void
        {
            $this->callCount++;
            $this->events[] = [
                'action' => 'action',
                'args' => $args,
            ];
        }

        public function action2(...$args): void
        {
            $this->callCount++;
            $this->events[] = [
                'action' => 'action2',
                'args' => $args,
            ];
        }

        public function filter(...$args): mixed
        {
            $this->callCount++;
            $this->events[] = [
                'filter' => 'filter',
                'args' => $args,
            ];
            return $args[0] ?? null;
        }

        public function filter2(...$args): mixed
        {
            $this->callCount++;
            $this->events[] = [
                'filter' => 'filter2',
                'args' => $args,
            ];
            return $args[0] ?? null;
        }

        public function getCallCount(): int
        {
            return $this->callCount;
        }

        public function getEvents(): array
        {
            return $this->events;
        }
    };
}
