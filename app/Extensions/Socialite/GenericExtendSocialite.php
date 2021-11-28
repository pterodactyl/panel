<?php

namespace Pterodactyl\Extensions\Socialite;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GenericExtendSocialite {
    public function handle(SocialiteWasCalled $socialiteWasCalled) {
        $socialiteWasCalled->extendSocialite('generic', GenericProvider::class);
    }
}
