<?php

namespace Pterodactyl\Console\Commands\Environment;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Traits\Commands\EnvironmentWriterTrait;

class OAuth2SettingsCommand extends Command
{
    use EnvironmentWriterTrait;

    /**
     * @var \Illuminate\Contracts\Console\Kernel
     */
    protected $command;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var string
     */
    protected $description = 'Configure OAuth2 environment settings for the Panel.';

    /**
     * @var string
     */
    protected $signature = 'p:environment:oauth2
                            {--clientId= : The client ID assigned to you by the provider.}
                            {--clientSecret= : The client password assigned to you by the provider.}
                            {--redirectUri= : The URI to which you should be redirected to after authentication.}
                            {--urlAuthorize= : The URL of the Authorization Endpoint.}
                            {--urlAccessToken= : The URL of the Token Endpoint.}
                            {--urlResourceOwnerDetails= : The URL of the User Info Endpoint.}
                            {--urlRevoke= : The URL to revoke access from your OAuth2 server/provider.}
                            {--proxy= : The URL and port of the Proxy.}
                            {--idKey= : The key for getting the user\'s id from the OAuth2 server.}
                            {--usernameKey= : The key for getting the user\'s username from the OAuth2 server.}
                            {--emailKey= : The key for getting the user\'s email from the OAuth2 server.}
                            {--firstNameKey= : The key for getting the user\'s first name from the OAuth2 server.}
                            {--lastNameKey= : The key for getting the user\'s last name from the OAuth2 server.}
                            {--scopes= : The scopes for the authorization url of OAuth2 server.}
                            {--createUser= : Whether or not to create a user if he doesnt exist on the system using OAuth2 resources.}
                            {--updateUser= : Whether or not to update the user\'s details using OAuth2 resources after each login.}';

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @var array
     */
    protected $unset = [];

    /**
     * OauthSettingsCommand constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(ConfigRepository $config)
    {
        parent::__construct();

        $this->config = $config;
    }

    /**
     * Handle command execution.
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function handle()
    {
        $this->output->warning(trans('command/messages.environment.oauth2.oauth2_warning'));
        $this->variables['OAUTH2_CLIENT_ID'] = $this->option('clientId') ?? $this->ask(
                trans('command/messages.environment.oauth2.clientId'), $this->config->get('oauth2.options.clientId', 'pterodactyl')
            );

        $this->variables['OAUTH2_CLIENT_SECRET'] = $this->option('clientSecret') ?? $this->ask(
                trans('command/messages.environment.oauth2.clientSecret'), env('OAUTH2_CLIENT_SECRET')
            );

        $this->variables['OAUTH2_URL_AUTHORIZE'] = $this->option('urlAuthorize') ?? $this->ask(
                trans('command/messages.environment.oauth2.urlAuthorize'), $this->config->get('oauth2.options.urlAuthorize', 'http://example.com/oauth2/authorize')
            );

        $this->variables['OAUTH2_URL_ACCESS_TOKEN'] = $this->option('urlAccessToken') ?? $this->ask(
                trans('command/messages.environment.oauth2.urlAccessToken'), $this->config->get('oauth2.options.urlAccessToken', 'http://example.com/oauth2/token')
            );

        $this->variables['OAUTH2_URL_RESOURCE_OWNER_DETAILS'] = $this->option('urlResourceOwnerDetails') ?? $this->ask(
                trans('command/messages.environment.oauth2.urlResourceOwnerDetails'), $this->config->get('oauth2.options.urlResourceOwnerDetails', 'http://example.com/oauth2/resource')
            );

        $this->variables['OAUTH2_URL_REVOKE'] = $this->option('urlRevoke') ?? $this->ask(
                trans('command/messages.environment.oauth2.urlRevoke'), env('OAUTH2_URL_REVOKE', 'http://example.com/oauth2/revoke')
            );

        if ($this->option('proxy') ?? $this->confirm(trans('command/messages.environment.oauth2.ask_proxy'), env('OAUTH2_URL_PROXY_URL') != null)) {
            $this->variables['OAUTH2_URL_PROXY_URL'] = $this->option('proxy') ?? $this->ask(
                    trans('command/messages.environment.oauth2.proxy'), $this->config->get('oauth2.options.proxy-options.proxy', '192.168.0.1:8888')
                );
        } else {
            array_push($this->unset, 'OAUTH2_URL_PROXY_URL');
        }

        $this->output->comment(trans('command/messages.environment.oauth2.resource_keys_help'));

        $this->variables['OAUTH2_ID_KEY'] = $this->option('idKey') ?? $this->ask(
                trans('command/messages.environment.oauth2.id'), env('OAUTH2_ID_KEY', 'id')
            );

        $this->variables['OAUTH2_USERNAME_KEY'] = $this->option('usernameKey') ?? $this->ask(
                trans('command/messages.environment.oauth2.username'),env('OAUTH2_USERNAME_KEY', 'username')
            );

        $this->variables['OAUTH2_EMAIL_KEY'] = $this->option('emailKey') ?? $this->ask(
                trans('command/messages.environment.oauth2.email'),env('OAUTH2_EMAIL_KEY', 'email')
            );

        if ($this->option('firstNameKey') ?? $this->confirm(trans('command/messages.environment.oauth2.ask_first_name'), env('OAUTH2_FIRST_NAME_KEY') != null)) {
            $this->variables['OAUTH2_FIRST_NAME_KEY'] = $this->option('firstNameKey') ?? $this->ask(
                    trans('command/messages.environment.oauth2.first_name'),env('OAUTH2_FIRST_NAME_KEY', 'first_name')
                );
        } else {
            array_push($this->unset,'OAUTH2_FIRST_NAME_KEY');
        }

        if ($this->option('lastNameKey') ?? $this->confirm(trans('command/messages.environment.oauth2.ask_last_name'), env('OAUTH2_LAST_NAME_KEY') != null)) {
            $this->variables['OAUTH2_LAST_NAME_KEY'] = $this->option('lastNameKey') ?? $this->ask(
                    trans('command/messages.environment.oauth2.last_name'),env('OAUTH2_LAST_NAME_KEY', 'last_name')
                );
        } else {
            array_push($this->unset,'OAUTH2_LAST_NAME_KEY');
        }

        $this->variables['OAUTH2_SCOPES'] =  $this->option('scopes') ?? $this->ask(
                trans('command/messages.environment.oauth2.scopes'), env('OAUTH2_SCOPES', 'email')
            );

        $this->variables['OAUTH2_CREATE_USER'] = $this->option('createUser') ?? ($this->choice(
                trans('command/messages.environment.oauth2.create_user'),
                [trans('command/messages.environment.oauth2.create_user_options.only_allow_login'), trans('command/messages.environment.oauth2.create_user_options.create')],
                (env('OAUTH2_CREATE_USER', false) ? '1' : '0')
            ) == trans('command/messages.environment.oauth2.create_user_options.create') ? 'true' : 'false');

        $this->output->warning(trans(('command/messages.environment.oauth2.create_user_warning.'. ($this->variables['OAUTH2_CREATE_USER'] == 'true' ? 'create' : 'only_allow_login'))));

        $this->variables['OAUTH2_UPDATE_USER'] = $this->option('updateUser') ?? ($this->confirm(
                trans('command/messages.environment.oauth2.update_user'),env('OAUTH2_UPDATE_USER', true)
            ) ? 'true' : 'false');


        if (!empty($this->unset)) {
            $path = base_path('.env');
            if (! file_exists($path)) {
                throw new PterodactylException('Cannot locate .env file, was this software installed correctly?');
            }

            $saveContents = file_get_contents($path);

            foreach ($this->unset as $key) {
                $saveContents = preg_replace('/^' . $key . '=(.*)$/m', '', $saveContents);
            }
            file_put_contents($path, $saveContents);
        }

        $this->writeToEnvironment($this->variables);

        $this->output->note(trans('command/messages.environment.oauth2.setup_finished'));
        $this->output->warning(trans('command/messages.environment.oauth2.redirect_uri_warning'));
        $this->output->warning(trans('command/messages.environment.oauth2.not_official'));
    }
}
