<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Rules\ExtensionFieldValue;
use Throwable;
use UnexpectedValueException;

class ExtensionFormFields
{
    public function __construct(
        private readonly ExtensionFormFieldRegistry $registry,
        private readonly ExtensionRepository $extensions,
        private readonly ConnectionInterface $connection,
    ) {}

    /**
     * @param  list<string>  $submitted
     * @return ValidationRules
     */
    public function rules(string $form, array $submitted): array
    {
        $rules = ['extensions' => ['sometimes', 'array']];
        foreach ($this->active($form) as $identifier => $entry) {
            if (! in_array($identifier, $submitted, true)) {
                continue;
            }

            $prefix = 'extensions.'.$identifier;
            $rules[$prefix] = ['array'];
            foreach ($entry['rules'] as $key => $rule) {
                $rules[$prefix.'.'.$key] = $rule;
            }

            foreach ($entry['keys'] as $key) {
                $rules[$prefix.'.'.$key] = [...$entry['rules'][$key] ?? [], new ExtensionFieldValue];
            }
        }

        return $rules;
    }

    /**
     * @return ExtensionFormValues
     */
    public function values(string $form, Model $subject): array
    {
        $values = [];
        foreach ($this->active($form) as $identifier => $entry) {
            try {
                $loaded = $entry['load'] instanceof Closure
                    ? ($entry['load'])($subject)
                    : $this->settings($identifier, $subject)->all();
                ExtensionFieldValueGuard::assertValues($loaded);
                $values[$identifier] = array_intersect_key($loaded, array_flip($entry['keys']));
            } catch (Throwable $throwable) {
                $this->extensions->recordFailure($identifier, $throwable->getMessage(), $throwable, 'form');
            }
        }

        return $values;
    }

    /**
     * @param  ExtensionFormValues  $values
     */
    public function save(string $form, Model $subject, array $values): void
    {
        foreach ($this->pending($form, $values) as $identifier => $entry) {
            $data = array_intersect_key($values[$identifier] ?? [], array_flip($entry['keys']));
            if ($entry['save'] instanceof Closure) {
                ($entry['save'])($subject, $data);
            } else {
                $this->settings($identifier, $subject)->setMany($data);
            }
        }
    }

    /**
     * @template TModel of Model
     *
     * @param  ExtensionFormValues  $values
     * @param  Closure(): TModel  $action
     * @return TModel
     */
    public function persist(string $form, array $values, Closure $action, bool $atomic = true): Model
    {
        if ($this->pending($form, $values) === []) {
            return $action();
        }

        if (! $atomic) {
            $subject = $action();
            $this->save($form, $subject, $values);

            return $subject;
        }

        return $this->connection->transaction(function () use ($form, $values, $action): Model {
            $subject = $action();
            $this->save($form, $subject, $values);

            return $subject;
        });
    }

    /**
     * @return array<string, ExtensionFormFieldEntry>
     */
    private function active(string $form): array
    {
        return array_filter(
            $this->registry->all($form),
            $this->extensions->isAvailable(...),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @param  ExtensionFormValues  $values
     * @return array<string, ExtensionFormFieldEntry>
     */
    private function pending(string $form, array $values): array
    {
        return array_filter(
            $this->active($form),
            fn (string $identifier): bool => array_key_exists($identifier, $values),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function settings(string $identifier, Model $subject): ExtensionSettings
    {
        $settings = $this->extensions->settings($identifier);

        return match (true) {
            $subject instanceof User => $settings->forUser($subject),
            $subject instanceof Server => $settings->forServer($subject),
            default => throw new UnexpectedValueException(sprintf('Extension "%s" has no default storage for %s.', $identifier, $subject::class)),
        };
    }
}
