<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Pterodactyl\Services\Hooks\ActionDefinitionService;

class ActionDefinitionSeeder extends Seeder
{
    protected ActionDefinitionService $actionService;

    public function __construct(
        ActionDefinitionService $actionService
    ) {
        $this->actionService = $actionService;
    }

    public function run(): void
    {
        $actions = [
            [
                'key' => 'run_schedule',
                'name' => 'Run Schedule',
                'description' => 'Execute a selected schedule for this server.',
                'config_schema' => [
                    [
                        'type' => 'integer',
                        'required' => true,
                        'label' => 'Schedule ID',
                        'input' => 'text',
                    ],
                ],
            ],
            [
                'key' => 'send_email',
                'name' => 'Send Email',
                'description' => 'Send an email to the server owner or a specified address.',
                'config_schema' => [
                    [
                        'type' => 'string',
                        'required' => false,
                        'label' => 'Email Address',
                        'input' => 'text',
                    ],
                    [
                        'type' => 'string',
                        'required' => true,
                        'label' => 'Subject',
                        'input' => 'text',
                    ],
                    [
                        'type' => 'string',
                        'required' => true,
                        'label' => 'Message',
                        'input' => 'text',
                    ],
                ],
            ],
            [
                'key' => 'discord_webhook',
                'name' => 'Discord Webhook',
                'description' => 'Send a message to a Discord channel using a webhook.',
                'config_schema' => [
                    [
                        'type' => 'string',
                        'required' => true,
                        'label' => 'Webhook URL',
                        'input' => 'text',
                    ],
                    [
                        'type' => 'string',
                        'required' => true,
                        'label' => 'Message Content',
                        'input' => 'text',
                    ],
                ],
            ],
        ];

        foreach ($actions as $action) {
            $this->actionService->create($action);
        }
    }
}
