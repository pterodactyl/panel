<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Settings;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Settings\SendTestMailRequest;
use Pterodactyl\Notifications\MailTested;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Settings', 'View and update global panel settings.')]
class TestMailController extends AdminApiController
{
    private const array MAIL_ERROR = [
        'errors' => [
            [
                'code' => 'HttpException',
                'status' => '500',
                'detail' => 'Unable to send mail using the configured transport.',
            ],
        ],
    ];

    /**
     * Send test mail message.
     */
    #[Endpoint('Send test mail', 'Sends a test email to the authenticated administrator using the configured mail transport.')]
    #[ScribeResponse(status: 204, description: 'Test mail queued.')]
    #[ScribeResponse(self::MAIL_ERROR, status: 500, description: 'The configured mail transport failed to send the message.')]
    public function __invoke(SendTestMailRequest $request): Response
    {
        try {
            Notification::route('mail', $request->user()->email)
                ->notify(new MailTested($request->user()));
        } catch (Throwable $throwable) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, $throwable->getMessage(), $throwable);
        }

        return $this->returnNoContent();
    }
}
