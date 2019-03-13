<?php
/**
 * Created by PhpStorm.
 * User: atlas
 * Date: 3/13/2019
 * Time: 4:01 AM
 */

namespace Entrypoint\Providers;


class EntrypointServiceProvider
{
    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * This is a custom namespace that you can change yourself to fit your needs.
     *
     * @var string
     */
    protected $namespace = 'Entrypoint\Http\Controllers';


    /**
     * Define the routes for the application.
     */
    public function map()
    {
        $this->mapEntrypointRoutes();
    }

    /**
     *
     * This is the main entrypoint to introduce new route files into pterodactyl. This is the cleanest way to add without introducing vendor packages.
     * @return void
     */
    protected function mapEntrypointRoutes()
    {
        /**
         * Example of an extension of the base routes.
         */
//        Route::middleware(['web', 'auth', 'csrf'])
//            ->namespace($this->namespace . '\Base')
//            ->group(base_path('routes/entrypoint/base.php'));
    }

}