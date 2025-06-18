<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Pterodactyl\Services\Hooks\TriggerDefinitionService;

class ActionDefinitionSeeder extends Seeder
{
    protected TriggerDefinitionService $triggerService;

    public function __construct(
        TriggerDefinitionService $triggerService
    ) {
        $this->triggerService = $triggerService;
    }

    public function run(): void
    {
        $triggers = [
            [
                'key' => 'power_changed',
                'name' => 'Power Changed',
                'description' => 'Executes when server power status changes.',
                'config_schema' => [
                    [
                        'type' => 'string',
                        'required' => true,
                        'label' => 'Power Status',
                        'input' => 'dropdown',
                        'options' => [
                            'power_off' => 'When server is powered off.',
                            'power_on' => 'When server is started',
                            'killed' => 'When server is killed',
                        ]
                    ]
                ],
            ],
            [
                'key' => 'console_match',
                'name' => 'Console regex match',
                'description' => 'When console outputs something that matches the following regex',
                'config_schema' => [
                     [
                        'type' => 'string',
                        'required' => true,
                        'validate' => 'regex',
                        'label' => 'Console Regex Match',
                        'input' => 'text',
                    ]
                ],
            ],
            [
                'key' => 'high_stats',
                'name' => 'High Usage',
                'description' => 'When the specified spec reaches the threshold',
                'config_schema' => [
                    [
                        'type' => 'string',
                        'required' => true,
                        'label' => 'Server Stat',
                        'input' => 'dropdown',
                        'options' => [
                            'memory' => 'Memory Usage',
                            'disk' => 'Disk Usage',
                        ]
                    ],
                    [
                        'type' => 'numeric',
                        'required' => true,
                        'label' => 'Server Stat',
                        'input' => 'number',
                    ],
                ],
            ],
        ];

        foreach ($triggers as $trigger) {
            $this->triggerService->create($trigger);
        }
    }
}
