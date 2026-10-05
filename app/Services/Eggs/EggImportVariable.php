<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Eggs;

final readonly class EggImportVariable
{
    public function __construct(
        public string $name,
        public string $description,
        public string $environmentVariable,
        public string $defaultValue,
        public bool $userViewable,
        public bool $userEditable,
        public string $rules,
    ) {}

    /**
     * @return array{name: string, description: string, env_variable: string, default_value: string, user_viewable: bool, user_editable: bool, rules: string}
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'env_variable' => $this->environmentVariable,
            'default_value' => $this->defaultValue,
            'user_viewable' => $this->userViewable,
            'user_editable' => $this->userEditable,
            'rules' => $this->rules,
        ];
    }

    /**
     * @return array{name: string, description: string, env_variable: string, default_value: string, user_viewable: bool, user_editable: bool, rules: string, egg_id: int}
     */
    public function attributesForEgg(int $eggId): array
    {
        return array_merge($this->attributes(), ['egg_id' => $eggId]);
    }
}
