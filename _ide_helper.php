<?php
// @formatter:off

/**
 * A helper file for Laravel, to provide autocomplete information to your IDE
 * Generated for Laravel 8.51.0.
 *
 * This file should not be included in your code, only analyzed by your IDE!
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 * @see https://github.com/barryvdh/laravel-ide-helper
 */

    namespace Prologue\Alerts\Facades { 
            /**
     * 
     *
     * @method static \Prologue\Alerts\AlertsMessageBag success(string $text)
     * @method static \Prologue\Alerts\AlertsMessageBag error(string $text)
     * @method static \Prologue\Alerts\AlertsMessageBag warning(string $text)
     * @method static \Prologue\Alerts\AlertsMessageBag info(string $text)
     */ 
        class Alert {
                    /**
         * Store the messages in the current session.
         *
         * @return \Prologue\Alerts\AlertsMessageBag 
         * @static 
         */ 
        public static function flash()
        {
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->flash();
        }
                    /**
         * Deletes all messages.
         *
         * @param bool $withSession
         * @return \Prologue\Alerts\AlertsMessageBag 
         * @static 
         */ 
        public static function flush($withSession = true)
        {
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->flush($withSession);
        }
                    /**
         * Checks to see if any messages exist.
         *
         * @param null $key A specific level you wish to check for.
         * @return bool 
         * @static 
         */ 
        public static function has($level = null)
        {
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->has($level);
        }
                    /**
         * Get the number of messages in the message bag.
         *
         * @param null $level A specific level name you wish to count.
         * @return int 
         * @static 
         */ 
        public static function count($level = null)
        {
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->count($level);
        }
                    /**
         * Returns the alert levels from the config.
         *
         * @return array 
         * @static 
         */ 
        public static function getLevels()
        {
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->getLevels();
        }
                    /**
         * Returns the Illuminate Session Store.
         *
         * @return \Illuminate\Session\Store 
         * @static 
         */ 
        public static function getSession()
        {
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->getSession();
        }
                    /**
         * Returns the Illuminate Config Repository.
         *
         * @return \Illuminate\Config\Repository 
         * @static 
         */ 
        public static function getConfig()
        {
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->getConfig();
        }
                    /**
         * Get the keys present in the message bag.
         *
         * @return array 
         * @static 
         */ 
        public static function keys()
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->keys();
        }
                    /**
         * Add a message to the message bag.
         *
         * @param string $key
         * @param string $message
         * @return \Prologue\Alerts\AlertsMessageBag 
         * @static 
         */ 
        public static function add($key, $message)
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->add($key, $message);
        }
                    /**
         * Add a message to the message bag if the given conditional is "true".
         *
         * @param bool $boolean
         * @param string $key
         * @param string $message
         * @return \Prologue\Alerts\AlertsMessageBag 
         * @static 
         */ 
        public static function addIf($boolean, $key, $message)
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->addIf($boolean, $key, $message);
        }
                    /**
         * Merge a new array of messages into the message bag.
         *
         * @param \Illuminate\Contracts\Support\MessageProvider|array $messages
         * @return \Prologue\Alerts\AlertsMessageBag 
         * @static 
         */ 
        public static function merge($messages)
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->merge($messages);
        }
                    /**
         * Determine if messages exist for any of the given keys.
         *
         * @param array|string $keys
         * @return bool 
         * @static 
         */ 
        public static function hasAny($keys = [])
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->hasAny($keys);
        }
                    /**
         * Get the first message from the message bag for a given key.
         *
         * @param string|null $key
         * @param string|null $format
         * @return string 
         * @static 
         */ 
        public static function first($key = null, $format = null)
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->first($key, $format);
        }
                    /**
         * Get all of the messages from the message bag for a given key.
         *
         * @param string $key
         * @param string|null $format
         * @return array 
         * @static 
         */ 
        public static function get($key, $format = null)
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->get($key, $format);
        }
                    /**
         * Get all of the messages for every key in the message bag.
         *
         * @param string|null $format
         * @return array 
         * @static 
         */ 
        public static function all($format = null)
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->all($format);
        }
                    /**
         * Get all of the unique messages for every key in the message bag.
         *
         * @param string|null $format
         * @return array 
         * @static 
         */ 
        public static function unique($format = null)
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->unique($format);
        }
                    /**
         * Get the raw messages in the message bag.
         *
         * @return array 
         * @static 
         */ 
        public static function messages()
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->messages();
        }
                    /**
         * Get the raw messages in the message bag.
         *
         * @return array 
         * @static 
         */ 
        public static function getMessages()
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->getMessages();
        }
                    /**
         * Get the messages for the instance.
         *
         * @return \Illuminate\Support\MessageBag 
         * @static 
         */ 
        public static function getMessageBag()
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->getMessageBag();
        }
                    /**
         * Get the default message format.
         *
         * @return string 
         * @static 
         */ 
        public static function getFormat()
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->getFormat();
        }
                    /**
         * Set the default message format.
         *
         * @param string $format
         * @return \Illuminate\Support\MessageBag 
         * @static 
         */ 
        public static function setFormat($format = ':message')
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->setFormat($format);
        }
                    /**
         * Determine if the message bag has any messages.
         *
         * @return bool 
         * @static 
         */ 
        public static function isEmpty()
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->isEmpty();
        }
                    /**
         * Determine if the message bag has any messages.
         *
         * @return bool 
         * @static 
         */ 
        public static function isNotEmpty()
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->isNotEmpty();
        }
                    /**
         * Determine if the message bag has any messages.
         *
         * @return bool 
         * @static 
         */ 
        public static function any()
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->any();
        }
                    /**
         * Get the instance as an array.
         *
         * @return array 
         * @static 
         */ 
        public static function toArray()
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->toArray();
        }
                    /**
         * Convert the object into something JSON serializable.
         *
         * @return array 
         * @static 
         */ 
        public static function jsonSerialize()
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->jsonSerialize();
        }
                    /**
         * Convert the object to its JSON representation.
         *
         * @param int $options
         * @return string 
         * @static 
         */ 
        public static function toJson($options = 0)
        {            //Method inherited from \Illuminate\Support\MessageBag         
                        /** @var \Prologue\Alerts\AlertsMessageBag $instance */
                        return $instance->toJson($options);
        }
         
    }
     
}

    namespace Illuminate\Support\Facades { 
            /**
     * 
     *
     * @see \Illuminate\Contracts\Foundation\Application
     */ 
        class App {
                    /**
         * Get the version number of the application.
         *
         * @return string 
         * @static 
         */ 
        public static function version()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->version();
        }
                    /**
         * Run the given array of bootstrap classes.
         *
         * @param string[] $bootstrappers
         * @return void 
         * @static 
         */ 
        public static function bootstrapWith($bootstrappers)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->bootstrapWith($bootstrappers);
        }
                    /**
         * Register a callback to run after loading the environment.
         *
         * @param \Closure $callback
         * @return void 
         * @static 
         */ 
        public static function afterLoadingEnvironment($callback)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->afterLoadingEnvironment($callback);
        }
                    /**
         * Register a callback to run before a bootstrapper.
         *
         * @param string $bootstrapper
         * @param \Closure $callback
         * @return void 
         * @static 
         */ 
        public static function beforeBootstrapping($bootstrapper, $callback)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->beforeBootstrapping($bootstrapper, $callback);
        }
                    /**
         * Register a callback to run after a bootstrapper.
         *
         * @param string $bootstrapper
         * @param \Closure $callback
         * @return void 
         * @static 
         */ 
        public static function afterBootstrapping($bootstrapper, $callback)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->afterBootstrapping($bootstrapper, $callback);
        }
                    /**
         * Determine if the application has been bootstrapped before.
         *
         * @return bool 
         * @static 
         */ 
        public static function hasBeenBootstrapped()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->hasBeenBootstrapped();
        }
                    /**
         * Set the base path for the application.
         *
         * @param string $basePath
         * @return \Illuminate\Foundation\Application 
         * @static 
         */ 
        public static function setBasePath($basePath)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->setBasePath($basePath);
        }
                    /**
         * Get the path to the application "app" directory.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function path($path = '')
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->path($path);
        }
                    /**
         * Set the application directory.
         *
         * @param string $path
         * @return \Illuminate\Foundation\Application 
         * @static 
         */ 
        public static function useAppPath($path)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->useAppPath($path);
        }
                    /**
         * Get the base path of the Laravel installation.
         *
         * @param string $path Optionally, a path to append to the base path
         * @return string 
         * @static 
         */ 
        public static function basePath($path = '')
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->basePath($path);
        }
                    /**
         * Get the path to the bootstrap directory.
         *
         * @param string $path Optionally, a path to append to the bootstrap path
         * @return string 
         * @static 
         */ 
        public static function bootstrapPath($path = '')
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->bootstrapPath($path);
        }
                    /**
         * Get the path to the application configuration files.
         *
         * @param string $path Optionally, a path to append to the config path
         * @return string 
         * @static 
         */ 
        public static function configPath($path = '')
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->configPath($path);
        }
                    /**
         * Get the path to the database directory.
         *
         * @param string $path Optionally, a path to append to the database path
         * @return string 
         * @static 
         */ 
        public static function databasePath($path = '')
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->databasePath($path);
        }
                    /**
         * Set the database directory.
         *
         * @param string $path
         * @return \Illuminate\Foundation\Application 
         * @static 
         */ 
        public static function useDatabasePath($path)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->useDatabasePath($path);
        }
                    /**
         * Get the path to the language files.
         *
         * @return string 
         * @static 
         */ 
        public static function langPath()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->langPath();
        }
                    /**
         * Set the language file directory.
         *
         * @param string $path
         * @return \Illuminate\Foundation\Application 
         * @static 
         */ 
        public static function useLangPath($path)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->useLangPath($path);
        }
                    /**
         * Get the path to the public / web directory.
         *
         * @return string 
         * @static 
         */ 
        public static function publicPath()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->publicPath();
        }
                    /**
         * Get the path to the storage directory.
         *
         * @return string 
         * @static 
         */ 
        public static function storagePath()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->storagePath();
        }
                    /**
         * Set the storage directory.
         *
         * @param string $path
         * @return \Illuminate\Foundation\Application 
         * @static 
         */ 
        public static function useStoragePath($path)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->useStoragePath($path);
        }
                    /**
         * Get the path to the resources directory.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function resourcePath($path = '')
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->resourcePath($path);
        }
                    /**
         * Get the path to the views directory.
         * 
         * This method returns the first configured path in the array of view paths.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function viewPath($path = '')
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->viewPath($path);
        }
                    /**
         * Get the path to the environment file directory.
         *
         * @return string 
         * @static 
         */ 
        public static function environmentPath()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->environmentPath();
        }
                    /**
         * Set the directory for the environment file.
         *
         * @param string $path
         * @return \Illuminate\Foundation\Application 
         * @static 
         */ 
        public static function useEnvironmentPath($path)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->useEnvironmentPath($path);
        }
                    /**
         * Set the environment file to be loaded during bootstrapping.
         *
         * @param string $file
         * @return \Illuminate\Foundation\Application 
         * @static 
         */ 
        public static function loadEnvironmentFrom($file)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->loadEnvironmentFrom($file);
        }
                    /**
         * Get the environment file the application is using.
         *
         * @return string 
         * @static 
         */ 
        public static function environmentFile()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->environmentFile();
        }
                    /**
         * Get the fully qualified path to the environment file.
         *
         * @return string 
         * @static 
         */ 
        public static function environmentFilePath()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->environmentFilePath();
        }
                    /**
         * Get or check the current application environment.
         *
         * @param string|array $environments
         * @return string|bool 
         * @static 
         */ 
        public static function environment(...$environments)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->environment(...$environments);
        }
                    /**
         * Determine if the application is in the local environment.
         *
         * @return bool 
         * @static 
         */ 
        public static function isLocal()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->isLocal();
        }
                    /**
         * Determine if the application is in the production environment.
         *
         * @return bool 
         * @static 
         */ 
        public static function isProduction()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->isProduction();
        }
                    /**
         * Detect the application's current environment.
         *
         * @param \Closure $callback
         * @return string 
         * @static 
         */ 
        public static function detectEnvironment($callback)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->detectEnvironment($callback);
        }
                    /**
         * Determine if the application is running in the console.
         *
         * @return bool 
         * @static 
         */ 
        public static function runningInConsole()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->runningInConsole();
        }
                    /**
         * Determine if the application is running unit tests.
         *
         * @return bool 
         * @static 
         */ 
        public static function runningUnitTests()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->runningUnitTests();
        }
                    /**
         * Register all of the configured providers.
         *
         * @return void 
         * @static 
         */ 
        public static function registerConfiguredProviders()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->registerConfiguredProviders();
        }
                    /**
         * Register a service provider with the application.
         *
         * @param \Illuminate\Support\ServiceProvider|string $provider
         * @param bool $force
         * @return \Illuminate\Support\ServiceProvider 
         * @static 
         */ 
        public static function register($provider, $force = false)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->register($provider, $force);
        }
                    /**
         * Get the registered service provider instance if it exists.
         *
         * @param \Illuminate\Support\ServiceProvider|string $provider
         * @return \Illuminate\Support\ServiceProvider|null 
         * @static 
         */ 
        public static function getProvider($provider)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getProvider($provider);
        }
                    /**
         * Get the registered service provider instances if any exist.
         *
         * @param \Illuminate\Support\ServiceProvider|string $provider
         * @return array 
         * @static 
         */ 
        public static function getProviders($provider)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getProviders($provider);
        }
                    /**
         * Resolve a service provider instance from the class name.
         *
         * @param string $provider
         * @return \Illuminate\Support\ServiceProvider 
         * @static 
         */ 
        public static function resolveProvider($provider)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->resolveProvider($provider);
        }
                    /**
         * Load and boot all of the remaining deferred providers.
         *
         * @return void 
         * @static 
         */ 
        public static function loadDeferredProviders()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->loadDeferredProviders();
        }
                    /**
         * Load the provider for a deferred service.
         *
         * @param string $service
         * @return void 
         * @static 
         */ 
        public static function loadDeferredProvider($service)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->loadDeferredProvider($service);
        }
                    /**
         * Register a deferred provider and service.
         *
         * @param string $provider
         * @param string|null $service
         * @return void 
         * @static 
         */ 
        public static function registerDeferredProvider($provider, $service = null)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->registerDeferredProvider($provider, $service);
        }
                    /**
         * Resolve the given type from the container.
         *
         * @param string $abstract
         * @param array $parameters
         * @return mixed 
         * @static 
         */ 
        public static function make($abstract, $parameters = [])
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->make($abstract, $parameters);
        }
                    /**
         * Determine if the given abstract type has been bound.
         *
         * @param string $abstract
         * @return bool 
         * @static 
         */ 
        public static function bound($abstract)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->bound($abstract);
        }
                    /**
         * Determine if the application has booted.
         *
         * @return bool 
         * @static 
         */ 
        public static function isBooted()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->isBooted();
        }
                    /**
         * Boot the application's service providers.
         *
         * @return void 
         * @static 
         */ 
        public static function boot()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->boot();
        }
                    /**
         * Register a new boot listener.
         *
         * @param callable $callback
         * @return void 
         * @static 
         */ 
        public static function booting($callback)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->booting($callback);
        }
                    /**
         * Register a new "booted" listener.
         *
         * @param callable $callback
         * @return void 
         * @static 
         */ 
        public static function booted($callback)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->booted($callback);
        }
                    /**
         * {@inheritdoc}
         *
         * @static 
         */ 
        public static function handle($request, $type = 1, $catch = true)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->handle($request, $type, $catch);
        }
                    /**
         * Determine if middleware has been disabled for the application.
         *
         * @return bool 
         * @static 
         */ 
        public static function shouldSkipMiddleware()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->shouldSkipMiddleware();
        }
                    /**
         * Get the path to the cached services.php file.
         *
         * @return string 
         * @static 
         */ 
        public static function getCachedServicesPath()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getCachedServicesPath();
        }
                    /**
         * Get the path to the cached packages.php file.
         *
         * @return string 
         * @static 
         */ 
        public static function getCachedPackagesPath()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getCachedPackagesPath();
        }
                    /**
         * Determine if the application configuration is cached.
         *
         * @return bool 
         * @static 
         */ 
        public static function configurationIsCached()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->configurationIsCached();
        }
                    /**
         * Get the path to the configuration cache file.
         *
         * @return string 
         * @static 
         */ 
        public static function getCachedConfigPath()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getCachedConfigPath();
        }
                    /**
         * Determine if the application routes are cached.
         *
         * @return bool 
         * @static 
         */ 
        public static function routesAreCached()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->routesAreCached();
        }
                    /**
         * Get the path to the routes cache file.
         *
         * @return string 
         * @static 
         */ 
        public static function getCachedRoutesPath()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getCachedRoutesPath();
        }
                    /**
         * Determine if the application events are cached.
         *
         * @return bool 
         * @static 
         */ 
        public static function eventsAreCached()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->eventsAreCached();
        }
                    /**
         * Get the path to the events cache file.
         *
         * @return string 
         * @static 
         */ 
        public static function getCachedEventsPath()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getCachedEventsPath();
        }
                    /**
         * Add new prefix to list of absolute path prefixes.
         *
         * @param string $prefix
         * @return \Illuminate\Foundation\Application 
         * @static 
         */ 
        public static function addAbsoluteCachePathPrefix($prefix)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->addAbsoluteCachePathPrefix($prefix);
        }
                    /**
         * Determine if the application is currently down for maintenance.
         *
         * @return bool 
         * @static 
         */ 
        public static function isDownForMaintenance()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->isDownForMaintenance();
        }
                    /**
         * Throw an HttpException with the given data.
         *
         * @param int $code
         * @param string $message
         * @param array $headers
         * @return void 
         * @throws \Symfony\Component\HttpKernel\Exception\HttpException
         * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
         * @static 
         */ 
        public static function abort($code, $message = '', $headers = [])
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->abort($code, $message, $headers);
        }
                    /**
         * Register a terminating callback with the application.
         *
         * @param callable|string $callback
         * @return \Illuminate\Foundation\Application 
         * @static 
         */ 
        public static function terminating($callback)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->terminating($callback);
        }
                    /**
         * Terminate the application.
         *
         * @return void 
         * @static 
         */ 
        public static function terminate()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->terminate();
        }
                    /**
         * Get the service providers that have been loaded.
         *
         * @return array 
         * @static 
         */ 
        public static function getLoadedProviders()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getLoadedProviders();
        }
                    /**
         * Determine if the given service provider is loaded.
         *
         * @param string $provider
         * @return bool 
         * @static 
         */ 
        public static function providerIsLoaded($provider)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->providerIsLoaded($provider);
        }
                    /**
         * Get the application's deferred services.
         *
         * @return array 
         * @static 
         */ 
        public static function getDeferredServices()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getDeferredServices();
        }
                    /**
         * Set the application's deferred services.
         *
         * @param array $services
         * @return void 
         * @static 
         */ 
        public static function setDeferredServices($services)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->setDeferredServices($services);
        }
                    /**
         * Add an array of services to the application's deferred services.
         *
         * @param array $services
         * @return void 
         * @static 
         */ 
        public static function addDeferredServices($services)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->addDeferredServices($services);
        }
                    /**
         * Determine if the given service is a deferred service.
         *
         * @param string $service
         * @return bool 
         * @static 
         */ 
        public static function isDeferredService($service)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->isDeferredService($service);
        }
                    /**
         * Configure the real-time facade namespace.
         *
         * @param string $namespace
         * @return void 
         * @static 
         */ 
        public static function provideFacades($namespace)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->provideFacades($namespace);
        }
                    /**
         * Get the current application locale.
         *
         * @return string 
         * @static 
         */ 
        public static function getLocale()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getLocale();
        }
                    /**
         * Get the current application locale.
         *
         * @return string 
         * @static 
         */ 
        public static function currentLocale()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->currentLocale();
        }
                    /**
         * Get the current application fallback locale.
         *
         * @return string 
         * @static 
         */ 
        public static function getFallbackLocale()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getFallbackLocale();
        }
                    /**
         * Set the current application locale.
         *
         * @param string $locale
         * @return void 
         * @static 
         */ 
        public static function setLocale($locale)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->setLocale($locale);
        }
                    /**
         * Set the current application fallback locale.
         *
         * @param string $fallbackLocale
         * @return void 
         * @static 
         */ 
        public static function setFallbackLocale($fallbackLocale)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->setFallbackLocale($fallbackLocale);
        }
                    /**
         * Determine if the application locale is the given locale.
         *
         * @param string $locale
         * @return bool 
         * @static 
         */ 
        public static function isLocale($locale)
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->isLocale($locale);
        }
                    /**
         * Register the core class aliases in the container.
         *
         * @return void 
         * @static 
         */ 
        public static function registerCoreContainerAliases()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->registerCoreContainerAliases();
        }
                    /**
         * Flush the container of all bindings and resolved instances.
         *
         * @return void 
         * @static 
         */ 
        public static function flush()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->flush();
        }
                    /**
         * Get the application namespace.
         *
         * @return string 
         * @throws \RuntimeException
         * @static 
         */ 
        public static function getNamespace()
        {
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getNamespace();
        }
                    /**
         * Define a contextual binding.
         *
         * @param array|string $concrete
         * @return \Illuminate\Contracts\Container\ContextualBindingBuilder 
         * @static 
         */ 
        public static function when($concrete)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->when($concrete);
        }
                    /**
         * Returns true if the container can return an entry for the given identifier.
         * 
         * Returns false otherwise.
         * 
         * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
         * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
         *
         * @param string $id Identifier of the entry to look for.
         * @return bool 
         * @static 
         */ 
        public static function has($id)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->has($id);
        }
                    /**
         * Determine if the given abstract type has been resolved.
         *
         * @param string $abstract
         * @return bool 
         * @static 
         */ 
        public static function resolved($abstract)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->resolved($abstract);
        }
                    /**
         * Determine if a given type is shared.
         *
         * @param string $abstract
         * @return bool 
         * @static 
         */ 
        public static function isShared($abstract)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->isShared($abstract);
        }
                    /**
         * Determine if a given string is an alias.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function isAlias($name)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->isAlias($name);
        }
                    /**
         * Register a binding with the container.
         *
         * @param string $abstract
         * @param \Closure|string|null $concrete
         * @param bool $shared
         * @return void 
         * @throws \TypeError
         * @static 
         */ 
        public static function bind($abstract, $concrete = null, $shared = false)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->bind($abstract, $concrete, $shared);
        }
                    /**
         * Determine if the container has a method binding.
         *
         * @param string $method
         * @return bool 
         * @static 
         */ 
        public static function hasMethodBinding($method)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->hasMethodBinding($method);
        }
                    /**
         * Bind a callback to resolve with Container::call.
         *
         * @param array|string $method
         * @param \Closure $callback
         * @return void 
         * @static 
         */ 
        public static function bindMethod($method, $callback)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->bindMethod($method, $callback);
        }
                    /**
         * Get the method binding for the given method.
         *
         * @param string $method
         * @param mixed $instance
         * @return mixed 
         * @static 
         */ 
        public static function callMethodBinding($method, $instance)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->callMethodBinding($method, $instance);
        }
                    /**
         * Add a contextual binding to the container.
         *
         * @param string $concrete
         * @param string $abstract
         * @param \Closure|string $implementation
         * @return void 
         * @static 
         */ 
        public static function addContextualBinding($concrete, $abstract, $implementation)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->addContextualBinding($concrete, $abstract, $implementation);
        }
                    /**
         * Register a binding if it hasn't already been registered.
         *
         * @param string $abstract
         * @param \Closure|string|null $concrete
         * @param bool $shared
         * @return void 
         * @static 
         */ 
        public static function bindIf($abstract, $concrete = null, $shared = false)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->bindIf($abstract, $concrete, $shared);
        }
                    /**
         * Register a shared binding in the container.
         *
         * @param string $abstract
         * @param \Closure|string|null $concrete
         * @return void 
         * @static 
         */ 
        public static function singleton($abstract, $concrete = null)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->singleton($abstract, $concrete);
        }
                    /**
         * Register a shared binding if it hasn't already been registered.
         *
         * @param string $abstract
         * @param \Closure|string|null $concrete
         * @return void 
         * @static 
         */ 
        public static function singletonIf($abstract, $concrete = null)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->singletonIf($abstract, $concrete);
        }
                    /**
         * Register a scoped binding in the container.
         *
         * @param string $abstract
         * @param \Closure|string|null $concrete
         * @return void 
         * @static 
         */ 
        public static function scoped($abstract, $concrete = null)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->scoped($abstract, $concrete);
        }
                    /**
         * Register a scoped binding if it hasn't already been registered.
         *
         * @param string $abstract
         * @param \Closure|string|null $concrete
         * @return void 
         * @static 
         */ 
        public static function scopedIf($abstract, $concrete = null)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->scopedIf($abstract, $concrete);
        }
                    /**
         * "Extend" an abstract type in the container.
         *
         * @param string $abstract
         * @param \Closure $closure
         * @return void 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function extend($abstract, $closure)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->extend($abstract, $closure);
        }
                    /**
         * Register an existing instance as shared in the container.
         *
         * @param string $abstract
         * @param mixed $instance
         * @return mixed 
         * @static 
         */ 
        public static function instance($abstract, $instance)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->instance($abstract, $instance);
        }
                    /**
         * Assign a set of tags to a given binding.
         *
         * @param array|string $abstracts
         * @param array|mixed $tags
         * @return void 
         * @static 
         */ 
        public static function tag($abstracts, $tags)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->tag($abstracts, $tags);
        }
                    /**
         * Resolve all of the bindings for a given tag.
         *
         * @param string $tag
         * @return \Illuminate\Container\iterable 
         * @static 
         */ 
        public static function tagged($tag)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->tagged($tag);
        }
                    /**
         * Alias a type to a different name.
         *
         * @param string $abstract
         * @param string $alias
         * @return void 
         * @throws \LogicException
         * @static 
         */ 
        public static function alias($abstract, $alias)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->alias($abstract, $alias);
        }
                    /**
         * Bind a new callback to an abstract's rebind event.
         *
         * @param string $abstract
         * @param \Closure $callback
         * @return mixed 
         * @static 
         */ 
        public static function rebinding($abstract, $callback)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->rebinding($abstract, $callback);
        }
                    /**
         * Refresh an instance on the given target and method.
         *
         * @param string $abstract
         * @param mixed $target
         * @param string $method
         * @return mixed 
         * @static 
         */ 
        public static function refresh($abstract, $target, $method)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->refresh($abstract, $target, $method);
        }
                    /**
         * Wrap the given closure such that its dependencies will be injected when executed.
         *
         * @param \Closure $callback
         * @param array $parameters
         * @return \Closure 
         * @static 
         */ 
        public static function wrap($callback, $parameters = [])
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->wrap($callback, $parameters);
        }
                    /**
         * Call the given Closure / class@method and inject its dependencies.
         *
         * @param callable|string $callback
         * @param \Illuminate\Container\array<string,  mixed>  $parameters
         * @param string|null $defaultMethod
         * @return mixed 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function call($callback, $parameters = [], $defaultMethod = null)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->call($callback, $parameters, $defaultMethod);
        }
                    /**
         * Get a closure to resolve the given type from the container.
         *
         * @param string $abstract
         * @return \Closure 
         * @static 
         */ 
        public static function factory($abstract)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->factory($abstract);
        }
                    /**
         * An alias function name for make().
         *
         * @param string|callable $abstract
         * @param array $parameters
         * @return mixed 
         * @throws \Illuminate\Contracts\Container\BindingResolutionException
         * @static 
         */ 
        public static function makeWith($abstract, $parameters = [])
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->makeWith($abstract, $parameters);
        }
                    /**
         * Finds an entry of the container by its identifier and returns it.
         *
         * @param string $id Identifier of the entry to look for.
         * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
         * @throws ContainerExceptionInterface Error while retrieving the entry.
         * @return mixed Entry.
         * @static 
         */ 
        public static function get($id)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->get($id);
        }
                    /**
         * Instantiate a concrete instance of the given type.
         *
         * @param \Closure|string $concrete
         * @return mixed 
         * @throws \Illuminate\Contracts\Container\BindingResolutionException
         * @throws \Illuminate\Contracts\Container\CircularDependencyException
         * @static 
         */ 
        public static function build($concrete)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->build($concrete);
        }
                    /**
         * Register a new before resolving callback for all types.
         *
         * @param \Closure|string $abstract
         * @param \Closure|null $callback
         * @return void 
         * @static 
         */ 
        public static function beforeResolving($abstract, $callback = null)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->beforeResolving($abstract, $callback);
        }
                    /**
         * Register a new resolving callback.
         *
         * @param \Closure|string $abstract
         * @param \Closure|null $callback
         * @return void 
         * @static 
         */ 
        public static function resolving($abstract, $callback = null)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->resolving($abstract, $callback);
        }
                    /**
         * Register a new after resolving callback for all types.
         *
         * @param \Closure|string $abstract
         * @param \Closure|null $callback
         * @return void 
         * @static 
         */ 
        public static function afterResolving($abstract, $callback = null)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->afterResolving($abstract, $callback);
        }
                    /**
         * Get the container's bindings.
         *
         * @return array 
         * @static 
         */ 
        public static function getBindings()
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getBindings();
        }
                    /**
         * Get the alias for an abstract if available.
         *
         * @param string $abstract
         * @return string 
         * @static 
         */ 
        public static function getAlias($abstract)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->getAlias($abstract);
        }
                    /**
         * Remove all of the extender callbacks for a given type.
         *
         * @param string $abstract
         * @return void 
         * @static 
         */ 
        public static function forgetExtenders($abstract)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->forgetExtenders($abstract);
        }
                    /**
         * Remove a resolved instance from the instance cache.
         *
         * @param string $abstract
         * @return void 
         * @static 
         */ 
        public static function forgetInstance($abstract)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->forgetInstance($abstract);
        }
                    /**
         * Clear all of the instances from the container.
         *
         * @return void 
         * @static 
         */ 
        public static function forgetInstances()
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->forgetInstances();
        }
                    /**
         * Clear all of the scoped instances from the container.
         *
         * @return void 
         * @static 
         */ 
        public static function forgetScopedInstances()
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->forgetScopedInstances();
        }
                    /**
         * Get the globally available instance of the container.
         *
         * @return static 
         * @static 
         */ 
        public static function getInstance()
        {            //Method inherited from \Illuminate\Container\Container         
                        return \Illuminate\Foundation\Application::getInstance();
        }
                    /**
         * Set the shared instance of the container.
         *
         * @param \Illuminate\Contracts\Container\Container|null $container
         * @return \Illuminate\Contracts\Container\Container|static 
         * @static 
         */ 
        public static function setInstance($container = null)
        {            //Method inherited from \Illuminate\Container\Container         
                        return \Illuminate\Foundation\Application::setInstance($container);
        }
                    /**
         * Determine if a given offset exists.
         *
         * @param string $key
         * @return bool 
         * @static 
         */ 
        public static function offsetExists($key)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->offsetExists($key);
        }
                    /**
         * Get the value at a given offset.
         *
         * @param string $key
         * @return mixed 
         * @static 
         */ 
        public static function offsetGet($key)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        return $instance->offsetGet($key);
        }
                    /**
         * Set the value at a given offset.
         *
         * @param string $key
         * @param mixed $value
         * @return void 
         * @static 
         */ 
        public static function offsetSet($key, $value)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->offsetSet($key, $value);
        }
                    /**
         * Unset the value at a given offset.
         *
         * @param string $key
         * @return void 
         * @static 
         */ 
        public static function offsetUnset($key)
        {            //Method inherited from \Illuminate\Container\Container         
                        /** @var \Illuminate\Foundation\Application $instance */
                        $instance->offsetUnset($key);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Contracts\Console\Kernel
     */ 
        class Artisan {
                    /**
         * Run the console application.
         *
         * @param \Symfony\Component\Console\Input\InputInterface $input
         * @param \Symfony\Component\Console\Output\OutputInterface|null $output
         * @return int 
         * @static 
         */ 
        public static function handle($input, $output = null)
        {            //Method inherited from \Illuminate\Foundation\Console\Kernel         
                        /** @var \Pterodactyl\Console\Kernel $instance */
                        return $instance->handle($input, $output);
        }
                    /**
         * Terminate the application.
         *
         * @param \Symfony\Component\Console\Input\InputInterface $input
         * @param int $status
         * @return void 
         * @static 
         */ 
        public static function terminate($input, $status)
        {            //Method inherited from \Illuminate\Foundation\Console\Kernel         
                        /** @var \Pterodactyl\Console\Kernel $instance */
                        $instance->terminate($input, $status);
        }
                    /**
         * Register a Closure based command with the application.
         *
         * @param string $signature
         * @param \Closure $callback
         * @return \Illuminate\Foundation\Console\ClosureCommand 
         * @static 
         */ 
        public static function command($signature, $callback)
        {            //Method inherited from \Illuminate\Foundation\Console\Kernel         
                        /** @var \Pterodactyl\Console\Kernel $instance */
                        return $instance->command($signature, $callback);
        }
                    /**
         * Register the given command with the console application.
         *
         * @param \Symfony\Component\Console\Command\Command $command
         * @return void 
         * @static 
         */ 
        public static function registerCommand($command)
        {            //Method inherited from \Illuminate\Foundation\Console\Kernel         
                        /** @var \Pterodactyl\Console\Kernel $instance */
                        $instance->registerCommand($command);
        }
                    /**
         * Run an Artisan console command by name.
         *
         * @param string $command
         * @param array $parameters
         * @param \Symfony\Component\Console\Output\OutputInterface|null $outputBuffer
         * @return int 
         * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
         * @static 
         */ 
        public static function call($command, $parameters = [], $outputBuffer = null)
        {            //Method inherited from \Illuminate\Foundation\Console\Kernel         
                        /** @var \Pterodactyl\Console\Kernel $instance */
                        return $instance->call($command, $parameters, $outputBuffer);
        }
                    /**
         * Queue the given console command.
         *
         * @param string $command
         * @param array $parameters
         * @return \Illuminate\Foundation\Bus\PendingDispatch 
         * @static 
         */ 
        public static function queue($command, $parameters = [])
        {            //Method inherited from \Illuminate\Foundation\Console\Kernel         
                        /** @var \Pterodactyl\Console\Kernel $instance */
                        return $instance->queue($command, $parameters);
        }
                    /**
         * Get all of the commands registered with the console.
         *
         * @return array 
         * @static 
         */ 
        public static function all()
        {            //Method inherited from \Illuminate\Foundation\Console\Kernel         
                        /** @var \Pterodactyl\Console\Kernel $instance */
                        return $instance->all();
        }
                    /**
         * Get the output for the last run command.
         *
         * @return string 
         * @static 
         */ 
        public static function output()
        {            //Method inherited from \Illuminate\Foundation\Console\Kernel         
                        /** @var \Pterodactyl\Console\Kernel $instance */
                        return $instance->output();
        }
                    /**
         * Bootstrap the application for artisan commands.
         *
         * @return void 
         * @static 
         */ 
        public static function bootstrap()
        {            //Method inherited from \Illuminate\Foundation\Console\Kernel         
                        /** @var \Pterodactyl\Console\Kernel $instance */
                        $instance->bootstrap();
        }
                    /**
         * Set the Artisan application instance.
         *
         * @param \Illuminate\Console\Application $artisan
         * @return void 
         * @static 
         */ 
        public static function setArtisan($artisan)
        {            //Method inherited from \Illuminate\Foundation\Console\Kernel         
                        /** @var \Pterodactyl\Console\Kernel $instance */
                        $instance->setArtisan($artisan);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Auth\AuthManager
     * @see \Illuminate\Contracts\Auth\Factory
     * @see \Illuminate\Contracts\Auth\Guard
     * @see \Illuminate\Contracts\Auth\StatefulGuard
     */ 
        class Auth {
                    /**
         * Attempt to get the guard from the local cache.
         *
         * @param string|null $name
         * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard 
         * @static 
         */ 
        public static function guard($name = null)
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->guard($name);
        }
                    /**
         * Create a session based authentication guard.
         *
         * @param string $name
         * @param array $config
         * @return \Illuminate\Auth\SessionGuard 
         * @static 
         */ 
        public static function createSessionDriver($name, $config)
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->createSessionDriver($name, $config);
        }
                    /**
         * Create a token based authentication guard.
         *
         * @param string $name
         * @param array $config
         * @return \Illuminate\Auth\TokenGuard 
         * @static 
         */ 
        public static function createTokenDriver($name, $config)
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->createTokenDriver($name, $config);
        }
                    /**
         * Get the default authentication driver name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultDriver()
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->getDefaultDriver();
        }
                    /**
         * Set the default guard driver the factory should serve.
         *
         * @param string $name
         * @return void 
         * @static 
         */ 
        public static function shouldUse($name)
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        $instance->shouldUse($name);
        }
                    /**
         * Set the default authentication driver name.
         *
         * @param string $name
         * @return void 
         * @static 
         */ 
        public static function setDefaultDriver($name)
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        $instance->setDefaultDriver($name);
        }
                    /**
         * Register a new callback based request guard.
         *
         * @param string $driver
         * @param callable $callback
         * @return \Illuminate\Auth\AuthManager 
         * @static 
         */ 
        public static function viaRequest($driver, $callback)
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->viaRequest($driver, $callback);
        }
                    /**
         * Get the user resolver callback.
         *
         * @return \Closure 
         * @static 
         */ 
        public static function userResolver()
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->userResolver();
        }
                    /**
         * Set the callback to be used to resolve users.
         *
         * @param \Closure $userResolver
         * @return \Illuminate\Auth\AuthManager 
         * @static 
         */ 
        public static function resolveUsersUsing($userResolver)
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->resolveUsersUsing($userResolver);
        }
                    /**
         * Register a custom driver creator Closure.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return \Illuminate\Auth\AuthManager 
         * @static 
         */ 
        public static function extend($driver, $callback)
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->extend($driver, $callback);
        }
                    /**
         * Register a custom provider creator Closure.
         *
         * @param string $name
         * @param \Closure $callback
         * @return \Illuminate\Auth\AuthManager 
         * @static 
         */ 
        public static function provider($name, $callback)
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->provider($name, $callback);
        }
                    /**
         * Determines if any guards have already been resolved.
         *
         * @return bool 
         * @static 
         */ 
        public static function hasResolvedGuards()
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->hasResolvedGuards();
        }
                    /**
         * Forget all of the resolved guard instances.
         *
         * @return \Illuminate\Auth\AuthManager 
         * @static 
         */ 
        public static function forgetGuards()
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->forgetGuards();
        }
                    /**
         * Set the application instance used by the manager.
         *
         * @param \Illuminate\Contracts\Foundation\Application $app
         * @return \Illuminate\Auth\AuthManager 
         * @static 
         */ 
        public static function setApplication($app)
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->setApplication($app);
        }
                    /**
         * Create the user provider implementation for the driver.
         *
         * @param string|null $provider
         * @return \Illuminate\Contracts\Auth\UserProvider|null 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function createUserProvider($provider = null)
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->createUserProvider($provider);
        }
                    /**
         * Get the default user provider name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultUserProvider()
        {
                        /** @var \Illuminate\Auth\AuthManager $instance */
                        return $instance->getDefaultUserProvider();
        }
                    /**
         * Get the currently authenticated user.
         *
         * @return \Pterodactyl\Models\User|null 
         * @static 
         */ 
        public static function user()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->user();
        }
                    /**
         * Get the ID for the currently authenticated user.
         *
         * @return int|string|null 
         * @static 
         */ 
        public static function id()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->id();
        }
                    /**
         * Log a user into the application without sessions or cookies.
         *
         * @param array $credentials
         * @return bool 
         * @static 
         */ 
        public static function once($credentials = [])
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->once($credentials);
        }
                    /**
         * Log the given user ID into the application without sessions or cookies.
         *
         * @param mixed $id
         * @return \Pterodactyl\Models\User|false 
         * @static 
         */ 
        public static function onceUsingId($id)
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->onceUsingId($id);
        }
                    /**
         * Validate a user's credentials.
         *
         * @param array $credentials
         * @return bool 
         * @static 
         */ 
        public static function validate($credentials = [])
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->validate($credentials);
        }
                    /**
         * Attempt to authenticate using HTTP Basic Auth.
         *
         * @param string $field
         * @param array $extraConditions
         * @return \Symfony\Component\HttpFoundation\Response|null 
         * @static 
         */ 
        public static function basic($field = 'email', $extraConditions = [])
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->basic($field, $extraConditions);
        }
                    /**
         * Perform a stateless HTTP Basic login attempt.
         *
         * @param string $field
         * @param array $extraConditions
         * @return \Symfony\Component\HttpFoundation\Response|null 
         * @static 
         */ 
        public static function onceBasic($field = 'email', $extraConditions = [])
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->onceBasic($field, $extraConditions);
        }
                    /**
         * Attempt to authenticate a user using the given credentials.
         *
         * @param array $credentials
         * @param bool $remember
         * @return bool 
         * @static 
         */ 
        public static function attempt($credentials = [], $remember = false)
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->attempt($credentials, $remember);
        }
                    /**
         * Attempt to authenticate a user with credentials and additional callbacks.
         *
         * @param array $credentials
         * @param array|callable $callbacks
         * @param false $remember
         * @return bool 
         * @static 
         */ 
        public static function attemptWhen($credentials = [], $callbacks = null, $remember = false)
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->attemptWhen($credentials, $callbacks, $remember);
        }
                    /**
         * Log the given user ID into the application.
         *
         * @param mixed $id
         * @param bool $remember
         * @return \Pterodactyl\Models\User|false 
         * @static 
         */ 
        public static function loginUsingId($id, $remember = false)
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->loginUsingId($id, $remember);
        }
                    /**
         * Log a user into the application.
         *
         * @param \Illuminate\Contracts\Auth\Authenticatable $user
         * @param bool $remember
         * @return void 
         * @static 
         */ 
        public static function login($user, $remember = false)
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        $instance->login($user, $remember);
        }
                    /**
         * Log the user out of the application.
         *
         * @return void 
         * @static 
         */ 
        public static function logout()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        $instance->logout();
        }
                    /**
         * Log the user out of the application on their current device only.
         * 
         * This method does not cycle the "remember" token.
         *
         * @return void 
         * @static 
         */ 
        public static function logoutCurrentDevice()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        $instance->logoutCurrentDevice();
        }
                    /**
         * Invalidate other sessions for the current user.
         * 
         * The application must be using the AuthenticateSession middleware.
         *
         * @param string $password
         * @param string $attribute
         * @return \Pterodactyl\Models\User|null 
         * @throws \Illuminate\Auth\AuthenticationException
         * @static 
         */ 
        public static function logoutOtherDevices($password, $attribute = 'password')
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->logoutOtherDevices($password, $attribute);
        }
                    /**
         * Register an authentication attempt event listener.
         *
         * @param mixed $callback
         * @return void 
         * @static 
         */ 
        public static function attempting($callback)
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        $instance->attempting($callback);
        }
                    /**
         * Get the last user we attempted to authenticate.
         *
         * @return \Pterodactyl\Models\User 
         * @static 
         */ 
        public static function getLastAttempted()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->getLastAttempted();
        }
                    /**
         * Get a unique identifier for the auth session value.
         *
         * @return string 
         * @static 
         */ 
        public static function getName()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->getName();
        }
                    /**
         * Get the name of the cookie used to store the "recaller".
         *
         * @return string 
         * @static 
         */ 
        public static function getRecallerName()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->getRecallerName();
        }
                    /**
         * Determine if the user was authenticated via "remember me" cookie.
         *
         * @return bool 
         * @static 
         */ 
        public static function viaRemember()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->viaRemember();
        }
                    /**
         * Get the cookie creator instance used by the guard.
         *
         * @return \Illuminate\Contracts\Cookie\QueueingFactory 
         * @throws \RuntimeException
         * @static 
         */ 
        public static function getCookieJar()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->getCookieJar();
        }
                    /**
         * Set the cookie creator instance used by the guard.
         *
         * @param \Illuminate\Contracts\Cookie\QueueingFactory $cookie
         * @return void 
         * @static 
         */ 
        public static function setCookieJar($cookie)
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        $instance->setCookieJar($cookie);
        }
                    /**
         * Get the event dispatcher instance.
         *
         * @return \Illuminate\Contracts\Events\Dispatcher 
         * @static 
         */ 
        public static function getDispatcher()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->getDispatcher();
        }
                    /**
         * Set the event dispatcher instance.
         *
         * @param \Illuminate\Contracts\Events\Dispatcher $events
         * @return void 
         * @static 
         */ 
        public static function setDispatcher($events)
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        $instance->setDispatcher($events);
        }
                    /**
         * Get the session store used by the guard.
         *
         * @return \Illuminate\Contracts\Session\Session 
         * @static 
         */ 
        public static function getSession()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->getSession();
        }
                    /**
         * Return the currently cached user.
         *
         * @return \Pterodactyl\Models\User|null 
         * @static 
         */ 
        public static function getUser()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->getUser();
        }
                    /**
         * Set the current user.
         *
         * @param \Illuminate\Contracts\Auth\Authenticatable $user
         * @return \Illuminate\Auth\SessionGuard 
         * @static 
         */ 
        public static function setUser($user)
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->setUser($user);
        }
                    /**
         * Get the current request instance.
         *
         * @return \Symfony\Component\HttpFoundation\Request 
         * @static 
         */ 
        public static function getRequest()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->getRequest();
        }
                    /**
         * Set the current request instance.
         *
         * @param \Symfony\Component\HttpFoundation\Request $request
         * @return \Illuminate\Auth\SessionGuard 
         * @static 
         */ 
        public static function setRequest($request)
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->setRequest($request);
        }
                    /**
         * Determine if the current user is authenticated. If not, throw an exception.
         *
         * @return \Pterodactyl\Models\User 
         * @throws \Illuminate\Auth\AuthenticationException
         * @static 
         */ 
        public static function authenticate()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->authenticate();
        }
                    /**
         * Determine if the guard has a user instance.
         *
         * @return bool 
         * @static 
         */ 
        public static function hasUser()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->hasUser();
        }
                    /**
         * Determine if the current user is authenticated.
         *
         * @return bool 
         * @static 
         */ 
        public static function check()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->check();
        }
                    /**
         * Determine if the current user is a guest.
         *
         * @return bool 
         * @static 
         */ 
        public static function guest()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->guest();
        }
                    /**
         * Get the user provider used by the guard.
         *
         * @return \Illuminate\Contracts\Auth\UserProvider 
         * @static 
         */ 
        public static function getProvider()
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        return $instance->getProvider();
        }
                    /**
         * Set the user provider used by the guard.
         *
         * @param \Illuminate\Contracts\Auth\UserProvider $provider
         * @return void 
         * @static 
         */ 
        public static function setProvider($provider)
        {
                        /** @var \Illuminate\Auth\SessionGuard $instance */
                        $instance->setProvider($provider);
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Auth\SessionGuard::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Auth\SessionGuard::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Auth\SessionGuard::hasMacro($name);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\View\Compilers\BladeCompiler
     */ 
        class Blade {
                    /**
         * Compile the view at the given path.
         *
         * @param string|null $path
         * @return void 
         * @static 
         */ 
        public static function compile($path = null)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->compile($path);
        }
                    /**
         * Get the path currently being compiled.
         *
         * @return string 
         * @static 
         */ 
        public static function getPath()
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->getPath();
        }
                    /**
         * Set the path currently being compiled.
         *
         * @param string $path
         * @return void 
         * @static 
         */ 
        public static function setPath($path)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->setPath($path);
        }
                    /**
         * Compile the given Blade template contents.
         *
         * @param string $value
         * @return string 
         * @static 
         */ 
        public static function compileString($value)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->compileString($value);
        }
                    /**
         * Strip the parentheses from the given expression.
         *
         * @param string $expression
         * @return string 
         * @static 
         */ 
        public static function stripParentheses($expression)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->stripParentheses($expression);
        }
                    /**
         * Register a custom Blade compiler.
         *
         * @param callable $compiler
         * @return void 
         * @static 
         */ 
        public static function extend($compiler)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->extend($compiler);
        }
                    /**
         * Get the extensions used by the compiler.
         *
         * @return array 
         * @static 
         */ 
        public static function getExtensions()
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->getExtensions();
        }
                    /**
         * Register an "if" statement directive.
         *
         * @param string $name
         * @param callable $callback
         * @return void 
         * @static 
         */ 
        public static function if($name, $callback)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->if($name, $callback);
        }
                    /**
         * Check the result of a condition.
         *
         * @param string $name
         * @param array $parameters
         * @return bool 
         * @static 
         */ 
        public static function check($name, ...$parameters)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->check($name, ...$parameters);
        }
                    /**
         * Register a class-based component alias directive.
         *
         * @param string $class
         * @param string|null $alias
         * @param string $prefix
         * @return void 
         * @static 
         */ 
        public static function component($class, $alias = null, $prefix = '')
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->component($class, $alias, $prefix);
        }
                    /**
         * Register an array of class-based components.
         *
         * @param array $components
         * @param string $prefix
         * @return void 
         * @static 
         */ 
        public static function components($components, $prefix = '')
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->components($components, $prefix);
        }
                    /**
         * Get the registered class component aliases.
         *
         * @return array 
         * @static 
         */ 
        public static function getClassComponentAliases()
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->getClassComponentAliases();
        }
                    /**
         * Register a class-based component namespace.
         *
         * @param string $namespace
         * @param string $prefix
         * @return void 
         * @static 
         */ 
        public static function componentNamespace($namespace, $prefix)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->componentNamespace($namespace, $prefix);
        }
                    /**
         * Get the registered class component namespaces.
         *
         * @return array 
         * @static 
         */ 
        public static function getClassComponentNamespaces()
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->getClassComponentNamespaces();
        }
                    /**
         * Register a component alias directive.
         *
         * @param string $path
         * @param string|null $alias
         * @return void 
         * @static 
         */ 
        public static function aliasComponent($path, $alias = null)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->aliasComponent($path, $alias);
        }
                    /**
         * Register an include alias directive.
         *
         * @param string $path
         * @param string|null $alias
         * @return void 
         * @static 
         */ 
        public static function include($path, $alias = null)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->include($path, $alias);
        }
                    /**
         * Register an include alias directive.
         *
         * @param string $path
         * @param string|null $alias
         * @return void 
         * @static 
         */ 
        public static function aliasInclude($path, $alias = null)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->aliasInclude($path, $alias);
        }
                    /**
         * Register a handler for custom directives.
         *
         * @param string $name
         * @param callable $handler
         * @return void 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function directive($name, $handler)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->directive($name, $handler);
        }
                    /**
         * Get the list of custom directives.
         *
         * @return array 
         * @static 
         */ 
        public static function getCustomDirectives()
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->getCustomDirectives();
        }
                    /**
         * Register a new precompiler.
         *
         * @param callable $precompiler
         * @return void 
         * @static 
         */ 
        public static function precompiler($precompiler)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->precompiler($precompiler);
        }
                    /**
         * Set the echo format to be used by the compiler.
         *
         * @param string $format
         * @return void 
         * @static 
         */ 
        public static function setEchoFormat($format)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->setEchoFormat($format);
        }
                    /**
         * Set the "echo" format to double encode entities.
         *
         * @return void 
         * @static 
         */ 
        public static function withDoubleEncoding()
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->withDoubleEncoding();
        }
                    /**
         * Set the "echo" format to not double encode entities.
         *
         * @return void 
         * @static 
         */ 
        public static function withoutDoubleEncoding()
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->withoutDoubleEncoding();
        }
                    /**
         * Indicate that component tags should not be compiled.
         *
         * @return void 
         * @static 
         */ 
        public static function withoutComponentTags()
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->withoutComponentTags();
        }
                    /**
         * Get the path to the compiled version of a view.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function getCompiledPath($path)
        {            //Method inherited from \Illuminate\View\Compilers\Compiler         
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->getCompiledPath($path);
        }
                    /**
         * Determine if the view at the given path is expired.
         *
         * @param string $path
         * @return bool 
         * @static 
         */ 
        public static function isExpired($path)
        {            //Method inherited from \Illuminate\View\Compilers\Compiler         
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->isExpired($path);
        }
                    /**
         * Get a new component hash for a component name.
         *
         * @param string $component
         * @return string 
         * @static 
         */ 
        public static function newComponentHash($component)
        {
                        return \Illuminate\View\Compilers\BladeCompiler::newComponentHash($component);
        }
                    /**
         * Compile a class component opening.
         *
         * @param string $component
         * @param string $alias
         * @param string $data
         * @param string $hash
         * @return string 
         * @static 
         */ 
        public static function compileClassComponentOpening($component, $alias, $data, $hash)
        {
                        return \Illuminate\View\Compilers\BladeCompiler::compileClassComponentOpening($component, $alias, $data, $hash);
        }
                    /**
         * Compile the end-component statements into valid PHP.
         *
         * @return string 
         * @static 
         */ 
        public static function compileEndComponentClass()
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->compileEndComponentClass();
        }
                    /**
         * Sanitize the given component attribute value.
         *
         * @param mixed $value
         * @return mixed 
         * @static 
         */ 
        public static function sanitizeComponentAttribute($value)
        {
                        return \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($value);
        }
                    /**
         * Compile an end-once block into valid PHP.
         *
         * @return string 
         * @static 
         */ 
        public static function compileEndOnce()
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->compileEndOnce();
        }
                    /**
         * Add a handler to be executed before echoing a given class.
         *
         * @param string|callable $class
         * @param callable|null $handler
         * @return void 
         * @static 
         */ 
        public static function stringable($class, $handler = null)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        $instance->stringable($class, $handler);
        }
                    /**
         * Compile Blade echos into valid PHP.
         *
         * @param string $value
         * @return string 
         * @static 
         */ 
        public static function compileEchos($value)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->compileEchos($value);
        }
                    /**
         * Apply the echo handler for the value if it exists.
         *
         * @param $value string
         * @return string 
         * @static 
         */ 
        public static function applyEchoHandler($value)
        {
                        /** @var \Illuminate\View\Compilers\BladeCompiler $instance */
                        return $instance->applyEchoHandler($value);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Contracts\Bus\Dispatcher
     */ 
        class Bus {
                    /**
         * Dispatch a command to its appropriate handler.
         *
         * @param mixed $command
         * @return mixed 
         * @static 
         */ 
        public static function dispatch($command)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        return $instance->dispatch($command);
        }
                    /**
         * Dispatch a command to its appropriate handler in the current process.
         * 
         * Queueable jobs will be dispatched to the "sync" queue.
         *
         * @param mixed $command
         * @param mixed $handler
         * @return mixed 
         * @static 
         */ 
        public static function dispatchSync($command, $handler = null)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        return $instance->dispatchSync($command, $handler);
        }
                    /**
         * Dispatch a command to its appropriate handler in the current process without using the synchronous queue.
         *
         * @param mixed $command
         * @param mixed $handler
         * @return mixed 
         * @static 
         */ 
        public static function dispatchNow($command, $handler = null)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        return $instance->dispatchNow($command, $handler);
        }
                    /**
         * Attempt to find the batch with the given ID.
         *
         * @param string $batchId
         * @return \Illuminate\Bus\Batch|null 
         * @static 
         */ 
        public static function findBatch($batchId)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        return $instance->findBatch($batchId);
        }
                    /**
         * Create a new batch of queueable jobs.
         *
         * @param \Illuminate\Support\Collection|array|mixed $jobs
         * @return \Illuminate\Bus\PendingBatch 
         * @static 
         */ 
        public static function batch($jobs)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        return $instance->batch($jobs);
        }
                    /**
         * Create a new chain of queueable jobs.
         *
         * @param \Illuminate\Support\Collection|array $jobs
         * @return \Illuminate\Foundation\Bus\PendingChain 
         * @static 
         */ 
        public static function chain($jobs)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        return $instance->chain($jobs);
        }
                    /**
         * Determine if the given command has a handler.
         *
         * @param mixed $command
         * @return bool 
         * @static 
         */ 
        public static function hasCommandHandler($command)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        return $instance->hasCommandHandler($command);
        }
                    /**
         * Retrieve the handler for a command.
         *
         * @param mixed $command
         * @return bool|mixed 
         * @static 
         */ 
        public static function getCommandHandler($command)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        return $instance->getCommandHandler($command);
        }
                    /**
         * Dispatch a command to its appropriate handler behind a queue.
         *
         * @param mixed $command
         * @return mixed 
         * @throws \RuntimeException
         * @static 
         */ 
        public static function dispatchToQueue($command)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        return $instance->dispatchToQueue($command);
        }
                    /**
         * Dispatch a command to its appropriate handler after the current process.
         *
         * @param mixed $command
         * @param mixed $handler
         * @return void 
         * @static 
         */ 
        public static function dispatchAfterResponse($command, $handler = null)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        $instance->dispatchAfterResponse($command, $handler);
        }
                    /**
         * Set the pipes through which commands should be piped before dispatching.
         *
         * @param array $pipes
         * @return \Illuminate\Bus\Dispatcher 
         * @static 
         */ 
        public static function pipeThrough($pipes)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        return $instance->pipeThrough($pipes);
        }
                    /**
         * Map a command to a handler.
         *
         * @param array $map
         * @return \Illuminate\Bus\Dispatcher 
         * @static 
         */ 
        public static function map($map)
        {
                        /** @var \Illuminate\Bus\Dispatcher $instance */
                        return $instance->map($map);
        }
                    /**
         * Assert if a job was dispatched based on a truth-test callback.
         *
         * @param string|\Closure $command
         * @param callable|int|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertDispatched($command, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertDispatched($command, $callback);
        }
                    /**
         * Assert if a job was pushed a number of times.
         *
         * @param string $command
         * @param int $times
         * @return void 
         * @static 
         */ 
        public static function assertDispatchedTimes($command, $times = 1)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertDispatchedTimes($command, $times);
        }
                    /**
         * Determine if a job was dispatched based on a truth-test callback.
         *
         * @param string|\Closure $command
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertNotDispatched($command, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertNotDispatched($command, $callback);
        }
                    /**
         * Assert if a job was explicitly dispatched synchronously based on a truth-test callback.
         *
         * @param string|\Closure $command
         * @param callable|int|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertDispatchedSync($command, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertDispatchedSync($command, $callback);
        }
                    /**
         * Assert if a job was pushed synchronously a number of times.
         *
         * @param string $command
         * @param int $times
         * @return void 
         * @static 
         */ 
        public static function assertDispatchedSyncTimes($command, $times = 1)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertDispatchedSyncTimes($command, $times);
        }
                    /**
         * Determine if a job was dispatched based on a truth-test callback.
         *
         * @param string|\Closure $command
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertNotDispatchedSync($command, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertNotDispatchedSync($command, $callback);
        }
                    /**
         * Assert if a job was dispatched after the response was sent based on a truth-test callback.
         *
         * @param string|\Closure $command
         * @param callable|int|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertDispatchedAfterResponse($command, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertDispatchedAfterResponse($command, $callback);
        }
                    /**
         * Assert if a job was pushed after the response was sent a number of times.
         *
         * @param string $command
         * @param int $times
         * @return void 
         * @static 
         */ 
        public static function assertDispatchedAfterResponseTimes($command, $times = 1)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertDispatchedAfterResponseTimes($command, $times);
        }
                    /**
         * Determine if a job was dispatched based on a truth-test callback.
         *
         * @param string|\Closure $command
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertNotDispatchedAfterResponse($command, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertNotDispatchedAfterResponse($command, $callback);
        }
                    /**
         * Assert if a chain of jobs was dispatched.
         *
         * @param array $expectedChain
         * @return void 
         * @static 
         */ 
        public static function assertChained($expectedChain)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertChained($expectedChain);
        }
                    /**
         * Assert if a job was dispatched with an empty chain based on a truth-test callback.
         *
         * @param string|\Closure $command
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertDispatchedWithoutChain($command, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertDispatchedWithoutChain($command, $callback);
        }
                    /**
         * Assert if a batch was dispatched based on a truth-test callback.
         *
         * @param callable $callback
         * @return void 
         * @static 
         */ 
        public static function assertBatched($callback)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        $instance->assertBatched($callback);
        }
                    /**
         * Get all of the jobs matching a truth-test callback.
         *
         * @param string $command
         * @param callable|null $callback
         * @return \Illuminate\Support\Collection 
         * @static 
         */ 
        public static function dispatched($command, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        return $instance->dispatched($command, $callback);
        }
                    /**
         * Get all of the jobs dispatched synchronously matching a truth-test callback.
         *
         * @param string $command
         * @param callable|null $callback
         * @return \Illuminate\Support\Collection 
         * @static 
         */ 
        public static function dispatchedSync($command, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        return $instance->dispatchedSync($command, $callback);
        }
                    /**
         * Get all of the jobs dispatched after the response was sent matching a truth-test callback.
         *
         * @param string $command
         * @param callable|null $callback
         * @return \Illuminate\Support\Collection 
         * @static 
         */ 
        public static function dispatchedAfterResponse($command, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        return $instance->dispatchedAfterResponse($command, $callback);
        }
                    /**
         * Get all of the pending batches matching a truth-test callback.
         *
         * @param callable $callback
         * @return \Illuminate\Support\Collection 
         * @static 
         */ 
        public static function batched($callback)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        return $instance->batched($callback);
        }
                    /**
         * Determine if there are any stored commands for a given class.
         *
         * @param string $command
         * @return bool 
         * @static 
         */ 
        public static function hasDispatched($command)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        return $instance->hasDispatched($command);
        }
                    /**
         * Determine if there are any stored commands for a given class.
         *
         * @param string $command
         * @return bool 
         * @static 
         */ 
        public static function hasDispatchedSync($command)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        return $instance->hasDispatchedSync($command);
        }
                    /**
         * Determine if there are any stored commands for a given class.
         *
         * @param string $command
         * @return bool 
         * @static 
         */ 
        public static function hasDispatchedAfterResponse($command)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        return $instance->hasDispatchedAfterResponse($command);
        }
                    /**
         * Record the fake pending batch dispatch.
         *
         * @param \Illuminate\Bus\PendingBatch $pendingBatch
         * @return \Illuminate\Bus\Batch 
         * @static 
         */ 
        public static function recordPendingBatch($pendingBatch)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\BusFake $instance */
                        return $instance->recordPendingBatch($pendingBatch);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Cache\CacheManager
     * @see \Illuminate\Cache\Repository
     */ 
        class Cache {
                    /**
         * Get a cache store instance by name, wrapped in a repository.
         *
         * @param string|null $name
         * @return \Illuminate\Contracts\Cache\Repository 
         * @static 
         */ 
        public static function store($name = null)
        {
                        /** @var \Illuminate\Cache\CacheManager $instance */
                        return $instance->store($name);
        }
                    /**
         * Get a cache driver instance.
         *
         * @param string|null $driver
         * @return \Illuminate\Contracts\Cache\Repository 
         * @static 
         */ 
        public static function driver($driver = null)
        {
                        /** @var \Illuminate\Cache\CacheManager $instance */
                        return $instance->driver($driver);
        }
                    /**
         * Create a new cache repository with the given implementation.
         *
         * @param \Illuminate\Contracts\Cache\Store $store
         * @return \Illuminate\Cache\Repository 
         * @static 
         */ 
        public static function repository($store)
        {
                        /** @var \Illuminate\Cache\CacheManager $instance */
                        return $instance->repository($store);
        }
                    /**
         * Re-set the event dispatcher on all resolved cache repositories.
         *
         * @return void 
         * @static 
         */ 
        public static function refreshEventDispatcher()
        {
                        /** @var \Illuminate\Cache\CacheManager $instance */
                        $instance->refreshEventDispatcher();
        }
                    /**
         * Get the default cache driver name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultDriver()
        {
                        /** @var \Illuminate\Cache\CacheManager $instance */
                        return $instance->getDefaultDriver();
        }
                    /**
         * Set the default cache driver name.
         *
         * @param string $name
         * @return void 
         * @static 
         */ 
        public static function setDefaultDriver($name)
        {
                        /** @var \Illuminate\Cache\CacheManager $instance */
                        $instance->setDefaultDriver($name);
        }
                    /**
         * Unset the given driver instances.
         *
         * @param array|string|null $name
         * @return \Illuminate\Cache\CacheManager 
         * @static 
         */ 
        public static function forgetDriver($name = null)
        {
                        /** @var \Illuminate\Cache\CacheManager $instance */
                        return $instance->forgetDriver($name);
        }
                    /**
         * Disconnect the given driver and remove from local cache.
         *
         * @param string|null $name
         * @return void 
         * @static 
         */ 
        public static function purge($name = null)
        {
                        /** @var \Illuminate\Cache\CacheManager $instance */
                        $instance->purge($name);
        }
                    /**
         * Register a custom driver creator Closure.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return \Illuminate\Cache\CacheManager 
         * @static 
         */ 
        public static function extend($driver, $callback)
        {
                        /** @var \Illuminate\Cache\CacheManager $instance */
                        return $instance->extend($driver, $callback);
        }
                    /**
         * Determine if an item exists in the cache.
         *
         * @param string $key
         * @return bool 
         * @static 
         */ 
        public static function has($key)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->has($key);
        }
                    /**
         * Determine if an item doesn't exist in the cache.
         *
         * @param string $key
         * @return bool 
         * @static 
         */ 
        public static function missing($key)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->missing($key);
        }
                    /**
         * Retrieve an item from the cache by key.
         *
         * @param string $key
         * @param mixed $default
         * @return mixed 
         * @static 
         */ 
        public static function get($key, $default = null)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->get($key, $default);
        }
                    /**
         * Retrieve multiple items from the cache by key.
         * 
         * Items not found in the cache will have a null value.
         *
         * @param array $keys
         * @return array 
         * @static 
         */ 
        public static function many($keys)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->many($keys);
        }
                    /**
         * Obtains multiple cache items by their unique keys.
         *
         * @param \Psr\SimpleCache\iterable $keys A list of keys that can obtained in a single operation.
         * @param mixed $default Default value to return for keys that do not exist.
         * @return \Psr\SimpleCache\iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
         * @throws \Psr\SimpleCache\InvalidArgumentException
         *   MUST be thrown if $keys is neither an array nor a Traversable,
         *   or if any of the $keys are not a legal value.
         * @static 
         */ 
        public static function getMultiple($keys, $default = null)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->getMultiple($keys, $default);
        }
                    /**
         * Retrieve an item from the cache and delete it.
         *
         * @param string $key
         * @param mixed $default
         * @return mixed 
         * @static 
         */ 
        public static function pull($key, $default = null)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->pull($key, $default);
        }
                    /**
         * Store an item in the cache.
         *
         * @param string $key
         * @param mixed $value
         * @param \DateTimeInterface|\DateInterval|int|null $ttl
         * @return bool 
         * @static 
         */ 
        public static function put($key, $value, $ttl = null)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->put($key, $value, $ttl);
        }
                    /**
         * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
         *
         * @param string $key The key of the item to store.
         * @param mixed $value The value of the item to store, must be serializable.
         * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
         *                                      the driver supports TTL then the library may set a default value
         *                                      for it or let the driver take care of that.
         * @return bool True on success and false on failure.
         * @throws \Psr\SimpleCache\InvalidArgumentException
         *   MUST be thrown if the $key string is not a legal value.
         * @static 
         */ 
        public static function set($key, $value, $ttl = null)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->set($key, $value, $ttl);
        }
                    /**
         * Store multiple items in the cache for a given number of seconds.
         *
         * @param array $values
         * @param \DateTimeInterface|\DateInterval|int|null $ttl
         * @return bool 
         * @static 
         */ 
        public static function putMany($values, $ttl = null)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->putMany($values, $ttl);
        }
                    /**
         * Persists a set of key => value pairs in the cache, with an optional TTL.
         *
         * @param \Psr\SimpleCache\iterable $values A list of key => value pairs for a multiple-set operation.
         * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
         *                                       the driver supports TTL then the library may set a default value
         *                                       for it or let the driver take care of that.
         * @return bool True on success and false on failure.
         * @throws \Psr\SimpleCache\InvalidArgumentException
         *   MUST be thrown if $values is neither an array nor a Traversable,
         *   or if any of the $values are not a legal value.
         * @static 
         */ 
        public static function setMultiple($values, $ttl = null)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->setMultiple($values, $ttl);
        }
                    /**
         * Store an item in the cache if the key does not exist.
         *
         * @param string $key
         * @param mixed $value
         * @param \DateTimeInterface|\DateInterval|int|null $ttl
         * @return bool 
         * @static 
         */ 
        public static function add($key, $value, $ttl = null)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->add($key, $value, $ttl);
        }
                    /**
         * Increment the value of an item in the cache.
         *
         * @param string $key
         * @param mixed $value
         * @return int|bool 
         * @static 
         */ 
        public static function increment($key, $value = 1)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->increment($key, $value);
        }
                    /**
         * Decrement the value of an item in the cache.
         *
         * @param string $key
         * @param mixed $value
         * @return int|bool 
         * @static 
         */ 
        public static function decrement($key, $value = 1)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->decrement($key, $value);
        }
                    /**
         * Store an item in the cache indefinitely.
         *
         * @param string $key
         * @param mixed $value
         * @return bool 
         * @static 
         */ 
        public static function forever($key, $value)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->forever($key, $value);
        }
                    /**
         * Get an item from the cache, or execute the given Closure and store the result.
         *
         * @param string $key
         * @param \DateTimeInterface|\DateInterval|int|null $ttl
         * @param \Closure $callback
         * @return mixed 
         * @static 
         */ 
        public static function remember($key, $ttl, $callback)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->remember($key, $ttl, $callback);
        }
                    /**
         * Get an item from the cache, or execute the given Closure and store the result forever.
         *
         * @param string $key
         * @param \Closure $callback
         * @return mixed 
         * @static 
         */ 
        public static function sear($key, $callback)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->sear($key, $callback);
        }
                    /**
         * Get an item from the cache, or execute the given Closure and store the result forever.
         *
         * @param string $key
         * @param \Closure $callback
         * @return mixed 
         * @static 
         */ 
        public static function rememberForever($key, $callback)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->rememberForever($key, $callback);
        }
                    /**
         * Remove an item from the cache.
         *
         * @param string $key
         * @return bool 
         * @static 
         */ 
        public static function forget($key)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->forget($key);
        }
                    /**
         * Delete an item from the cache by its unique key.
         *
         * @param string $key The unique cache key of the item to delete.
         * @return bool True if the item was successfully removed. False if there was an error.
         * @throws \Psr\SimpleCache\InvalidArgumentException
         *   MUST be thrown if the $key string is not a legal value.
         * @static 
         */ 
        public static function delete($key)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->delete($key);
        }
                    /**
         * Deletes multiple cache items in a single operation.
         *
         * @param \Psr\SimpleCache\iterable $keys A list of string-based keys to be deleted.
         * @return bool True if the items were successfully removed. False if there was an error.
         * @throws \Psr\SimpleCache\InvalidArgumentException
         *   MUST be thrown if $keys is neither an array nor a Traversable,
         *   or if any of the $keys are not a legal value.
         * @static 
         */ 
        public static function deleteMultiple($keys)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->deleteMultiple($keys);
        }
                    /**
         * Wipes clean the entire cache's keys.
         *
         * @return bool True on success and false on failure.
         * @static 
         */ 
        public static function clear()
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->clear();
        }
                    /**
         * Begin executing a new tags operation if the store supports it.
         *
         * @param array|mixed $names
         * @return \Illuminate\Cache\TaggedCache 
         * @throws \BadMethodCallException
         * @static 
         */ 
        public static function tags($names)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->tags($names);
        }
                    /**
         * Determine if the current store supports tags.
         *
         * @return bool 
         * @static 
         */ 
        public static function supportsTags()
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->supportsTags();
        }
                    /**
         * Get the default cache time.
         *
         * @return int|null 
         * @static 
         */ 
        public static function getDefaultCacheTime()
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->getDefaultCacheTime();
        }
                    /**
         * Set the default cache time in seconds.
         *
         * @param int|null $seconds
         * @return \Illuminate\Cache\Repository 
         * @static 
         */ 
        public static function setDefaultCacheTime($seconds)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->setDefaultCacheTime($seconds);
        }
                    /**
         * Get the cache store implementation.
         *
         * @return \Illuminate\Contracts\Cache\Store 
         * @static 
         */ 
        public static function getStore()
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->getStore();
        }
                    /**
         * Get the event dispatcher instance.
         *
         * @return \Illuminate\Contracts\Events\Dispatcher 
         * @static 
         */ 
        public static function getEventDispatcher()
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->getEventDispatcher();
        }
                    /**
         * Set the event dispatcher instance.
         *
         * @param \Illuminate\Contracts\Events\Dispatcher $events
         * @return void 
         * @static 
         */ 
        public static function setEventDispatcher($events)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        $instance->setEventDispatcher($events);
        }
                    /**
         * Determine if a cached value exists.
         *
         * @param string $key
         * @return bool 
         * @static 
         */ 
        public static function offsetExists($key)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->offsetExists($key);
        }
                    /**
         * Retrieve an item from the cache by key.
         *
         * @param string $key
         * @return mixed 
         * @static 
         */ 
        public static function offsetGet($key)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->offsetGet($key);
        }
                    /**
         * Store an item in the cache for the default time.
         *
         * @param string $key
         * @param mixed $value
         * @return void 
         * @static 
         */ 
        public static function offsetSet($key, $value)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        $instance->offsetSet($key, $value);
        }
                    /**
         * Remove an item from the cache.
         *
         * @param string $key
         * @return void 
         * @static 
         */ 
        public static function offsetUnset($key)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        $instance->offsetUnset($key);
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Cache\Repository::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Cache\Repository::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Cache\Repository::hasMacro($name);
        }
                    /**
         * Dynamically handle calls to the class.
         *
         * @param string $method
         * @param array $parameters
         * @return mixed 
         * @throws \BadMethodCallException
         * @static 
         */ 
        public static function macroCall($method, $parameters)
        {
                        /** @var \Illuminate\Cache\Repository $instance */
                        return $instance->macroCall($method, $parameters);
        }
                    /**
         * Get a lock instance.
         *
         * @param string $name
         * @param int $seconds
         * @param string|null $owner
         * @return \Illuminate\Contracts\Cache\Lock 
         * @static 
         */ 
        public static function lock($name, $seconds = 0, $owner = null)
        {
                        /** @var \Illuminate\Cache\RedisStore $instance */
                        return $instance->lock($name, $seconds, $owner);
        }
                    /**
         * Restore a lock instance using the owner identifier.
         *
         * @param string $name
         * @param string $owner
         * @return \Illuminate\Contracts\Cache\Lock 
         * @static 
         */ 
        public static function restoreLock($name, $owner)
        {
                        /** @var \Illuminate\Cache\RedisStore $instance */
                        return $instance->restoreLock($name, $owner);
        }
                    /**
         * Remove all items from the cache.
         *
         * @return bool 
         * @static 
         */ 
        public static function flush()
        {
                        /** @var \Illuminate\Cache\RedisStore $instance */
                        return $instance->flush();
        }
                    /**
         * Get the Redis connection instance.
         *
         * @return \Illuminate\Redis\Connections\Connection 
         * @static 
         */ 
        public static function connection()
        {
                        /** @var \Illuminate\Cache\RedisStore $instance */
                        return $instance->connection();
        }
                    /**
         * Get the Redis connection instance that should be used to manage locks.
         *
         * @return \Illuminate\Redis\Connections\Connection 
         * @static 
         */ 
        public static function lockConnection()
        {
                        /** @var \Illuminate\Cache\RedisStore $instance */
                        return $instance->lockConnection();
        }
                    /**
         * Specify the name of the connection that should be used to store data.
         *
         * @param string $connection
         * @return void 
         * @static 
         */ 
        public static function setConnection($connection)
        {
                        /** @var \Illuminate\Cache\RedisStore $instance */
                        $instance->setConnection($connection);
        }
                    /**
         * Specify the name of the connection that should be used to manage locks.
         *
         * @param string $connection
         * @return \Illuminate\Cache\RedisStore 
         * @static 
         */ 
        public static function setLockConnection($connection)
        {
                        /** @var \Illuminate\Cache\RedisStore $instance */
                        return $instance->setLockConnection($connection);
        }
                    /**
         * Get the Redis database instance.
         *
         * @return \Illuminate\Contracts\Redis\Factory 
         * @static 
         */ 
        public static function getRedis()
        {
                        /** @var \Illuminate\Cache\RedisStore $instance */
                        return $instance->getRedis();
        }
                    /**
         * Get the cache key prefix.
         *
         * @return string 
         * @static 
         */ 
        public static function getPrefix()
        {
                        /** @var \Illuminate\Cache\RedisStore $instance */
                        return $instance->getPrefix();
        }
                    /**
         * Set the cache key prefix.
         *
         * @param string $prefix
         * @return void 
         * @static 
         */ 
        public static function setPrefix($prefix)
        {
                        /** @var \Illuminate\Cache\RedisStore $instance */
                        $instance->setPrefix($prefix);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Config\Repository
     */ 
        class Config {
                    /**
         * Determine if the given configuration value exists.
         *
         * @param string $key
         * @return bool 
         * @static 
         */ 
        public static function has($key)
        {
                        /** @var \Illuminate\Config\Repository $instance */
                        return $instance->has($key);
        }
                    /**
         * Get the specified configuration value.
         *
         * @param array|string $key
         * @param mixed $default
         * @return mixed 
         * @static 
         */ 
        public static function get($key, $default = null)
        {
                        /** @var \Illuminate\Config\Repository $instance */
                        return $instance->get($key, $default);
        }
                    /**
         * Get many configuration values.
         *
         * @param array $keys
         * @return array 
         * @static 
         */ 
        public static function getMany($keys)
        {
                        /** @var \Illuminate\Config\Repository $instance */
                        return $instance->getMany($keys);
        }
                    /**
         * Set a given configuration value.
         *
         * @param array|string $key
         * @param mixed $value
         * @return void 
         * @static 
         */ 
        public static function set($key, $value = null)
        {
                        /** @var \Illuminate\Config\Repository $instance */
                        $instance->set($key, $value);
        }
                    /**
         * Prepend a value onto an array configuration value.
         *
         * @param string $key
         * @param mixed $value
         * @return void 
         * @static 
         */ 
        public static function prepend($key, $value)
        {
                        /** @var \Illuminate\Config\Repository $instance */
                        $instance->prepend($key, $value);
        }
                    /**
         * Push a value onto an array configuration value.
         *
         * @param string $key
         * @param mixed $value
         * @return void 
         * @static 
         */ 
        public static function push($key, $value)
        {
                        /** @var \Illuminate\Config\Repository $instance */
                        $instance->push($key, $value);
        }
                    /**
         * Get all of the configuration items for the application.
         *
         * @return array 
         * @static 
         */ 
        public static function all()
        {
                        /** @var \Illuminate\Config\Repository $instance */
                        return $instance->all();
        }
                    /**
         * Determine if the given configuration option exists.
         *
         * @param string $key
         * @return bool 
         * @static 
         */ 
        public static function offsetExists($key)
        {
                        /** @var \Illuminate\Config\Repository $instance */
                        return $instance->offsetExists($key);
        }
                    /**
         * Get a configuration option.
         *
         * @param string $key
         * @return mixed 
         * @static 
         */ 
        public static function offsetGet($key)
        {
                        /** @var \Illuminate\Config\Repository $instance */
                        return $instance->offsetGet($key);
        }
                    /**
         * Set a configuration option.
         *
         * @param string $key
         * @param mixed $value
         * @return void 
         * @static 
         */ 
        public static function offsetSet($key, $value)
        {
                        /** @var \Illuminate\Config\Repository $instance */
                        $instance->offsetSet($key, $value);
        }
                    /**
         * Unset a configuration option.
         *
         * @param string $key
         * @return void 
         * @static 
         */ 
        public static function offsetUnset($key)
        {
                        /** @var \Illuminate\Config\Repository $instance */
                        $instance->offsetUnset($key);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Cookie\CookieJar
     */ 
        class Cookie {
                    /**
         * Create a new cookie instance.
         *
         * @param string $name
         * @param string $value
         * @param int $minutes
         * @param string|null $path
         * @param string|null $domain
         * @param bool|null $secure
         * @param bool $httpOnly
         * @param bool $raw
         * @param string|null $sameSite
         * @return \Symfony\Component\HttpFoundation\Cookie 
         * @static 
         */ 
        public static function make($name, $value, $minutes = 0, $path = null, $domain = null, $secure = null, $httpOnly = true, $raw = false, $sameSite = null)
        {
                        /** @var \Illuminate\Cookie\CookieJar $instance */
                        return $instance->make($name, $value, $minutes, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
        }
                    /**
         * Create a cookie that lasts "forever" (five years).
         *
         * @param string $name
         * @param string $value
         * @param string|null $path
         * @param string|null $domain
         * @param bool|null $secure
         * @param bool $httpOnly
         * @param bool $raw
         * @param string|null $sameSite
         * @return \Symfony\Component\HttpFoundation\Cookie 
         * @static 
         */ 
        public static function forever($name, $value, $path = null, $domain = null, $secure = null, $httpOnly = true, $raw = false, $sameSite = null)
        {
                        /** @var \Illuminate\Cookie\CookieJar $instance */
                        return $instance->forever($name, $value, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
        }
                    /**
         * Expire the given cookie.
         *
         * @param string $name
         * @param string|null $path
         * @param string|null $domain
         * @return \Symfony\Component\HttpFoundation\Cookie 
         * @static 
         */ 
        public static function forget($name, $path = null, $domain = null)
        {
                        /** @var \Illuminate\Cookie\CookieJar $instance */
                        return $instance->forget($name, $path, $domain);
        }
                    /**
         * Determine if a cookie has been queued.
         *
         * @param string $key
         * @param string|null $path
         * @return bool 
         * @static 
         */ 
        public static function hasQueued($key, $path = null)
        {
                        /** @var \Illuminate\Cookie\CookieJar $instance */
                        return $instance->hasQueued($key, $path);
        }
                    /**
         * Get a queued cookie instance.
         *
         * @param string $key
         * @param mixed $default
         * @param string|null $path
         * @return \Symfony\Component\HttpFoundation\Cookie|null 
         * @static 
         */ 
        public static function queued($key, $default = null, $path = null)
        {
                        /** @var \Illuminate\Cookie\CookieJar $instance */
                        return $instance->queued($key, $default, $path);
        }
                    /**
         * Queue a cookie to send with the next response.
         *
         * @param array $parameters
         * @return void 
         * @static 
         */ 
        public static function queue(...$parameters)
        {
                        /** @var \Illuminate\Cookie\CookieJar $instance */
                        $instance->queue(...$parameters);
        }
                    /**
         * Queue a cookie to expire with the next response.
         *
         * @param string $name
         * @param string|null $path
         * @param string|null $domain
         * @return void 
         * @static 
         */ 
        public static function expire($name, $path = null, $domain = null)
        {
                        /** @var \Illuminate\Cookie\CookieJar $instance */
                        $instance->expire($name, $path, $domain);
        }
                    /**
         * Remove a cookie from the queue.
         *
         * @param string $name
         * @param string|null $path
         * @return void 
         * @static 
         */ 
        public static function unqueue($name, $path = null)
        {
                        /** @var \Illuminate\Cookie\CookieJar $instance */
                        $instance->unqueue($name, $path);
        }
                    /**
         * Set the default path and domain for the jar.
         *
         * @param string $path
         * @param string $domain
         * @param bool $secure
         * @param string|null $sameSite
         * @return \Illuminate\Cookie\CookieJar 
         * @static 
         */ 
        public static function setDefaultPathAndDomain($path, $domain, $secure = false, $sameSite = null)
        {
                        /** @var \Illuminate\Cookie\CookieJar $instance */
                        return $instance->setDefaultPathAndDomain($path, $domain, $secure, $sameSite);
        }
                    /**
         * Get the cookies which have been queued for the next request.
         *
         * @return \Symfony\Component\HttpFoundation\Cookie[] 
         * @static 
         */ 
        public static function getQueuedCookies()
        {
                        /** @var \Illuminate\Cookie\CookieJar $instance */
                        return $instance->getQueuedCookies();
        }
                    /**
         * Flush the cookies which have been queued for the next request.
         *
         * @return \Illuminate\Cookie\CookieJar 
         * @static 
         */ 
        public static function flushQueuedCookies()
        {
                        /** @var \Illuminate\Cookie\CookieJar $instance */
                        return $instance->flushQueuedCookies();
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Cookie\CookieJar::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Cookie\CookieJar::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Cookie\CookieJar::hasMacro($name);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Encryption\Encrypter
     */ 
        class Crypt {
                    /**
         * Determine if the given key and cipher combination is valid.
         *
         * @param string $key
         * @param string $cipher
         * @return bool 
         * @static 
         */ 
        public static function supported($key, $cipher)
        {
                        return \Illuminate\Encryption\Encrypter::supported($key, $cipher);
        }
                    /**
         * Create a new encryption key for the given cipher.
         *
         * @param string $cipher
         * @return string 
         * @static 
         */ 
        public static function generateKey($cipher)
        {
                        return \Illuminate\Encryption\Encrypter::generateKey($cipher);
        }
                    /**
         * Encrypt the given value.
         *
         * @param mixed $value
         * @param bool $serialize
         * @return string 
         * @throws \Illuminate\Contracts\Encryption\EncryptException
         * @static 
         */ 
        public static function encrypt($value, $serialize = true)
        {
                        /** @var \Illuminate\Encryption\Encrypter $instance */
                        return $instance->encrypt($value, $serialize);
        }
                    /**
         * Encrypt a string without serialization.
         *
         * @param string $value
         * @return string 
         * @throws \Illuminate\Contracts\Encryption\EncryptException
         * @static 
         */ 
        public static function encryptString($value)
        {
                        /** @var \Illuminate\Encryption\Encrypter $instance */
                        return $instance->encryptString($value);
        }
                    /**
         * Decrypt the given value.
         *
         * @param string $payload
         * @param bool $unserialize
         * @return mixed 
         * @throws \Illuminate\Contracts\Encryption\DecryptException
         * @static 
         */ 
        public static function decrypt($payload, $unserialize = true)
        {
                        /** @var \Illuminate\Encryption\Encrypter $instance */
                        return $instance->decrypt($payload, $unserialize);
        }
                    /**
         * Decrypt the given string without unserialization.
         *
         * @param string $payload
         * @return string 
         * @throws \Illuminate\Contracts\Encryption\DecryptException
         * @static 
         */ 
        public static function decryptString($payload)
        {
                        /** @var \Illuminate\Encryption\Encrypter $instance */
                        return $instance->decryptString($payload);
        }
                    /**
         * Get the encryption key.
         *
         * @return string 
         * @static 
         */ 
        public static function getKey()
        {
                        /** @var \Illuminate\Encryption\Encrypter $instance */
                        return $instance->getKey();
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Database\DatabaseManager
     * @see \Illuminate\Database\Connection
     */ 
        class DB {
                    /**
         * Get a database connection instance.
         *
         * @param string|null $name
         * @return \Illuminate\Database\Connection 
         * @static 
         */ 
        public static function connection($name = null)
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        return $instance->connection($name);
        }
                    /**
         * Disconnect from the given database and remove from local cache.
         *
         * @param string|null $name
         * @return void 
         * @static 
         */ 
        public static function purge($name = null)
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        $instance->purge($name);
        }
                    /**
         * Disconnect from the given database.
         *
         * @param string|null $name
         * @return void 
         * @static 
         */ 
        public static function disconnect($name = null)
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        $instance->disconnect($name);
        }
                    /**
         * Reconnect to the given database.
         *
         * @param string|null $name
         * @return \Illuminate\Database\Connection 
         * @static 
         */ 
        public static function reconnect($name = null)
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        return $instance->reconnect($name);
        }
                    /**
         * Set the default database connection for the callback execution.
         *
         * @param string $name
         * @param callable $callback
         * @return mixed 
         * @static 
         */ 
        public static function usingConnection($name, $callback)
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        return $instance->usingConnection($name, $callback);
        }
                    /**
         * Get the default connection name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultConnection()
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        return $instance->getDefaultConnection();
        }
                    /**
         * Set the default connection name.
         *
         * @param string $name
         * @return void 
         * @static 
         */ 
        public static function setDefaultConnection($name)
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        $instance->setDefaultConnection($name);
        }
                    /**
         * Get all of the support drivers.
         *
         * @return array 
         * @static 
         */ 
        public static function supportedDrivers()
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        return $instance->supportedDrivers();
        }
                    /**
         * Get all of the drivers that are actually available.
         *
         * @return array 
         * @static 
         */ 
        public static function availableDrivers()
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        return $instance->availableDrivers();
        }
                    /**
         * Register an extension connection resolver.
         *
         * @param string $name
         * @param callable $resolver
         * @return void 
         * @static 
         */ 
        public static function extend($name, $resolver)
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        $instance->extend($name, $resolver);
        }
                    /**
         * Return all of the created connections.
         *
         * @return array 
         * @static 
         */ 
        public static function getConnections()
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        return $instance->getConnections();
        }
                    /**
         * Set the database reconnector callback.
         *
         * @param callable $reconnector
         * @return void 
         * @static 
         */ 
        public static function setReconnector($reconnector)
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        $instance->setReconnector($reconnector);
        }
                    /**
         * Set the application instance used by the manager.
         *
         * @param \Illuminate\Contracts\Foundation\Application $app
         * @return \Illuminate\Database\DatabaseManager 
         * @static 
         */ 
        public static function setApplication($app)
        {
                        /** @var \Illuminate\Database\DatabaseManager $instance */
                        return $instance->setApplication($app);
        }
                    /**
         * Determine if the connected database is a MariaDB database.
         *
         * @return bool 
         * @static 
         */ 
        public static function isMaria()
        {
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->isMaria();
        }
                    /**
         * Get a schema builder instance for the connection.
         *
         * @return \Illuminate\Database\Schema\MySqlBuilder 
         * @static 
         */ 
        public static function getSchemaBuilder()
        {
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getSchemaBuilder();
        }
                    /**
         * Get the schema state for the connection.
         *
         * @param \Illuminate\Filesystem\Filesystem|null $files
         * @param callable|null $processFactory
         * @return \Illuminate\Database\Schema\MySqlSchemaState 
         * @static 
         */ 
        public static function getSchemaState($files = null, $processFactory = null)
        {
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getSchemaState($files, $processFactory);
        }
                    /**
         * Set the query grammar to the default implementation.
         *
         * @return void 
         * @static 
         */ 
        public static function useDefaultQueryGrammar()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->useDefaultQueryGrammar();
        }
                    /**
         * Set the schema grammar to the default implementation.
         *
         * @return void 
         * @static 
         */ 
        public static function useDefaultSchemaGrammar()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->useDefaultSchemaGrammar();
        }
                    /**
         * Set the query post processor to the default implementation.
         *
         * @return void 
         * @static 
         */ 
        public static function useDefaultPostProcessor()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->useDefaultPostProcessor();
        }
                    /**
         * Begin a fluent query against a database table.
         *
         * @param \Closure|\Illuminate\Database\Query\Builder|string $table
         * @param string|null $as
         * @return \Illuminate\Database\Query\Builder 
         * @static 
         */ 
        public static function table($table, $as = null)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->table($table, $as);
        }
                    /**
         * Get a new query builder instance.
         *
         * @return \Illuminate\Database\Query\Builder 
         * @static 
         */ 
        public static function query()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->query();
        }
                    /**
         * Run a select statement and return a single result.
         *
         * @param string $query
         * @param array $bindings
         * @param bool $useReadPdo
         * @return mixed 
         * @static 
         */ 
        public static function selectOne($query, $bindings = [], $useReadPdo = true)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->selectOne($query, $bindings, $useReadPdo);
        }
                    /**
         * Run a select statement against the database.
         *
         * @param string $query
         * @param array $bindings
         * @return array 
         * @static 
         */ 
        public static function selectFromWriteConnection($query, $bindings = [])
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->selectFromWriteConnection($query, $bindings);
        }
                    /**
         * Run a select statement against the database.
         *
         * @param string $query
         * @param array $bindings
         * @param bool $useReadPdo
         * @return array 
         * @static 
         */ 
        public static function select($query, $bindings = [], $useReadPdo = true)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->select($query, $bindings, $useReadPdo);
        }
                    /**
         * Run a select statement against the database and returns a generator.
         *
         * @param string $query
         * @param array $bindings
         * @param bool $useReadPdo
         * @return \Generator 
         * @static 
         */ 
        public static function cursor($query, $bindings = [], $useReadPdo = true)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->cursor($query, $bindings, $useReadPdo);
        }
                    /**
         * Run an insert statement against the database.
         *
         * @param string $query
         * @param array $bindings
         * @return bool 
         * @static 
         */ 
        public static function insert($query, $bindings = [])
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->insert($query, $bindings);
        }
                    /**
         * Run an update statement against the database.
         *
         * @param string $query
         * @param array $bindings
         * @return int 
         * @static 
         */ 
        public static function update($query, $bindings = [])
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->update($query, $bindings);
        }
                    /**
         * Run a delete statement against the database.
         *
         * @param string $query
         * @param array $bindings
         * @return int 
         * @static 
         */ 
        public static function delete($query, $bindings = [])
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->delete($query, $bindings);
        }
                    /**
         * Execute an SQL statement and return the boolean result.
         *
         * @param string $query
         * @param array $bindings
         * @return bool 
         * @static 
         */ 
        public static function statement($query, $bindings = [])
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->statement($query, $bindings);
        }
                    /**
         * Run an SQL statement and get the number of rows affected.
         *
         * @param string $query
         * @param array $bindings
         * @return int 
         * @static 
         */ 
        public static function affectingStatement($query, $bindings = [])
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->affectingStatement($query, $bindings);
        }
                    /**
         * Run a raw, unprepared query against the PDO connection.
         *
         * @param string $query
         * @return bool 
         * @static 
         */ 
        public static function unprepared($query)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->unprepared($query);
        }
                    /**
         * Execute the given callback in "dry run" mode.
         *
         * @param \Closure $callback
         * @return array 
         * @static 
         */ 
        public static function pretend($callback)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->pretend($callback);
        }
                    /**
         * Bind values to their parameters in the given statement.
         *
         * @param \PDOStatement $statement
         * @param array $bindings
         * @return void 
         * @static 
         */ 
        public static function bindValues($statement, $bindings)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->bindValues($statement, $bindings);
        }
                    /**
         * Prepare the query bindings for execution.
         *
         * @param array $bindings
         * @return array 
         * @static 
         */ 
        public static function prepareBindings($bindings)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->prepareBindings($bindings);
        }
                    /**
         * Log a query in the connection's query log.
         *
         * @param string $query
         * @param array $bindings
         * @param float|null $time
         * @return void 
         * @static 
         */ 
        public static function logQuery($query, $bindings, $time = null)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->logQuery($query, $bindings, $time);
        }
                    /**
         * Register a database query listener with the connection.
         *
         * @param \Closure $callback
         * @return void 
         * @static 
         */ 
        public static function listen($callback)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->listen($callback);
        }
                    /**
         * Get a new raw query expression.
         *
         * @param mixed $value
         * @return \Illuminate\Database\Query\Expression 
         * @static 
         */ 
        public static function raw($value)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->raw($value);
        }
                    /**
         * Determine if the database connection has modified any database records.
         *
         * @return bool 
         * @static 
         */ 
        public static function hasModifiedRecords()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->hasModifiedRecords();
        }
                    /**
         * Indicate if any records have been modified.
         *
         * @param bool $value
         * @return void 
         * @static 
         */ 
        public static function recordsHaveBeenModified($value = true)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->recordsHaveBeenModified($value);
        }
                    /**
         * Set the record modification state.
         *
         * @param bool $value
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function setRecordModificationState($value)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->setRecordModificationState($value);
        }
                    /**
         * Reset the record modification state.
         *
         * @return void 
         * @static 
         */ 
        public static function forgetRecordModificationState()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->forgetRecordModificationState();
        }
                    /**
         * Indicate that the connection should use the write PDO connection for reads.
         *
         * @param bool $value
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function useWriteConnectionWhenReading($value = true)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->useWriteConnectionWhenReading($value);
        }
                    /**
         * Is Doctrine available?
         *
         * @return bool 
         * @static 
         */ 
        public static function isDoctrineAvailable()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->isDoctrineAvailable();
        }
                    /**
         * Get a Doctrine Schema Column instance.
         *
         * @param string $table
         * @param string $column
         * @return \Doctrine\DBAL\Schema\Column 
         * @static 
         */ 
        public static function getDoctrineColumn($table, $column)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getDoctrineColumn($table, $column);
        }
                    /**
         * Get the Doctrine DBAL schema manager for the connection.
         *
         * @return \Doctrine\DBAL\Schema\AbstractSchemaManager 
         * @static 
         */ 
        public static function getDoctrineSchemaManager()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getDoctrineSchemaManager();
        }
                    /**
         * Get the Doctrine DBAL database connection instance.
         *
         * @return \Doctrine\DBAL\Connection 
         * @static 
         */ 
        public static function getDoctrineConnection()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getDoctrineConnection();
        }
                    /**
         * Get the current PDO connection.
         *
         * @return \PDO 
         * @static 
         */ 
        public static function getPdo()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getPdo();
        }
                    /**
         * Get the current PDO connection parameter without executing any reconnect logic.
         *
         * @return \PDO|\Closure|null 
         * @static 
         */ 
        public static function getRawPdo()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getRawPdo();
        }
                    /**
         * Get the current PDO connection used for reading.
         *
         * @return \PDO 
         * @static 
         */ 
        public static function getReadPdo()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getReadPdo();
        }
                    /**
         * Get the current read PDO connection parameter without executing any reconnect logic.
         *
         * @return \PDO|\Closure|null 
         * @static 
         */ 
        public static function getRawReadPdo()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getRawReadPdo();
        }
                    /**
         * Set the PDO connection.
         *
         * @param \PDO|\Closure|null $pdo
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function setPdo($pdo)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->setPdo($pdo);
        }
                    /**
         * Set the PDO connection used for reading.
         *
         * @param \PDO|\Closure|null $pdo
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function setReadPdo($pdo)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->setReadPdo($pdo);
        }
                    /**
         * Get the database connection name.
         *
         * @return string|null 
         * @static 
         */ 
        public static function getName()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getName();
        }
                    /**
         * Get the database connection full name.
         *
         * @return string|null 
         * @static 
         */ 
        public static function getNameWithReadWriteType()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getNameWithReadWriteType();
        }
                    /**
         * Get an option from the configuration options.
         *
         * @param string|null $option
         * @return mixed 
         * @static 
         */ 
        public static function getConfig($option = null)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getConfig($option);
        }
                    /**
         * Get the PDO driver name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDriverName()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getDriverName();
        }
                    /**
         * Get the query grammar used by the connection.
         *
         * @return \Illuminate\Database\Query\Grammars\Grammar 
         * @static 
         */ 
        public static function getQueryGrammar()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getQueryGrammar();
        }
                    /**
         * Set the query grammar used by the connection.
         *
         * @param \Illuminate\Database\Query\Grammars\Grammar $grammar
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function setQueryGrammar($grammar)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->setQueryGrammar($grammar);
        }
                    /**
         * Get the schema grammar used by the connection.
         *
         * @return \Illuminate\Database\Schema\Grammars\Grammar 
         * @static 
         */ 
        public static function getSchemaGrammar()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getSchemaGrammar();
        }
                    /**
         * Set the schema grammar used by the connection.
         *
         * @param \Illuminate\Database\Schema\Grammars\Grammar $grammar
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function setSchemaGrammar($grammar)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->setSchemaGrammar($grammar);
        }
                    /**
         * Get the query post processor used by the connection.
         *
         * @return \Illuminate\Database\Query\Processors\Processor 
         * @static 
         */ 
        public static function getPostProcessor()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getPostProcessor();
        }
                    /**
         * Set the query post processor used by the connection.
         *
         * @param \Illuminate\Database\Query\Processors\Processor $processor
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function setPostProcessor($processor)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->setPostProcessor($processor);
        }
                    /**
         * Get the event dispatcher used by the connection.
         *
         * @return \Illuminate\Contracts\Events\Dispatcher 
         * @static 
         */ 
        public static function getEventDispatcher()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getEventDispatcher();
        }
                    /**
         * Set the event dispatcher instance on the connection.
         *
         * @param \Illuminate\Contracts\Events\Dispatcher $events
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function setEventDispatcher($events)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->setEventDispatcher($events);
        }
                    /**
         * Unset the event dispatcher for this connection.
         *
         * @return void 
         * @static 
         */ 
        public static function unsetEventDispatcher()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->unsetEventDispatcher();
        }
                    /**
         * Set the transaction manager instance on the connection.
         *
         * @param \Illuminate\Database\DatabaseTransactionsManager $manager
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function setTransactionManager($manager)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->setTransactionManager($manager);
        }
                    /**
         * Unset the transaction manager for this connection.
         *
         * @return void 
         * @static 
         */ 
        public static function unsetTransactionManager()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->unsetTransactionManager();
        }
                    /**
         * Determine if the connection is in a "dry run".
         *
         * @return bool 
         * @static 
         */ 
        public static function pretending()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->pretending();
        }
                    /**
         * Get the connection query log.
         *
         * @return array 
         * @static 
         */ 
        public static function getQueryLog()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getQueryLog();
        }
                    /**
         * Clear the query log.
         *
         * @return void 
         * @static 
         */ 
        public static function flushQueryLog()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->flushQueryLog();
        }
                    /**
         * Enable the query log on the connection.
         *
         * @return void 
         * @static 
         */ 
        public static function enableQueryLog()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->enableQueryLog();
        }
                    /**
         * Disable the query log on the connection.
         *
         * @return void 
         * @static 
         */ 
        public static function disableQueryLog()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->disableQueryLog();
        }
                    /**
         * Determine whether we're logging queries.
         *
         * @return bool 
         * @static 
         */ 
        public static function logging()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->logging();
        }
                    /**
         * Get the name of the connected database.
         *
         * @return string 
         * @static 
         */ 
        public static function getDatabaseName()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getDatabaseName();
        }
                    /**
         * Set the name of the connected database.
         *
         * @param string $database
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function setDatabaseName($database)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->setDatabaseName($database);
        }
                    /**
         * Set the read / write type of the connection.
         *
         * @param string|null $readWriteType
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function setReadWriteType($readWriteType)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->setReadWriteType($readWriteType);
        }
                    /**
         * Get the table prefix for the connection.
         *
         * @return string 
         * @static 
         */ 
        public static function getTablePrefix()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->getTablePrefix();
        }
                    /**
         * Set the table prefix in use by the connection.
         *
         * @param string $prefix
         * @return \Illuminate\Database\MySqlConnection 
         * @static 
         */ 
        public static function setTablePrefix($prefix)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->setTablePrefix($prefix);
        }
                    /**
         * Set the table prefix and return the grammar.
         *
         * @param \Illuminate\Database\Grammar $grammar
         * @return \Illuminate\Database\Grammar 
         * @static 
         */ 
        public static function withTablePrefix($grammar)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->withTablePrefix($grammar);
        }
                    /**
         * Register a connection resolver.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return void 
         * @static 
         */ 
        public static function resolverFor($driver, $callback)
        {            //Method inherited from \Illuminate\Database\Connection         
                        \Illuminate\Database\MySqlConnection::resolverFor($driver, $callback);
        }
                    /**
         * Get the connection resolver for the given driver.
         *
         * @param string $driver
         * @return mixed 
         * @static 
         */ 
        public static function getResolver($driver)
        {            //Method inherited from \Illuminate\Database\Connection         
                        return \Illuminate\Database\MySqlConnection::getResolver($driver);
        }
                    /**
         * Execute a Closure within a transaction.
         *
         * @param \Closure $callback
         * @param int $attempts
         * @return mixed 
         * @throws \Throwable
         * @static 
         */ 
        public static function transaction($callback, $attempts = 1)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->transaction($callback, $attempts);
        }
                    /**
         * Start a new database transaction.
         *
         * @return void 
         * @throws \Throwable
         * @static 
         */ 
        public static function beginTransaction()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->beginTransaction();
        }
                    /**
         * Commit the active database transaction.
         *
         * @return void 
         * @throws \Throwable
         * @static 
         */ 
        public static function commit()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->commit();
        }
                    /**
         * Rollback the active database transaction.
         *
         * @param int|null $toLevel
         * @return void 
         * @throws \Throwable
         * @static 
         */ 
        public static function rollBack($toLevel = null)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->rollBack($toLevel);
        }
                    /**
         * Get the number of active transactions.
         *
         * @return int 
         * @static 
         */ 
        public static function transactionLevel()
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        return $instance->transactionLevel();
        }
                    /**
         * Execute the callback after a transaction commits.
         *
         * @param callable $callback
         * @return void 
         * @throws \RuntimeException
         * @static 
         */ 
        public static function afterCommit($callback)
        {            //Method inherited from \Illuminate\Database\Connection         
                        /** @var \Illuminate\Database\MySqlConnection $instance */
                        $instance->afterCommit($callback);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Events\Dispatcher
     */ 
        class Event {
                    /**
         * Register an event listener with the dispatcher.
         *
         * @param \Closure|string|array $events
         * @param \Closure|string|array|null $listener
         * @return void 
         * @static 
         */ 
        public static function listen($events, $listener = null)
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        $instance->listen($events, $listener);
        }
                    /**
         * Determine if a given event has listeners.
         *
         * @param string $eventName
         * @return bool 
         * @static 
         */ 
        public static function hasListeners($eventName)
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        return $instance->hasListeners($eventName);
        }
                    /**
         * Determine if the given event has any wildcard listeners.
         *
         * @param string $eventName
         * @return bool 
         * @static 
         */ 
        public static function hasWildcardListeners($eventName)
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        return $instance->hasWildcardListeners($eventName);
        }
                    /**
         * Register an event and payload to be fired later.
         *
         * @param string $event
         * @param array $payload
         * @return void 
         * @static 
         */ 
        public static function push($event, $payload = [])
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        $instance->push($event, $payload);
        }
                    /**
         * Flush a set of pushed events.
         *
         * @param string $event
         * @return void 
         * @static 
         */ 
        public static function flush($event)
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        $instance->flush($event);
        }
                    /**
         * Register an event subscriber with the dispatcher.
         *
         * @param object|string $subscriber
         * @return void 
         * @static 
         */ 
        public static function subscribe($subscriber)
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        $instance->subscribe($subscriber);
        }
                    /**
         * Fire an event until the first non-null response is returned.
         *
         * @param string|object $event
         * @param mixed $payload
         * @return array|null 
         * @static 
         */ 
        public static function until($event, $payload = [])
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        return $instance->until($event, $payload);
        }
                    /**
         * Fire an event and call the listeners.
         *
         * @param string|object $event
         * @param mixed $payload
         * @param bool $halt
         * @return array|null 
         * @static 
         */ 
        public static function dispatch($event, $payload = [], $halt = false)
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        return $instance->dispatch($event, $payload, $halt);
        }
                    /**
         * Get all of the listeners for a given event name.
         *
         * @param string $eventName
         * @return array 
         * @static 
         */ 
        public static function getListeners($eventName)
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        return $instance->getListeners($eventName);
        }
                    /**
         * Register an event listener with the dispatcher.
         *
         * @param \Closure|string $listener
         * @param bool $wildcard
         * @return \Closure 
         * @static 
         */ 
        public static function makeListener($listener, $wildcard = false)
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        return $instance->makeListener($listener, $wildcard);
        }
                    /**
         * Create a class based listener using the IoC container.
         *
         * @param string $listener
         * @param bool $wildcard
         * @return \Closure 
         * @static 
         */ 
        public static function createClassListener($listener, $wildcard = false)
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        return $instance->createClassListener($listener, $wildcard);
        }
                    /**
         * Remove a set of listeners from the dispatcher.
         *
         * @param string $event
         * @return void 
         * @static 
         */ 
        public static function forget($event)
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        $instance->forget($event);
        }
                    /**
         * Forget all of the pushed listeners.
         *
         * @return void 
         * @static 
         */ 
        public static function forgetPushed()
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        $instance->forgetPushed();
        }
                    /**
         * Set the queue resolver implementation.
         *
         * @param callable $resolver
         * @return \Illuminate\Events\Dispatcher 
         * @static 
         */ 
        public static function setQueueResolver($resolver)
        {
                        /** @var \Illuminate\Events\Dispatcher $instance */
                        return $instance->setQueueResolver($resolver);
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Events\Dispatcher::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Events\Dispatcher::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Events\Dispatcher::hasMacro($name);
        }
                    /**
         * Assert if an event has a listener attached to it.
         *
         * @param string $expectedEvent
         * @param string $expectedListener
         * @return void 
         * @static 
         */ 
        public static function assertListening($expectedEvent, $expectedListener)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\EventFake $instance */
                        $instance->assertListening($expectedEvent, $expectedListener);
        }
                    /**
         * Assert if an event was dispatched based on a truth-test callback.
         *
         * @param string|\Closure $event
         * @param callable|int|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertDispatched($event, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\EventFake $instance */
                        $instance->assertDispatched($event, $callback);
        }
                    /**
         * Assert if an event was dispatched a number of times.
         *
         * @param string $event
         * @param int $times
         * @return void 
         * @static 
         */ 
        public static function assertDispatchedTimes($event, $times = 1)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\EventFake $instance */
                        $instance->assertDispatchedTimes($event, $times);
        }
                    /**
         * Determine if an event was dispatched based on a truth-test callback.
         *
         * @param string|\Closure $event
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertNotDispatched($event, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\EventFake $instance */
                        $instance->assertNotDispatched($event, $callback);
        }
                    /**
         * Assert that no events were dispatched.
         *
         * @return void 
         * @static 
         */ 
        public static function assertNothingDispatched()
        {
                        /** @var \Illuminate\Support\Testing\Fakes\EventFake $instance */
                        $instance->assertNothingDispatched();
        }
                    /**
         * Get all of the events matching a truth-test callback.
         *
         * @param string $event
         * @param callable|null $callback
         * @return \Illuminate\Support\Collection 
         * @static 
         */ 
        public static function dispatched($event, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\EventFake $instance */
                        return $instance->dispatched($event, $callback);
        }
                    /**
         * Determine if the given event has been dispatched.
         *
         * @param string $event
         * @return bool 
         * @static 
         */ 
        public static function hasDispatched($event)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\EventFake $instance */
                        return $instance->hasDispatched($event);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Filesystem\Filesystem
     */ 
        class File {
                    /**
         * Determine if a file or directory exists.
         *
         * @param string $path
         * @return bool 
         * @static 
         */ 
        public static function exists($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->exists($path);
        }
                    /**
         * Determine if a file or directory is missing.
         *
         * @param string $path
         * @return bool 
         * @static 
         */ 
        public static function missing($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->missing($path);
        }
                    /**
         * Get the contents of a file.
         *
         * @param string $path
         * @param bool $lock
         * @return string 
         * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
         * @static 
         */ 
        public static function get($path, $lock = false)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->get($path, $lock);
        }
                    /**
         * Get contents of a file with shared access.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function sharedGet($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->sharedGet($path);
        }
                    /**
         * Get the returned value of a file.
         *
         * @param string $path
         * @param array $data
         * @return mixed 
         * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
         * @static 
         */ 
        public static function getRequire($path, $data = [])
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->getRequire($path, $data);
        }
                    /**
         * Require the given file once.
         *
         * @param string $path
         * @param array $data
         * @return mixed 
         * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
         * @static 
         */ 
        public static function requireOnce($path, $data = [])
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->requireOnce($path, $data);
        }
                    /**
         * Get the contents of a file one line at a time.
         *
         * @param string $path
         * @return \Illuminate\Support\LazyCollection 
         * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
         * @static 
         */ 
        public static function lines($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->lines($path);
        }
                    /**
         * Get the MD5 hash of the file at the given path.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function hash($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->hash($path);
        }
                    /**
         * Write the contents of a file.
         *
         * @param string $path
         * @param string $contents
         * @param bool $lock
         * @return int|bool 
         * @static 
         */ 
        public static function put($path, $contents, $lock = false)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->put($path, $contents, $lock);
        }
                    /**
         * Write the contents of a file, replacing it atomically if it already exists.
         *
         * @param string $path
         * @param string $content
         * @return void 
         * @static 
         */ 
        public static function replace($path, $content)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        $instance->replace($path, $content);
        }
                    /**
         * Replace a given string within a given file.
         *
         * @param array|string $search
         * @param array|string $replace
         * @param string $path
         * @return void 
         * @static 
         */ 
        public static function replaceInFile($search, $replace, $path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        $instance->replaceInFile($search, $replace, $path);
        }
                    /**
         * Prepend to a file.
         *
         * @param string $path
         * @param string $data
         * @return int 
         * @static 
         */ 
        public static function prepend($path, $data)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->prepend($path, $data);
        }
                    /**
         * Append to a file.
         *
         * @param string $path
         * @param string $data
         * @return int 
         * @static 
         */ 
        public static function append($path, $data)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->append($path, $data);
        }
                    /**
         * Get or set UNIX mode of a file or directory.
         *
         * @param string $path
         * @param int|null $mode
         * @return mixed 
         * @static 
         */ 
        public static function chmod($path, $mode = null)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->chmod($path, $mode);
        }
                    /**
         * Delete the file at a given path.
         *
         * @param string|array $paths
         * @return bool 
         * @static 
         */ 
        public static function delete($paths)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->delete($paths);
        }
                    /**
         * Move a file to a new location.
         *
         * @param string $path
         * @param string $target
         * @return bool 
         * @static 
         */ 
        public static function move($path, $target)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->move($path, $target);
        }
                    /**
         * Copy a file to a new location.
         *
         * @param string $path
         * @param string $target
         * @return bool 
         * @static 
         */ 
        public static function copy($path, $target)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->copy($path, $target);
        }
                    /**
         * Create a symlink to the target file or directory. On Windows, a hard link is created if the target is a file.
         *
         * @param string $target
         * @param string $link
         * @return void 
         * @static 
         */ 
        public static function link($target, $link)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        $instance->link($target, $link);
        }
                    /**
         * Create a relative symlink to the target file or directory.
         *
         * @param string $target
         * @param string $link
         * @return void 
         * @throws \RuntimeException
         * @static 
         */ 
        public static function relativeLink($target, $link)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        $instance->relativeLink($target, $link);
        }
                    /**
         * Extract the file name from a file path.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function name($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->name($path);
        }
                    /**
         * Extract the trailing name component from a file path.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function basename($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->basename($path);
        }
                    /**
         * Extract the parent directory from a file path.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function dirname($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->dirname($path);
        }
                    /**
         * Extract the file extension from a file path.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function extension($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->extension($path);
        }
                    /**
         * Guess the file extension from the mime-type of a given file.
         *
         * @param string $path
         * @return string|null 
         * @throws \RuntimeException
         * @static 
         */ 
        public static function guessExtension($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->guessExtension($path);
        }
                    /**
         * Get the file type of a given file.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function type($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->type($path);
        }
                    /**
         * Get the mime-type of a given file.
         *
         * @param string $path
         * @return string|false 
         * @static 
         */ 
        public static function mimeType($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->mimeType($path);
        }
                    /**
         * Get the file size of a given file.
         *
         * @param string $path
         * @return int 
         * @static 
         */ 
        public static function size($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->size($path);
        }
                    /**
         * Get the file's last modification time.
         *
         * @param string $path
         * @return int 
         * @static 
         */ 
        public static function lastModified($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->lastModified($path);
        }
                    /**
         * Determine if the given path is a directory.
         *
         * @param string $directory
         * @return bool 
         * @static 
         */ 
        public static function isDirectory($directory)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->isDirectory($directory);
        }
                    /**
         * Determine if the given path is readable.
         *
         * @param string $path
         * @return bool 
         * @static 
         */ 
        public static function isReadable($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->isReadable($path);
        }
                    /**
         * Determine if the given path is writable.
         *
         * @param string $path
         * @return bool 
         * @static 
         */ 
        public static function isWritable($path)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->isWritable($path);
        }
                    /**
         * Determine if the given path is a file.
         *
         * @param string $file
         * @return bool 
         * @static 
         */ 
        public static function isFile($file)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->isFile($file);
        }
                    /**
         * Find path names matching a given pattern.
         *
         * @param string $pattern
         * @param int $flags
         * @return array 
         * @static 
         */ 
        public static function glob($pattern, $flags = 0)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->glob($pattern, $flags);
        }
                    /**
         * Get an array of all files in a directory.
         *
         * @param string $directory
         * @param bool $hidden
         * @return \Symfony\Component\Finder\SplFileInfo[] 
         * @static 
         */ 
        public static function files($directory, $hidden = false)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->files($directory, $hidden);
        }
                    /**
         * Get all of the files from the given directory (recursive).
         *
         * @param string $directory
         * @param bool $hidden
         * @return \Symfony\Component\Finder\SplFileInfo[] 
         * @static 
         */ 
        public static function allFiles($directory, $hidden = false)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->allFiles($directory, $hidden);
        }
                    /**
         * Get all of the directories within a given directory.
         *
         * @param string $directory
         * @return array 
         * @static 
         */ 
        public static function directories($directory)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->directories($directory);
        }
                    /**
         * Ensure a directory exists.
         *
         * @param string $path
         * @param int $mode
         * @param bool $recursive
         * @return void 
         * @static 
         */ 
        public static function ensureDirectoryExists($path, $mode = 493, $recursive = true)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        $instance->ensureDirectoryExists($path, $mode, $recursive);
        }
                    /**
         * Create a directory.
         *
         * @param string $path
         * @param int $mode
         * @param bool $recursive
         * @param bool $force
         * @return bool 
         * @static 
         */ 
        public static function makeDirectory($path, $mode = 493, $recursive = false, $force = false)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->makeDirectory($path, $mode, $recursive, $force);
        }
                    /**
         * Move a directory.
         *
         * @param string $from
         * @param string $to
         * @param bool $overwrite
         * @return bool 
         * @static 
         */ 
        public static function moveDirectory($from, $to, $overwrite = false)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->moveDirectory($from, $to, $overwrite);
        }
                    /**
         * Copy a directory from one location to another.
         *
         * @param string $directory
         * @param string $destination
         * @param int|null $options
         * @return bool 
         * @static 
         */ 
        public static function copyDirectory($directory, $destination, $options = null)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->copyDirectory($directory, $destination, $options);
        }
                    /**
         * Recursively delete a directory.
         * 
         * The directory itself may be optionally preserved.
         *
         * @param string $directory
         * @param bool $preserve
         * @return bool 
         * @static 
         */ 
        public static function deleteDirectory($directory, $preserve = false)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->deleteDirectory($directory, $preserve);
        }
                    /**
         * Remove all of the directories within a given directory.
         *
         * @param string $directory
         * @return bool 
         * @static 
         */ 
        public static function deleteDirectories($directory)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->deleteDirectories($directory);
        }
                    /**
         * Empty the specified directory of all files and folders.
         *
         * @param string $directory
         * @return bool 
         * @static 
         */ 
        public static function cleanDirectory($directory)
        {
                        /** @var \Illuminate\Filesystem\Filesystem $instance */
                        return $instance->cleanDirectory($directory);
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Filesystem\Filesystem::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Filesystem\Filesystem::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Filesystem\Filesystem::hasMacro($name);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Contracts\Auth\Access\Gate
     */ 
        class Gate {
                    /**
         * Determine if a given ability has been defined.
         *
         * @param string|array $ability
         * @return bool 
         * @static 
         */ 
        public static function has($ability)
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->has($ability);
        }
                    /**
         * Define a new ability.
         *
         * @param string $ability
         * @param callable|string $callback
         * @return \Illuminate\Auth\Access\Gate 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function define($ability, $callback)
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->define($ability, $callback);
        }
                    /**
         * Define abilities for a resource.
         *
         * @param string $name
         * @param string $class
         * @param array|null $abilities
         * @return \Illuminate\Auth\Access\Gate 
         * @static 
         */ 
        public static function resource($name, $class, $abilities = null)
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->resource($name, $class, $abilities);
        }
                    /**
         * Define a policy class for a given class type.
         *
         * @param string $class
         * @param string $policy
         * @return \Illuminate\Auth\Access\Gate 
         * @static 
         */ 
        public static function policy($class, $policy)
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->policy($class, $policy);
        }
                    /**
         * Register a callback to run before all Gate checks.
         *
         * @param callable $callback
         * @return \Illuminate\Auth\Access\Gate 
         * @static 
         */ 
        public static function before($callback)
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->before($callback);
        }
                    /**
         * Register a callback to run after all Gate checks.
         *
         * @param callable $callback
         * @return \Illuminate\Auth\Access\Gate 
         * @static 
         */ 
        public static function after($callback)
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->after($callback);
        }
                    /**
         * Determine if the given ability should be granted for the current user.
         *
         * @param string $ability
         * @param array|mixed $arguments
         * @return bool 
         * @static 
         */ 
        public static function allows($ability, $arguments = [])
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->allows($ability, $arguments);
        }
                    /**
         * Determine if the given ability should be denied for the current user.
         *
         * @param string $ability
         * @param array|mixed $arguments
         * @return bool 
         * @static 
         */ 
        public static function denies($ability, $arguments = [])
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->denies($ability, $arguments);
        }
                    /**
         * Determine if all of the given abilities should be granted for the current user.
         *
         * @param \Illuminate\Auth\Access\iterable|string $abilities
         * @param array|mixed $arguments
         * @return bool 
         * @static 
         */ 
        public static function check($abilities, $arguments = [])
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->check($abilities, $arguments);
        }
                    /**
         * Determine if any one of the given abilities should be granted for the current user.
         *
         * @param \Illuminate\Auth\Access\iterable|string $abilities
         * @param array|mixed $arguments
         * @return bool 
         * @static 
         */ 
        public static function any($abilities, $arguments = [])
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->any($abilities, $arguments);
        }
                    /**
         * Determine if all of the given abilities should be denied for the current user.
         *
         * @param \Illuminate\Auth\Access\iterable|string $abilities
         * @param array|mixed $arguments
         * @return bool 
         * @static 
         */ 
        public static function none($abilities, $arguments = [])
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->none($abilities, $arguments);
        }
                    /**
         * Determine if the given ability should be granted for the current user.
         *
         * @param string $ability
         * @param array|mixed $arguments
         * @return \Illuminate\Auth\Access\Response 
         * @throws \Illuminate\Auth\Access\AuthorizationException
         * @static 
         */ 
        public static function authorize($ability, $arguments = [])
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->authorize($ability, $arguments);
        }
                    /**
         * Inspect the user for the given ability.
         *
         * @param string $ability
         * @param array|mixed $arguments
         * @return \Illuminate\Auth\Access\Response 
         * @static 
         */ 
        public static function inspect($ability, $arguments = [])
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->inspect($ability, $arguments);
        }
                    /**
         * Get the raw result from the authorization callback.
         *
         * @param string $ability
         * @param array|mixed $arguments
         * @return mixed 
         * @throws \Illuminate\Auth\Access\AuthorizationException
         * @static 
         */ 
        public static function raw($ability, $arguments = [])
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->raw($ability, $arguments);
        }
                    /**
         * Get a policy instance for a given class.
         *
         * @param object|string $class
         * @return mixed 
         * @static 
         */ 
        public static function getPolicyFor($class)
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->getPolicyFor($class);
        }
                    /**
         * Specify a callback to be used to guess policy names.
         *
         * @param callable $callback
         * @return \Illuminate\Auth\Access\Gate 
         * @static 
         */ 
        public static function guessPolicyNamesUsing($callback)
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->guessPolicyNamesUsing($callback);
        }
                    /**
         * Build a policy class instance of the given type.
         *
         * @param object|string $class
         * @return mixed 
         * @throws \Illuminate\Contracts\Container\BindingResolutionException
         * @static 
         */ 
        public static function resolvePolicy($class)
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->resolvePolicy($class);
        }
                    /**
         * Get a gate instance for the given user.
         *
         * @param \Illuminate\Contracts\Auth\Authenticatable|mixed $user
         * @return static 
         * @static 
         */ 
        public static function forUser($user)
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->forUser($user);
        }
                    /**
         * Get all of the defined abilities.
         *
         * @return array 
         * @static 
         */ 
        public static function abilities()
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->abilities();
        }
                    /**
         * Get all of the defined policies.
         *
         * @return array 
         * @static 
         */ 
        public static function policies()
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->policies();
        }
                    /**
         * Set the container instance used by the gate.
         *
         * @param \Illuminate\Contracts\Container\Container $container
         * @return \Illuminate\Auth\Access\Gate 
         * @static 
         */ 
        public static function setContainer($container)
        {
                        /** @var \Illuminate\Auth\Access\Gate $instance */
                        return $instance->setContainer($container);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Hashing\HashManager
     */ 
        class Hash {
                    /**
         * Create an instance of the Bcrypt hash Driver.
         *
         * @return \Illuminate\Hashing\BcryptHasher 
         * @static 
         */ 
        public static function createBcryptDriver()
        {
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->createBcryptDriver();
        }
                    /**
         * Create an instance of the Argon2i hash Driver.
         *
         * @return \Illuminate\Hashing\ArgonHasher 
         * @static 
         */ 
        public static function createArgonDriver()
        {
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->createArgonDriver();
        }
                    /**
         * Create an instance of the Argon2id hash Driver.
         *
         * @return \Illuminate\Hashing\Argon2IdHasher 
         * @static 
         */ 
        public static function createArgon2idDriver()
        {
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->createArgon2idDriver();
        }
                    /**
         * Get information about the given hashed value.
         *
         * @param string $hashedValue
         * @return array 
         * @static 
         */ 
        public static function info($hashedValue)
        {
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->info($hashedValue);
        }
                    /**
         * Hash the given value.
         *
         * @param string $value
         * @param array $options
         * @return string 
         * @static 
         */ 
        public static function make($value, $options = [])
        {
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->make($value, $options);
        }
                    /**
         * Check the given plain value against a hash.
         *
         * @param string $value
         * @param string $hashedValue
         * @param array $options
         * @return bool 
         * @static 
         */ 
        public static function check($value, $hashedValue, $options = [])
        {
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->check($value, $hashedValue, $options);
        }
                    /**
         * Check if the given hash has been hashed using the given options.
         *
         * @param string $hashedValue
         * @param array $options
         * @return bool 
         * @static 
         */ 
        public static function needsRehash($hashedValue, $options = [])
        {
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->needsRehash($hashedValue, $options);
        }
                    /**
         * Get the default driver name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultDriver()
        {
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->getDefaultDriver();
        }
                    /**
         * Get a driver instance.
         *
         * @param string|null $driver
         * @return mixed 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function driver($driver = null)
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->driver($driver);
        }
                    /**
         * Register a custom driver creator Closure.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return \Illuminate\Hashing\HashManager 
         * @static 
         */ 
        public static function extend($driver, $callback)
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->extend($driver, $callback);
        }
                    /**
         * Get all of the created "drivers".
         *
         * @return array 
         * @static 
         */ 
        public static function getDrivers()
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->getDrivers();
        }
                    /**
         * Get the container instance used by the manager.
         *
         * @return \Illuminate\Contracts\Container\Container 
         * @static 
         */ 
        public static function getContainer()
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->getContainer();
        }
                    /**
         * Set the container instance used by the manager.
         *
         * @param \Illuminate\Contracts\Container\Container $container
         * @return \Illuminate\Hashing\HashManager 
         * @static 
         */ 
        public static function setContainer($container)
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->setContainer($container);
        }
                    /**
         * Forget all of the resolved driver instances.
         *
         * @return \Illuminate\Hashing\HashManager 
         * @static 
         */ 
        public static function forgetDrivers()
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Hashing\HashManager $instance */
                        return $instance->forgetDrivers();
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Translation\Translator
     */ 
        class Lang {
                    /**
         * Determine if a translation exists for a given locale.
         *
         * @param string $key
         * @param string|null $locale
         * @return bool 
         * @static 
         */ 
        public static function hasForLocale($key, $locale = null)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        return $instance->hasForLocale($key, $locale);
        }
                    /**
         * Determine if a translation exists.
         *
         * @param string $key
         * @param string|null $locale
         * @param bool $fallback
         * @return bool 
         * @static 
         */ 
        public static function has($key, $locale = null, $fallback = true)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        return $instance->has($key, $locale, $fallback);
        }
                    /**
         * Get the translation for the given key.
         *
         * @param string $key
         * @param array $replace
         * @param string|null $locale
         * @param bool $fallback
         * @return string|array 
         * @static 
         */ 
        public static function get($key, $replace = [], $locale = null, $fallback = true)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        return $instance->get($key, $replace, $locale, $fallback);
        }
                    /**
         * Get a translation according to an integer value.
         *
         * @param string $key
         * @param \Countable|int|array $number
         * @param array $replace
         * @param string|null $locale
         * @return string 
         * @static 
         */ 
        public static function choice($key, $number, $replace = [], $locale = null)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        return $instance->choice($key, $number, $replace, $locale);
        }
                    /**
         * Add translation lines to the given locale.
         *
         * @param array $lines
         * @param string $locale
         * @param string $namespace
         * @return void 
         * @static 
         */ 
        public static function addLines($lines, $locale, $namespace = '*')
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        $instance->addLines($lines, $locale, $namespace);
        }
                    /**
         * Load the specified language group.
         *
         * @param string $namespace
         * @param string $group
         * @param string $locale
         * @return void 
         * @static 
         */ 
        public static function load($namespace, $group, $locale)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        $instance->load($namespace, $group, $locale);
        }
                    /**
         * Add a new namespace to the loader.
         *
         * @param string $namespace
         * @param string $hint
         * @return void 
         * @static 
         */ 
        public static function addNamespace($namespace, $hint)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        $instance->addNamespace($namespace, $hint);
        }
                    /**
         * Add a new JSON path to the loader.
         *
         * @param string $path
         * @return void 
         * @static 
         */ 
        public static function addJsonPath($path)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        $instance->addJsonPath($path);
        }
                    /**
         * Parse a key into namespace, group, and item.
         *
         * @param string $key
         * @return array 
         * @static 
         */ 
        public static function parseKey($key)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        return $instance->parseKey($key);
        }
                    /**
         * Get the message selector instance.
         *
         * @return \Illuminate\Translation\MessageSelector 
         * @static 
         */ 
        public static function getSelector()
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        return $instance->getSelector();
        }
                    /**
         * Set the message selector instance.
         *
         * @param \Illuminate\Translation\MessageSelector $selector
         * @return void 
         * @static 
         */ 
        public static function setSelector($selector)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        $instance->setSelector($selector);
        }
                    /**
         * Get the language line loader implementation.
         *
         * @return \Illuminate\Contracts\Translation\Loader 
         * @static 
         */ 
        public static function getLoader()
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        return $instance->getLoader();
        }
                    /**
         * Get the default locale being used.
         *
         * @return string 
         * @static 
         */ 
        public static function locale()
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        return $instance->locale();
        }
                    /**
         * Get the default locale being used.
         *
         * @return string 
         * @static 
         */ 
        public static function getLocale()
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        return $instance->getLocale();
        }
                    /**
         * Set the default locale.
         *
         * @param string $locale
         * @return void 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function setLocale($locale)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        $instance->setLocale($locale);
        }
                    /**
         * Get the fallback locale being used.
         *
         * @return string 
         * @static 
         */ 
        public static function getFallback()
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        return $instance->getFallback();
        }
                    /**
         * Set the fallback locale being used.
         *
         * @param string $fallback
         * @return void 
         * @static 
         */ 
        public static function setFallback($fallback)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        $instance->setFallback($fallback);
        }
                    /**
         * Set the loaded translation groups.
         *
         * @param array $loaded
         * @return void 
         * @static 
         */ 
        public static function setLoaded($loaded)
        {
                        /** @var \Illuminate\Translation\Translator $instance */
                        $instance->setLoaded($loaded);
        }
                    /**
         * Set the parsed value of a key.
         *
         * @param string $key
         * @param array $parsed
         * @return void 
         * @static 
         */ 
        public static function setParsedKey($key, $parsed)
        {            //Method inherited from \Illuminate\Support\NamespacedItemResolver         
                        /** @var \Illuminate\Translation\Translator $instance */
                        $instance->setParsedKey($key, $parsed);
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Translation\Translator::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Translation\Translator::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Translation\Translator::hasMacro($name);
        }
         
    }
            /**
     * 
     *
     * @method static \Illuminate\Log\Logger withContext(array $context = [])
     * @method static \Illuminate\Log\Logger withoutContext()
     * @method static void write(string $level, string $message, array $context = [])
     * @method static void listen(\Closure $callback)
     * @see \Illuminate\Log\Logger
     */ 
        class Log {
                    /**
         * Create a new, on-demand aggregate logger instance.
         *
         * @param array $channels
         * @param string|null $channel
         * @return \Psr\Log\LoggerInterface 
         * @static 
         */ 
        public static function stack($channels, $channel = null)
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        return $instance->stack($channels, $channel);
        }
                    /**
         * Get a log channel instance.
         *
         * @param string|null $channel
         * @return \Psr\Log\LoggerInterface 
         * @static 
         */ 
        public static function channel($channel = null)
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        return $instance->channel($channel);
        }
                    /**
         * Get a log driver instance.
         *
         * @param string|null $driver
         * @return \Psr\Log\LoggerInterface 
         * @static 
         */ 
        public static function driver($driver = null)
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        return $instance->driver($driver);
        }
                    /**
         * 
         *
         * @return array 
         * @static 
         */ 
        public static function getChannels()
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        return $instance->getChannels();
        }
                    /**
         * Get the default log driver name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultDriver()
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        return $instance->getDefaultDriver();
        }
                    /**
         * Set the default log driver name.
         *
         * @param string $name
         * @return void 
         * @static 
         */ 
        public static function setDefaultDriver($name)
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        $instance->setDefaultDriver($name);
        }
                    /**
         * Register a custom driver creator Closure.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return \Illuminate\Log\LogManager 
         * @static 
         */ 
        public static function extend($driver, $callback)
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        return $instance->extend($driver, $callback);
        }
                    /**
         * Unset the given channel instance.
         *
         * @param string|null $driver
         * @return \Illuminate\Log\LogManager 
         * @static 
         */ 
        public static function forgetChannel($driver = null)
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        return $instance->forgetChannel($driver);
        }
                    /**
         * System is unusable.
         *
         * @param string $message
         * @param array $context
         * @return void 
         * @static 
         */ 
        public static function emergency($message, $context = [])
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        $instance->emergency($message, $context);
        }
                    /**
         * Action must be taken immediately.
         * 
         * Example: Entire website down, database unavailable, etc. This should
         * trigger the SMS alerts and wake you up.
         *
         * @param string $message
         * @param array $context
         * @return void 
         * @static 
         */ 
        public static function alert($message, $context = [])
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        $instance->alert($message, $context);
        }
                    /**
         * Critical conditions.
         * 
         * Example: Application component unavailable, unexpected exception.
         *
         * @param string $message
         * @param array $context
         * @return void 
         * @static 
         */ 
        public static function critical($message, $context = [])
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        $instance->critical($message, $context);
        }
                    /**
         * Runtime errors that do not require immediate action but should typically
         * be logged and monitored.
         *
         * @param string $message
         * @param array $context
         * @return void 
         * @static 
         */ 
        public static function error($message, $context = [])
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        $instance->error($message, $context);
        }
                    /**
         * Exceptional occurrences that are not errors.
         * 
         * Example: Use of deprecated APIs, poor use of an API, undesirable things
         * that are not necessarily wrong.
         *
         * @param string $message
         * @param array $context
         * @return void 
         * @static 
         */ 
        public static function warning($message, $context = [])
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        $instance->warning($message, $context);
        }
                    /**
         * Normal but significant events.
         *
         * @param string $message
         * @param array $context
         * @return void 
         * @static 
         */ 
        public static function notice($message, $context = [])
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        $instance->notice($message, $context);
        }
                    /**
         * Interesting events.
         * 
         * Example: User logs in, SQL logs.
         *
         * @param string $message
         * @param array $context
         * @return void 
         * @static 
         */ 
        public static function info($message, $context = [])
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        $instance->info($message, $context);
        }
                    /**
         * Detailed debug information.
         *
         * @param string $message
         * @param array $context
         * @return void 
         * @static 
         */ 
        public static function debug($message, $context = [])
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        $instance->debug($message, $context);
        }
                    /**
         * Logs with an arbitrary level.
         *
         * @param mixed $level
         * @param string $message
         * @param array $context
         * @return void 
         * @static 
         */ 
        public static function log($level, $message, $context = [])
        {
                        /** @var \Illuminate\Log\LogManager $instance */
                        $instance->log($level, $message, $context);
        }
         
    }
            /**
     * 
     *
     * @method static mixed laterOn(string $queue, \DateTimeInterface|\DateInterval|int $delay, \Illuminate\Contracts\Mail\Mailable|string|array $view)
     * @method static mixed queueOn(string $queue, \Illuminate\Contracts\Mail\Mailable|string|array $view)
     * @method static void plain(string $view, array $data, $callback)
     * @method static void html(string $html, $callback)
     * @see \Illuminate\Mail\Mailer
     * @see \Illuminate\Support\Testing\Fakes\MailFake
     */ 
        class Mail {
                    /**
         * Get a mailer instance by name.
         *
         * @param string|null $name
         * @return \Illuminate\Contracts\Mail\Mailer 
         * @static 
         */ 
        public static function mailer($name = null)
        {
                        /** @var \Illuminate\Mail\MailManager $instance */
                        return $instance->mailer($name);
        }
                    /**
         * Get a mailer driver instance.
         *
         * @param string|null $driver
         * @return \Illuminate\Mail\Mailer 
         * @static 
         */ 
        public static function driver($driver = null)
        {
                        /** @var \Illuminate\Mail\MailManager $instance */
                        return $instance->driver($driver);
        }
                    /**
         * Create a new transport instance.
         *
         * @param array $config
         * @return \Swift_Transport 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function createTransport($config)
        {
                        /** @var \Illuminate\Mail\MailManager $instance */
                        return $instance->createTransport($config);
        }
                    /**
         * Get the default mail driver name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultDriver()
        {
                        /** @var \Illuminate\Mail\MailManager $instance */
                        return $instance->getDefaultDriver();
        }
                    /**
         * Set the default mail driver name.
         *
         * @param string $name
         * @return void 
         * @static 
         */ 
        public static function setDefaultDriver($name)
        {
                        /** @var \Illuminate\Mail\MailManager $instance */
                        $instance->setDefaultDriver($name);
        }
                    /**
         * Disconnect the given mailer and remove from local cache.
         *
         * @param string|null $name
         * @return void 
         * @static 
         */ 
        public static function purge($name = null)
        {
                        /** @var \Illuminate\Mail\MailManager $instance */
                        $instance->purge($name);
        }
                    /**
         * Register a custom transport creator Closure.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return \Illuminate\Mail\MailManager 
         * @static 
         */ 
        public static function extend($driver, $callback)
        {
                        /** @var \Illuminate\Mail\MailManager $instance */
                        return $instance->extend($driver, $callback);
        }
                    /**
         * Get the application instance used by the manager.
         *
         * @return \Illuminate\Contracts\Foundation\Application 
         * @static 
         */ 
        public static function getApplication()
        {
                        /** @var \Illuminate\Mail\MailManager $instance */
                        return $instance->getApplication();
        }
                    /**
         * Set the application instance used by the manager.
         *
         * @param \Illuminate\Contracts\Foundation\Application $app
         * @return \Illuminate\Mail\MailManager 
         * @static 
         */ 
        public static function setApplication($app)
        {
                        /** @var \Illuminate\Mail\MailManager $instance */
                        return $instance->setApplication($app);
        }
                    /**
         * Forget all of the resolved mailer instances.
         *
         * @return \Illuminate\Mail\MailManager 
         * @static 
         */ 
        public static function forgetMailers()
        {
                        /** @var \Illuminate\Mail\MailManager $instance */
                        return $instance->forgetMailers();
        }
                    /**
         * Assert if a mailable was sent based on a truth-test callback.
         *
         * @param string|\Closure $mailable
         * @param callable|int|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertSent($mailable, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        $instance->assertSent($mailable, $callback);
        }
                    /**
         * Determine if a mailable was not sent based on a truth-test callback.
         *
         * @param string $mailable
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertNotSent($mailable, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        $instance->assertNotSent($mailable, $callback);
        }
                    /**
         * Assert that no mailables were sent.
         *
         * @return void 
         * @static 
         */ 
        public static function assertNothingSent()
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        $instance->assertNothingSent();
        }
                    /**
         * Assert if a mailable was queued based on a truth-test callback.
         *
         * @param string|\Closure $mailable
         * @param callable|int|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertQueued($mailable, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        $instance->assertQueued($mailable, $callback);
        }
                    /**
         * Determine if a mailable was not queued based on a truth-test callback.
         *
         * @param string $mailable
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertNotQueued($mailable, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        $instance->assertNotQueued($mailable, $callback);
        }
                    /**
         * Assert that no mailables were queued.
         *
         * @return void 
         * @static 
         */ 
        public static function assertNothingQueued()
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        $instance->assertNothingQueued();
        }
                    /**
         * Get all of the mailables matching a truth-test callback.
         *
         * @param string $mailable
         * @param callable|null $callback
         * @return \Illuminate\Support\Collection 
         * @static 
         */ 
        public static function sent($mailable, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        return $instance->sent($mailable, $callback);
        }
                    /**
         * Determine if the given mailable has been sent.
         *
         * @param string $mailable
         * @return bool 
         * @static 
         */ 
        public static function hasSent($mailable)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        return $instance->hasSent($mailable);
        }
                    /**
         * Get all of the queued mailables matching a truth-test callback.
         *
         * @param string $mailable
         * @param callable|null $callback
         * @return \Illuminate\Support\Collection 
         * @static 
         */ 
        public static function queued($mailable, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        return $instance->queued($mailable, $callback);
        }
                    /**
         * Determine if the given mailable has been queued.
         *
         * @param string $mailable
         * @return bool 
         * @static 
         */ 
        public static function hasQueued($mailable)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        return $instance->hasQueued($mailable);
        }
                    /**
         * Begin the process of mailing a mailable class instance.
         *
         * @param mixed $users
         * @return \Illuminate\Mail\PendingMail 
         * @static 
         */ 
        public static function to($users)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        return $instance->to($users);
        }
                    /**
         * Begin the process of mailing a mailable class instance.
         *
         * @param mixed $users
         * @return \Illuminate\Mail\PendingMail 
         * @static 
         */ 
        public static function bcc($users)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        return $instance->bcc($users);
        }
                    /**
         * Send a new message with only a raw text part.
         *
         * @param string $text
         * @param \Closure|string $callback
         * @return void 
         * @static 
         */ 
        public static function raw($text, $callback)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        $instance->raw($text, $callback);
        }
                    /**
         * Send a new message using a view.
         *
         * @param \Illuminate\Contracts\Mail\Mailable|string|array $view
         * @param array $data
         * @param \Closure|string|null $callback
         * @return void 
         * @static 
         */ 
        public static function send($view, $data = [], $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        $instance->send($view, $data, $callback);
        }
                    /**
         * Queue a new e-mail message for sending.
         *
         * @param \Illuminate\Contracts\Mail\Mailable|string|array $view
         * @param string|null $queue
         * @return mixed 
         * @static 
         */ 
        public static function queue($view, $queue = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        return $instance->queue($view, $queue);
        }
                    /**
         * Queue a new e-mail message for sending after (n) seconds.
         *
         * @param \DateTimeInterface|\DateInterval|int $delay
         * @param \Illuminate\Contracts\Mail\Mailable|string|array $view
         * @param string|null $queue
         * @return mixed 
         * @static 
         */ 
        public static function later($delay, $view, $queue = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        return $instance->later($delay, $view, $queue);
        }
                    /**
         * Get the array of failed recipients.
         *
         * @return array 
         * @static 
         */ 
        public static function failures()
        {
                        /** @var \Illuminate\Support\Testing\Fakes\MailFake $instance */
                        return $instance->failures();
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Notifications\ChannelManager
     */ 
        class Notification {
                    /**
         * Send the given notification to the given notifiable entities.
         *
         * @param \Illuminate\Support\Collection|array|mixed $notifiables
         * @param mixed $notification
         * @return void 
         * @static 
         */ 
        public static function send($notifiables, $notification)
        {
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        $instance->send($notifiables, $notification);
        }
                    /**
         * Send the given notification immediately.
         *
         * @param \Illuminate\Support\Collection|array|mixed $notifiables
         * @param mixed $notification
         * @param array|null $channels
         * @return void 
         * @static 
         */ 
        public static function sendNow($notifiables, $notification, $channels = null)
        {
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        $instance->sendNow($notifiables, $notification, $channels);
        }
                    /**
         * Get a channel instance.
         *
         * @param string|null $name
         * @return mixed 
         * @static 
         */ 
        public static function channel($name = null)
        {
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        return $instance->channel($name);
        }
                    /**
         * Get the default channel driver name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultDriver()
        {
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        return $instance->getDefaultDriver();
        }
                    /**
         * Get the default channel driver name.
         *
         * @return string 
         * @static 
         */ 
        public static function deliversVia()
        {
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        return $instance->deliversVia();
        }
                    /**
         * Set the default channel driver name.
         *
         * @param string $channel
         * @return void 
         * @static 
         */ 
        public static function deliverVia($channel)
        {
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        $instance->deliverVia($channel);
        }
                    /**
         * Set the locale of notifications.
         *
         * @param string $locale
         * @return \Illuminate\Notifications\ChannelManager 
         * @static 
         */ 
        public static function locale($locale)
        {
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        return $instance->locale($locale);
        }
                    /**
         * Get a driver instance.
         *
         * @param string|null $driver
         * @return mixed 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function driver($driver = null)
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        return $instance->driver($driver);
        }
                    /**
         * Register a custom driver creator Closure.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return \Illuminate\Notifications\ChannelManager 
         * @static 
         */ 
        public static function extend($driver, $callback)
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        return $instance->extend($driver, $callback);
        }
                    /**
         * Get all of the created "drivers".
         *
         * @return array 
         * @static 
         */ 
        public static function getDrivers()
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        return $instance->getDrivers();
        }
                    /**
         * Get the container instance used by the manager.
         *
         * @return \Illuminate\Contracts\Container\Container 
         * @static 
         */ 
        public static function getContainer()
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        return $instance->getContainer();
        }
                    /**
         * Set the container instance used by the manager.
         *
         * @param \Illuminate\Contracts\Container\Container $container
         * @return \Illuminate\Notifications\ChannelManager 
         * @static 
         */ 
        public static function setContainer($container)
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        return $instance->setContainer($container);
        }
                    /**
         * Forget all of the resolved driver instances.
         *
         * @return \Illuminate\Notifications\ChannelManager 
         * @static 
         */ 
        public static function forgetDrivers()
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Notifications\ChannelManager $instance */
                        return $instance->forgetDrivers();
        }
                    /**
         * Assert if a notification was sent based on a truth-test callback.
         *
         * @param mixed $notifiable
         * @param string|\Closure $notification
         * @param callable|null $callback
         * @return void 
         * @throws \Exception
         * @static 
         */ 
        public static function assertSentTo($notifiable, $notification, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\NotificationFake $instance */
                        $instance->assertSentTo($notifiable, $notification, $callback);
        }
                    /**
         * Assert if a notification was sent a number of times.
         *
         * @param mixed $notifiable
         * @param string $notification
         * @param int $times
         * @return void 
         * @static 
         */ 
        public static function assertSentToTimes($notifiable, $notification, $times = 1)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\NotificationFake $instance */
                        $instance->assertSentToTimes($notifiable, $notification, $times);
        }
                    /**
         * Determine if a notification was sent based on a truth-test callback.
         *
         * @param mixed $notifiable
         * @param string|\Closure $notification
         * @param callable|null $callback
         * @return void 
         * @throws \Exception
         * @static 
         */ 
        public static function assertNotSentTo($notifiable, $notification, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\NotificationFake $instance */
                        $instance->assertNotSentTo($notifiable, $notification, $callback);
        }
                    /**
         * Assert that no notifications were sent.
         *
         * @return void 
         * @static 
         */ 
        public static function assertNothingSent()
        {
                        /** @var \Illuminate\Support\Testing\Fakes\NotificationFake $instance */
                        $instance->assertNothingSent();
        }
                    /**
         * Assert the total amount of times a notification was sent.
         *
         * @param int $expectedCount
         * @param string $notification
         * @return void 
         * @static 
         */ 
        public static function assertTimesSent($expectedCount, $notification)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\NotificationFake $instance */
                        $instance->assertTimesSent($expectedCount, $notification);
        }
                    /**
         * Get all of the notifications matching a truth-test callback.
         *
         * @param mixed $notifiable
         * @param string $notification
         * @param callable|null $callback
         * @return \Illuminate\Support\Collection 
         * @static 
         */ 
        public static function sent($notifiable, $notification, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\NotificationFake $instance */
                        return $instance->sent($notifiable, $notification, $callback);
        }
                    /**
         * Determine if there are more notifications left to inspect.
         *
         * @param mixed $notifiable
         * @param string $notification
         * @return bool 
         * @static 
         */ 
        public static function hasSent($notifiable, $notification)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\NotificationFake $instance */
                        return $instance->hasSent($notifiable, $notification);
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Support\Testing\Fakes\NotificationFake::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Support\Testing\Fakes\NotificationFake::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Support\Testing\Fakes\NotificationFake::hasMacro($name);
        }
         
    }
            /**
     * 
     *
     * @method static mixed reset(array $credentials, \Closure $callback)
     * @method static string sendResetLink(array $credentials, \Closure $callback = null)
     * @method static \Illuminate\Contracts\Auth\CanResetPassword getUser(array $credentials)
     * @method static string createToken(\Illuminate\Contracts\Auth\CanResetPassword $user)
     * @method static void deleteToken(\Illuminate\Contracts\Auth\CanResetPassword $user)
     * @method static bool tokenExists(\Illuminate\Contracts\Auth\CanResetPassword $user, string $token)
     * @method static \Illuminate\Auth\Passwords\TokenRepositoryInterface getRepository()
     * @see \Illuminate\Auth\Passwords\PasswordBroker
     */ 
        class Password {
                    /**
         * Attempt to get the broker from the local cache.
         *
         * @param string|null $name
         * @return \Illuminate\Contracts\Auth\PasswordBroker 
         * @static 
         */ 
        public static function broker($name = null)
        {
                        /** @var \Illuminate\Auth\Passwords\PasswordBrokerManager $instance */
                        return $instance->broker($name);
        }
                    /**
         * Get the default password broker name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultDriver()
        {
                        /** @var \Illuminate\Auth\Passwords\PasswordBrokerManager $instance */
                        return $instance->getDefaultDriver();
        }
                    /**
         * Set the default password broker name.
         *
         * @param string $name
         * @return void 
         * @static 
         */ 
        public static function setDefaultDriver($name)
        {
                        /** @var \Illuminate\Auth\Passwords\PasswordBrokerManager $instance */
                        $instance->setDefaultDriver($name);
        }
         
    }
            /**
     * 
     *
     * @method static void popUsing(string $workerName, callable $callback)
     * @see \Illuminate\Queue\QueueManager
     * @see \Illuminate\Queue\Queue
     */ 
        class Queue {
                    /**
         * Register an event listener for the before job event.
         *
         * @param mixed $callback
         * @return void 
         * @static 
         */ 
        public static function before($callback)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        $instance->before($callback);
        }
                    /**
         * Register an event listener for the after job event.
         *
         * @param mixed $callback
         * @return void 
         * @static 
         */ 
        public static function after($callback)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        $instance->after($callback);
        }
                    /**
         * Register an event listener for the exception occurred job event.
         *
         * @param mixed $callback
         * @return void 
         * @static 
         */ 
        public static function exceptionOccurred($callback)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        $instance->exceptionOccurred($callback);
        }
                    /**
         * Register an event listener for the daemon queue loop.
         *
         * @param mixed $callback
         * @return void 
         * @static 
         */ 
        public static function looping($callback)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        $instance->looping($callback);
        }
                    /**
         * Register an event listener for the failed job event.
         *
         * @param mixed $callback
         * @return void 
         * @static 
         */ 
        public static function failing($callback)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        $instance->failing($callback);
        }
                    /**
         * Register an event listener for the daemon queue stopping.
         *
         * @param mixed $callback
         * @return void 
         * @static 
         */ 
        public static function stopping($callback)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        $instance->stopping($callback);
        }
                    /**
         * Determine if the driver is connected.
         *
         * @param string|null $name
         * @return bool 
         * @static 
         */ 
        public static function connected($name = null)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        return $instance->connected($name);
        }
                    /**
         * Resolve a queue connection instance.
         *
         * @param string|null $name
         * @return \Illuminate\Contracts\Queue\Queue 
         * @static 
         */ 
        public static function connection($name = null)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        return $instance->connection($name);
        }
                    /**
         * Add a queue connection resolver.
         *
         * @param string $driver
         * @param \Closure $resolver
         * @return void 
         * @static 
         */ 
        public static function extend($driver, $resolver)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        $instance->extend($driver, $resolver);
        }
                    /**
         * Add a queue connection resolver.
         *
         * @param string $driver
         * @param \Closure $resolver
         * @return void 
         * @static 
         */ 
        public static function addConnector($driver, $resolver)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        $instance->addConnector($driver, $resolver);
        }
                    /**
         * Get the name of the default queue connection.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultDriver()
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        return $instance->getDefaultDriver();
        }
                    /**
         * Set the name of the default queue connection.
         *
         * @param string $name
         * @return void 
         * @static 
         */ 
        public static function setDefaultDriver($name)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        $instance->setDefaultDriver($name);
        }
                    /**
         * Get the full name for the given connection.
         *
         * @param string|null $connection
         * @return string 
         * @static 
         */ 
        public static function getName($connection = null)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        return $instance->getName($connection);
        }
                    /**
         * Get the application instance used by the manager.
         *
         * @return \Illuminate\Contracts\Foundation\Application 
         * @static 
         */ 
        public static function getApplication()
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        return $instance->getApplication();
        }
                    /**
         * Set the application instance used by the manager.
         *
         * @param \Illuminate\Contracts\Foundation\Application $app
         * @return \Illuminate\Queue\QueueManager 
         * @static 
         */ 
        public static function setApplication($app)
        {
                        /** @var \Illuminate\Queue\QueueManager $instance */
                        return $instance->setApplication($app);
        }
                    /**
         * Assert if a job was pushed based on a truth-test callback.
         *
         * @param string|\Closure $job
         * @param callable|int|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertPushed($job, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        $instance->assertPushed($job, $callback);
        }
                    /**
         * Assert if a job was pushed based on a truth-test callback.
         *
         * @param string $queue
         * @param string|\Closure $job
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertPushedOn($queue, $job, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        $instance->assertPushedOn($queue, $job, $callback);
        }
                    /**
         * Assert if a job was pushed with chained jobs based on a truth-test callback.
         *
         * @param string $job
         * @param array $expectedChain
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertPushedWithChain($job, $expectedChain = [], $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        $instance->assertPushedWithChain($job, $expectedChain, $callback);
        }
                    /**
         * Assert if a job was pushed with an empty chain based on a truth-test callback.
         *
         * @param string $job
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertPushedWithoutChain($job, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        $instance->assertPushedWithoutChain($job, $callback);
        }
                    /**
         * Determine if a job was pushed based on a truth-test callback.
         *
         * @param string|\Closure $job
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function assertNotPushed($job, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        $instance->assertNotPushed($job, $callback);
        }
                    /**
         * Assert that no jobs were pushed.
         *
         * @return void 
         * @static 
         */ 
        public static function assertNothingPushed()
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        $instance->assertNothingPushed();
        }
                    /**
         * Get all of the jobs matching a truth-test callback.
         *
         * @param string $job
         * @param callable|null $callback
         * @return \Illuminate\Support\Collection 
         * @static 
         */ 
        public static function pushed($job, $callback = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->pushed($job, $callback);
        }
                    /**
         * Determine if there are any stored jobs for a given class.
         *
         * @param string $job
         * @return bool 
         * @static 
         */ 
        public static function hasPushed($job)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->hasPushed($job);
        }
                    /**
         * Get the size of the queue.
         *
         * @param string|null $queue
         * @return int 
         * @static 
         */ 
        public static function size($queue = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->size($queue);
        }
                    /**
         * Push a new job onto the queue.
         *
         * @param string $job
         * @param mixed $data
         * @param string|null $queue
         * @return mixed 
         * @static 
         */ 
        public static function push($job, $data = '', $queue = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->push($job, $data, $queue);
        }
                    /**
         * Push a raw payload onto the queue.
         *
         * @param string $payload
         * @param string|null $queue
         * @param array $options
         * @return mixed 
         * @static 
         */ 
        public static function pushRaw($payload, $queue = null, $options = [])
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->pushRaw($payload, $queue, $options);
        }
                    /**
         * Push a new job onto the queue after a delay.
         *
         * @param \DateTimeInterface|\DateInterval|int $delay
         * @param string $job
         * @param mixed $data
         * @param string|null $queue
         * @return mixed 
         * @static 
         */ 
        public static function later($delay, $job, $data = '', $queue = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->later($delay, $job, $data, $queue);
        }
                    /**
         * Push a new job onto the queue.
         *
         * @param string $queue
         * @param string $job
         * @param mixed $data
         * @return mixed 
         * @static 
         */ 
        public static function pushOn($queue, $job, $data = '')
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->pushOn($queue, $job, $data);
        }
                    /**
         * Push a new job onto the queue after a delay.
         *
         * @param string $queue
         * @param \DateTimeInterface|\DateInterval|int $delay
         * @param string $job
         * @param mixed $data
         * @return mixed 
         * @static 
         */ 
        public static function laterOn($queue, $delay, $job, $data = '')
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->laterOn($queue, $delay, $job, $data);
        }
                    /**
         * Pop the next job off of the queue.
         *
         * @param string|null $queue
         * @return \Illuminate\Contracts\Queue\Job|null 
         * @static 
         */ 
        public static function pop($queue = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->pop($queue);
        }
                    /**
         * Push an array of jobs onto the queue.
         *
         * @param array $jobs
         * @param mixed $data
         * @param string|null $queue
         * @return mixed 
         * @static 
         */ 
        public static function bulk($jobs, $data = '', $queue = null)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->bulk($jobs, $data, $queue);
        }
                    /**
         * Get the jobs that have been pushed.
         *
         * @return array 
         * @static 
         */ 
        public static function pushedJobs()
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->pushedJobs();
        }
                    /**
         * Get the connection name for the queue.
         *
         * @return string 
         * @static 
         */ 
        public static function getConnectionName()
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->getConnectionName();
        }
                    /**
         * Set the connection name for the queue.
         *
         * @param string $name
         * @return \Illuminate\Support\Testing\Fakes\QueueFake 
         * @static 
         */ 
        public static function setConnectionName($name)
        {
                        /** @var \Illuminate\Support\Testing\Fakes\QueueFake $instance */
                        return $instance->setConnectionName($name);
        }
                    /**
         * Release a reserved job back onto the queue.
         *
         * @param string $queue
         * @param \Illuminate\Queue\Jobs\DatabaseJobRecord $job
         * @param int $delay
         * @return mixed 
         * @static 
         */ 
        public static function release($queue, $job, $delay)
        {
                        /** @var \Illuminate\Queue\DatabaseQueue $instance */
                        return $instance->release($queue, $job, $delay);
        }
                    /**
         * Delete a reserved job from the queue.
         *
         * @param string $queue
         * @param string $id
         * @return void 
         * @throws \Throwable
         * @static 
         */ 
        public static function deleteReserved($queue, $id)
        {
                        /** @var \Illuminate\Queue\DatabaseQueue $instance */
                        $instance->deleteReserved($queue, $id);
        }
                    /**
         * Delete a reserved job from the reserved queue and release it.
         *
         * @param string $queue
         * @param \Illuminate\Queue\Jobs\DatabaseJob $job
         * @param int $delay
         * @return void 
         * @static 
         */ 
        public static function deleteAndRelease($queue, $job, $delay)
        {
                        /** @var \Illuminate\Queue\DatabaseQueue $instance */
                        $instance->deleteAndRelease($queue, $job, $delay);
        }
                    /**
         * Delete all of the jobs from the queue.
         *
         * @param string $queue
         * @return int 
         * @static 
         */ 
        public static function clear($queue)
        {
                        /** @var \Illuminate\Queue\DatabaseQueue $instance */
                        return $instance->clear($queue);
        }
                    /**
         * Get the queue or return the default.
         *
         * @param string|null $queue
         * @return string 
         * @static 
         */ 
        public static function getQueue($queue)
        {
                        /** @var \Illuminate\Queue\DatabaseQueue $instance */
                        return $instance->getQueue($queue);
        }
                    /**
         * Get the underlying database instance.
         *
         * @return \Illuminate\Database\Connection 
         * @static 
         */ 
        public static function getDatabase()
        {
                        /** @var \Illuminate\Queue\DatabaseQueue $instance */
                        return $instance->getDatabase();
        }
                    /**
         * Get the backoff for an object-based queue handler.
         *
         * @param mixed $job
         * @return mixed 
         * @static 
         */ 
        public static function getJobBackoff($job)
        {            //Method inherited from \Illuminate\Queue\Queue         
                        /** @var \Illuminate\Queue\DatabaseQueue $instance */
                        return $instance->getJobBackoff($job);
        }
                    /**
         * Get the expiration timestamp for an object-based queue handler.
         *
         * @param mixed $job
         * @return mixed 
         * @static 
         */ 
        public static function getJobExpiration($job)
        {            //Method inherited from \Illuminate\Queue\Queue         
                        /** @var \Illuminate\Queue\DatabaseQueue $instance */
                        return $instance->getJobExpiration($job);
        }
                    /**
         * Register a callback to be executed when creating job payloads.
         *
         * @param callable|null $callback
         * @return void 
         * @static 
         */ 
        public static function createPayloadUsing($callback)
        {            //Method inherited from \Illuminate\Queue\Queue         
                        \Illuminate\Queue\DatabaseQueue::createPayloadUsing($callback);
        }
                    /**
         * Get the container instance being used by the connection.
         *
         * @return \Illuminate\Container\Container 
         * @static 
         */ 
        public static function getContainer()
        {            //Method inherited from \Illuminate\Queue\Queue         
                        /** @var \Illuminate\Queue\DatabaseQueue $instance */
                        return $instance->getContainer();
        }
                    /**
         * Set the IoC container instance.
         *
         * @param \Illuminate\Container\Container $container
         * @return void 
         * @static 
         */ 
        public static function setContainer($container)
        {            //Method inherited from \Illuminate\Queue\Queue         
                        /** @var \Illuminate\Queue\DatabaseQueue $instance */
                        $instance->setContainer($container);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Routing\Redirector
     */ 
        class Redirect {
                    /**
         * Create a new redirect response to the "home" route.
         *
         * @param int $status
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function home($status = 302)
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->home($status);
        }
                    /**
         * Create a new redirect response to the previous location.
         *
         * @param int $status
         * @param array $headers
         * @param mixed $fallback
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function back($status = 302, $headers = [], $fallback = false)
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->back($status, $headers, $fallback);
        }
                    /**
         * Create a new redirect response to the current URI.
         *
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function refresh($status = 302, $headers = [])
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->refresh($status, $headers);
        }
                    /**
         * Create a new redirect response, while putting the current URL in the session.
         *
         * @param string $path
         * @param int $status
         * @param array $headers
         * @param bool|null $secure
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function guest($path, $status = 302, $headers = [], $secure = null)
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->guest($path, $status, $headers, $secure);
        }
                    /**
         * Create a new redirect response to the previously intended location.
         *
         * @param string $default
         * @param int $status
         * @param array $headers
         * @param bool|null $secure
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function intended($default = '/', $status = 302, $headers = [], $secure = null)
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->intended($default, $status, $headers, $secure);
        }
                    /**
         * Set the intended url.
         *
         * @param string $url
         * @return void 
         * @static 
         */ 
        public static function setIntendedUrl($url)
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        $instance->setIntendedUrl($url);
        }
                    /**
         * Create a new redirect response to the given path.
         *
         * @param string $path
         * @param int $status
         * @param array $headers
         * @param bool|null $secure
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function to($path, $status = 302, $headers = [], $secure = null)
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->to($path, $status, $headers, $secure);
        }
                    /**
         * Create a new redirect response to an external URL (no validation).
         *
         * @param string $path
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function away($path, $status = 302, $headers = [])
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->away($path, $status, $headers);
        }
                    /**
         * Create a new redirect response to the given HTTPS path.
         *
         * @param string $path
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function secure($path, $status = 302, $headers = [])
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->secure($path, $status, $headers);
        }
                    /**
         * Create a new redirect response to a named route.
         *
         * @param string $route
         * @param mixed $parameters
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function route($route, $parameters = [], $status = 302, $headers = [])
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->route($route, $parameters, $status, $headers);
        }
                    /**
         * Create a new redirect response to a signed named route.
         *
         * @param string $route
         * @param mixed $parameters
         * @param \DateTimeInterface|\DateInterval|int|null $expiration
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function signedRoute($route, $parameters = [], $expiration = null, $status = 302, $headers = [])
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->signedRoute($route, $parameters, $expiration, $status, $headers);
        }
                    /**
         * Create a new redirect response to a signed named route.
         *
         * @param string $route
         * @param \DateTimeInterface|\DateInterval|int|null $expiration
         * @param mixed $parameters
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function temporarySignedRoute($route, $expiration, $parameters = [], $status = 302, $headers = [])
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->temporarySignedRoute($route, $expiration, $parameters, $status, $headers);
        }
                    /**
         * Create a new redirect response to a controller action.
         *
         * @param string|array $action
         * @param mixed $parameters
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function action($action, $parameters = [], $status = 302, $headers = [])
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->action($action, $parameters, $status, $headers);
        }
                    /**
         * Get the URL generator instance.
         *
         * @return \Illuminate\Routing\UrlGenerator 
         * @static 
         */ 
        public static function getUrlGenerator()
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        return $instance->getUrlGenerator();
        }
                    /**
         * Set the active session store.
         *
         * @param \Illuminate\Session\Store $session
         * @return void 
         * @static 
         */ 
        public static function setSession($session)
        {
                        /** @var \Illuminate\Routing\Redirector $instance */
                        $instance->setSession($session);
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Routing\Redirector::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Routing\Redirector::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Routing\Redirector::hasMacro($name);
        }
         
    }
            /**
     * 
     *
     * @method static \Illuminate\Redis\Limiters\ConcurrencyLimiterBuilder funnel(string $name)
     * @method static \Illuminate\Redis\Limiters\DurationLimiterBuilder throttle(string $name)
     * @see \Illuminate\Redis\RedisManager
     * @see \Illuminate\Contracts\Redis\Factory
     */ 
        class Redis {
                    /**
         * Get a Redis connection by name.
         *
         * @param string|null $name
         * @return \Illuminate\Redis\Connections\Connection 
         * @static 
         */ 
        public static function connection($name = null)
        {
                        /** @var \Illuminate\Redis\RedisManager $instance */
                        return $instance->connection($name);
        }
                    /**
         * Resolve the given connection by name.
         *
         * @param string|null $name
         * @return \Illuminate\Redis\Connections\Connection 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function resolve($name = null)
        {
                        /** @var \Illuminate\Redis\RedisManager $instance */
                        return $instance->resolve($name);
        }
                    /**
         * Return all of the created connections.
         *
         * @return array 
         * @static 
         */ 
        public static function connections()
        {
                        /** @var \Illuminate\Redis\RedisManager $instance */
                        return $instance->connections();
        }
                    /**
         * Enable the firing of Redis command events.
         *
         * @return void 
         * @static 
         */ 
        public static function enableEvents()
        {
                        /** @var \Illuminate\Redis\RedisManager $instance */
                        $instance->enableEvents();
        }
                    /**
         * Disable the firing of Redis command events.
         *
         * @return void 
         * @static 
         */ 
        public static function disableEvents()
        {
                        /** @var \Illuminate\Redis\RedisManager $instance */
                        $instance->disableEvents();
        }
                    /**
         * Set the default driver.
         *
         * @param string $driver
         * @return void 
         * @static 
         */ 
        public static function setDriver($driver)
        {
                        /** @var \Illuminate\Redis\RedisManager $instance */
                        $instance->setDriver($driver);
        }
                    /**
         * Disconnect the given connection and remove from local cache.
         *
         * @param string|null $name
         * @return void 
         * @static 
         */ 
        public static function purge($name = null)
        {
                        /** @var \Illuminate\Redis\RedisManager $instance */
                        $instance->purge($name);
        }
                    /**
         * Register a custom driver creator Closure.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return \Illuminate\Redis\RedisManager 
         * @static 
         */ 
        public static function extend($driver, $callback)
        {
                        /** @var \Illuminate\Redis\RedisManager $instance */
                        return $instance->extend($driver, $callback);
        }
         
    }
            /**
     * 
     *
     * @method static mixed filterFiles(mixed $files)
     * @see \Illuminate\Http\Request
     */ 
        class Request {
                    /**
         * Create a new Illuminate HTTP request from server variables.
         *
         * @return static 
         * @static 
         */ 
        public static function capture()
        {
                        return \Illuminate\Http\Request::capture();
        }
                    /**
         * Return the Request instance.
         *
         * @return \Illuminate\Http\Request 
         * @static 
         */ 
        public static function instance()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->instance();
        }
                    /**
         * Get the request method.
         *
         * @return string 
         * @static 
         */ 
        public static function method()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->method();
        }
                    /**
         * Get the root URL for the application.
         *
         * @return string 
         * @static 
         */ 
        public static function root()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->root();
        }
                    /**
         * Get the URL (no query string) for the request.
         *
         * @return string 
         * @static 
         */ 
        public static function url()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->url();
        }
                    /**
         * Get the full URL for the request.
         *
         * @return string 
         * @static 
         */ 
        public static function fullUrl()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->fullUrl();
        }
                    /**
         * Get the full URL for the request with the added query string parameters.
         *
         * @param array $query
         * @return string 
         * @static 
         */ 
        public static function fullUrlWithQuery($query)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->fullUrlWithQuery($query);
        }
                    /**
         * Get the current path info for the request.
         *
         * @return string 
         * @static 
         */ 
        public static function path()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->path();
        }
                    /**
         * Get the current decoded path info for the request.
         *
         * @return string 
         * @static 
         */ 
        public static function decodedPath()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->decodedPath();
        }
                    /**
         * Get a segment from the URI (1 based index).
         *
         * @param int $index
         * @param string|null $default
         * @return string|null 
         * @static 
         */ 
        public static function segment($index, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->segment($index, $default);
        }
                    /**
         * Get all of the segments for the request path.
         *
         * @return array 
         * @static 
         */ 
        public static function segments()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->segments();
        }
                    /**
         * Determine if the current request URI matches a pattern.
         *
         * @param mixed $patterns
         * @return bool 
         * @static 
         */ 
        public static function is(...$patterns)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->is(...$patterns);
        }
                    /**
         * Determine if the route name matches a given pattern.
         *
         * @param mixed $patterns
         * @return bool 
         * @static 
         */ 
        public static function routeIs(...$patterns)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->routeIs(...$patterns);
        }
                    /**
         * Determine if the current request URL and query string match a pattern.
         *
         * @param mixed $patterns
         * @return bool 
         * @static 
         */ 
        public static function fullUrlIs(...$patterns)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->fullUrlIs(...$patterns);
        }
                    /**
         * Determine if the request is the result of an AJAX call.
         *
         * @return bool 
         * @static 
         */ 
        public static function ajax()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->ajax();
        }
                    /**
         * Determine if the request is the result of a PJAX call.
         *
         * @return bool 
         * @static 
         */ 
        public static function pjax()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->pjax();
        }
                    /**
         * Determine if the request is the result of a prefetch call.
         *
         * @return bool 
         * @static 
         */ 
        public static function prefetch()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->prefetch();
        }
                    /**
         * Determine if the request is over HTTPS.
         *
         * @return bool 
         * @static 
         */ 
        public static function secure()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->secure();
        }
                    /**
         * Get the client IP address.
         *
         * @return string|null 
         * @static 
         */ 
        public static function ip()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->ip();
        }
                    /**
         * Get the client IP addresses.
         *
         * @return array 
         * @static 
         */ 
        public static function ips()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->ips();
        }
                    /**
         * Get the client user agent.
         *
         * @return string|null 
         * @static 
         */ 
        public static function userAgent()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->userAgent();
        }
                    /**
         * Merge new input into the current request's input array.
         *
         * @param array $input
         * @return \Illuminate\Http\Request 
         * @static 
         */ 
        public static function merge($input)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->merge($input);
        }
                    /**
         * Replace the input for the current request.
         *
         * @param array $input
         * @return \Illuminate\Http\Request 
         * @static 
         */ 
        public static function replace($input)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->replace($input);
        }
                    /**
         * This method belongs to Symfony HttpFoundation and is not usually needed when using Laravel.
         * 
         * Instead, you may use the "input" method.
         *
         * @param string $key
         * @param mixed $default
         * @return mixed 
         * @static 
         */ 
        public static function get($key, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->get($key, $default);
        }
                    /**
         * Get the JSON payload for the request.
         *
         * @param string|null $key
         * @param mixed $default
         * @return \Symfony\Component\HttpFoundation\ParameterBag|mixed 
         * @static 
         */ 
        public static function json($key = null, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->json($key, $default);
        }
                    /**
         * Create a new request instance from the given Laravel request.
         *
         * @param \Illuminate\Http\Request $from
         * @param \Illuminate\Http\Request|null $to
         * @return static 
         * @static 
         */ 
        public static function createFrom($from, $to = null)
        {
                        return \Illuminate\Http\Request::createFrom($from, $to);
        }
                    /**
         * Create an Illuminate request from a Symfony instance.
         *
         * @param \Symfony\Component\HttpFoundation\Request $request
         * @return static 
         * @static 
         */ 
        public static function createFromBase($request)
        {
                        return \Illuminate\Http\Request::createFromBase($request);
        }
                    /**
         * Clones a request and overrides some of its parameters.
         *
         * @param array $query The GET parameters
         * @param array $request The POST parameters
         * @param array $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
         * @param array $cookies The COOKIE parameters
         * @param array $files The FILES parameters
         * @param array $server The SERVER parameters
         * @return static 
         * @static 
         */ 
        public static function duplicate($query = null, $request = null, $attributes = null, $cookies = null, $files = null, $server = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->duplicate($query, $request, $attributes, $cookies, $files, $server);
        }
                    /**
         * Get the session associated with the request.
         *
         * @return \Illuminate\Session\Store 
         * @throws \RuntimeException
         * @static 
         */ 
        public static function session()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->session();
        }
                    /**
         * Get the session associated with the request.
         *
         * @return \Illuminate\Session\Store|null 
         * @static 
         */ 
        public static function getSession()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getSession();
        }
                    /**
         * Set the session instance on the request.
         *
         * @param \Illuminate\Contracts\Session\Session $session
         * @return void 
         * @static 
         */ 
        public static function setLaravelSession($session)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        $instance->setLaravelSession($session);
        }
                    /**
         * Get the user making the request.
         *
         * @param string|null $guard
         * @return mixed 
         * @static 
         */ 
        public static function user($guard = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->user($guard);
        }
                    /**
         * Get the route handling the request.
         *
         * @param string|null $param
         * @param mixed $default
         * @return \Illuminate\Routing\Route|object|string|null 
         * @static 
         */ 
        public static function route($param = null, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->route($param, $default);
        }
                    /**
         * Get a unique fingerprint for the request / route / IP address.
         *
         * @return string 
         * @throws \RuntimeException
         * @static 
         */ 
        public static function fingerprint()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->fingerprint();
        }
                    /**
         * Set the JSON payload for the request.
         *
         * @param \Symfony\Component\HttpFoundation\ParameterBag $json
         * @return \Illuminate\Http\Request 
         * @static 
         */ 
        public static function setJson($json)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->setJson($json);
        }
                    /**
         * Get the user resolver callback.
         *
         * @return \Closure 
         * @static 
         */ 
        public static function getUserResolver()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getUserResolver();
        }
                    /**
         * Set the user resolver callback.
         *
         * @param \Closure $callback
         * @return \Illuminate\Http\Request 
         * @static 
         */ 
        public static function setUserResolver($callback)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->setUserResolver($callback);
        }
                    /**
         * Get the route resolver callback.
         *
         * @return \Closure 
         * @static 
         */ 
        public static function getRouteResolver()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getRouteResolver();
        }
                    /**
         * Set the route resolver callback.
         *
         * @param \Closure $callback
         * @return \Illuminate\Http\Request 
         * @static 
         */ 
        public static function setRouteResolver($callback)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->setRouteResolver($callback);
        }
                    /**
         * Get all of the input and files for the request.
         *
         * @return array 
         * @static 
         */ 
        public static function toArray()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->toArray();
        }
                    /**
         * Determine if the given offset exists.
         *
         * @param string $offset
         * @return bool 
         * @static 
         */ 
        public static function offsetExists($offset)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->offsetExists($offset);
        }
                    /**
         * Get the value at the given offset.
         *
         * @param string $offset
         * @return mixed 
         * @static 
         */ 
        public static function offsetGet($offset)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->offsetGet($offset);
        }
                    /**
         * Set the value at the given offset.
         *
         * @param string $offset
         * @param mixed $value
         * @return void 
         * @static 
         */ 
        public static function offsetSet($offset, $value)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        $instance->offsetSet($offset, $value);
        }
                    /**
         * Remove the value at the given offset.
         *
         * @param string $offset
         * @return void 
         * @static 
         */ 
        public static function offsetUnset($offset)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        $instance->offsetUnset($offset);
        }
                    /**
         * Sets the parameters for this request.
         * 
         * This method also re-initializes all properties.
         *
         * @param array $query The GET parameters
         * @param array $request The POST parameters
         * @param array $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
         * @param array $cookies The COOKIE parameters
         * @param array $files The FILES parameters
         * @param array $server The SERVER parameters
         * @param string|resource|null $content The raw body data
         * @static 
         */ 
        public static function initialize($query = [], $request = [], $attributes = [], $cookies = [], $files = [], $server = [], $content = null)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
        }
                    /**
         * Creates a new request with values from PHP's super globals.
         *
         * @return static 
         * @static 
         */ 
        public static function createFromGlobals()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::createFromGlobals();
        }
                    /**
         * Creates a Request based on a given URI and configuration.
         * 
         * The information contained in the URI always take precedence
         * over the other information (server and parameters).
         *
         * @param string $uri The URI
         * @param string $method The HTTP method
         * @param array $parameters The query (GET) or request (POST) parameters
         * @param array $cookies The request cookies ($_COOKIE)
         * @param array $files The request files ($_FILES)
         * @param array $server The server parameters ($_SERVER)
         * @param string|resource|null $content The raw body data
         * @return static 
         * @static 
         */ 
        public static function create($uri, $method = 'GET', $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);
        }
                    /**
         * Sets a callable able to create a Request instance.
         * 
         * This is mainly useful when you need to override the Request class
         * to keep BC with an existing system. It should not be used for any
         * other purpose.
         *
         * @static 
         */ 
        public static function setFactory($callable)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::setFactory($callable);
        }
                    /**
         * Overrides the PHP global variables according to this request instance.
         * 
         * It overrides $_GET, $_POST, $_REQUEST, $_SERVER, $_COOKIE.
         * $_FILES is never overridden, see rfc1867
         *
         * @static 
         */ 
        public static function overrideGlobals()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->overrideGlobals();
        }
                    /**
         * Sets a list of trusted proxies.
         * 
         * You should only list the reverse proxies that you manage directly.
         *
         * @param array $proxies A list of trusted proxies, the string 'REMOTE_ADDR' will be replaced with $_SERVER['REMOTE_ADDR']
         * @param int $trustedHeaderSet A bit field of Request::HEADER_*, to set which headers to trust from your proxies
         * @static 
         */ 
        public static function setTrustedProxies($proxies, $trustedHeaderSet)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::setTrustedProxies($proxies, $trustedHeaderSet);
        }
                    /**
         * Gets the list of trusted proxies.
         *
         * @return array An array of trusted proxies
         * @static 
         */ 
        public static function getTrustedProxies()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::getTrustedProxies();
        }
                    /**
         * Gets the set of trusted headers from trusted proxies.
         *
         * @return int A bit field of Request::HEADER_* that defines which headers are trusted from your proxies
         * @static 
         */ 
        public static function getTrustedHeaderSet()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::getTrustedHeaderSet();
        }
                    /**
         * Sets a list of trusted host patterns.
         * 
         * You should only list the hosts you manage using regexs.
         *
         * @param array $hostPatterns A list of trusted host patterns
         * @static 
         */ 
        public static function setTrustedHosts($hostPatterns)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::setTrustedHosts($hostPatterns);
        }
                    /**
         * Gets the list of trusted host patterns.
         *
         * @return array An array of trusted host patterns
         * @static 
         */ 
        public static function getTrustedHosts()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::getTrustedHosts();
        }
                    /**
         * Normalizes a query string.
         * 
         * It builds a normalized query string, where keys/value pairs are alphabetized,
         * have consistent escaping and unneeded delimiters are removed.
         *
         * @return string A normalized query string for the Request
         * @static 
         */ 
        public static function normalizeQueryString($qs)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::normalizeQueryString($qs);
        }
                    /**
         * Enables support for the _method request parameter to determine the intended HTTP method.
         * 
         * Be warned that enabling this feature might lead to CSRF issues in your code.
         * Check that you are using CSRF tokens when required.
         * If the HTTP method parameter override is enabled, an html-form with method "POST" can be altered
         * and used to send a "PUT" or "DELETE" request via the _method request parameter.
         * If these methods are not protected against CSRF, this presents a possible vulnerability.
         * 
         * The HTTP method can only be overridden when the real HTTP method is POST.
         *
         * @static 
         */ 
        public static function enableHttpMethodParameterOverride()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::enableHttpMethodParameterOverride();
        }
                    /**
         * Checks whether support for the _method request parameter is enabled.
         *
         * @return bool True when the _method request parameter is enabled, false otherwise
         * @static 
         */ 
        public static function getHttpMethodParameterOverride()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::getHttpMethodParameterOverride();
        }
                    /**
         * Whether the request contains a Session which was started in one of the
         * previous requests.
         *
         * @return bool 
         * @static 
         */ 
        public static function hasPreviousSession()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->hasPreviousSession();
        }
                    /**
         * Whether the request contains a Session object.
         * 
         * This method does not give any information about the state of the session object,
         * like whether the session is started or not. It is just a way to check if this Request
         * is associated with a Session instance.
         *
         * @return bool true when the Request contains a Session object, false otherwise
         * @static 
         */ 
        public static function hasSession()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->hasSession();
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function setSession($session)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->setSession($session);
        }
                    /**
         * 
         *
         * @internal 
         * @static 
         */ 
        public static function setSessionFactory($factory)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->setSessionFactory($factory);
        }
                    /**
         * Returns the client IP addresses.
         * 
         * In the returned array the most trusted IP address is first, and the
         * least trusted one last. The "real" client IP address is the last one,
         * but this is also the least trusted one. Trusted proxies are stripped.
         * 
         * Use this method carefully; you should use getClientIp() instead.
         *
         * @return array The client IP addresses
         * @see getClientIp()
         * @static 
         */ 
        public static function getClientIps()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getClientIps();
        }
                    /**
         * Returns the client IP address.
         * 
         * This method can read the client IP address from the "X-Forwarded-For" header
         * when trusted proxies were set via "setTrustedProxies()". The "X-Forwarded-For"
         * header value is a comma+space separated list of IP addresses, the left-most
         * being the original client, and each successive proxy that passed the request
         * adding the IP address where it received the request from.
         * 
         * If your reverse proxy uses a different header name than "X-Forwarded-For",
         * ("Client-Ip" for instance), configure it via the $trustedHeaderSet
         * argument of the Request::setTrustedProxies() method instead.
         *
         * @return string|null The client IP address
         * @see getClientIps()
         * @see https://wikipedia.org/wiki/X-Forwarded-For
         * @static 
         */ 
        public static function getClientIp()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getClientIp();
        }
                    /**
         * Returns current script name.
         *
         * @return string 
         * @static 
         */ 
        public static function getScriptName()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getScriptName();
        }
                    /**
         * Returns the path being requested relative to the executed script.
         * 
         * The path info always starts with a /.
         * 
         * Suppose this request is instantiated from /mysite on localhost:
         * 
         *  * http://localhost/mysite              returns an empty string
         *  * http://localhost/mysite/about        returns '/about'
         *  * http://localhost/mysite/enco%20ded   returns '/enco%20ded'
         *  * http://localhost/mysite/about?var=1  returns '/about'
         *
         * @return string The raw path (i.e. not urldecoded)
         * @static 
         */ 
        public static function getPathInfo()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getPathInfo();
        }
                    /**
         * Returns the root path from which this request is executed.
         * 
         * Suppose that an index.php file instantiates this request object:
         * 
         *  * http://localhost/index.php         returns an empty string
         *  * http://localhost/index.php/page    returns an empty string
         *  * http://localhost/web/index.php     returns '/web'
         *  * http://localhost/we%20b/index.php  returns '/we%20b'
         *
         * @return string The raw path (i.e. not urldecoded)
         * @static 
         */ 
        public static function getBasePath()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getBasePath();
        }
                    /**
         * Returns the root URL from which this request is executed.
         * 
         * The base URL never ends with a /.
         * 
         * This is similar to getBasePath(), except that it also includes the
         * script filename (e.g. index.php) if one exists.
         *
         * @return string The raw URL (i.e. not urldecoded)
         * @static 
         */ 
        public static function getBaseUrl()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getBaseUrl();
        }
                    /**
         * Gets the request's scheme.
         *
         * @return string 
         * @static 
         */ 
        public static function getScheme()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getScheme();
        }
                    /**
         * Returns the port on which the request is made.
         * 
         * This method can read the client port from the "X-Forwarded-Port" header
         * when trusted proxies were set via "setTrustedProxies()".
         * 
         * The "X-Forwarded-Port" header must contain the client port.
         *
         * @return int|string can be a string if fetched from the server bag
         * @static 
         */ 
        public static function getPort()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getPort();
        }
                    /**
         * Returns the user.
         *
         * @return string|null 
         * @static 
         */ 
        public static function getUser()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getUser();
        }
                    /**
         * Returns the password.
         *
         * @return string|null 
         * @static 
         */ 
        public static function getPassword()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getPassword();
        }
                    /**
         * Gets the user info.
         *
         * @return string A user name and, optionally, scheme-specific information about how to gain authorization to access the server
         * @static 
         */ 
        public static function getUserInfo()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getUserInfo();
        }
                    /**
         * Returns the HTTP host being requested.
         * 
         * The port name will be appended to the host if it's non-standard.
         *
         * @return string 
         * @static 
         */ 
        public static function getHttpHost()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getHttpHost();
        }
                    /**
         * Returns the requested URI (path and query string).
         *
         * @return string The raw URI (i.e. not URI decoded)
         * @static 
         */ 
        public static function getRequestUri()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getRequestUri();
        }
                    /**
         * Gets the scheme and HTTP host.
         * 
         * If the URL was called with basic authentication, the user
         * and the password are not added to the generated string.
         *
         * @return string The scheme and HTTP host
         * @static 
         */ 
        public static function getSchemeAndHttpHost()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getSchemeAndHttpHost();
        }
                    /**
         * Generates a normalized URI (URL) for the Request.
         *
         * @return string A normalized URI (URL) for the Request
         * @see getQueryString()
         * @static 
         */ 
        public static function getUri()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getUri();
        }
                    /**
         * Generates a normalized URI for the given path.
         *
         * @param string $path A path to use instead of the current one
         * @return string The normalized URI for the path
         * @static 
         */ 
        public static function getUriForPath($path)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getUriForPath($path);
        }
                    /**
         * Returns the path as relative reference from the current Request path.
         * 
         * Only the URIs path component (no schema, host etc.) is relevant and must be given.
         * Both paths must be absolute and not contain relative parts.
         * Relative URLs from one resource to another are useful when generating self-contained downloadable document archives.
         * Furthermore, they can be used to reduce the link size in documents.
         * 
         * Example target paths, given a base path of "/a/b/c/d":
         * - "/a/b/c/d"     -> ""
         * - "/a/b/c/"      -> "./"
         * - "/a/b/"        -> "../"
         * - "/a/b/c/other" -> "other"
         * - "/a/x/y"       -> "../../x/y"
         *
         * @return string The relative target path
         * @static 
         */ 
        public static function getRelativeUriForPath($path)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getRelativeUriForPath($path);
        }
                    /**
         * Generates the normalized query string for the Request.
         * 
         * It builds a normalized query string, where keys/value pairs are alphabetized
         * and have consistent escaping.
         *
         * @return string|null A normalized query string for the Request
         * @static 
         */ 
        public static function getQueryString()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getQueryString();
        }
                    /**
         * Checks whether the request is secure or not.
         * 
         * This method can read the client protocol from the "X-Forwarded-Proto" header
         * when trusted proxies were set via "setTrustedProxies()".
         * 
         * The "X-Forwarded-Proto" header must contain the protocol: "https" or "http".
         *
         * @return bool 
         * @static 
         */ 
        public static function isSecure()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->isSecure();
        }
                    /**
         * Returns the host name.
         * 
         * This method can read the client host name from the "X-Forwarded-Host" header
         * when trusted proxies were set via "setTrustedProxies()".
         * 
         * The "X-Forwarded-Host" header must contain the client host name.
         *
         * @return string 
         * @throws SuspiciousOperationException when the host name is invalid or not trusted
         * @static 
         */ 
        public static function getHost()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getHost();
        }
                    /**
         * Sets the request method.
         *
         * @static 
         */ 
        public static function setMethod($method)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->setMethod($method);
        }
                    /**
         * Gets the request "intended" method.
         * 
         * If the X-HTTP-Method-Override header is set, and if the method is a POST,
         * then it is used to determine the "real" intended HTTP method.
         * 
         * The _method request parameter can also be used to determine the HTTP method,
         * but only if enableHttpMethodParameterOverride() has been called.
         * 
         * The method is always an uppercased string.
         *
         * @return string The request method
         * @see getRealMethod()
         * @static 
         */ 
        public static function getMethod()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getMethod();
        }
                    /**
         * Gets the "real" request method.
         *
         * @return string The request method
         * @see getMethod()
         * @static 
         */ 
        public static function getRealMethod()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getRealMethod();
        }
                    /**
         * Gets the mime type associated with the format.
         *
         * @return string|null The associated mime type (null if not found)
         * @static 
         */ 
        public static function getMimeType($format)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getMimeType($format);
        }
                    /**
         * Gets the mime types associated with the format.
         *
         * @return array The associated mime types
         * @static 
         */ 
        public static function getMimeTypes($format)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        return \Illuminate\Http\Request::getMimeTypes($format);
        }
                    /**
         * Gets the format associated with the mime type.
         *
         * @return string|null The format (null if not found)
         * @static 
         */ 
        public static function getFormat($mimeType)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getFormat($mimeType);
        }
                    /**
         * Associates a format with mime types.
         *
         * @param string|array $mimeTypes The associated mime types (the preferred one must be the first as it will be used as the content type)
         * @static 
         */ 
        public static function setFormat($format, $mimeTypes)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->setFormat($format, $mimeTypes);
        }
                    /**
         * Gets the request format.
         * 
         * Here is the process to determine the format:
         * 
         *  * format defined by the user (with setRequestFormat())
         *  * _format request attribute
         *  * $default
         *
         * @see getPreferredFormat
         * @return string|null The request format
         * @static 
         */ 
        public static function getRequestFormat($default = 'html')
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getRequestFormat($default);
        }
                    /**
         * Sets the request format.
         *
         * @static 
         */ 
        public static function setRequestFormat($format)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->setRequestFormat($format);
        }
                    /**
         * Gets the format associated with the request.
         *
         * @return string|null The format (null if no content type is present)
         * @static 
         */ 
        public static function getContentType()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getContentType();
        }
                    /**
         * Sets the default locale.
         *
         * @static 
         */ 
        public static function setDefaultLocale($locale)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->setDefaultLocale($locale);
        }
                    /**
         * Get the default locale.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultLocale()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getDefaultLocale();
        }
                    /**
         * Sets the locale.
         *
         * @static 
         */ 
        public static function setLocale($locale)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->setLocale($locale);
        }
                    /**
         * Get the locale.
         *
         * @return string 
         * @static 
         */ 
        public static function getLocale()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getLocale();
        }
                    /**
         * Checks if the request method is of specified type.
         *
         * @param string $method Uppercase request method (GET, POST etc)
         * @return bool 
         * @static 
         */ 
        public static function isMethod($method)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->isMethod($method);
        }
                    /**
         * Checks whether or not the method is safe.
         *
         * @see https://tools.ietf.org/html/rfc7231#section-4.2.1
         * @return bool 
         * @static 
         */ 
        public static function isMethodSafe()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->isMethodSafe();
        }
                    /**
         * Checks whether or not the method is idempotent.
         *
         * @return bool 
         * @static 
         */ 
        public static function isMethodIdempotent()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->isMethodIdempotent();
        }
                    /**
         * Checks whether the method is cacheable or not.
         *
         * @see https://tools.ietf.org/html/rfc7231#section-4.2.3
         * @return bool True for GET and HEAD, false otherwise
         * @static 
         */ 
        public static function isMethodCacheable()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->isMethodCacheable();
        }
                    /**
         * Returns the protocol version.
         * 
         * If the application is behind a proxy, the protocol version used in the
         * requests between the client and the proxy and between the proxy and the
         * server might be different. This returns the former (from the "Via" header)
         * if the proxy is trusted (see "setTrustedProxies()"), otherwise it returns
         * the latter (from the "SERVER_PROTOCOL" server parameter).
         *
         * @return string|null 
         * @static 
         */ 
        public static function getProtocolVersion()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getProtocolVersion();
        }
                    /**
         * Returns the request body content.
         *
         * @param bool $asResource If true, a resource will be returned
         * @return string|resource The request body content or a resource to read the body stream
         * @static 
         */ 
        public static function getContent($asResource = false)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getContent($asResource);
        }
                    /**
         * Gets the Etags.
         *
         * @return array The entity tags
         * @static 
         */ 
        public static function getETags()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getETags();
        }
                    /**
         * 
         *
         * @return bool 
         * @static 
         */ 
        public static function isNoCache()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->isNoCache();
        }
                    /**
         * Gets the preferred format for the response by inspecting, in the following order:
         *   * the request format set using setRequestFormat;
         *   * the values of the Accept HTTP header.
         * 
         * Note that if you use this method, you should send the "Vary: Accept" header
         * in the response to prevent any issues with intermediary HTTP caches.
         *
         * @static 
         */ 
        public static function getPreferredFormat($default = 'html')
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getPreferredFormat($default);
        }
                    /**
         * Returns the preferred language.
         *
         * @param string[] $locales An array of ordered available locales
         * @return string|null The preferred locale
         * @static 
         */ 
        public static function getPreferredLanguage($locales = null)
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getPreferredLanguage($locales);
        }
                    /**
         * Gets a list of languages acceptable by the client browser.
         *
         * @return array Languages ordered in the user browser preferences
         * @static 
         */ 
        public static function getLanguages()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getLanguages();
        }
                    /**
         * Gets a list of charsets acceptable by the client browser.
         *
         * @return array List of charsets in preferable order
         * @static 
         */ 
        public static function getCharsets()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getCharsets();
        }
                    /**
         * Gets a list of encodings acceptable by the client browser.
         *
         * @return array List of encodings in preferable order
         * @static 
         */ 
        public static function getEncodings()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getEncodings();
        }
                    /**
         * Gets a list of content types acceptable by the client browser.
         *
         * @return array List of content types in preferable order
         * @static 
         */ 
        public static function getAcceptableContentTypes()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->getAcceptableContentTypes();
        }
                    /**
         * Returns true if the request is an XMLHttpRequest.
         * 
         * It works if your JavaScript library sets an X-Requested-With HTTP header.
         * It is known to work with common JavaScript frameworks:
         *
         * @see https://wikipedia.org/wiki/List_of_Ajax_frameworks#JavaScript
         * @return bool true if the request is an XMLHttpRequest, false otherwise
         * @static 
         */ 
        public static function isXmlHttpRequest()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->isXmlHttpRequest();
        }
                    /**
         * Checks whether the client browser prefers safe content or not according to RFC8674.
         *
         * @see https://tools.ietf.org/html/rfc8674
         * @static 
         */ 
        public static function preferSafeContent()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->preferSafeContent();
        }
                    /**
         * Indicates whether this request originated from a trusted proxy.
         * 
         * This can be useful to determine whether or not to trust the
         * contents of a proxy-specific header.
         *
         * @return bool true if the request came from a trusted proxy, false otherwise
         * @static 
         */ 
        public static function isFromTrustedProxy()
        {            //Method inherited from \Symfony\Component\HttpFoundation\Request         
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->isFromTrustedProxy();
        }
                    /**
         * Determine if the request is sending JSON.
         *
         * @return bool 
         * @static 
         */ 
        public static function isJson()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->isJson();
        }
                    /**
         * Determine if the current request probably expects a JSON response.
         *
         * @return bool 
         * @static 
         */ 
        public static function expectsJson()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->expectsJson();
        }
                    /**
         * Determine if the current request is asking for JSON.
         *
         * @return bool 
         * @static 
         */ 
        public static function wantsJson()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->wantsJson();
        }
                    /**
         * Determines whether the current requests accepts a given content type.
         *
         * @param string|array $contentTypes
         * @return bool 
         * @static 
         */ 
        public static function accepts($contentTypes)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->accepts($contentTypes);
        }
                    /**
         * Return the most suitable content type from the given array based on content negotiation.
         *
         * @param string|array $contentTypes
         * @return string|null 
         * @static 
         */ 
        public static function prefers($contentTypes)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->prefers($contentTypes);
        }
                    /**
         * Determine if the current request accepts any content type.
         *
         * @return bool 
         * @static 
         */ 
        public static function acceptsAnyContentType()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->acceptsAnyContentType();
        }
                    /**
         * Determines whether a request accepts JSON.
         *
         * @return bool 
         * @static 
         */ 
        public static function acceptsJson()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->acceptsJson();
        }
                    /**
         * Determines whether a request accepts HTML.
         *
         * @return bool 
         * @static 
         */ 
        public static function acceptsHtml()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->acceptsHtml();
        }
                    /**
         * Determine if the given content types match.
         *
         * @param string $actual
         * @param string $type
         * @return bool 
         * @static 
         */ 
        public static function matchesType($actual, $type)
        {
                        return \Illuminate\Http\Request::matchesType($actual, $type);
        }
                    /**
         * Get the data format expected in the response.
         *
         * @param string $default
         * @return string 
         * @static 
         */ 
        public static function format($default = 'html')
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->format($default);
        }
                    /**
         * Retrieve an old input item.
         *
         * @param string|null $key
         * @param string|array|null $default
         * @return string|array|null 
         * @static 
         */ 
        public static function old($key = null, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->old($key, $default);
        }
                    /**
         * Flash the input for the current request to the session.
         *
         * @return void 
         * @static 
         */ 
        public static function flash()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        $instance->flash();
        }
                    /**
         * Flash only some of the input to the session.
         *
         * @param array|mixed $keys
         * @return void 
         * @static 
         */ 
        public static function flashOnly($keys)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        $instance->flashOnly($keys);
        }
                    /**
         * Flash only some of the input to the session.
         *
         * @param array|mixed $keys
         * @return void 
         * @static 
         */ 
        public static function flashExcept($keys)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        $instance->flashExcept($keys);
        }
                    /**
         * Flush all of the old input from the session.
         *
         * @return void 
         * @static 
         */ 
        public static function flush()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        $instance->flush();
        }
                    /**
         * Retrieve a server variable from the request.
         *
         * @param string|null $key
         * @param string|array|null $default
         * @return string|array|null 
         * @static 
         */ 
        public static function server($key = null, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->server($key, $default);
        }
                    /**
         * Determine if a header is set on the request.
         *
         * @param string $key
         * @return bool 
         * @static 
         */ 
        public static function hasHeader($key)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->hasHeader($key);
        }
                    /**
         * Retrieve a header from the request.
         *
         * @param string|null $key
         * @param string|array|null $default
         * @return string|array|null 
         * @static 
         */ 
        public static function header($key = null, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->header($key, $default);
        }
                    /**
         * Get the bearer token from the request headers.
         *
         * @return string|null 
         * @static 
         */ 
        public static function bearerToken()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->bearerToken();
        }
                    /**
         * Determine if the request contains a given input item key.
         *
         * @param string|array $key
         * @return bool 
         * @static 
         */ 
        public static function exists($key)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->exists($key);
        }
                    /**
         * Determine if the request contains a given input item key.
         *
         * @param string|array $key
         * @return bool 
         * @static 
         */ 
        public static function has($key)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->has($key);
        }
                    /**
         * Determine if the request contains any of the given inputs.
         *
         * @param string|array $keys
         * @return bool 
         * @static 
         */ 
        public static function hasAny($keys)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->hasAny($keys);
        }
                    /**
         * Apply the callback if the request contains the given input item key.
         *
         * @param string $key
         * @param callable $callback
         * @param callable|null $default
         * @return $this|mixed 
         * @static 
         */ 
        public static function whenHas($key, $callback, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->whenHas($key, $callback, $default);
        }
                    /**
         * Determine if the request contains a non-empty value for an input item.
         *
         * @param string|array $key
         * @return bool 
         * @static 
         */ 
        public static function filled($key)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->filled($key);
        }
                    /**
         * Determine if the request contains an empty value for an input item.
         *
         * @param string|array $key
         * @return bool 
         * @static 
         */ 
        public static function isNotFilled($key)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->isNotFilled($key);
        }
                    /**
         * Determine if the request contains a non-empty value for any of the given inputs.
         *
         * @param string|array $keys
         * @return bool 
         * @static 
         */ 
        public static function anyFilled($keys)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->anyFilled($keys);
        }
                    /**
         * Apply the callback if the request contains a non-empty value for the given input item key.
         *
         * @param string $key
         * @param callable $callback
         * @param callable|null $default
         * @return $this|mixed 
         * @static 
         */ 
        public static function whenFilled($key, $callback, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->whenFilled($key, $callback, $default);
        }
                    /**
         * Determine if the request is missing a given input item key.
         *
         * @param string|array $key
         * @return bool 
         * @static 
         */ 
        public static function missing($key)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->missing($key);
        }
                    /**
         * Get the keys for all of the input and files.
         *
         * @return array 
         * @static 
         */ 
        public static function keys()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->keys();
        }
                    /**
         * Get all of the input and files for the request.
         *
         * @param array|mixed|null $keys
         * @return array 
         * @static 
         */ 
        public static function all($keys = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->all($keys);
        }
                    /**
         * Retrieve an input item from the request.
         *
         * @param string|null $key
         * @param mixed $default
         * @return mixed 
         * @static 
         */ 
        public static function input($key = null, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->input($key, $default);
        }
                    /**
         * Retrieve input as a boolean value.
         * 
         * Returns true when value is "1", "true", "on", and "yes". Otherwise, returns false.
         *
         * @param string|null $key
         * @param bool $default
         * @return bool 
         * @static 
         */ 
        public static function boolean($key = null, $default = false)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->boolean($key, $default);
        }
                    /**
         * Get a subset containing the provided keys with values from the input data.
         *
         * @param array|mixed $keys
         * @return array 
         * @static 
         */ 
        public static function only($keys)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->only($keys);
        }
                    /**
         * Get all of the input except for a specified array of items.
         *
         * @param array|mixed $keys
         * @return array 
         * @static 
         */ 
        public static function except($keys)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->except($keys);
        }
                    /**
         * Retrieve a query string item from the request.
         *
         * @param string|null $key
         * @param string|array|null $default
         * @return string|array|null 
         * @static 
         */ 
        public static function query($key = null, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->query($key, $default);
        }
                    /**
         * Retrieve a request payload item from the request.
         *
         * @param string|null $key
         * @param string|array|null $default
         * @return string|array|null 
         * @static 
         */ 
        public static function post($key = null, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->post($key, $default);
        }
                    /**
         * Determine if a cookie is set on the request.
         *
         * @param string $key
         * @return bool 
         * @static 
         */ 
        public static function hasCookie($key)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->hasCookie($key);
        }
                    /**
         * Retrieve a cookie from the request.
         *
         * @param string|null $key
         * @param string|array|null $default
         * @return string|array|null 
         * @static 
         */ 
        public static function cookie($key = null, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->cookie($key, $default);
        }
                    /**
         * Get an array of all of the files on the request.
         *
         * @return array 
         * @static 
         */ 
        public static function allFiles()
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->allFiles();
        }
                    /**
         * Determine if the uploaded data contains a file.
         *
         * @param string $key
         * @return bool 
         * @static 
         */ 
        public static function hasFile($key)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->hasFile($key);
        }
                    /**
         * Retrieve a file from the request.
         *
         * @param string|null $key
         * @param mixed $default
         * @return \Illuminate\Http\UploadedFile|\Illuminate\Http\UploadedFile[]|array|null 
         * @static 
         */ 
        public static function file($key = null, $default = null)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->file($key, $default);
        }
                    /**
         * Dump the request items and end the script.
         *
         * @param array|mixed $keys
         * @return void 
         * @static 
         */ 
        public static function dd(...$keys)
        {
                        /** @var \Illuminate\Http\Request $instance */
                        $instance->dd(...$keys);
        }
                    /**
         * Dump the items.
         *
         * @param array $keys
         * @return \Illuminate\Http\Request 
         * @static 
         */ 
        public static function dump($keys = [])
        {
                        /** @var \Illuminate\Http\Request $instance */
                        return $instance->dump($keys);
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Http\Request::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Http\Request::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Http\Request::hasMacro($name);
        }
                    /**
         * 
         *
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestValidation()
         * @param array $rules
         * @param mixed $params
         * @static 
         */ 
        public static function validate($rules, ...$params)
        {
                        return \Illuminate\Http\Request::validate($rules, ...$params);
        }
                    /**
         * 
         *
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestValidation()
         * @param string $errorBag
         * @param array $rules
         * @param mixed $params
         * @static 
         */ 
        public static function validateWithBag($errorBag, $rules, ...$params)
        {
                        return \Illuminate\Http\Request::validateWithBag($errorBag, $rules, ...$params);
        }
                    /**
         * 
         *
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @param mixed $absolute
         * @static 
         */ 
        public static function hasValidSignature($absolute = true)
        {
                        return \Illuminate\Http\Request::hasValidSignature($absolute);
        }
                    /**
         * 
         *
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @static 
         */ 
        public static function hasValidRelativeSignature()
        {
                        return \Illuminate\Http\Request::hasValidRelativeSignature();
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Contracts\Routing\ResponseFactory
     */ 
        class Response {
                    /**
         * Create a new response instance.
         *
         * @param string $content
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\Response 
         * @static 
         */ 
        public static function make($content = '', $status = 200, $headers = [])
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->make($content, $status, $headers);
        }
                    /**
         * Create a new "no content" response.
         *
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\Response 
         * @static 
         */ 
        public static function noContent($status = 204, $headers = [])
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->noContent($status, $headers);
        }
                    /**
         * Create a new response for a given view.
         *
         * @param string|array $view
         * @param array $data
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\Response 
         * @static 
         */ 
        public static function view($view, $data = [], $status = 200, $headers = [])
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->view($view, $data, $status, $headers);
        }
                    /**
         * Create a new JSON response instance.
         *
         * @param mixed $data
         * @param int $status
         * @param array $headers
         * @param int $options
         * @return \Illuminate\Http\JsonResponse 
         * @static 
         */ 
        public static function json($data = [], $status = 200, $headers = [], $options = 0)
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->json($data, $status, $headers, $options);
        }
                    /**
         * Create a new JSONP response instance.
         *
         * @param string $callback
         * @param mixed $data
         * @param int $status
         * @param array $headers
         * @param int $options
         * @return \Illuminate\Http\JsonResponse 
         * @static 
         */ 
        public static function jsonp($callback, $data = [], $status = 200, $headers = [], $options = 0)
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->jsonp($callback, $data, $status, $headers, $options);
        }
                    /**
         * Create a new streamed response instance.
         *
         * @param \Closure $callback
         * @param int $status
         * @param array $headers
         * @return \Symfony\Component\HttpFoundation\StreamedResponse 
         * @static 
         */ 
        public static function stream($callback, $status = 200, $headers = [])
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->stream($callback, $status, $headers);
        }
                    /**
         * Create a new streamed response instance as a file download.
         *
         * @param \Closure $callback
         * @param string|null $name
         * @param array $headers
         * @param string|null $disposition
         * @return \Symfony\Component\HttpFoundation\StreamedResponse 
         * @static 
         */ 
        public static function streamDownload($callback, $name = null, $headers = [], $disposition = 'attachment')
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->streamDownload($callback, $name, $headers, $disposition);
        }
                    /**
         * Create a new file download response.
         *
         * @param \SplFileInfo|string $file
         * @param string|null $name
         * @param array $headers
         * @param string|null $disposition
         * @return \Symfony\Component\HttpFoundation\BinaryFileResponse 
         * @static 
         */ 
        public static function download($file, $name = null, $headers = [], $disposition = 'attachment')
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->download($file, $name, $headers, $disposition);
        }
                    /**
         * Return the raw contents of a binary file.
         *
         * @param \SplFileInfo|string $file
         * @param array $headers
         * @return \Symfony\Component\HttpFoundation\BinaryFileResponse 
         * @static 
         */ 
        public static function file($file, $headers = [])
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->file($file, $headers);
        }
                    /**
         * Create a new redirect response to the given path.
         *
         * @param string $path
         * @param int $status
         * @param array $headers
         * @param bool|null $secure
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function redirectTo($path, $status = 302, $headers = [], $secure = null)
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->redirectTo($path, $status, $headers, $secure);
        }
                    /**
         * Create a new redirect response to a named route.
         *
         * @param string $route
         * @param mixed $parameters
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function redirectToRoute($route, $parameters = [], $status = 302, $headers = [])
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->redirectToRoute($route, $parameters, $status, $headers);
        }
                    /**
         * Create a new redirect response to a controller action.
         *
         * @param string $action
         * @param mixed $parameters
         * @param int $status
         * @param array $headers
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function redirectToAction($action, $parameters = [], $status = 302, $headers = [])
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->redirectToAction($action, $parameters, $status, $headers);
        }
                    /**
         * Create a new redirect response, while putting the current URL in the session.
         *
         * @param string $path
         * @param int $status
         * @param array $headers
         * @param bool|null $secure
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function redirectGuest($path, $status = 302, $headers = [], $secure = null)
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->redirectGuest($path, $status, $headers, $secure);
        }
                    /**
         * Create a new redirect response to the previously intended location.
         *
         * @param string $default
         * @param int $status
         * @param array $headers
         * @param bool|null $secure
         * @return \Illuminate\Http\RedirectResponse 
         * @static 
         */ 
        public static function redirectToIntended($default = '/', $status = 302, $headers = [], $secure = null)
        {
                        /** @var \Illuminate\Routing\ResponseFactory $instance */
                        return $instance->redirectToIntended($default, $status, $headers, $secure);
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Routing\ResponseFactory::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Routing\ResponseFactory::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Routing\ResponseFactory::hasMacro($name);
        }
         
    }
            /**
     * 
     *
     * @method static \Illuminate\Routing\RouteRegistrar as(string $value)
     * @method static \Illuminate\Routing\RouteRegistrar domain(string $value)
     * @method static \Illuminate\Routing\RouteRegistrar middleware(array|string|null $middleware)
     * @method static \Illuminate\Routing\RouteRegistrar name(string $value)
     * @method static \Illuminate\Routing\RouteRegistrar namespace(string|null $value)
     * @method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix)
     * @method static \Illuminate\Routing\RouteRegistrar where(array $where)
     * @see \Illuminate\Routing\Router
     */ 
        class Route {
                    /**
         * Register a new GET route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function get($uri, $action = null)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->get($uri, $action);
        }
                    /**
         * Register a new POST route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function post($uri, $action = null)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->post($uri, $action);
        }
                    /**
         * Register a new PUT route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function put($uri, $action = null)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->put($uri, $action);
        }
                    /**
         * Register a new PATCH route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function patch($uri, $action = null)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->patch($uri, $action);
        }
                    /**
         * Register a new DELETE route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function delete($uri, $action = null)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->delete($uri, $action);
        }
                    /**
         * Register a new OPTIONS route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function options($uri, $action = null)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->options($uri, $action);
        }
                    /**
         * Register a new route responding to all verbs.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function any($uri, $action = null)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->any($uri, $action);
        }
                    /**
         * Register a new Fallback route with the router.
         *
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function fallback($action)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->fallback($action);
        }
                    /**
         * Create a redirect from one URI to another.
         *
         * @param string $uri
         * @param string $destination
         * @param int $status
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function redirect($uri, $destination, $status = 302)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->redirect($uri, $destination, $status);
        }
                    /**
         * Create a permanent redirect from one URI to another.
         *
         * @param string $uri
         * @param string $destination
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function permanentRedirect($uri, $destination)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->permanentRedirect($uri, $destination);
        }
                    /**
         * Register a new route that returns a view.
         *
         * @param string $uri
         * @param string $view
         * @param array $data
         * @param int|array $status
         * @param array $headers
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function view($uri, $view, $data = [], $status = 200, $headers = [])
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->view($uri, $view, $data, $status, $headers);
        }
                    /**
         * Register a new route with the given verbs.
         *
         * @param array|string $methods
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function match($methods, $uri, $action = null)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->match($methods, $uri, $action);
        }
                    /**
         * Register an array of resource controllers.
         *
         * @param array $resources
         * @param array $options
         * @return void 
         * @static 
         */ 
        public static function resources($resources, $options = [])
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->resources($resources, $options);
        }
                    /**
         * Route a resource to a controller.
         *
         * @param string $name
         * @param string $controller
         * @param array $options
         * @return \Illuminate\Routing\PendingResourceRegistration 
         * @static 
         */ 
        public static function resource($name, $controller, $options = [])
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->resource($name, $controller, $options);
        }
                    /**
         * Register an array of API resource controllers.
         *
         * @param array $resources
         * @param array $options
         * @return void 
         * @static 
         */ 
        public static function apiResources($resources, $options = [])
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->apiResources($resources, $options);
        }
                    /**
         * Route an API resource to a controller.
         *
         * @param string $name
         * @param string $controller
         * @param array $options
         * @return \Illuminate\Routing\PendingResourceRegistration 
         * @static 
         */ 
        public static function apiResource($name, $controller, $options = [])
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->apiResource($name, $controller, $options);
        }
                    /**
         * Create a route group with shared attributes.
         *
         * @param array $attributes
         * @param \Closure|string $routes
         * @return void 
         * @static 
         */ 
        public static function group($attributes, $routes)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->group($attributes, $routes);
        }
                    /**
         * Merge the given array with the last group stack.
         *
         * @param array $new
         * @param bool $prependExistingPrefix
         * @return array 
         * @static 
         */ 
        public static function mergeWithLastGroup($new, $prependExistingPrefix = true)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->mergeWithLastGroup($new, $prependExistingPrefix);
        }
                    /**
         * Get the prefix from the last group on the stack.
         *
         * @return string 
         * @static 
         */ 
        public static function getLastGroupPrefix()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->getLastGroupPrefix();
        }
                    /**
         * Add a route to the underlying route collection.
         *
         * @param array|string $methods
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function addRoute($methods, $uri, $action)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->addRoute($methods, $uri, $action);
        }
                    /**
         * Create a new Route object.
         *
         * @param array|string $methods
         * @param string $uri
         * @param mixed $action
         * @return \Illuminate\Routing\Route 
         * @static 
         */ 
        public static function newRoute($methods, $uri, $action)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->newRoute($methods, $uri, $action);
        }
                    /**
         * Return the response returned by the given route.
         *
         * @param string $name
         * @return \Symfony\Component\HttpFoundation\Response 
         * @static 
         */ 
        public static function respondWithRoute($name)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->respondWithRoute($name);
        }
                    /**
         * Dispatch the request to the application.
         *
         * @param \Illuminate\Http\Request $request
         * @return \Symfony\Component\HttpFoundation\Response 
         * @static 
         */ 
        public static function dispatch($request)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->dispatch($request);
        }
                    /**
         * Dispatch the request to a route and return the response.
         *
         * @param \Illuminate\Http\Request $request
         * @return \Symfony\Component\HttpFoundation\Response 
         * @static 
         */ 
        public static function dispatchToRoute($request)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->dispatchToRoute($request);
        }
                    /**
         * Gather the middleware for the given route with resolved class names.
         *
         * @param \Illuminate\Routing\Route $route
         * @return array 
         * @static 
         */ 
        public static function gatherRouteMiddleware($route)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->gatherRouteMiddleware($route);
        }
                    /**
         * Create a response instance from the given value.
         *
         * @param \Symfony\Component\HttpFoundation\Request $request
         * @param mixed $response
         * @return \Symfony\Component\HttpFoundation\Response 
         * @static 
         */ 
        public static function prepareResponse($request, $response)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->prepareResponse($request, $response);
        }
                    /**
         * Static version of prepareResponse.
         *
         * @param \Symfony\Component\HttpFoundation\Request $request
         * @param mixed $response
         * @return \Symfony\Component\HttpFoundation\Response 
         * @static 
         */ 
        public static function toResponse($request, $response)
        {
                        return \Illuminate\Routing\Router::toResponse($request, $response);
        }
                    /**
         * Substitute the route bindings onto the route.
         *
         * @param \Illuminate\Routing\Route $route
         * @return \Illuminate\Routing\Route 
         * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
         * @static 
         */ 
        public static function substituteBindings($route)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->substituteBindings($route);
        }
                    /**
         * Substitute the implicit Eloquent model bindings for the route.
         *
         * @param \Illuminate\Routing\Route $route
         * @return void 
         * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
         * @static 
         */ 
        public static function substituteImplicitBindings($route)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->substituteImplicitBindings($route);
        }
                    /**
         * Register a route matched event listener.
         *
         * @param string|callable $callback
         * @return void 
         * @static 
         */ 
        public static function matched($callback)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->matched($callback);
        }
                    /**
         * Get all of the defined middleware short-hand names.
         *
         * @return array 
         * @static 
         */ 
        public static function getMiddleware()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->getMiddleware();
        }
                    /**
         * Register a short-hand name for a middleware.
         *
         * @param string $name
         * @param string $class
         * @return \Illuminate\Routing\Router 
         * @static 
         */ 
        public static function aliasMiddleware($name, $class)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->aliasMiddleware($name, $class);
        }
                    /**
         * Check if a middlewareGroup with the given name exists.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMiddlewareGroup($name)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->hasMiddlewareGroup($name);
        }
                    /**
         * Get all of the defined middleware groups.
         *
         * @return array 
         * @static 
         */ 
        public static function getMiddlewareGroups()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->getMiddlewareGroups();
        }
                    /**
         * Register a group of middleware.
         *
         * @param string $name
         * @param array $middleware
         * @return \Illuminate\Routing\Router 
         * @static 
         */ 
        public static function middlewareGroup($name, $middleware)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->middlewareGroup($name, $middleware);
        }
                    /**
         * Add a middleware to the beginning of a middleware group.
         * 
         * If the middleware is already in the group, it will not be added again.
         *
         * @param string $group
         * @param string $middleware
         * @return \Illuminate\Routing\Router 
         * @static 
         */ 
        public static function prependMiddlewareToGroup($group, $middleware)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->prependMiddlewareToGroup($group, $middleware);
        }
                    /**
         * Add a middleware to the end of a middleware group.
         * 
         * If the middleware is already in the group, it will not be added again.
         *
         * @param string $group
         * @param string $middleware
         * @return \Illuminate\Routing\Router 
         * @static 
         */ 
        public static function pushMiddlewareToGroup($group, $middleware)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->pushMiddlewareToGroup($group, $middleware);
        }
                    /**
         * Flush the router's middleware groups.
         *
         * @return \Illuminate\Routing\Router 
         * @static 
         */ 
        public static function flushMiddlewareGroups()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->flushMiddlewareGroups();
        }
                    /**
         * Add a new route parameter binder.
         *
         * @param string $key
         * @param string|callable $binder
         * @return void 
         * @static 
         */ 
        public static function bind($key, $binder)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->bind($key, $binder);
        }
                    /**
         * Register a model binder for a wildcard.
         *
         * @param string $key
         * @param string $class
         * @param \Closure|null $callback
         * @return void 
         * @static 
         */ 
        public static function model($key, $class, $callback = null)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->model($key, $class, $callback);
        }
                    /**
         * Get the binding callback for a given binding.
         *
         * @param string $key
         * @return \Closure|null 
         * @static 
         */ 
        public static function getBindingCallback($key)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->getBindingCallback($key);
        }
                    /**
         * Get the global "where" patterns.
         *
         * @return array 
         * @static 
         */ 
        public static function getPatterns()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->getPatterns();
        }
                    /**
         * Set a global where pattern on all routes.
         *
         * @param string $key
         * @param string $pattern
         * @return void 
         * @static 
         */ 
        public static function pattern($key, $pattern)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->pattern($key, $pattern);
        }
                    /**
         * Set a group of global where patterns on all routes.
         *
         * @param array $patterns
         * @return void 
         * @static 
         */ 
        public static function patterns($patterns)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->patterns($patterns);
        }
                    /**
         * Determine if the router currently has a group stack.
         *
         * @return bool 
         * @static 
         */ 
        public static function hasGroupStack()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->hasGroupStack();
        }
                    /**
         * Get the current group stack for the router.
         *
         * @return array 
         * @static 
         */ 
        public static function getGroupStack()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->getGroupStack();
        }
                    /**
         * Get a route parameter for the current route.
         *
         * @param string $key
         * @param string|null $default
         * @return mixed 
         * @static 
         */ 
        public static function input($key, $default = null)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->input($key, $default);
        }
                    /**
         * Get the request currently being dispatched.
         *
         * @return \Illuminate\Http\Request 
         * @static 
         */ 
        public static function getCurrentRequest()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->getCurrentRequest();
        }
                    /**
         * Get the currently dispatched route instance.
         *
         * @return \Illuminate\Routing\Route|null 
         * @static 
         */ 
        public static function getCurrentRoute()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->getCurrentRoute();
        }
                    /**
         * Get the currently dispatched route instance.
         *
         * @return \Illuminate\Routing\Route|null 
         * @static 
         */ 
        public static function current()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->current();
        }
                    /**
         * Check if a route with the given name exists.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function has($name)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->has($name);
        }
                    /**
         * Get the current route name.
         *
         * @return string|null 
         * @static 
         */ 
        public static function currentRouteName()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->currentRouteName();
        }
                    /**
         * Alias for the "currentRouteNamed" method.
         *
         * @param mixed $patterns
         * @return bool 
         * @static 
         */ 
        public static function is(...$patterns)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->is(...$patterns);
        }
                    /**
         * Determine if the current route matches a pattern.
         *
         * @param mixed $patterns
         * @return bool 
         * @static 
         */ 
        public static function currentRouteNamed(...$patterns)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->currentRouteNamed(...$patterns);
        }
                    /**
         * Get the current route action.
         *
         * @return string|null 
         * @static 
         */ 
        public static function currentRouteAction()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->currentRouteAction();
        }
                    /**
         * Alias for the "currentRouteUses" method.
         *
         * @param array $patterns
         * @return bool 
         * @static 
         */ 
        public static function uses(...$patterns)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->uses(...$patterns);
        }
                    /**
         * Determine if the current route action matches a given action.
         *
         * @param string $action
         * @return bool 
         * @static 
         */ 
        public static function currentRouteUses($action)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->currentRouteUses($action);
        }
                    /**
         * Set the unmapped global resource parameters to singular.
         *
         * @param bool $singular
         * @return void 
         * @static 
         */ 
        public static function singularResourceParameters($singular = true)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->singularResourceParameters($singular);
        }
                    /**
         * Set the global resource parameter mapping.
         *
         * @param array $parameters
         * @return void 
         * @static 
         */ 
        public static function resourceParameters($parameters = [])
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->resourceParameters($parameters);
        }
                    /**
         * Get or set the verbs used in the resource URIs.
         *
         * @param array $verbs
         * @return array|null 
         * @static 
         */ 
        public static function resourceVerbs($verbs = [])
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->resourceVerbs($verbs);
        }
                    /**
         * Get the underlying route collection.
         *
         * @return \Illuminate\Routing\RouteCollectionInterface 
         * @static 
         */ 
        public static function getRoutes()
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->getRoutes();
        }
                    /**
         * Set the route collection instance.
         *
         * @param \Illuminate\Routing\RouteCollection $routes
         * @return void 
         * @static 
         */ 
        public static function setRoutes($routes)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->setRoutes($routes);
        }
                    /**
         * Set the compiled route collection instance.
         *
         * @param array $routes
         * @return void 
         * @static 
         */ 
        public static function setCompiledRoutes($routes)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        $instance->setCompiledRoutes($routes);
        }
                    /**
         * Remove any duplicate middleware from the given array.
         *
         * @param array $middleware
         * @return array 
         * @static 
         */ 
        public static function uniqueMiddleware($middleware)
        {
                        return \Illuminate\Routing\Router::uniqueMiddleware($middleware);
        }
                    /**
         * Set the container instance used by the router.
         *
         * @param \Illuminate\Container\Container $container
         * @return \Illuminate\Routing\Router 
         * @static 
         */ 
        public static function setContainer($container)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->setContainer($container);
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Routing\Router::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Routing\Router::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Routing\Router::hasMacro($name);
        }
                    /**
         * Dynamically handle calls to the class.
         *
         * @param string $method
         * @param array $parameters
         * @return mixed 
         * @throws \BadMethodCallException
         * @static 
         */ 
        public static function macroCall($method, $parameters)
        {
                        /** @var \Illuminate\Routing\Router $instance */
                        return $instance->macroCall($method, $parameters);
        }
                    /**
         * 
         *
         * @see \Laravel\Ui\AuthRouteMethods::auth()
         * @param mixed $options
         * @static 
         */ 
        public static function auth($options = [])
        {
                        return \Illuminate\Routing\Router::auth($options);
        }
                    /**
         * 
         *
         * @see \Laravel\Ui\AuthRouteMethods::resetPassword()
         * @static 
         */ 
        public static function resetPassword()
        {
                        return \Illuminate\Routing\Router::resetPassword();
        }
                    /**
         * 
         *
         * @see \Laravel\Ui\AuthRouteMethods::confirmPassword()
         * @static 
         */ 
        public static function confirmPassword()
        {
                        return \Illuminate\Routing\Router::confirmPassword();
        }
                    /**
         * 
         *
         * @see \Laravel\Ui\AuthRouteMethods::emailVerification()
         * @static 
         */ 
        public static function emailVerification()
        {
                        return \Illuminate\Routing\Router::emailVerification();
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Database\Schema\Builder
     */ 
        class Schema {
                    /**
         * Create a database in the schema.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function createDatabase($name)
        {
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->createDatabase($name);
        }
                    /**
         * Drop a database from the schema if the database exists.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function dropDatabaseIfExists($name)
        {
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->dropDatabaseIfExists($name);
        }
                    /**
         * Determine if the given table exists.
         *
         * @param string $table
         * @return bool 
         * @static 
         */ 
        public static function hasTable($table)
        {
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->hasTable($table);
        }
                    /**
         * Get the column listing for a given table.
         *
         * @param string $table
         * @return array 
         * @static 
         */ 
        public static function getColumnListing($table)
        {
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->getColumnListing($table);
        }
                    /**
         * Drop all tables from the database.
         *
         * @return void 
         * @static 
         */ 
        public static function dropAllTables()
        {
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        $instance->dropAllTables();
        }
                    /**
         * Drop all views from the database.
         *
         * @return void 
         * @static 
         */ 
        public static function dropAllViews()
        {
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        $instance->dropAllViews();
        }
                    /**
         * Get all of the table names for the database.
         *
         * @return array 
         * @static 
         */ 
        public static function getAllTables()
        {
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->getAllTables();
        }
                    /**
         * Get all of the view names for the database.
         *
         * @return array 
         * @static 
         */ 
        public static function getAllViews()
        {
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->getAllViews();
        }
                    /**
         * Set the default string length for migrations.
         *
         * @param int $length
         * @return void 
         * @static 
         */ 
        public static function defaultStringLength($length)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        \Illuminate\Database\Schema\MySqlBuilder::defaultStringLength($length);
        }
                    /**
         * Set the default morph key type for migrations.
         *
         * @param string $type
         * @return void 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function defaultMorphKeyType($type)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        \Illuminate\Database\Schema\MySqlBuilder::defaultMorphKeyType($type);
        }
                    /**
         * Set the default morph key type for migrations to UUIDs.
         *
         * @return void 
         * @static 
         */ 
        public static function morphUsingUuids()
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        \Illuminate\Database\Schema\MySqlBuilder::morphUsingUuids();
        }
                    /**
         * Determine if the given table has a given column.
         *
         * @param string $table
         * @param string $column
         * @return bool 
         * @static 
         */ 
        public static function hasColumn($table, $column)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->hasColumn($table, $column);
        }
                    /**
         * Determine if the given table has given columns.
         *
         * @param string $table
         * @param array $columns
         * @return bool 
         * @static 
         */ 
        public static function hasColumns($table, $columns)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->hasColumns($table, $columns);
        }
                    /**
         * Get the data type for the given column name.
         *
         * @param string $table
         * @param string $column
         * @return string 
         * @static 
         */ 
        public static function getColumnType($table, $column)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->getColumnType($table, $column);
        }
                    /**
         * Modify a table on the schema.
         *
         * @param string $table
         * @param \Closure $callback
         * @return void 
         * @static 
         */ 
        public static function table($table, $callback)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        $instance->table($table, $callback);
        }
                    /**
         * Create a new table on the schema.
         *
         * @param string $table
         * @param \Closure $callback
         * @return void 
         * @static 
         */ 
        public static function create($table, $callback)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        $instance->create($table, $callback);
        }
                    /**
         * Drop a table from the schema.
         *
         * @param string $table
         * @return void 
         * @static 
         */ 
        public static function drop($table)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        $instance->drop($table);
        }
                    /**
         * Drop a table from the schema if it exists.
         *
         * @param string $table
         * @return void 
         * @static 
         */ 
        public static function dropIfExists($table)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        $instance->dropIfExists($table);
        }
                    /**
         * Drop columns from a table schema.
         *
         * @param string $table
         * @param string|array $columns
         * @return void 
         * @static 
         */ 
        public static function dropColumns($table, $columns)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        $instance->dropColumns($table, $columns);
        }
                    /**
         * Drop all types from the database.
         *
         * @return void 
         * @throws \LogicException
         * @static 
         */ 
        public static function dropAllTypes()
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        $instance->dropAllTypes();
        }
                    /**
         * Rename a table on the schema.
         *
         * @param string $from
         * @param string $to
         * @return void 
         * @static 
         */ 
        public static function rename($from, $to)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        $instance->rename($from, $to);
        }
                    /**
         * Enable foreign key constraints.
         *
         * @return bool 
         * @static 
         */ 
        public static function enableForeignKeyConstraints()
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->enableForeignKeyConstraints();
        }
                    /**
         * Disable foreign key constraints.
         *
         * @return bool 
         * @static 
         */ 
        public static function disableForeignKeyConstraints()
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->disableForeignKeyConstraints();
        }
                    /**
         * Register a custom Doctrine mapping type.
         *
         * @param string $class
         * @param string $name
         * @param string $type
         * @return void 
         * @throws \Doctrine\DBAL\DBALException
         * @throws \RuntimeException
         * @static 
         */ 
        public static function registerCustomDoctrineType($class, $name, $type)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        $instance->registerCustomDoctrineType($class, $name, $type);
        }
                    /**
         * Get the database connection instance.
         *
         * @return \Illuminate\Database\Connection 
         * @static 
         */ 
        public static function getConnection()
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->getConnection();
        }
                    /**
         * Set the database connection instance.
         *
         * @param \Illuminate\Database\Connection $connection
         * @return \Illuminate\Database\Schema\MySqlBuilder 
         * @static 
         */ 
        public static function setConnection($connection)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        return $instance->setConnection($connection);
        }
                    /**
         * Set the Schema Blueprint resolver callback.
         *
         * @param \Closure $resolver
         * @return void 
         * @static 
         */ 
        public static function blueprintResolver($resolver)
        {            //Method inherited from \Illuminate\Database\Schema\Builder         
                        /** @var \Illuminate\Database\Schema\MySqlBuilder $instance */
                        $instance->blueprintResolver($resolver);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Session\SessionManager
     * @see \Illuminate\Session\Store
     */ 
        class Session {
                    /**
         * Determine if requests for the same session should wait for each to finish before executing.
         *
         * @return bool 
         * @static 
         */ 
        public static function shouldBlock()
        {
                        /** @var \Illuminate\Session\SessionManager $instance */
                        return $instance->shouldBlock();
        }
                    /**
         * Get the name of the cache store / driver that should be used to acquire session locks.
         *
         * @return string|null 
         * @static 
         */ 
        public static function blockDriver()
        {
                        /** @var \Illuminate\Session\SessionManager $instance */
                        return $instance->blockDriver();
        }
                    /**
         * Get the session configuration.
         *
         * @return array 
         * @static 
         */ 
        public static function getSessionConfig()
        {
                        /** @var \Illuminate\Session\SessionManager $instance */
                        return $instance->getSessionConfig();
        }
                    /**
         * Get the default session driver name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultDriver()
        {
                        /** @var \Illuminate\Session\SessionManager $instance */
                        return $instance->getDefaultDriver();
        }
                    /**
         * Set the default session driver name.
         *
         * @param string $name
         * @return void 
         * @static 
         */ 
        public static function setDefaultDriver($name)
        {
                        /** @var \Illuminate\Session\SessionManager $instance */
                        $instance->setDefaultDriver($name);
        }
                    /**
         * Get a driver instance.
         *
         * @param string|null $driver
         * @return mixed 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function driver($driver = null)
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Session\SessionManager $instance */
                        return $instance->driver($driver);
        }
                    /**
         * Register a custom driver creator Closure.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return \Illuminate\Session\SessionManager 
         * @static 
         */ 
        public static function extend($driver, $callback)
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Session\SessionManager $instance */
                        return $instance->extend($driver, $callback);
        }
                    /**
         * Get all of the created "drivers".
         *
         * @return array 
         * @static 
         */ 
        public static function getDrivers()
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Session\SessionManager $instance */
                        return $instance->getDrivers();
        }
                    /**
         * Get the container instance used by the manager.
         *
         * @return \Illuminate\Contracts\Container\Container 
         * @static 
         */ 
        public static function getContainer()
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Session\SessionManager $instance */
                        return $instance->getContainer();
        }
                    /**
         * Set the container instance used by the manager.
         *
         * @param \Illuminate\Contracts\Container\Container $container
         * @return \Illuminate\Session\SessionManager 
         * @static 
         */ 
        public static function setContainer($container)
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Session\SessionManager $instance */
                        return $instance->setContainer($container);
        }
                    /**
         * Forget all of the resolved driver instances.
         *
         * @return \Illuminate\Session\SessionManager 
         * @static 
         */ 
        public static function forgetDrivers()
        {            //Method inherited from \Illuminate\Support\Manager         
                        /** @var \Illuminate\Session\SessionManager $instance */
                        return $instance->forgetDrivers();
        }
                    /**
         * Start the session, reading the data from a handler.
         *
         * @return bool 
         * @static 
         */ 
        public static function start()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->start();
        }
                    /**
         * Save the session data to storage.
         *
         * @return void 
         * @static 
         */ 
        public static function save()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->save();
        }
                    /**
         * Age the flash data for the session.
         *
         * @return void 
         * @static 
         */ 
        public static function ageFlashData()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->ageFlashData();
        }
                    /**
         * Get all of the session data.
         *
         * @return array 
         * @static 
         */ 
        public static function all()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->all();
        }
                    /**
         * Get a subset of the session data.
         *
         * @param array $keys
         * @return array 
         * @static 
         */ 
        public static function only($keys)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->only($keys);
        }
                    /**
         * Checks if a key exists.
         *
         * @param string|array $key
         * @return bool 
         * @static 
         */ 
        public static function exists($key)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->exists($key);
        }
                    /**
         * Determine if the given key is missing from the session data.
         *
         * @param string|array $key
         * @return bool 
         * @static 
         */ 
        public static function missing($key)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->missing($key);
        }
                    /**
         * Checks if a key is present and not null.
         *
         * @param string|array $key
         * @return bool 
         * @static 
         */ 
        public static function has($key)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->has($key);
        }
                    /**
         * Get an item from the session.
         *
         * @param string $key
         * @param mixed $default
         * @return mixed 
         * @static 
         */ 
        public static function get($key, $default = null)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->get($key, $default);
        }
                    /**
         * Get the value of a given key and then forget it.
         *
         * @param string $key
         * @param mixed $default
         * @return mixed 
         * @static 
         */ 
        public static function pull($key, $default = null)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->pull($key, $default);
        }
                    /**
         * Determine if the session contains old input.
         *
         * @param string|null $key
         * @return bool 
         * @static 
         */ 
        public static function hasOldInput($key = null)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->hasOldInput($key);
        }
                    /**
         * Get the requested item from the flashed input array.
         *
         * @param string|null $key
         * @param mixed $default
         * @return mixed 
         * @static 
         */ 
        public static function getOldInput($key = null, $default = null)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->getOldInput($key, $default);
        }
                    /**
         * Replace the given session attributes entirely.
         *
         * @param array $attributes
         * @return void 
         * @static 
         */ 
        public static function replace($attributes)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->replace($attributes);
        }
                    /**
         * Put a key / value pair or array of key / value pairs in the session.
         *
         * @param string|array $key
         * @param mixed $value
         * @return void 
         * @static 
         */ 
        public static function put($key, $value = null)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->put($key, $value);
        }
                    /**
         * Get an item from the session, or store the default value.
         *
         * @param string $key
         * @param \Closure $callback
         * @return mixed 
         * @static 
         */ 
        public static function remember($key, $callback)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->remember($key, $callback);
        }
                    /**
         * Push a value onto a session array.
         *
         * @param string $key
         * @param mixed $value
         * @return void 
         * @static 
         */ 
        public static function push($key, $value)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->push($key, $value);
        }
                    /**
         * Increment the value of an item in the session.
         *
         * @param string $key
         * @param int $amount
         * @return mixed 
         * @static 
         */ 
        public static function increment($key, $amount = 1)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->increment($key, $amount);
        }
                    /**
         * Decrement the value of an item in the session.
         *
         * @param string $key
         * @param int $amount
         * @return int 
         * @static 
         */ 
        public static function decrement($key, $amount = 1)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->decrement($key, $amount);
        }
                    /**
         * Flash a key / value pair to the session.
         *
         * @param string $key
         * @param mixed $value
         * @return void 
         * @static 
         */ 
        public static function flash($key, $value = true)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->flash($key, $value);
        }
                    /**
         * Flash a key / value pair to the session for immediate use.
         *
         * @param string $key
         * @param mixed $value
         * @return void 
         * @static 
         */ 
        public static function now($key, $value)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->now($key, $value);
        }
                    /**
         * Reflash all of the session flash data.
         *
         * @return void 
         * @static 
         */ 
        public static function reflash()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->reflash();
        }
                    /**
         * Reflash a subset of the current flash data.
         *
         * @param array|mixed $keys
         * @return void 
         * @static 
         */ 
        public static function keep($keys = null)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->keep($keys);
        }
                    /**
         * Flash an input array to the session.
         *
         * @param array $value
         * @return void 
         * @static 
         */ 
        public static function flashInput($value)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->flashInput($value);
        }
                    /**
         * Remove an item from the session, returning its value.
         *
         * @param string $key
         * @return mixed 
         * @static 
         */ 
        public static function remove($key)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->remove($key);
        }
                    /**
         * Remove one or many items from the session.
         *
         * @param string|array $keys
         * @return void 
         * @static 
         */ 
        public static function forget($keys)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->forget($keys);
        }
                    /**
         * Remove all of the items from the session.
         *
         * @return void 
         * @static 
         */ 
        public static function flush()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->flush();
        }
                    /**
         * Flush the session data and regenerate the ID.
         *
         * @return bool 
         * @static 
         */ 
        public static function invalidate()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->invalidate();
        }
                    /**
         * Generate a new session identifier.
         *
         * @param bool $destroy
         * @return bool 
         * @static 
         */ 
        public static function regenerate($destroy = false)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->regenerate($destroy);
        }
                    /**
         * Generate a new session ID for the session.
         *
         * @param bool $destroy
         * @return bool 
         * @static 
         */ 
        public static function migrate($destroy = false)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->migrate($destroy);
        }
                    /**
         * Determine if the session has been started.
         *
         * @return bool 
         * @static 
         */ 
        public static function isStarted()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->isStarted();
        }
                    /**
         * Get the name of the session.
         *
         * @return string 
         * @static 
         */ 
        public static function getName()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->getName();
        }
                    /**
         * Set the name of the session.
         *
         * @param string $name
         * @return void 
         * @static 
         */ 
        public static function setName($name)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->setName($name);
        }
                    /**
         * Get the current session ID.
         *
         * @return string 
         * @static 
         */ 
        public static function getId()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->getId();
        }
                    /**
         * Set the session ID.
         *
         * @param string $id
         * @return void 
         * @static 
         */ 
        public static function setId($id)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->setId($id);
        }
                    /**
         * Determine if this is a valid session ID.
         *
         * @param string $id
         * @return bool 
         * @static 
         */ 
        public static function isValidId($id)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->isValidId($id);
        }
                    /**
         * Set the existence of the session on the handler if applicable.
         *
         * @param bool $value
         * @return void 
         * @static 
         */ 
        public static function setExists($value)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->setExists($value);
        }
                    /**
         * Get the CSRF token value.
         *
         * @return string 
         * @static 
         */ 
        public static function token()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->token();
        }
                    /**
         * Regenerate the CSRF token value.
         *
         * @return void 
         * @static 
         */ 
        public static function regenerateToken()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->regenerateToken();
        }
                    /**
         * Get the previous URL from the session.
         *
         * @return string|null 
         * @static 
         */ 
        public static function previousUrl()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->previousUrl();
        }
                    /**
         * Set the "previous" URL in the session.
         *
         * @param string $url
         * @return void 
         * @static 
         */ 
        public static function setPreviousUrl($url)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->setPreviousUrl($url);
        }
                    /**
         * Specify that the user has confirmed their password.
         *
         * @return void 
         * @static 
         */ 
        public static function passwordConfirmed()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->passwordConfirmed();
        }
                    /**
         * Get the underlying session handler implementation.
         *
         * @return \SessionHandlerInterface 
         * @static 
         */ 
        public static function getHandler()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->getHandler();
        }
                    /**
         * Determine if the session handler needs a request.
         *
         * @return bool 
         * @static 
         */ 
        public static function handlerNeedsRequest()
        {
                        /** @var \Illuminate\Session\Store $instance */
                        return $instance->handlerNeedsRequest();
        }
                    /**
         * Set the request on the handler instance.
         *
         * @param \Illuminate\Http\Request $request
         * @return void 
         * @static 
         */ 
        public static function setRequestOnHandler($request)
        {
                        /** @var \Illuminate\Session\Store $instance */
                        $instance->setRequestOnHandler($request);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Filesystem\FilesystemManager
     */ 
        class Storage {
                    /**
         * Get a filesystem instance.
         *
         * @param string|null $name
         * @return \Illuminate\Filesystem\FilesystemAdapter 
         * @static 
         */ 
        public static function drive($name = null)
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->drive($name);
        }
                    /**
         * Get a filesystem instance.
         *
         * @param string|null $name
         * @return \Illuminate\Filesystem\FilesystemAdapter 
         * @static 
         */ 
        public static function disk($name = null)
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->disk($name);
        }
                    /**
         * Get a default cloud filesystem instance.
         *
         * @return \Illuminate\Filesystem\FilesystemAdapter 
         * @static 
         */ 
        public static function cloud()
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->cloud();
        }
                    /**
         * Build an on-demand disk.
         *
         * @param string|array $config
         * @return \Illuminate\Filesystem\FilesystemAdapter 
         * @static 
         */ 
        public static function build($config)
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->build($config);
        }
                    /**
         * Create an instance of the local driver.
         *
         * @param array $config
         * @return \Illuminate\Filesystem\FilesystemAdapter 
         * @static 
         */ 
        public static function createLocalDriver($config)
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->createLocalDriver($config);
        }
                    /**
         * Create an instance of the ftp driver.
         *
         * @param array $config
         * @return \Illuminate\Filesystem\FilesystemAdapter 
         * @static 
         */ 
        public static function createFtpDriver($config)
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->createFtpDriver($config);
        }
                    /**
         * Create an instance of the sftp driver.
         *
         * @param array $config
         * @return \Illuminate\Filesystem\FilesystemAdapter 
         * @static 
         */ 
        public static function createSftpDriver($config)
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->createSftpDriver($config);
        }
                    /**
         * Create an instance of the Amazon S3 driver.
         *
         * @param array $config
         * @return \Illuminate\Contracts\Filesystem\Cloud 
         * @static 
         */ 
        public static function createS3Driver($config)
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->createS3Driver($config);
        }
                    /**
         * Set the given disk instance.
         *
         * @param string $name
         * @param mixed $disk
         * @return \Illuminate\Filesystem\FilesystemManager 
         * @static 
         */ 
        public static function set($name, $disk)
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->set($name, $disk);
        }
                    /**
         * Get the default driver name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultDriver()
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->getDefaultDriver();
        }
                    /**
         * Get the default cloud driver name.
         *
         * @return string 
         * @static 
         */ 
        public static function getDefaultCloudDriver()
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->getDefaultCloudDriver();
        }
                    /**
         * Unset the given disk instances.
         *
         * @param array|string $disk
         * @return \Illuminate\Filesystem\FilesystemManager 
         * @static 
         */ 
        public static function forgetDisk($disk)
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->forgetDisk($disk);
        }
                    /**
         * Disconnect the given disk and remove from local cache.
         *
         * @param string|null $name
         * @return void 
         * @static 
         */ 
        public static function purge($name = null)
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        $instance->purge($name);
        }
                    /**
         * Register a custom driver creator Closure.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return \Illuminate\Filesystem\FilesystemManager 
         * @static 
         */ 
        public static function extend($driver, $callback)
        {
                        /** @var \Illuminate\Filesystem\FilesystemManager $instance */
                        return $instance->extend($driver, $callback);
        }
                    /**
         * Assert that the given file exists.
         *
         * @param string|array $path
         * @param string|null $content
         * @return \Illuminate\Filesystem\FilesystemAdapter 
         * @static 
         */ 
        public static function assertExists($path, $content = null)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->assertExists($path, $content);
        }
                    /**
         * Assert that the given file does not exist.
         *
         * @param string|array $path
         * @return \Illuminate\Filesystem\FilesystemAdapter 
         * @static 
         */ 
        public static function assertMissing($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->assertMissing($path);
        }
                    /**
         * Determine if a file exists.
         *
         * @param string $path
         * @return bool 
         * @static 
         */ 
        public static function exists($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->exists($path);
        }
                    /**
         * Determine if a file or directory is missing.
         *
         * @param string $path
         * @return bool 
         * @static 
         */ 
        public static function missing($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->missing($path);
        }
                    /**
         * Get the full path for the file at the given "short" path.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function path($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->path($path);
        }
                    /**
         * Get the contents of a file.
         *
         * @param string $path
         * @return string 
         * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
         * @static 
         */ 
        public static function get($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->get($path);
        }
                    /**
         * Create a streamed response for a given file.
         *
         * @param string $path
         * @param string|null $name
         * @param array|null $headers
         * @param string|null $disposition
         * @return \Symfony\Component\HttpFoundation\StreamedResponse 
         * @static 
         */ 
        public static function response($path, $name = null, $headers = [], $disposition = 'inline')
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->response($path, $name, $headers, $disposition);
        }
                    /**
         * Create a streamed download response for a given file.
         *
         * @param string $path
         * @param string|null $name
         * @param array|null $headers
         * @return \Symfony\Component\HttpFoundation\StreamedResponse 
         * @static 
         */ 
        public static function download($path, $name = null, $headers = [])
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->download($path, $name, $headers);
        }
                    /**
         * Write the contents of a file.
         *
         * @param string $path
         * @param string|resource $contents
         * @param mixed $options
         * @return bool 
         * @static 
         */ 
        public static function put($path, $contents, $options = [])
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->put($path, $contents, $options);
        }
                    /**
         * Store the uploaded file on the disk.
         *
         * @param string $path
         * @param \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string $file
         * @param mixed $options
         * @return string|false 
         * @static 
         */ 
        public static function putFile($path, $file, $options = [])
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->putFile($path, $file, $options);
        }
                    /**
         * Store the uploaded file on the disk with a given name.
         *
         * @param string $path
         * @param \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string $file
         * @param string $name
         * @param mixed $options
         * @return string|false 
         * @static 
         */ 
        public static function putFileAs($path, $file, $name, $options = [])
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->putFileAs($path, $file, $name, $options);
        }
                    /**
         * Get the visibility for the given path.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function getVisibility($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->getVisibility($path);
        }
                    /**
         * Set the visibility for the given path.
         *
         * @param string $path
         * @param string $visibility
         * @return bool 
         * @static 
         */ 
        public static function setVisibility($path, $visibility)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->setVisibility($path, $visibility);
        }
                    /**
         * Prepend to a file.
         *
         * @param string $path
         * @param string $data
         * @param string $separator
         * @return bool 
         * @static 
         */ 
        public static function prepend($path, $data, $separator = '
')
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->prepend($path, $data, $separator);
        }
                    /**
         * Append to a file.
         *
         * @param string $path
         * @param string $data
         * @param string $separator
         * @return bool 
         * @static 
         */ 
        public static function append($path, $data, $separator = '
')
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->append($path, $data, $separator);
        }
                    /**
         * Delete the file at a given path.
         *
         * @param string|array $paths
         * @return bool 
         * @static 
         */ 
        public static function delete($paths)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->delete($paths);
        }
                    /**
         * Copy a file to a new location.
         *
         * @param string $from
         * @param string $to
         * @return bool 
         * @static 
         */ 
        public static function copy($from, $to)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->copy($from, $to);
        }
                    /**
         * Move a file to a new location.
         *
         * @param string $from
         * @param string $to
         * @return bool 
         * @static 
         */ 
        public static function move($from, $to)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->move($from, $to);
        }
                    /**
         * Get the file size of a given file.
         *
         * @param string $path
         * @return int 
         * @static 
         */ 
        public static function size($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->size($path);
        }
                    /**
         * Get the mime-type of a given file.
         *
         * @param string $path
         * @return string|false 
         * @static 
         */ 
        public static function mimeType($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->mimeType($path);
        }
                    /**
         * Get the file's last modification time.
         *
         * @param string $path
         * @return int 
         * @static 
         */ 
        public static function lastModified($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->lastModified($path);
        }
                    /**
         * Get the URL for the file at the given path.
         *
         * @param string $path
         * @return string 
         * @throws \RuntimeException
         * @static 
         */ 
        public static function url($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->url($path);
        }
                    /**
         * Get a resource to read the file.
         *
         * @param string $path
         * @return resource|null The path resource or null on failure.
         * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
         * @static 
         */ 
        public static function readStream($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->readStream($path);
        }
                    /**
         * Write a new file using a stream.
         *
         * @param string $path
         * @param resource $resource
         * @param array $options
         * @return bool 
         * @throws \InvalidArgumentException If $resource is not a file handle.
         * @throws \Illuminate\Contracts\Filesystem\FileExistsException
         * @static 
         */ 
        public static function writeStream($path, $resource, $options = [])
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->writeStream($path, $resource, $options);
        }
                    /**
         * Get a temporary URL for the file at the given path.
         *
         * @param string $path
         * @param \DateTimeInterface $expiration
         * @param array $options
         * @return string 
         * @throws \RuntimeException
         * @static 
         */ 
        public static function temporaryUrl($path, $expiration, $options = [])
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->temporaryUrl($path, $expiration, $options);
        }
                    /**
         * Get a temporary URL for the file at the given path.
         *
         * @param \League\Flysystem\AwsS3v3\AwsS3Adapter $adapter
         * @param string $path
         * @param \DateTimeInterface $expiration
         * @param array $options
         * @return string 
         * @static 
         */ 
        public static function getAwsTemporaryUrl($adapter, $path, $expiration, $options)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->getAwsTemporaryUrl($adapter, $path, $expiration, $options);
        }
                    /**
         * Get an array of all files in a directory.
         *
         * @param string|null $directory
         * @param bool $recursive
         * @return array 
         * @static 
         */ 
        public static function files($directory = null, $recursive = false)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->files($directory, $recursive);
        }
                    /**
         * Get all of the files from the given directory (recursive).
         *
         * @param string|null $directory
         * @return array 
         * @static 
         */ 
        public static function allFiles($directory = null)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->allFiles($directory);
        }
                    /**
         * Get all of the directories within a given directory.
         *
         * @param string|null $directory
         * @param bool $recursive
         * @return array 
         * @static 
         */ 
        public static function directories($directory = null, $recursive = false)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->directories($directory, $recursive);
        }
                    /**
         * Get all (recursive) of the directories within a given directory.
         *
         * @param string|null $directory
         * @return array 
         * @static 
         */ 
        public static function allDirectories($directory = null)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->allDirectories($directory);
        }
                    /**
         * Create a directory.
         *
         * @param string $path
         * @return bool 
         * @static 
         */ 
        public static function makeDirectory($path)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->makeDirectory($path);
        }
                    /**
         * Recursively delete a directory.
         *
         * @param string $directory
         * @return bool 
         * @static 
         */ 
        public static function deleteDirectory($directory)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->deleteDirectory($directory);
        }
                    /**
         * Flush the Flysystem cache.
         *
         * @return void 
         * @static 
         */ 
        public static function flushCache()
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        $instance->flushCache();
        }
                    /**
         * Get the Flysystem driver.
         *
         * @return \League\Flysystem\FilesystemInterface 
         * @static 
         */ 
        public static function getDriver()
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->getDriver();
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Filesystem\FilesystemAdapter::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Filesystem\FilesystemAdapter::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Filesystem\FilesystemAdapter::hasMacro($name);
        }
                    /**
         * Dynamically handle calls to the class.
         *
         * @param string $method
         * @param array $parameters
         * @return mixed 
         * @throws \BadMethodCallException
         * @static 
         */ 
        public static function macroCall($method, $parameters)
        {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $instance */
                        return $instance->macroCall($method, $parameters);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Routing\UrlGenerator
     */ 
        class URL {
                    /**
         * Get the full URL for the current request.
         *
         * @return string 
         * @static 
         */ 
        public static function full()
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->full();
        }
                    /**
         * Get the current URL for the request.
         *
         * @return string 
         * @static 
         */ 
        public static function current()
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->current();
        }
                    /**
         * Get the URL for the previous request.
         *
         * @param mixed $fallback
         * @return string 
         * @static 
         */ 
        public static function previous($fallback = false)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->previous($fallback);
        }
                    /**
         * Generate an absolute URL to the given path.
         *
         * @param string $path
         * @param mixed $extra
         * @param bool|null $secure
         * @return string 
         * @static 
         */ 
        public static function to($path, $extra = [], $secure = null)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->to($path, $extra, $secure);
        }
                    /**
         * Generate a secure, absolute URL to the given path.
         *
         * @param string $path
         * @param array $parameters
         * @return string 
         * @static 
         */ 
        public static function secure($path, $parameters = [])
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->secure($path, $parameters);
        }
                    /**
         * Generate the URL to an application asset.
         *
         * @param string $path
         * @param bool|null $secure
         * @return string 
         * @static 
         */ 
        public static function asset($path, $secure = null)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->asset($path, $secure);
        }
                    /**
         * Generate the URL to a secure asset.
         *
         * @param string $path
         * @return string 
         * @static 
         */ 
        public static function secureAsset($path)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->secureAsset($path);
        }
                    /**
         * Generate the URL to an asset from a custom root domain such as CDN, etc.
         *
         * @param string $root
         * @param string $path
         * @param bool|null $secure
         * @return string 
         * @static 
         */ 
        public static function assetFrom($root, $path, $secure = null)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->assetFrom($root, $path, $secure);
        }
                    /**
         * Get the default scheme for a raw URL.
         *
         * @param bool|null $secure
         * @return string 
         * @static 
         */ 
        public static function formatScheme($secure = null)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->formatScheme($secure);
        }
                    /**
         * Create a signed route URL for a named route.
         *
         * @param string $name
         * @param mixed $parameters
         * @param \DateTimeInterface|\DateInterval|int|null $expiration
         * @param bool $absolute
         * @return string 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function signedRoute($name, $parameters = [], $expiration = null, $absolute = true)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->signedRoute($name, $parameters, $expiration, $absolute);
        }
                    /**
         * Create a temporary signed route URL for a named route.
         *
         * @param string $name
         * @param \DateTimeInterface|\DateInterval|int $expiration
         * @param array $parameters
         * @param bool $absolute
         * @return string 
         * @static 
         */ 
        public static function temporarySignedRoute($name, $expiration, $parameters = [], $absolute = true)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->temporarySignedRoute($name, $expiration, $parameters, $absolute);
        }
                    /**
         * Determine if the given request has a valid signature.
         *
         * @param \Illuminate\Http\Request $request
         * @param bool $absolute
         * @return bool 
         * @static 
         */ 
        public static function hasValidSignature($request, $absolute = true)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->hasValidSignature($request, $absolute);
        }
                    /**
         * Determine if the given request has a valid signature for a relative URL.
         *
         * @param \Illuminate\Http\Request $request
         * @return bool 
         * @static 
         */ 
        public static function hasValidRelativeSignature($request)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->hasValidRelativeSignature($request);
        }
                    /**
         * Determine if the signature from the given request matches the URL.
         *
         * @param \Illuminate\Http\Request $request
         * @param bool $absolute
         * @return bool 
         * @static 
         */ 
        public static function hasCorrectSignature($request, $absolute = true)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->hasCorrectSignature($request, $absolute);
        }
                    /**
         * Determine if the expires timestamp from the given request is not from the past.
         *
         * @param \Illuminate\Http\Request $request
         * @return bool 
         * @static 
         */ 
        public static function signatureHasNotExpired($request)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->signatureHasNotExpired($request);
        }
                    /**
         * Get the URL to a named route.
         *
         * @param string $name
         * @param mixed $parameters
         * @param bool $absolute
         * @return string 
         * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
         * @static 
         */ 
        public static function route($name, $parameters = [], $absolute = true)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->route($name, $parameters, $absolute);
        }
                    /**
         * Get the URL for a given route instance.
         *
         * @param \Illuminate\Routing\Route $route
         * @param mixed $parameters
         * @param bool $absolute
         * @return string 
         * @throws \Illuminate\Routing\Exceptions\UrlGenerationException
         * @static 
         */ 
        public static function toRoute($route, $parameters, $absolute)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->toRoute($route, $parameters, $absolute);
        }
                    /**
         * Get the URL to a controller action.
         *
         * @param string|array $action
         * @param mixed $parameters
         * @param bool $absolute
         * @return string 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function action($action, $parameters = [], $absolute = true)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->action($action, $parameters, $absolute);
        }
                    /**
         * Format the array of URL parameters.
         *
         * @param mixed|array $parameters
         * @return array 
         * @static 
         */ 
        public static function formatParameters($parameters)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->formatParameters($parameters);
        }
                    /**
         * Get the base URL for the request.
         *
         * @param string $scheme
         * @param string|null $root
         * @return string 
         * @static 
         */ 
        public static function formatRoot($scheme, $root = null)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->formatRoot($scheme, $root);
        }
                    /**
         * Format the given URL segments into a single URL.
         *
         * @param string $root
         * @param string $path
         * @param \Illuminate\Routing\Route|null $route
         * @return string 
         * @static 
         */ 
        public static function format($root, $path, $route = null)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->format($root, $path, $route);
        }
                    /**
         * Determine if the given path is a valid URL.
         *
         * @param string $path
         * @return bool 
         * @static 
         */ 
        public static function isValidUrl($path)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->isValidUrl($path);
        }
                    /**
         * Set the default named parameters used by the URL generator.
         *
         * @param array $defaults
         * @return void 
         * @static 
         */ 
        public static function defaults($defaults)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        $instance->defaults($defaults);
        }
                    /**
         * Get the default named parameters used by the URL generator.
         *
         * @return array 
         * @static 
         */ 
        public static function getDefaultParameters()
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->getDefaultParameters();
        }
                    /**
         * Force the scheme for URLs.
         *
         * @param string|null $scheme
         * @return void 
         * @static 
         */ 
        public static function forceScheme($scheme)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        $instance->forceScheme($scheme);
        }
                    /**
         * Set the forced root URL.
         *
         * @param string|null $root
         * @return void 
         * @static 
         */ 
        public static function forceRootUrl($root)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        $instance->forceRootUrl($root);
        }
                    /**
         * Set a callback to be used to format the host of generated URLs.
         *
         * @param \Closure $callback
         * @return \Illuminate\Routing\UrlGenerator 
         * @static 
         */ 
        public static function formatHostUsing($callback)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->formatHostUsing($callback);
        }
                    /**
         * Set a callback to be used to format the path of generated URLs.
         *
         * @param \Closure $callback
         * @return \Illuminate\Routing\UrlGenerator 
         * @static 
         */ 
        public static function formatPathUsing($callback)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->formatPathUsing($callback);
        }
                    /**
         * Get the path formatter being used by the URL generator.
         *
         * @return \Closure 
         * @static 
         */ 
        public static function pathFormatter()
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->pathFormatter();
        }
                    /**
         * Get the request instance.
         *
         * @return \Illuminate\Http\Request 
         * @static 
         */ 
        public static function getRequest()
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->getRequest();
        }
                    /**
         * Set the current request instance.
         *
         * @param \Illuminate\Http\Request $request
         * @return void 
         * @static 
         */ 
        public static function setRequest($request)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        $instance->setRequest($request);
        }
                    /**
         * Set the route collection.
         *
         * @param \Illuminate\Routing\RouteCollectionInterface $routes
         * @return \Illuminate\Routing\UrlGenerator 
         * @static 
         */ 
        public static function setRoutes($routes)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->setRoutes($routes);
        }
                    /**
         * Set the session resolver for the generator.
         *
         * @param callable $sessionResolver
         * @return \Illuminate\Routing\UrlGenerator 
         * @static 
         */ 
        public static function setSessionResolver($sessionResolver)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->setSessionResolver($sessionResolver);
        }
                    /**
         * Set the encryption key resolver.
         *
         * @param callable $keyResolver
         * @return \Illuminate\Routing\UrlGenerator 
         * @static 
         */ 
        public static function setKeyResolver($keyResolver)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->setKeyResolver($keyResolver);
        }
                    /**
         * Set the root controller namespace.
         *
         * @param string $rootNamespace
         * @return \Illuminate\Routing\UrlGenerator 
         * @static 
         */ 
        public static function setRootControllerNamespace($rootNamespace)
        {
                        /** @var \Illuminate\Routing\UrlGenerator $instance */
                        return $instance->setRootControllerNamespace($rootNamespace);
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\Routing\UrlGenerator::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\Routing\UrlGenerator::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\Routing\UrlGenerator::hasMacro($name);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\Validation\Factory
     */ 
        class Validator {
                    /**
         * Create a new Validator instance.
         *
         * @param array $data
         * @param array $rules
         * @param array $messages
         * @param array $customAttributes
         * @return \Illuminate\Validation\Validator 
         * @static 
         */ 
        public static function make($data, $rules, $messages = [], $customAttributes = [])
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        return $instance->make($data, $rules, $messages, $customAttributes);
        }
                    /**
         * Validate the given data against the provided rules.
         *
         * @param array $data
         * @param array $rules
         * @param array $messages
         * @param array $customAttributes
         * @return array 
         * @throws \Illuminate\Validation\ValidationException
         * @static 
         */ 
        public static function validate($data, $rules, $messages = [], $customAttributes = [])
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        return $instance->validate($data, $rules, $messages, $customAttributes);
        }
                    /**
         * Register a custom validator extension.
         *
         * @param string $rule
         * @param \Closure|string $extension
         * @param string|null $message
         * @return void 
         * @static 
         */ 
        public static function extend($rule, $extension, $message = null)
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        $instance->extend($rule, $extension, $message);
        }
                    /**
         * Register a custom implicit validator extension.
         *
         * @param string $rule
         * @param \Closure|string $extension
         * @param string|null $message
         * @return void 
         * @static 
         */ 
        public static function extendImplicit($rule, $extension, $message = null)
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        $instance->extendImplicit($rule, $extension, $message);
        }
                    /**
         * Register a custom dependent validator extension.
         *
         * @param string $rule
         * @param \Closure|string $extension
         * @param string|null $message
         * @return void 
         * @static 
         */ 
        public static function extendDependent($rule, $extension, $message = null)
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        $instance->extendDependent($rule, $extension, $message);
        }
                    /**
         * Register a custom validator message replacer.
         *
         * @param string $rule
         * @param \Closure|string $replacer
         * @return void 
         * @static 
         */ 
        public static function replacer($rule, $replacer)
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        $instance->replacer($rule, $replacer);
        }
                    /**
         * Indicate that unvalidated array keys should be excluded, even if the parent array was validated.
         *
         * @return void 
         * @static 
         */ 
        public static function excludeUnvalidatedArrayKeys()
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        $instance->excludeUnvalidatedArrayKeys();
        }
                    /**
         * Set the Validator instance resolver.
         *
         * @param \Closure $resolver
         * @return void 
         * @static 
         */ 
        public static function resolver($resolver)
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        $instance->resolver($resolver);
        }
                    /**
         * Get the Translator implementation.
         *
         * @return \Illuminate\Contracts\Translation\Translator 
         * @static 
         */ 
        public static function getTranslator()
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        return $instance->getTranslator();
        }
                    /**
         * Get the Presence Verifier implementation.
         *
         * @return \Illuminate\Validation\PresenceVerifierInterface 
         * @static 
         */ 
        public static function getPresenceVerifier()
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        return $instance->getPresenceVerifier();
        }
                    /**
         * Set the Presence Verifier implementation.
         *
         * @param \Illuminate\Validation\PresenceVerifierInterface $presenceVerifier
         * @return void 
         * @static 
         */ 
        public static function setPresenceVerifier($presenceVerifier)
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        $instance->setPresenceVerifier($presenceVerifier);
        }
                    /**
         * Get the container instance used by the validation factory.
         *
         * @return \Illuminate\Contracts\Container\Container 
         * @static 
         */ 
        public static function getContainer()
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        return $instance->getContainer();
        }
                    /**
         * Set the container instance used by the validation factory.
         *
         * @param \Illuminate\Contracts\Container\Container $container
         * @return \Illuminate\Validation\Factory 
         * @static 
         */ 
        public static function setContainer($container)
        {
                        /** @var \Illuminate\Validation\Factory $instance */
                        return $instance->setContainer($container);
        }
         
    }
            /**
     * 
     *
     * @see \Illuminate\View\Factory
     */ 
        class View {
                    /**
         * Get the evaluated view contents for the given view.
         *
         * @param string $path
         * @param \Illuminate\Contracts\Support\Arrayable|array $data
         * @param array $mergeData
         * @return \Illuminate\Contracts\View\View 
         * @static 
         */ 
        public static function file($path, $data = [], $mergeData = [])
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->file($path, $data, $mergeData);
        }
                    /**
         * Get the evaluated view contents for the given view.
         *
         * @param string $view
         * @param \Illuminate\Contracts\Support\Arrayable|array $data
         * @param array $mergeData
         * @return \Illuminate\Contracts\View\View 
         * @static 
         */ 
        public static function make($view, $data = [], $mergeData = [])
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->make($view, $data, $mergeData);
        }
                    /**
         * Get the first view that actually exists from the given list.
         *
         * @param array $views
         * @param \Illuminate\Contracts\Support\Arrayable|array $data
         * @param array $mergeData
         * @return \Illuminate\Contracts\View\View 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function first($views, $data = [], $mergeData = [])
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->first($views, $data, $mergeData);
        }
                    /**
         * Get the rendered content of the view based on a given condition.
         *
         * @param bool $condition
         * @param string $view
         * @param \Illuminate\Contracts\Support\Arrayable|array $data
         * @param array $mergeData
         * @return string 
         * @static 
         */ 
        public static function renderWhen($condition, $view, $data = [], $mergeData = [])
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->renderWhen($condition, $view, $data, $mergeData);
        }
                    /**
         * Get the rendered contents of a partial from a loop.
         *
         * @param string $view
         * @param array $data
         * @param string $iterator
         * @param string $empty
         * @return string 
         * @static 
         */ 
        public static function renderEach($view, $data, $iterator, $empty = 'raw|')
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->renderEach($view, $data, $iterator, $empty);
        }
                    /**
         * Determine if a given view exists.
         *
         * @param string $view
         * @return bool 
         * @static 
         */ 
        public static function exists($view)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->exists($view);
        }
                    /**
         * Get the appropriate view engine for the given path.
         *
         * @param string $path
         * @return \Illuminate\Contracts\View\Engine 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function getEngineFromPath($path)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->getEngineFromPath($path);
        }
                    /**
         * Add a piece of shared data to the environment.
         *
         * @param array|string $key
         * @param mixed|null $value
         * @return mixed 
         * @static 
         */ 
        public static function share($key, $value = null)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->share($key, $value);
        }
                    /**
         * Increment the rendering counter.
         *
         * @return void 
         * @static 
         */ 
        public static function incrementRender()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->incrementRender();
        }
                    /**
         * Decrement the rendering counter.
         *
         * @return void 
         * @static 
         */ 
        public static function decrementRender()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->decrementRender();
        }
                    /**
         * Check if there are no active render operations.
         *
         * @return bool 
         * @static 
         */ 
        public static function doneRendering()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->doneRendering();
        }
                    /**
         * Determine if the given once token has been rendered.
         *
         * @param string $id
         * @return bool 
         * @static 
         */ 
        public static function hasRenderedOnce($id)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->hasRenderedOnce($id);
        }
                    /**
         * Mark the given once token as having been rendered.
         *
         * @param string $id
         * @return void 
         * @static 
         */ 
        public static function markAsRenderedOnce($id)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->markAsRenderedOnce($id);
        }
                    /**
         * Add a location to the array of view locations.
         *
         * @param string $location
         * @return void 
         * @static 
         */ 
        public static function addLocation($location)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->addLocation($location);
        }
                    /**
         * Add a new namespace to the loader.
         *
         * @param string $namespace
         * @param string|array $hints
         * @return \Illuminate\View\Factory 
         * @static 
         */ 
        public static function addNamespace($namespace, $hints)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->addNamespace($namespace, $hints);
        }
                    /**
         * Prepend a new namespace to the loader.
         *
         * @param string $namespace
         * @param string|array $hints
         * @return \Illuminate\View\Factory 
         * @static 
         */ 
        public static function prependNamespace($namespace, $hints)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->prependNamespace($namespace, $hints);
        }
                    /**
         * Replace the namespace hints for the given namespace.
         *
         * @param string $namespace
         * @param string|array $hints
         * @return \Illuminate\View\Factory 
         * @static 
         */ 
        public static function replaceNamespace($namespace, $hints)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->replaceNamespace($namespace, $hints);
        }
                    /**
         * Register a valid view extension and its engine.
         *
         * @param string $extension
         * @param string $engine
         * @param \Closure|null $resolver
         * @return void 
         * @static 
         */ 
        public static function addExtension($extension, $engine, $resolver = null)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->addExtension($extension, $engine, $resolver);
        }
                    /**
         * Flush all of the factory state like sections and stacks.
         *
         * @return void 
         * @static 
         */ 
        public static function flushState()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->flushState();
        }
                    /**
         * Flush all of the section contents if done rendering.
         *
         * @return void 
         * @static 
         */ 
        public static function flushStateIfDoneRendering()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->flushStateIfDoneRendering();
        }
                    /**
         * Get the extension to engine bindings.
         *
         * @return array 
         * @static 
         */ 
        public static function getExtensions()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->getExtensions();
        }
                    /**
         * Get the engine resolver instance.
         *
         * @return \Illuminate\View\Engines\EngineResolver 
         * @static 
         */ 
        public static function getEngineResolver()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->getEngineResolver();
        }
                    /**
         * Get the view finder instance.
         *
         * @return \Illuminate\View\ViewFinderInterface 
         * @static 
         */ 
        public static function getFinder()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->getFinder();
        }
                    /**
         * Set the view finder instance.
         *
         * @param \Illuminate\View\ViewFinderInterface $finder
         * @return void 
         * @static 
         */ 
        public static function setFinder($finder)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->setFinder($finder);
        }
                    /**
         * Flush the cache of views located by the finder.
         *
         * @return void 
         * @static 
         */ 
        public static function flushFinderCache()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->flushFinderCache();
        }
                    /**
         * Get the event dispatcher instance.
         *
         * @return \Illuminate\Contracts\Events\Dispatcher 
         * @static 
         */ 
        public static function getDispatcher()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->getDispatcher();
        }
                    /**
         * Set the event dispatcher instance.
         *
         * @param \Illuminate\Contracts\Events\Dispatcher $events
         * @return void 
         * @static 
         */ 
        public static function setDispatcher($events)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->setDispatcher($events);
        }
                    /**
         * Get the IoC container instance.
         *
         * @return \Illuminate\Contracts\Container\Container 
         * @static 
         */ 
        public static function getContainer()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->getContainer();
        }
                    /**
         * Set the IoC container instance.
         *
         * @param \Illuminate\Contracts\Container\Container $container
         * @return void 
         * @static 
         */ 
        public static function setContainer($container)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->setContainer($container);
        }
                    /**
         * Get an item from the shared data.
         *
         * @param string $key
         * @param mixed $default
         * @return mixed 
         * @static 
         */ 
        public static function shared($key, $default = null)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->shared($key, $default);
        }
                    /**
         * Get all of the shared data for the environment.
         *
         * @return array 
         * @static 
         */ 
        public static function getShared()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->getShared();
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Illuminate\View\Factory::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Illuminate\View\Factory::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Illuminate\View\Factory::hasMacro($name);
        }
                    /**
         * Start a component rendering process.
         *
         * @param \Illuminate\Contracts\View\View|\Illuminate\Contracts\Support\Htmlable|\Closure|string $view
         * @param array $data
         * @return void 
         * @static 
         */ 
        public static function startComponent($view, $data = [])
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->startComponent($view, $data);
        }
                    /**
         * Get the first view that actually exists from the given list, and start a component.
         *
         * @param array $names
         * @param array $data
         * @return void 
         * @static 
         */ 
        public static function startComponentFirst($names, $data = [])
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->startComponentFirst($names, $data);
        }
                    /**
         * Render the current component.
         *
         * @return string 
         * @static 
         */ 
        public static function renderComponent()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->renderComponent();
        }
                    /**
         * Start the slot rendering process.
         *
         * @param string $name
         * @param string|null $content
         * @return void 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function slot($name, $content = null)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->slot($name, $content);
        }
                    /**
         * Save the slot content for rendering.
         *
         * @return void 
         * @static 
         */ 
        public static function endSlot()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->endSlot();
        }
                    /**
         * Register a view creator event.
         *
         * @param array|string $views
         * @param \Closure|string $callback
         * @return array 
         * @static 
         */ 
        public static function creator($views, $callback)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->creator($views, $callback);
        }
                    /**
         * Register multiple view composers via an array.
         *
         * @param array $composers
         * @return array 
         * @static 
         */ 
        public static function composers($composers)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->composers($composers);
        }
                    /**
         * Register a view composer event.
         *
         * @param array|string $views
         * @param \Closure|string $callback
         * @return array 
         * @static 
         */ 
        public static function composer($views, $callback)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->composer($views, $callback);
        }
                    /**
         * Call the composer for a given view.
         *
         * @param \Illuminate\Contracts\View\View $view
         * @return void 
         * @static 
         */ 
        public static function callComposer($view)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->callComposer($view);
        }
                    /**
         * Call the creator for a given view.
         *
         * @param \Illuminate\Contracts\View\View $view
         * @return void 
         * @static 
         */ 
        public static function callCreator($view)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->callCreator($view);
        }
                    /**
         * Start injecting content into a section.
         *
         * @param string $section
         * @param string|null $content
         * @return void 
         * @static 
         */ 
        public static function startSection($section, $content = null)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->startSection($section, $content);
        }
                    /**
         * Inject inline content into a section.
         *
         * @param string $section
         * @param string $content
         * @return void 
         * @static 
         */ 
        public static function inject($section, $content)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->inject($section, $content);
        }
                    /**
         * Stop injecting content into a section and return its contents.
         *
         * @return string 
         * @static 
         */ 
        public static function yieldSection()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->yieldSection();
        }
                    /**
         * Stop injecting content into a section.
         *
         * @param bool $overwrite
         * @return string 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function stopSection($overwrite = false)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->stopSection($overwrite);
        }
                    /**
         * Stop injecting content into a section and append it.
         *
         * @return string 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function appendSection()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->appendSection();
        }
                    /**
         * Get the string contents of a section.
         *
         * @param string $section
         * @param string $default
         * @return string 
         * @static 
         */ 
        public static function yieldContent($section, $default = '')
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->yieldContent($section, $default);
        }
                    /**
         * Get the parent placeholder for the current request.
         *
         * @param string $section
         * @return string 
         * @static 
         */ 
        public static function parentPlaceholder($section = '')
        {
                        return \Illuminate\View\Factory::parentPlaceholder($section);
        }
                    /**
         * Check if the section exists.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasSection($name)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->hasSection($name);
        }
                    /**
         * Check if section does not exist.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function sectionMissing($name)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->sectionMissing($name);
        }
                    /**
         * Get the contents of a section.
         *
         * @param string $name
         * @param string|null $default
         * @return mixed 
         * @static 
         */ 
        public static function getSection($name, $default = null)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->getSection($name, $default);
        }
                    /**
         * Get the entire array of sections.
         *
         * @return array 
         * @static 
         */ 
        public static function getSections()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->getSections();
        }
                    /**
         * Flush all of the sections.
         *
         * @return void 
         * @static 
         */ 
        public static function flushSections()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->flushSections();
        }
                    /**
         * Add new loop to the stack.
         *
         * @param \Countable|array $data
         * @return void 
         * @static 
         */ 
        public static function addLoop($data)
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->addLoop($data);
        }
                    /**
         * Increment the top loop's indices.
         *
         * @return void 
         * @static 
         */ 
        public static function incrementLoopIndices()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->incrementLoopIndices();
        }
                    /**
         * Pop a loop from the top of the loop stack.
         *
         * @return void 
         * @static 
         */ 
        public static function popLoop()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->popLoop();
        }
                    /**
         * Get an instance of the last loop in the stack.
         *
         * @return \stdClass|null 
         * @static 
         */ 
        public static function getLastLoop()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->getLastLoop();
        }
                    /**
         * Get the entire loop stack.
         *
         * @return array 
         * @static 
         */ 
        public static function getLoopStack()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->getLoopStack();
        }
                    /**
         * Start injecting content into a push section.
         *
         * @param string $section
         * @param string $content
         * @return void 
         * @static 
         */ 
        public static function startPush($section, $content = '')
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->startPush($section, $content);
        }
                    /**
         * Stop injecting content into a push section.
         *
         * @return string 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function stopPush()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->stopPush();
        }
                    /**
         * Start prepending content into a push section.
         *
         * @param string $section
         * @param string $content
         * @return void 
         * @static 
         */ 
        public static function startPrepend($section, $content = '')
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->startPrepend($section, $content);
        }
                    /**
         * Stop prepending content into a push section.
         *
         * @return string 
         * @throws \InvalidArgumentException
         * @static 
         */ 
        public static function stopPrepend()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->stopPrepend();
        }
                    /**
         * Get the string contents of a push section.
         *
         * @param string $section
         * @param string $default
         * @return string 
         * @static 
         */ 
        public static function yieldPushContent($section, $default = '')
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->yieldPushContent($section, $default);
        }
                    /**
         * Flush all of the stacks.
         *
         * @return void 
         * @static 
         */ 
        public static function flushStacks()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->flushStacks();
        }
                    /**
         * Start a translation block.
         *
         * @param array $replacements
         * @return void 
         * @static 
         */ 
        public static function startTranslation($replacements = [])
        {
                        /** @var \Illuminate\View\Factory $instance */
                        $instance->startTranslation($replacements);
        }
                    /**
         * Render the current translation.
         *
         * @return string 
         * @static 
         */ 
        public static function renderTranslation()
        {
                        /** @var \Illuminate\View\Factory $instance */
                        return $instance->renderTranslation();
        }
         
    }
     
}

    namespace Carbon { 
            /**
     * A simple API extension for DateTime.
     * 
     * <autodoc generated by `composer phpdoc`>
     *
     * @property int                 $year
     * @property int                 $yearIso
     * @property int                 $month
     * @property int                 $day
     * @property int                 $hour
     * @property int                 $minute
     * @property int                 $second
     * @property int                 $micro
     * @property int                 $microsecond
     * @property int|float|string    $timestamp                                                                           seconds since the Unix Epoch
     * @property string              $englishDayOfWeek                                                                    the day of week in English
     * @property string              $shortEnglishDayOfWeek                                                               the abbreviated day of week in English
     * @property string              $englishMonth                                                                        the month in English
     * @property string              $shortEnglishMonth                                                                   the abbreviated month in English
     * @property string              $localeDayOfWeek                                                                     the day of week in current locale LC_TIME
     * @property string              $shortLocaleDayOfWeek                                                                the abbreviated day of week in current locale LC_TIME
     * @property string              $localeMonth                                                                         the month in current locale LC_TIME
     * @property string              $shortLocaleMonth                                                                    the abbreviated month in current locale LC_TIME
     * @property int                 $milliseconds
     * @property int                 $millisecond
     * @property int                 $milli
     * @property int                 $week                                                                                1 through 53
     * @property int                 $isoWeek                                                                             1 through 53
     * @property int                 $weekYear                                                                            year according to week format
     * @property int                 $isoWeekYear                                                                         year according to ISO week format
     * @property int                 $dayOfYear                                                                           1 through 366
     * @property int                 $age                                                                                 does a diffInYears() with default parameters
     * @property int                 $offset                                                                              the timezone offset in seconds from UTC
     * @property int                 $offsetMinutes                                                                       the timezone offset in minutes from UTC
     * @property int                 $offsetHours                                                                         the timezone offset in hours from UTC
     * @property CarbonTimeZone      $timezone                                                                            the current timezone
     * @property CarbonTimeZone      $tz                                                                                  alias of $timezone
     * @property-read int                 $dayOfWeek                                                                           0 (for Sunday) through 6 (for Saturday)
     * @property-read int                 $dayOfWeekIso                                                                        1 (for Monday) through 7 (for Sunday)
     * @property-read int                 $weekOfYear                                                                          ISO-8601 week number of year, weeks starting on Monday
     * @property-read int                 $daysInMonth                                                                         number of days in the given month
     * @property-read string              $latinMeridiem                                                                       "am"/"pm" (Ante meridiem or Post meridiem latin lowercase mark)
     * @property-read string              $latinUpperMeridiem                                                                  "AM"/"PM" (Ante meridiem or Post meridiem latin uppercase mark)
     * @property-read string              $timezoneAbbreviatedName                                                             the current timezone abbreviated name
     * @property-read string              $tzAbbrName                                                                          alias of $timezoneAbbreviatedName
     * @property-read string              $dayName                                                                             long name of weekday translated according to Carbon locale, in english if no translation available for current language
     * @property-read string              $shortDayName                                                                        short name of weekday translated according to Carbon locale, in english if no translation available for current language
     * @property-read string              $minDayName                                                                          very short name of weekday translated according to Carbon locale, in english if no translation available for current language
     * @property-read string              $monthName                                                                           long name of month translated according to Carbon locale, in english if no translation available for current language
     * @property-read string              $shortMonthName                                                                      short name of month translated according to Carbon locale, in english if no translation available for current language
     * @property-read string              $meridiem                                                                            lowercase meridiem mark translated according to Carbon locale, in latin if no translation available for current language
     * @property-read string              $upperMeridiem                                                                       uppercase meridiem mark translated according to Carbon locale, in latin if no translation available for current language
     * @property-read int                 $noZeroHour                                                                          current hour from 1 to 24
     * @property-read int                 $weeksInYear                                                                         51 through 53
     * @property-read int                 $isoWeeksInYear                                                                      51 through 53
     * @property-read int                 $weekOfMonth                                                                         1 through 5
     * @property-read int                 $weekNumberInMonth                                                                   1 through 5
     * @property-read int                 $firstWeekDay                                                                        0 through 6
     * @property-read int                 $lastWeekDay                                                                         0 through 6
     * @property-read int                 $daysInYear                                                                          365 or 366
     * @property-read int                 $quarter                                                                             the quarter of this instance, 1 - 4
     * @property-read int                 $decade                                                                              the decade of this instance
     * @property-read int                 $century                                                                             the century of this instance
     * @property-read int                 $millennium                                                                          the millennium of this instance
     * @property-read bool                $dst                                                                                 daylight savings time indicator, true if DST, false otherwise
     * @property-read bool                $local                                                                               checks if the timezone is local, true if local, false otherwise
     * @property-read bool                $utc                                                                                 checks if the timezone is UTC, true if UTC, false otherwise
     * @property-read string              $timezoneName                                                                        the current timezone name
     * @property-read string              $tzName                                                                              alias of $timezoneName
     * @property-read string              $locale                                                                              locale of the current instance
     * @method bool                isUtc()                                                                              Check if the current instance has UTC timezone. (Both isUtc and isUTC cases are valid.)
     * @method bool                isLocal()                                                                            Check if the current instance has non-UTC timezone.
     * @method bool                isValid()                                                                            Check if the current instance is a valid date.
     * @method bool                isDST()                                                                              Check if the current instance is in a daylight saving time.
     * @method bool                isSunday()                                                                           Checks if the instance day is sunday.
     * @method bool                isMonday()                                                                           Checks if the instance day is monday.
     * @method bool                isTuesday()                                                                          Checks if the instance day is tuesday.
     * @method bool                isWednesday()                                                                        Checks if the instance day is wednesday.
     * @method bool                isThursday()                                                                         Checks if the instance day is thursday.
     * @method bool                isFriday()                                                                           Checks if the instance day is friday.
     * @method bool                isSaturday()                                                                         Checks if the instance day is saturday.
     * @method bool                isSameYear(Carbon|DateTimeInterface|string|null $date = null)                        Checks if the given date is in the same year as the instance. If null passed, compare to now (with the same timezone).
     * @method bool                isCurrentYear()                                                                      Checks if the instance is in the same year as the current moment.
     * @method bool                isNextYear()                                                                         Checks if the instance is in the same year as the current moment next year.
     * @method bool                isLastYear()                                                                         Checks if the instance is in the same year as the current moment last year.
     * @method bool                isSameWeek(Carbon|DateTimeInterface|string|null $date = null)                        Checks if the given date is in the same week as the instance. If null passed, compare to now (with the same timezone).
     * @method bool                isCurrentWeek()                                                                      Checks if the instance is in the same week as the current moment.
     * @method bool                isNextWeek()                                                                         Checks if the instance is in the same week as the current moment next week.
     * @method bool                isLastWeek()                                                                         Checks if the instance is in the same week as the current moment last week.
     * @method bool                isSameDay(Carbon|DateTimeInterface|string|null $date = null)                         Checks if the given date is in the same day as the instance. If null passed, compare to now (with the same timezone).
     * @method bool                isCurrentDay()                                                                       Checks if the instance is in the same day as the current moment.
     * @method bool                isNextDay()                                                                          Checks if the instance is in the same day as the current moment next day.
     * @method bool                isLastDay()                                                                          Checks if the instance is in the same day as the current moment last day.
     * @method bool                isSameHour(Carbon|DateTimeInterface|string|null $date = null)                        Checks if the given date is in the same hour as the instance. If null passed, compare to now (with the same timezone).
     * @method bool                isCurrentHour()                                                                      Checks if the instance is in the same hour as the current moment.
     * @method bool                isNextHour()                                                                         Checks if the instance is in the same hour as the current moment next hour.
     * @method bool                isLastHour()                                                                         Checks if the instance is in the same hour as the current moment last hour.
     * @method bool                isSameMinute(Carbon|DateTimeInterface|string|null $date = null)                      Checks if the given date is in the same minute as the instance. If null passed, compare to now (with the same timezone).
     * @method bool                isCurrentMinute()                                                                    Checks if the instance is in the same minute as the current moment.
     * @method bool                isNextMinute()                                                                       Checks if the instance is in the same minute as the current moment next minute.
     * @method bool                isLastMinute()                                                                       Checks if the instance is in the same minute as the current moment last minute.
     * @method bool                isSameSecond(Carbon|DateTimeInterface|string|null $date = null)                      Checks if the given date is in the same second as the instance. If null passed, compare to now (with the same timezone).
     * @method bool                isCurrentSecond()                                                                    Checks if the instance is in the same second as the current moment.
     * @method bool                isNextSecond()                                                                       Checks if the instance is in the same second as the current moment next second.
     * @method bool                isLastSecond()                                                                       Checks if the instance is in the same second as the current moment last second.
     * @method bool                isSameMicro(Carbon|DateTimeInterface|string|null $date = null)                       Checks if the given date is in the same microsecond as the instance. If null passed, compare to now (with the same timezone).
     * @method bool                isCurrentMicro()                                                                     Checks if the instance is in the same microsecond as the current moment.
     * @method bool                isNextMicro()                                                                        Checks if the instance is in the same microsecond as the current moment next microsecond.
     * @method bool                isLastMicro()                                                                        Checks if the instance is in the same microsecond as the current moment last microsecond.
     * @method bool                isSameMicrosecond(Carbon|DateTimeInterface|string|null $date = null)                 Checks if the given date is in the same microsecond as the instance. If null passed, compare to now (with the same timezone).
     * @method bool                isCurrentMicrosecond()                                                               Checks if the instance is in the same microsecond as the current moment.
     * @method bool                isNextMicrosecond()                                                                  Checks if the instance is in the same microsecond as the current moment next microsecond.
     * @method bool                isLastMicrosecond()                                                                  Checks if the instance is in the same microsecond as the current moment last microsecond.
     * @method bool                isCurrentMonth()                                                                     Checks if the instance is in the same month as the current moment.
     * @method bool                isNextMonth()                                                                        Checks if the instance is in the same month as the current moment next month.
     * @method bool                isLastMonth()                                                                        Checks if the instance is in the same month as the current moment last month.
     * @method bool                isCurrentQuarter()                                                                   Checks if the instance is in the same quarter as the current moment.
     * @method bool                isNextQuarter()                                                                      Checks if the instance is in the same quarter as the current moment next quarter.
     * @method bool                isLastQuarter()                                                                      Checks if the instance is in the same quarter as the current moment last quarter.
     * @method bool                isSameDecade(Carbon|DateTimeInterface|string|null $date = null)                      Checks if the given date is in the same decade as the instance. If null passed, compare to now (with the same timezone).
     * @method bool                isCurrentDecade()                                                                    Checks if the instance is in the same decade as the current moment.
     * @method bool                isNextDecade()                                                                       Checks if the instance is in the same decade as the current moment next decade.
     * @method bool                isLastDecade()                                                                       Checks if the instance is in the same decade as the current moment last decade.
     * @method bool                isSameCentury(Carbon|DateTimeInterface|string|null $date = null)                     Checks if the given date is in the same century as the instance. If null passed, compare to now (with the same timezone).
     * @method bool                isCurrentCentury()                                                                   Checks if the instance is in the same century as the current moment.
     * @method bool                isNextCentury()                                                                      Checks if the instance is in the same century as the current moment next century.
     * @method bool                isLastCentury()                                                                      Checks if the instance is in the same century as the current moment last century.
     * @method bool                isSameMillennium(Carbon|DateTimeInterface|string|null $date = null)                  Checks if the given date is in the same millennium as the instance. If null passed, compare to now (with the same timezone).
     * @method bool                isCurrentMillennium()                                                                Checks if the instance is in the same millennium as the current moment.
     * @method bool                isNextMillennium()                                                                   Checks if the instance is in the same millennium as the current moment next millennium.
     * @method bool                isLastMillennium()                                                                   Checks if the instance is in the same millennium as the current moment last millennium.
     * @method $this               years(int $value)                                                                    Set current instance year to the given value.
     * @method $this               year(int $value)                                                                     Set current instance year to the given value.
     * @method $this               setYears(int $value)                                                                 Set current instance year to the given value.
     * @method $this               setYear(int $value)                                                                  Set current instance year to the given value.
     * @method $this               months(int $value)                                                                   Set current instance month to the given value.
     * @method $this               month(int $value)                                                                    Set current instance month to the given value.
     * @method $this               setMonths(int $value)                                                                Set current instance month to the given value.
     * @method $this               setMonth(int $value)                                                                 Set current instance month to the given value.
     * @method $this               days(int $value)                                                                     Set current instance day to the given value.
     * @method $this               day(int $value)                                                                      Set current instance day to the given value.
     * @method $this               setDays(int $value)                                                                  Set current instance day to the given value.
     * @method $this               setDay(int $value)                                                                   Set current instance day to the given value.
     * @method $this               hours(int $value)                                                                    Set current instance hour to the given value.
     * @method $this               hour(int $value)                                                                     Set current instance hour to the given value.
     * @method $this               setHours(int $value)                                                                 Set current instance hour to the given value.
     * @method $this               setHour(int $value)                                                                  Set current instance hour to the given value.
     * @method $this               minutes(int $value)                                                                  Set current instance minute to the given value.
     * @method $this               minute(int $value)                                                                   Set current instance minute to the given value.
     * @method $this               setMinutes(int $value)                                                               Set current instance minute to the given value.
     * @method $this               setMinute(int $value)                                                                Set current instance minute to the given value.
     * @method $this               seconds(int $value)                                                                  Set current instance second to the given value.
     * @method $this               second(int $value)                                                                   Set current instance second to the given value.
     * @method $this               setSeconds(int $value)                                                               Set current instance second to the given value.
     * @method $this               setSecond(int $value)                                                                Set current instance second to the given value.
     * @method $this               millis(int $value)                                                                   Set current instance millisecond to the given value.
     * @method $this               milli(int $value)                                                                    Set current instance millisecond to the given value.
     * @method $this               setMillis(int $value)                                                                Set current instance millisecond to the given value.
     * @method $this               setMilli(int $value)                                                                 Set current instance millisecond to the given value.
     * @method $this               milliseconds(int $value)                                                             Set current instance millisecond to the given value.
     * @method $this               millisecond(int $value)                                                              Set current instance millisecond to the given value.
     * @method $this               setMilliseconds(int $value)                                                          Set current instance millisecond to the given value.
     * @method $this               setMillisecond(int $value)                                                           Set current instance millisecond to the given value.
     * @method $this               micros(int $value)                                                                   Set current instance microsecond to the given value.
     * @method $this               micro(int $value)                                                                    Set current instance microsecond to the given value.
     * @method $this               setMicros(int $value)                                                                Set current instance microsecond to the given value.
     * @method $this               setMicro(int $value)                                                                 Set current instance microsecond to the given value.
     * @method $this               microseconds(int $value)                                                             Set current instance microsecond to the given value.
     * @method $this               microsecond(int $value)                                                              Set current instance microsecond to the given value.
     * @method $this               setMicroseconds(int $value)                                                          Set current instance microsecond to the given value.
     * @method $this               setMicrosecond(int $value)                                                           Set current instance microsecond to the given value.
     * @method $this               addYears(int $value = 1)                                                             Add years (the $value count passed in) to the instance (using date interval).
     * @method $this               addYear()                                                                            Add one year to the instance (using date interval).
     * @method $this               subYears(int $value = 1)                                                             Sub years (the $value count passed in) to the instance (using date interval).
     * @method $this               subYear()                                                                            Sub one year to the instance (using date interval).
     * @method $this               addYearsWithOverflow(int $value = 1)                                                 Add years (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addYearWithOverflow()                                                                Add one year to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subYearsWithOverflow(int $value = 1)                                                 Sub years (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subYearWithOverflow()                                                                Sub one year to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addYearsWithoutOverflow(int $value = 1)                                              Add years (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addYearWithoutOverflow()                                                             Add one year to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subYearsWithoutOverflow(int $value = 1)                                              Sub years (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subYearWithoutOverflow()                                                             Sub one year to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addYearsWithNoOverflow(int $value = 1)                                               Add years (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addYearWithNoOverflow()                                                              Add one year to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subYearsWithNoOverflow(int $value = 1)                                               Sub years (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subYearWithNoOverflow()                                                              Sub one year to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addYearsNoOverflow(int $value = 1)                                                   Add years (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addYearNoOverflow()                                                                  Add one year to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subYearsNoOverflow(int $value = 1)                                                   Sub years (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subYearNoOverflow()                                                                  Sub one year to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addMonths(int $value = 1)                                                            Add months (the $value count passed in) to the instance (using date interval).
     * @method $this               addMonth()                                                                           Add one month to the instance (using date interval).
     * @method $this               subMonths(int $value = 1)                                                            Sub months (the $value count passed in) to the instance (using date interval).
     * @method $this               subMonth()                                                                           Sub one month to the instance (using date interval).
     * @method $this               addMonthsWithOverflow(int $value = 1)                                                Add months (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addMonthWithOverflow()                                                               Add one month to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subMonthsWithOverflow(int $value = 1)                                                Sub months (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subMonthWithOverflow()                                                               Sub one month to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addMonthsWithoutOverflow(int $value = 1)                                             Add months (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addMonthWithoutOverflow()                                                            Add one month to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMonthsWithoutOverflow(int $value = 1)                                             Sub months (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMonthWithoutOverflow()                                                            Sub one month to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addMonthsWithNoOverflow(int $value = 1)                                              Add months (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addMonthWithNoOverflow()                                                             Add one month to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMonthsWithNoOverflow(int $value = 1)                                              Sub months (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMonthWithNoOverflow()                                                             Sub one month to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addMonthsNoOverflow(int $value = 1)                                                  Add months (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addMonthNoOverflow()                                                                 Add one month to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMonthsNoOverflow(int $value = 1)                                                  Sub months (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMonthNoOverflow()                                                                 Sub one month to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addDays(int $value = 1)                                                              Add days (the $value count passed in) to the instance (using date interval).
     * @method $this               addDay()                                                                             Add one day to the instance (using date interval).
     * @method $this               subDays(int $value = 1)                                                              Sub days (the $value count passed in) to the instance (using date interval).
     * @method $this               subDay()                                                                             Sub one day to the instance (using date interval).
     * @method $this               addHours(int $value = 1)                                                             Add hours (the $value count passed in) to the instance (using date interval).
     * @method $this               addHour()                                                                            Add one hour to the instance (using date interval).
     * @method $this               subHours(int $value = 1)                                                             Sub hours (the $value count passed in) to the instance (using date interval).
     * @method $this               subHour()                                                                            Sub one hour to the instance (using date interval).
     * @method $this               addMinutes(int $value = 1)                                                           Add minutes (the $value count passed in) to the instance (using date interval).
     * @method $this               addMinute()                                                                          Add one minute to the instance (using date interval).
     * @method $this               subMinutes(int $value = 1)                                                           Sub minutes (the $value count passed in) to the instance (using date interval).
     * @method $this               subMinute()                                                                          Sub one minute to the instance (using date interval).
     * @method $this               addSeconds(int $value = 1)                                                           Add seconds (the $value count passed in) to the instance (using date interval).
     * @method $this               addSecond()                                                                          Add one second to the instance (using date interval).
     * @method $this               subSeconds(int $value = 1)                                                           Sub seconds (the $value count passed in) to the instance (using date interval).
     * @method $this               subSecond()                                                                          Sub one second to the instance (using date interval).
     * @method $this               addMillis(int $value = 1)                                                            Add milliseconds (the $value count passed in) to the instance (using date interval).
     * @method $this               addMilli()                                                                           Add one millisecond to the instance (using date interval).
     * @method $this               subMillis(int $value = 1)                                                            Sub milliseconds (the $value count passed in) to the instance (using date interval).
     * @method $this               subMilli()                                                                           Sub one millisecond to the instance (using date interval).
     * @method $this               addMilliseconds(int $value = 1)                                                      Add milliseconds (the $value count passed in) to the instance (using date interval).
     * @method $this               addMillisecond()                                                                     Add one millisecond to the instance (using date interval).
     * @method $this               subMilliseconds(int $value = 1)                                                      Sub milliseconds (the $value count passed in) to the instance (using date interval).
     * @method $this               subMillisecond()                                                                     Sub one millisecond to the instance (using date interval).
     * @method $this               addMicros(int $value = 1)                                                            Add microseconds (the $value count passed in) to the instance (using date interval).
     * @method $this               addMicro()                                                                           Add one microsecond to the instance (using date interval).
     * @method $this               subMicros(int $value = 1)                                                            Sub microseconds (the $value count passed in) to the instance (using date interval).
     * @method $this               subMicro()                                                                           Sub one microsecond to the instance (using date interval).
     * @method $this               addMicroseconds(int $value = 1)                                                      Add microseconds (the $value count passed in) to the instance (using date interval).
     * @method $this               addMicrosecond()                                                                     Add one microsecond to the instance (using date interval).
     * @method $this               subMicroseconds(int $value = 1)                                                      Sub microseconds (the $value count passed in) to the instance (using date interval).
     * @method $this               subMicrosecond()                                                                     Sub one microsecond to the instance (using date interval).
     * @method $this               addMillennia(int $value = 1)                                                         Add millennia (the $value count passed in) to the instance (using date interval).
     * @method $this               addMillennium()                                                                      Add one millennium to the instance (using date interval).
     * @method $this               subMillennia(int $value = 1)                                                         Sub millennia (the $value count passed in) to the instance (using date interval).
     * @method $this               subMillennium()                                                                      Sub one millennium to the instance (using date interval).
     * @method $this               addMillenniaWithOverflow(int $value = 1)                                             Add millennia (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addMillenniumWithOverflow()                                                          Add one millennium to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subMillenniaWithOverflow(int $value = 1)                                             Sub millennia (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subMillenniumWithOverflow()                                                          Sub one millennium to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addMillenniaWithoutOverflow(int $value = 1)                                          Add millennia (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addMillenniumWithoutOverflow()                                                       Add one millennium to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMillenniaWithoutOverflow(int $value = 1)                                          Sub millennia (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMillenniumWithoutOverflow()                                                       Sub one millennium to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addMillenniaWithNoOverflow(int $value = 1)                                           Add millennia (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addMillenniumWithNoOverflow()                                                        Add one millennium to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMillenniaWithNoOverflow(int $value = 1)                                           Sub millennia (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMillenniumWithNoOverflow()                                                        Sub one millennium to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addMillenniaNoOverflow(int $value = 1)                                               Add millennia (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addMillenniumNoOverflow()                                                            Add one millennium to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMillenniaNoOverflow(int $value = 1)                                               Sub millennia (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subMillenniumNoOverflow()                                                            Sub one millennium to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addCenturies(int $value = 1)                                                         Add centuries (the $value count passed in) to the instance (using date interval).
     * @method $this               addCentury()                                                                         Add one century to the instance (using date interval).
     * @method $this               subCenturies(int $value = 1)                                                         Sub centuries (the $value count passed in) to the instance (using date interval).
     * @method $this               subCentury()                                                                         Sub one century to the instance (using date interval).
     * @method $this               addCenturiesWithOverflow(int $value = 1)                                             Add centuries (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addCenturyWithOverflow()                                                             Add one century to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subCenturiesWithOverflow(int $value = 1)                                             Sub centuries (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subCenturyWithOverflow()                                                             Sub one century to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addCenturiesWithoutOverflow(int $value = 1)                                          Add centuries (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addCenturyWithoutOverflow()                                                          Add one century to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subCenturiesWithoutOverflow(int $value = 1)                                          Sub centuries (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subCenturyWithoutOverflow()                                                          Sub one century to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addCenturiesWithNoOverflow(int $value = 1)                                           Add centuries (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addCenturyWithNoOverflow()                                                           Add one century to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subCenturiesWithNoOverflow(int $value = 1)                                           Sub centuries (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subCenturyWithNoOverflow()                                                           Sub one century to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addCenturiesNoOverflow(int $value = 1)                                               Add centuries (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addCenturyNoOverflow()                                                               Add one century to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subCenturiesNoOverflow(int $value = 1)                                               Sub centuries (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subCenturyNoOverflow()                                                               Sub one century to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addDecades(int $value = 1)                                                           Add decades (the $value count passed in) to the instance (using date interval).
     * @method $this               addDecade()                                                                          Add one decade to the instance (using date interval).
     * @method $this               subDecades(int $value = 1)                                                           Sub decades (the $value count passed in) to the instance (using date interval).
     * @method $this               subDecade()                                                                          Sub one decade to the instance (using date interval).
     * @method $this               addDecadesWithOverflow(int $value = 1)                                               Add decades (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addDecadeWithOverflow()                                                              Add one decade to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subDecadesWithOverflow(int $value = 1)                                               Sub decades (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subDecadeWithOverflow()                                                              Sub one decade to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addDecadesWithoutOverflow(int $value = 1)                                            Add decades (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addDecadeWithoutOverflow()                                                           Add one decade to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subDecadesWithoutOverflow(int $value = 1)                                            Sub decades (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subDecadeWithoutOverflow()                                                           Sub one decade to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addDecadesWithNoOverflow(int $value = 1)                                             Add decades (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addDecadeWithNoOverflow()                                                            Add one decade to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subDecadesWithNoOverflow(int $value = 1)                                             Sub decades (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subDecadeWithNoOverflow()                                                            Sub one decade to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addDecadesNoOverflow(int $value = 1)                                                 Add decades (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addDecadeNoOverflow()                                                                Add one decade to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subDecadesNoOverflow(int $value = 1)                                                 Sub decades (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subDecadeNoOverflow()                                                                Sub one decade to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addQuarters(int $value = 1)                                                          Add quarters (the $value count passed in) to the instance (using date interval).
     * @method $this               addQuarter()                                                                         Add one quarter to the instance (using date interval).
     * @method $this               subQuarters(int $value = 1)                                                          Sub quarters (the $value count passed in) to the instance (using date interval).
     * @method $this               subQuarter()                                                                         Sub one quarter to the instance (using date interval).
     * @method $this               addQuartersWithOverflow(int $value = 1)                                              Add quarters (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addQuarterWithOverflow()                                                             Add one quarter to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subQuartersWithOverflow(int $value = 1)                                              Sub quarters (the $value count passed in) to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               subQuarterWithOverflow()                                                             Sub one quarter to the instance (using date interval) with overflow explicitly allowed.
     * @method $this               addQuartersWithoutOverflow(int $value = 1)                                           Add quarters (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addQuarterWithoutOverflow()                                                          Add one quarter to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subQuartersWithoutOverflow(int $value = 1)                                           Sub quarters (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subQuarterWithoutOverflow()                                                          Sub one quarter to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addQuartersWithNoOverflow(int $value = 1)                                            Add quarters (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addQuarterWithNoOverflow()                                                           Add one quarter to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subQuartersWithNoOverflow(int $value = 1)                                            Sub quarters (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subQuarterWithNoOverflow()                                                           Sub one quarter to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addQuartersNoOverflow(int $value = 1)                                                Add quarters (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addQuarterNoOverflow()                                                               Add one quarter to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subQuartersNoOverflow(int $value = 1)                                                Sub quarters (the $value count passed in) to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               subQuarterNoOverflow()                                                               Sub one quarter to the instance (using date interval) with overflow explicitly forbidden.
     * @method $this               addWeeks(int $value = 1)                                                             Add weeks (the $value count passed in) to the instance (using date interval).
     * @method $this               addWeek()                                                                            Add one week to the instance (using date interval).
     * @method $this               subWeeks(int $value = 1)                                                             Sub weeks (the $value count passed in) to the instance (using date interval).
     * @method $this               subWeek()                                                                            Sub one week to the instance (using date interval).
     * @method $this               addWeekdays(int $value = 1)                                                          Add weekdays (the $value count passed in) to the instance (using date interval).
     * @method $this               addWeekday()                                                                         Add one weekday to the instance (using date interval).
     * @method $this               subWeekdays(int $value = 1)                                                          Sub weekdays (the $value count passed in) to the instance (using date interval).
     * @method $this               subWeekday()                                                                         Sub one weekday to the instance (using date interval).
     * @method $this               addRealMicros(int $value = 1)                                                        Add microseconds (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealMicro()                                                                       Add one microsecond to the instance (using timestamp).
     * @method $this               subRealMicros(int $value = 1)                                                        Sub microseconds (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealMicro()                                                                       Sub one microsecond to the instance (using timestamp).
     * @method CarbonPeriod        microsUntil($endDate = null, int $factor = 1)                                        Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each microsecond or every X microseconds if a factor is given.
     * @method $this               addRealMicroseconds(int $value = 1)                                                  Add microseconds (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealMicrosecond()                                                                 Add one microsecond to the instance (using timestamp).
     * @method $this               subRealMicroseconds(int $value = 1)                                                  Sub microseconds (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealMicrosecond()                                                                 Sub one microsecond to the instance (using timestamp).
     * @method CarbonPeriod        microsecondsUntil($endDate = null, int $factor = 1)                                  Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each microsecond or every X microseconds if a factor is given.
     * @method $this               addRealMillis(int $value = 1)                                                        Add milliseconds (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealMilli()                                                                       Add one millisecond to the instance (using timestamp).
     * @method $this               subRealMillis(int $value = 1)                                                        Sub milliseconds (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealMilli()                                                                       Sub one millisecond to the instance (using timestamp).
     * @method CarbonPeriod        millisUntil($endDate = null, int $factor = 1)                                        Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each millisecond or every X milliseconds if a factor is given.
     * @method $this               addRealMilliseconds(int $value = 1)                                                  Add milliseconds (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealMillisecond()                                                                 Add one millisecond to the instance (using timestamp).
     * @method $this               subRealMilliseconds(int $value = 1)                                                  Sub milliseconds (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealMillisecond()                                                                 Sub one millisecond to the instance (using timestamp).
     * @method CarbonPeriod        millisecondsUntil($endDate = null, int $factor = 1)                                  Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each millisecond or every X milliseconds if a factor is given.
     * @method $this               addRealSeconds(int $value = 1)                                                       Add seconds (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealSecond()                                                                      Add one second to the instance (using timestamp).
     * @method $this               subRealSeconds(int $value = 1)                                                       Sub seconds (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealSecond()                                                                      Sub one second to the instance (using timestamp).
     * @method CarbonPeriod        secondsUntil($endDate = null, int $factor = 1)                                       Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each second or every X seconds if a factor is given.
     * @method $this               addRealMinutes(int $value = 1)                                                       Add minutes (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealMinute()                                                                      Add one minute to the instance (using timestamp).
     * @method $this               subRealMinutes(int $value = 1)                                                       Sub minutes (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealMinute()                                                                      Sub one minute to the instance (using timestamp).
     * @method CarbonPeriod        minutesUntil($endDate = null, int $factor = 1)                                       Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each minute or every X minutes if a factor is given.
     * @method $this               addRealHours(int $value = 1)                                                         Add hours (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealHour()                                                                        Add one hour to the instance (using timestamp).
     * @method $this               subRealHours(int $value = 1)                                                         Sub hours (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealHour()                                                                        Sub one hour to the instance (using timestamp).
     * @method CarbonPeriod        hoursUntil($endDate = null, int $factor = 1)                                         Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each hour or every X hours if a factor is given.
     * @method $this               addRealDays(int $value = 1)                                                          Add days (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealDay()                                                                         Add one day to the instance (using timestamp).
     * @method $this               subRealDays(int $value = 1)                                                          Sub days (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealDay()                                                                         Sub one day to the instance (using timestamp).
     * @method CarbonPeriod        daysUntil($endDate = null, int $factor = 1)                                          Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each day or every X days if a factor is given.
     * @method $this               addRealWeeks(int $value = 1)                                                         Add weeks (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealWeek()                                                                        Add one week to the instance (using timestamp).
     * @method $this               subRealWeeks(int $value = 1)                                                         Sub weeks (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealWeek()                                                                        Sub one week to the instance (using timestamp).
     * @method CarbonPeriod        weeksUntil($endDate = null, int $factor = 1)                                         Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each week or every X weeks if a factor is given.
     * @method $this               addRealMonths(int $value = 1)                                                        Add months (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealMonth()                                                                       Add one month to the instance (using timestamp).
     * @method $this               subRealMonths(int $value = 1)                                                        Sub months (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealMonth()                                                                       Sub one month to the instance (using timestamp).
     * @method CarbonPeriod        monthsUntil($endDate = null, int $factor = 1)                                        Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each month or every X months if a factor is given.
     * @method $this               addRealQuarters(int $value = 1)                                                      Add quarters (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealQuarter()                                                                     Add one quarter to the instance (using timestamp).
     * @method $this               subRealQuarters(int $value = 1)                                                      Sub quarters (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealQuarter()                                                                     Sub one quarter to the instance (using timestamp).
     * @method CarbonPeriod        quartersUntil($endDate = null, int $factor = 1)                                      Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each quarter or every X quarters if a factor is given.
     * @method $this               addRealYears(int $value = 1)                                                         Add years (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealYear()                                                                        Add one year to the instance (using timestamp).
     * @method $this               subRealYears(int $value = 1)                                                         Sub years (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealYear()                                                                        Sub one year to the instance (using timestamp).
     * @method CarbonPeriod        yearsUntil($endDate = null, int $factor = 1)                                         Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each year or every X years if a factor is given.
     * @method $this               addRealDecades(int $value = 1)                                                       Add decades (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealDecade()                                                                      Add one decade to the instance (using timestamp).
     * @method $this               subRealDecades(int $value = 1)                                                       Sub decades (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealDecade()                                                                      Sub one decade to the instance (using timestamp).
     * @method CarbonPeriod        decadesUntil($endDate = null, int $factor = 1)                                       Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each decade or every X decades if a factor is given.
     * @method $this               addRealCenturies(int $value = 1)                                                     Add centuries (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealCentury()                                                                     Add one century to the instance (using timestamp).
     * @method $this               subRealCenturies(int $value = 1)                                                     Sub centuries (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealCentury()                                                                     Sub one century to the instance (using timestamp).
     * @method CarbonPeriod        centuriesUntil($endDate = null, int $factor = 1)                                     Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each century or every X centuries if a factor is given.
     * @method $this               addRealMillennia(int $value = 1)                                                     Add millennia (the $value count passed in) to the instance (using timestamp).
     * @method $this               addRealMillennium()                                                                  Add one millennium to the instance (using timestamp).
     * @method $this               subRealMillennia(int $value = 1)                                                     Sub millennia (the $value count passed in) to the instance (using timestamp).
     * @method $this               subRealMillennium()                                                                  Sub one millennium to the instance (using timestamp).
     * @method CarbonPeriod        millenniaUntil($endDate = null, int $factor = 1)                                     Return an iterable period from current date to given end (string, DateTime or Carbon instance) for each millennium or every X millennia if a factor is given.
     * @method $this               roundYear(float $precision = 1, string $function = "round")                          Round the current instance year with given precision using the given function.
     * @method $this               roundYears(float $precision = 1, string $function = "round")                         Round the current instance year with given precision using the given function.
     * @method $this               floorYear(float $precision = 1)                                                      Truncate the current instance year with given precision.
     * @method $this               floorYears(float $precision = 1)                                                     Truncate the current instance year with given precision.
     * @method $this               ceilYear(float $precision = 1)                                                       Ceil the current instance year with given precision.
     * @method $this               ceilYears(float $precision = 1)                                                      Ceil the current instance year with given precision.
     * @method $this               roundMonth(float $precision = 1, string $function = "round")                         Round the current instance month with given precision using the given function.
     * @method $this               roundMonths(float $precision = 1, string $function = "round")                        Round the current instance month with given precision using the given function.
     * @method $this               floorMonth(float $precision = 1)                                                     Truncate the current instance month with given precision.
     * @method $this               floorMonths(float $precision = 1)                                                    Truncate the current instance month with given precision.
     * @method $this               ceilMonth(float $precision = 1)                                                      Ceil the current instance month with given precision.
     * @method $this               ceilMonths(float $precision = 1)                                                     Ceil the current instance month with given precision.
     * @method $this               roundDay(float $precision = 1, string $function = "round")                           Round the current instance day with given precision using the given function.
     * @method $this               roundDays(float $precision = 1, string $function = "round")                          Round the current instance day with given precision using the given function.
     * @method $this               floorDay(float $precision = 1)                                                       Truncate the current instance day with given precision.
     * @method $this               floorDays(float $precision = 1)                                                      Truncate the current instance day with given precision.
     * @method $this               ceilDay(float $precision = 1)                                                        Ceil the current instance day with given precision.
     * @method $this               ceilDays(float $precision = 1)                                                       Ceil the current instance day with given precision.
     * @method $this               roundHour(float $precision = 1, string $function = "round")                          Round the current instance hour with given precision using the given function.
     * @method $this               roundHours(float $precision = 1, string $function = "round")                         Round the current instance hour with given precision using the given function.
     * @method $this               floorHour(float $precision = 1)                                                      Truncate the current instance hour with given precision.
     * @method $this               floorHours(float $precision = 1)                                                     Truncate the current instance hour with given precision.
     * @method $this               ceilHour(float $precision = 1)                                                       Ceil the current instance hour with given precision.
     * @method $this               ceilHours(float $precision = 1)                                                      Ceil the current instance hour with given precision.
     * @method $this               roundMinute(float $precision = 1, string $function = "round")                        Round the current instance minute with given precision using the given function.
     * @method $this               roundMinutes(float $precision = 1, string $function = "round")                       Round the current instance minute with given precision using the given function.
     * @method $this               floorMinute(float $precision = 1)                                                    Truncate the current instance minute with given precision.
     * @method $this               floorMinutes(float $precision = 1)                                                   Truncate the current instance minute with given precision.
     * @method $this               ceilMinute(float $precision = 1)                                                     Ceil the current instance minute with given precision.
     * @method $this               ceilMinutes(float $precision = 1)                                                    Ceil the current instance minute with given precision.
     * @method $this               roundSecond(float $precision = 1, string $function = "round")                        Round the current instance second with given precision using the given function.
     * @method $this               roundSeconds(float $precision = 1, string $function = "round")                       Round the current instance second with given precision using the given function.
     * @method $this               floorSecond(float $precision = 1)                                                    Truncate the current instance second with given precision.
     * @method $this               floorSeconds(float $precision = 1)                                                   Truncate the current instance second with given precision.
     * @method $this               ceilSecond(float $precision = 1)                                                     Ceil the current instance second with given precision.
     * @method $this               ceilSeconds(float $precision = 1)                                                    Ceil the current instance second with given precision.
     * @method $this               roundMillennium(float $precision = 1, string $function = "round")                    Round the current instance millennium with given precision using the given function.
     * @method $this               roundMillennia(float $precision = 1, string $function = "round")                     Round the current instance millennium with given precision using the given function.
     * @method $this               floorMillennium(float $precision = 1)                                                Truncate the current instance millennium with given precision.
     * @method $this               floorMillennia(float $precision = 1)                                                 Truncate the current instance millennium with given precision.
     * @method $this               ceilMillennium(float $precision = 1)                                                 Ceil the current instance millennium with given precision.
     * @method $this               ceilMillennia(float $precision = 1)                                                  Ceil the current instance millennium with given precision.
     * @method $this               roundCentury(float $precision = 1, string $function = "round")                       Round the current instance century with given precision using the given function.
     * @method $this               roundCenturies(float $precision = 1, string $function = "round")                     Round the current instance century with given precision using the given function.
     * @method $this               floorCentury(float $precision = 1)                                                   Truncate the current instance century with given precision.
     * @method $this               floorCenturies(float $precision = 1)                                                 Truncate the current instance century with given precision.
     * @method $this               ceilCentury(float $precision = 1)                                                    Ceil the current instance century with given precision.
     * @method $this               ceilCenturies(float $precision = 1)                                                  Ceil the current instance century with given precision.
     * @method $this               roundDecade(float $precision = 1, string $function = "round")                        Round the current instance decade with given precision using the given function.
     * @method $this               roundDecades(float $precision = 1, string $function = "round")                       Round the current instance decade with given precision using the given function.
     * @method $this               floorDecade(float $precision = 1)                                                    Truncate the current instance decade with given precision.
     * @method $this               floorDecades(float $precision = 1)                                                   Truncate the current instance decade with given precision.
     * @method $this               ceilDecade(float $precision = 1)                                                     Ceil the current instance decade with given precision.
     * @method $this               ceilDecades(float $precision = 1)                                                    Ceil the current instance decade with given precision.
     * @method $this               roundQuarter(float $precision = 1, string $function = "round")                       Round the current instance quarter with given precision using the given function.
     * @method $this               roundQuarters(float $precision = 1, string $function = "round")                      Round the current instance quarter with given precision using the given function.
     * @method $this               floorQuarter(float $precision = 1)                                                   Truncate the current instance quarter with given precision.
     * @method $this               floorQuarters(float $precision = 1)                                                  Truncate the current instance quarter with given precision.
     * @method $this               ceilQuarter(float $precision = 1)                                                    Ceil the current instance quarter with given precision.
     * @method $this               ceilQuarters(float $precision = 1)                                                   Ceil the current instance quarter with given precision.
     * @method $this               roundMillisecond(float $precision = 1, string $function = "round")                   Round the current instance millisecond with given precision using the given function.
     * @method $this               roundMilliseconds(float $precision = 1, string $function = "round")                  Round the current instance millisecond with given precision using the given function.
     * @method $this               floorMillisecond(float $precision = 1)                                               Truncate the current instance millisecond with given precision.
     * @method $this               floorMilliseconds(float $precision = 1)                                              Truncate the current instance millisecond with given precision.
     * @method $this               ceilMillisecond(float $precision = 1)                                                Ceil the current instance millisecond with given precision.
     * @method $this               ceilMilliseconds(float $precision = 1)                                               Ceil the current instance millisecond with given precision.
     * @method $this               roundMicrosecond(float $precision = 1, string $function = "round")                   Round the current instance microsecond with given precision using the given function.
     * @method $this               roundMicroseconds(float $precision = 1, string $function = "round")                  Round the current instance microsecond with given precision using the given function.
     * @method $this               floorMicrosecond(float $precision = 1)                                               Truncate the current instance microsecond with given precision.
     * @method $this               floorMicroseconds(float $precision = 1)                                              Truncate the current instance microsecond with given precision.
     * @method $this               ceilMicrosecond(float $precision = 1)                                                Ceil the current instance microsecond with given precision.
     * @method $this               ceilMicroseconds(float $precision = 1)                                               Ceil the current instance microsecond with given precision.
     * @method string              shortAbsoluteDiffForHumans(DateTimeInterface $other = null, int $parts = 1)          Get the difference (short format, 'Absolute' mode) in a human readable format in the current locale. ($other and $parts parameters can be swapped.)
     * @method string              longAbsoluteDiffForHumans(DateTimeInterface $other = null, int $parts = 1)           Get the difference (long format, 'Absolute' mode) in a human readable format in the current locale. ($other and $parts parameters can be swapped.)
     * @method string              shortRelativeDiffForHumans(DateTimeInterface $other = null, int $parts = 1)          Get the difference (short format, 'Relative' mode) in a human readable format in the current locale. ($other and $parts parameters can be swapped.)
     * @method string              longRelativeDiffForHumans(DateTimeInterface $other = null, int $parts = 1)           Get the difference (long format, 'Relative' mode) in a human readable format in the current locale. ($other and $parts parameters can be swapped.)
     * @method string              shortRelativeToNowDiffForHumans(DateTimeInterface $other = null, int $parts = 1)     Get the difference (short format, 'RelativeToNow' mode) in a human readable format in the current locale. ($other and $parts parameters can be swapped.)
     * @method string              longRelativeToNowDiffForHumans(DateTimeInterface $other = null, int $parts = 1)      Get the difference (long format, 'RelativeToNow' mode) in a human readable format in the current locale. ($other and $parts parameters can be swapped.)
     * @method string              shortRelativeToOtherDiffForHumans(DateTimeInterface $other = null, int $parts = 1)   Get the difference (short format, 'RelativeToOther' mode) in a human readable format in the current locale. ($other and $parts parameters can be swapped.)
     * @method string              longRelativeToOtherDiffForHumans(DateTimeInterface $other = null, int $parts = 1)    Get the difference (long format, 'RelativeToOther' mode) in a human readable format in the current locale. ($other and $parts parameters can be swapped.)
     * @method static Carbon|false createFromFormat(string $format, string $time, string|DateTimeZone $timezone = null) Parse a string into a new Carbon object according to the specified format.
     * @method static Carbon       __set_state(array $array)                                                            https://php.net/manual/en/datetime.set-state.php
     * 
     * </autodoc>
     */ 
        class Carbon {
         
    }
     
}

        namespace Laracasts\Utilities\JavaScript { 
            /**
     * 
     *
     */ 
        class JavaScriptFacade {
                    /**
         * Bind the given array of variables to the view.
         *
         * @static 
         */ 
        public static function put()
        {
                        /** @var \Laracasts\Utilities\JavaScript\Transformers\Transformer $instance */
                        return $instance->put();
        }
                    /**
         * Translate the array of PHP variables to a JavaScript syntax.
         *
         * @param array $variables
         * @return array 
         * @static 
         */ 
        public static function constructJavaScript($variables)
        {
                        /** @var \Laracasts\Utilities\JavaScript\Transformers\Transformer $instance */
                        return $instance->constructJavaScript($variables);
        }
         
    }
            /**
     * 
     *
     */ 
        class JavaScriptFacade {
                    /**
         * Bind the given array of variables to the view.
         *
         * @static 
         */ 
        public static function put()
        {
                        /** @var \Laracasts\Utilities\JavaScript\Transformers\Transformer $instance */
                        return $instance->put();
        }
                    /**
         * Translate the array of PHP variables to a JavaScript syntax.
         *
         * @param array $variables
         * @return array 
         * @static 
         */ 
        public static function constructJavaScript($variables)
        {
                        /** @var \Laracasts\Utilities\JavaScript\Transformers\Transformer $instance */
                        return $instance->constructJavaScript($variables);
        }
         
    }
     
}

    namespace Barryvdh\Debugbar { 
            /**
     * 
     *
     * @method static void alert(mixed $message)
     * @method static void critical(mixed $message)
     * @method static void debug(mixed $message)
     * @method static void emergency(mixed $message)
     * @method static void error(mixed $message)
     * @method static void info(mixed $message)
     * @method static void log(mixed $message)
     * @method static void notice(mixed $message)
     * @method static void warning(mixed $message)
     * @see \Barryvdh\Debugbar\LaravelDebugbar
     */ 
        class Facade {
                    /**
         * Enable the Debugbar and boot, if not already booted.
         *
         * @static 
         */ 
        public static function enable()
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->enable();
        }
                    /**
         * Boot the debugbar (add collectors, renderer and listener)
         *
         * @static 
         */ 
        public static function boot()
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->boot();
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function shouldCollect($name, $default = false)
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->shouldCollect($name, $default);
        }
                    /**
         * Adds a data collector
         *
         * @param \DebugBar\DataCollector\DataCollectorInterface $collector
         * @throws DebugBarException
         * @return \Barryvdh\Debugbar\LaravelDebugbar 
         * @static 
         */ 
        public static function addCollector($collector)
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->addCollector($collector);
        }
                    /**
         * Handle silenced errors
         *
         * @param $level
         * @param $message
         * @param string $file
         * @param int $line
         * @param array $context
         * @throws \ErrorException
         * @static 
         */ 
        public static function handleError($level, $message, $file = '', $line = 0, $context = [])
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->handleError($level, $message, $file, $line, $context);
        }
                    /**
         * Starts a measure
         *
         * @param string $name Internal name, used to stop the measure
         * @param string $label Public name
         * @static 
         */ 
        public static function startMeasure($name, $label = null)
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->startMeasure($name, $label);
        }
                    /**
         * Stops a measure
         *
         * @param string $name
         * @static 
         */ 
        public static function stopMeasure($name)
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->stopMeasure($name);
        }
                    /**
         * Adds an exception to be profiled in the debug bar
         *
         * @param \Exception $e
         * @deprecated in favor of addThrowable
         * @static 
         */ 
        public static function addException($e)
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->addException($e);
        }
                    /**
         * Adds an exception to be profiled in the debug bar
         *
         * @param \Exception $e
         * @static 
         */ 
        public static function addThrowable($e)
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->addThrowable($e);
        }
                    /**
         * Returns a JavascriptRenderer for this instance
         *
         * @param string $baseUrl
         * @param string $basePathng
         * @return \Barryvdh\Debugbar\JavascriptRenderer 
         * @static 
         */ 
        public static function getJavascriptRenderer($baseUrl = null, $basePath = null)
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->getJavascriptRenderer($baseUrl, $basePath);
        }
                    /**
         * Modify the response and inject the debugbar (or data in headers)
         *
         * @param \Symfony\Component\HttpFoundation\Request $request
         * @param \Symfony\Component\HttpFoundation\Response $response
         * @return \Symfony\Component\HttpFoundation\Response 
         * @static 
         */ 
        public static function modifyResponse($request, $response)
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->modifyResponse($request, $response);
        }
                    /**
         * Check if the Debugbar is enabled
         *
         * @return boolean 
         * @static 
         */ 
        public static function isEnabled()
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->isEnabled();
        }
                    /**
         * Collects the data from the collectors
         *
         * @return array 
         * @static 
         */ 
        public static function collect()
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->collect();
        }
                    /**
         * Injects the web debug toolbar into the given Response.
         *
         * @param \Symfony\Component\HttpFoundation\Response $response A Response instance
         * Based on https://github.com/symfony/WebProfilerBundle/blob/master/EventListener/WebDebugToolbarListener.php
         * @static 
         */ 
        public static function injectDebugbar($response)
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->injectDebugbar($response);
        }
                    /**
         * Disable the Debugbar
         *
         * @static 
         */ 
        public static function disable()
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->disable();
        }
                    /**
         * Adds a measure
         *
         * @param string $label
         * @param float $start
         * @param float $end
         * @static 
         */ 
        public static function addMeasure($label, $start, $end)
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->addMeasure($label, $start, $end);
        }
                    /**
         * Utility function to measure the execution of a Closure
         *
         * @param string $label
         * @param \Closure $closure
         * @return mixed 
         * @static 
         */ 
        public static function measure($label, $closure)
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->measure($label, $closure);
        }
                    /**
         * Collect data in a CLI request
         *
         * @return array 
         * @static 
         */ 
        public static function collectConsole()
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->collectConsole();
        }
                    /**
         * Adds a message to the MessagesCollector
         * 
         * A message can be anything from an object to a string
         *
         * @param mixed $message
         * @param string $label
         * @static 
         */ 
        public static function addMessage($message, $label = 'info')
        {
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->addMessage($message, $label);
        }
                    /**
         * Checks if a data collector has been added
         *
         * @param string $name
         * @return boolean 
         * @static 
         */ 
        public static function hasCollector($name)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->hasCollector($name);
        }
                    /**
         * Returns a data collector
         *
         * @param string $name
         * @return \DebugBar\DataCollector\DataCollectorInterface 
         * @throws DebugBarException
         * @static 
         */ 
        public static function getCollector($name)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->getCollector($name);
        }
                    /**
         * Returns an array of all data collectors
         *
         * @return \DebugBar\array[DataCollectorInterface] 
         * @static 
         */ 
        public static function getCollectors()
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->getCollectors();
        }
                    /**
         * Sets the request id generator
         *
         * @param \DebugBar\RequestIdGeneratorInterface $generator
         * @return \Barryvdh\Debugbar\LaravelDebugbar 
         * @static 
         */ 
        public static function setRequestIdGenerator($generator)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->setRequestIdGenerator($generator);
        }
                    /**
         * 
         *
         * @return \DebugBar\RequestIdGeneratorInterface 
         * @static 
         */ 
        public static function getRequestIdGenerator()
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->getRequestIdGenerator();
        }
                    /**
         * Returns the id of the current request
         *
         * @return string 
         * @static 
         */ 
        public static function getCurrentRequestId()
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->getCurrentRequestId();
        }
                    /**
         * Sets the storage backend to use to store the collected data
         *
         * @param \DebugBar\StorageInterface $storage
         * @return \Barryvdh\Debugbar\LaravelDebugbar 
         * @static 
         */ 
        public static function setStorage($storage = null)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->setStorage($storage);
        }
                    /**
         * 
         *
         * @return \DebugBar\StorageInterface 
         * @static 
         */ 
        public static function getStorage()
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->getStorage();
        }
                    /**
         * Checks if the data will be persisted
         *
         * @return boolean 
         * @static 
         */ 
        public static function isDataPersisted()
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->isDataPersisted();
        }
                    /**
         * Sets the HTTP driver
         *
         * @param \DebugBar\HttpDriverInterface $driver
         * @return \Barryvdh\Debugbar\LaravelDebugbar 
         * @static 
         */ 
        public static function setHttpDriver($driver)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->setHttpDriver($driver);
        }
                    /**
         * Returns the HTTP driver
         * 
         * If no http driver where defined, a PhpHttpDriver is automatically created
         *
         * @return \DebugBar\HttpDriverInterface 
         * @static 
         */ 
        public static function getHttpDriver()
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->getHttpDriver();
        }
                    /**
         * Returns collected data
         * 
         * Will collect the data if none have been collected yet
         *
         * @return array 
         * @static 
         */ 
        public static function getData()
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->getData();
        }
                    /**
         * Returns an array of HTTP headers containing the data
         *
         * @param string $headerName
         * @param integer $maxHeaderLength
         * @return array 
         * @static 
         */ 
        public static function getDataAsHeaders($headerName = 'phpdebugbar', $maxHeaderLength = 4096, $maxTotalHeaderLength = 250000)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->getDataAsHeaders($headerName, $maxHeaderLength, $maxTotalHeaderLength);
        }
                    /**
         * Sends the data through the HTTP headers
         *
         * @param bool $useOpenHandler
         * @param string $headerName
         * @param integer $maxHeaderLength
         * @return \Barryvdh\Debugbar\LaravelDebugbar 
         * @static 
         */ 
        public static function sendDataInHeaders($useOpenHandler = null, $headerName = 'phpdebugbar', $maxHeaderLength = 4096)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->sendDataInHeaders($useOpenHandler, $headerName, $maxHeaderLength);
        }
                    /**
         * Stacks the data in the session for later rendering
         *
         * @static 
         */ 
        public static function stackData()
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->stackData();
        }
                    /**
         * Checks if there is stacked data in the session
         *
         * @return boolean 
         * @static 
         */ 
        public static function hasStackedData()
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->hasStackedData();
        }
                    /**
         * Returns the data stacked in the session
         *
         * @param boolean $delete Whether to delete the data in the session
         * @return array 
         * @static 
         */ 
        public static function getStackedData($delete = true)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->getStackedData($delete);
        }
                    /**
         * Sets the key to use in the $_SESSION array
         *
         * @param string $ns
         * @return \Barryvdh\Debugbar\LaravelDebugbar 
         * @static 
         */ 
        public static function setStackDataSessionNamespace($ns)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->setStackDataSessionNamespace($ns);
        }
                    /**
         * Returns the key used in the $_SESSION array
         *
         * @return string 
         * @static 
         */ 
        public static function getStackDataSessionNamespace()
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->getStackDataSessionNamespace();
        }
                    /**
         * Sets whether to only use the session to store stacked data even
         * if a storage is enabled
         *
         * @param boolean $enabled
         * @return \Barryvdh\Debugbar\LaravelDebugbar 
         * @static 
         */ 
        public static function setStackAlwaysUseSessionStorage($enabled = true)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->setStackAlwaysUseSessionStorage($enabled);
        }
                    /**
         * Checks if the session is always used to store stacked data
         * even if a storage is enabled
         *
         * @return boolean 
         * @static 
         */ 
        public static function isStackAlwaysUseSessionStorage()
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->isStackAlwaysUseSessionStorage();
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function offsetSet($key, $value)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->offsetSet($key, $value);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function offsetGet($key)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->offsetGet($key);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function offsetExists($key)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->offsetExists($key);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function offsetUnset($key)
        {            //Method inherited from \DebugBar\DebugBar         
                        /** @var \Barryvdh\Debugbar\LaravelDebugbar $instance */
                        return $instance->offsetUnset($key);
        }
         
    }
     
}

    namespace Facade\Ignition\Facades { 
            /**
     * Class Flare.
     *
     * @see \Facade\FlareClient\Flare
     */ 
        class Flare {
                    /**
         * 
         *
         * @static 
         */ 
        public static function register($apiKey, $apiSecret = null, $contextDetector = null, $container = null)
        {
                        return \Facade\FlareClient\Flare::register($apiKey, $apiSecret, $contextDetector, $container);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function determineVersionUsing($determineVersionCallable)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->determineVersionUsing($determineVersionCallable);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function reportErrorLevels($reportErrorLevels)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->reportErrorLevels($reportErrorLevels);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function filterExceptionsUsing($filterExceptionsCallable)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->filterExceptionsUsing($filterExceptionsCallable);
        }
                    /**
         * 
         *
         * @return null|string 
         * @static 
         */ 
        public static function version()
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->version();
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function getMiddleware()
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->getMiddleware();
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function registerFlareHandlers()
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->registerFlareHandlers();
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function registerExceptionHandler()
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->registerExceptionHandler();
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function registerErrorHandler()
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->registerErrorHandler();
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function registerMiddleware($callable)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->registerMiddleware($callable);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function getMiddlewares()
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->getMiddlewares();
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function glow($name, $messageLevel = 'info', $metaData = [])
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->glow($name, $messageLevel, $metaData);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function handleException($throwable)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->handleException($throwable);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function handleError($code, $message, $file = '', $line = 0)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->handleError($code, $message, $file, $line);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function applicationPath($applicationPath)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->applicationPath($applicationPath);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function report($throwable, $callback = null)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->report($throwable, $callback);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function reportMessage($message, $logLevel, $callback = null)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->reportMessage($message, $logLevel, $callback);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function sendTestReport($throwable)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->sendTestReport($throwable);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function reset()
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->reset();
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function anonymizeIp()
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->anonymizeIp();
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function censorRequestBodyFields($fieldNames)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->censorRequestBodyFields($fieldNames);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function createReport($throwable)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->createReport($throwable);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function createReportFromMessage($message, $logLevel)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->createReportFromMessage($message, $logLevel);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function stage($stage)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->stage($stage);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function messageLevel($messageLevel)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->messageLevel($messageLevel);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function getGroup($groupName = 'context', $default = [])
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->getGroup($groupName, $default);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function context($key, $value)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->context($key, $value);
        }
                    /**
         * 
         *
         * @static 
         */ 
        public static function group($groupName, $properties)
        {
                        /** @var \Facade\FlareClient\Flare $instance */
                        return $instance->group($groupName, $properties);
        }
         
    }
     
}

    namespace Spatie\Fractal { 
            /**
     * 
     *
     * @see \Spatie\Fractal\Fractal
     */ 
        class FractalFacade {
                    /**
         * 
         *
         * @param null|mixed $data
         * @param null|callable|\League\Fractal\TransformerAbstract $transformer
         * @param null|\League\Fractal\Serializer\SerializerAbstract $serializer
         * @return \Spatie\Fractalistic\Fractal 
         * @static 
         */ 
        public static function create($data = null, $transformer = null, $serializer = null)
        {
                        return \Spatie\Fractal\Fractal::create($data, $transformer, $serializer);
        }
                    /**
         * Return a new JSON response.
         *
         * @param callable|int $statusCode
         * @param callable|array $headers
         * @param callable|int $options
         * @return \Illuminate\Http\JsonResponse 
         * @static 
         */ 
        public static function respond($statusCode = 200, $headers = [], $options = 0)
        {
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->respond($statusCode, $headers, $options);
        }
                    /**
         * Set the collection data that must be transformed.
         *
         * @param mixed $data
         * @param null|string|callable|\League\Fractal\TransformerAbstract $transformer
         * @param null|string $resourceName
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function collection($data, $transformer = null, $resourceName = null)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->collection($data, $transformer, $resourceName);
        }
                    /**
         * Set the item data that must be transformed.
         *
         * @param mixed $data
         * @param null|string|callable|\League\Fractal\TransformerAbstract $transformer
         * @param null|string $resourceName
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function item($data, $transformer = null, $resourceName = null)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->item($data, $transformer, $resourceName);
        }
                    /**
         * Set the primitive data that must be transformed.
         *
         * @param mixed $data
         * @param null|string|callable|\League\Fractal\TransformerAbstract $transformer
         * @param null|string $resourceName
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function primitive($data, $transformer = null, $resourceName = null)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->primitive($data, $transformer, $resourceName);
        }
                    /**
         * Set the data that must be transformed.
         *
         * @param string $dataType
         * @param mixed $data
         * @param null|string|callable|\League\Fractal\TransformerAbstract $transformer
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function data($dataType, $data, $transformer = null)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->data($dataType, $data, $transformer);
        }
                    /**
         * Set the class or function that will perform the transform.
         *
         * @param string|callable|\League\Fractal\TransformerAbstract $transformer
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function transformWith($transformer)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->transformWith($transformer);
        }
                    /**
         * Set the serializer to be used.
         *
         * @param string|\League\Fractal\Serializer\SerializerAbstract $serializer
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function serializeWith($serializer)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->serializeWith($serializer);
        }
                    /**
         * Set a Fractal paginator for the data.
         *
         * @param \League\Fractal\Pagination\PaginatorInterface $paginator
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function paginateWith($paginator)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->paginateWith($paginator);
        }
                    /**
         * Set a Fractal cursor for the data.
         *
         * @param \League\Fractal\Pagination\CursorInterface $cursor
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function withCursor($cursor)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->withCursor($cursor);
        }
                    /**
         * Specify the includes.
         *
         * @param array|string $includes Array or string of resources to include.
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function parseIncludes($includes)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->parseIncludes($includes);
        }
                    /**
         * Specify the excludes.
         *
         * @param array|string $excludes Array or string of resources to exclude.
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function parseExcludes($excludes)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->parseExcludes($excludes);
        }
                    /**
         * Specify the fieldsets to include in the response.
         *
         * @param array $fieldsets array with key = resourceName and value = fields to include
         *                                (array or comma separated string with field names)
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function parseFieldsets($fieldsets)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->parseFieldsets($fieldsets);
        }
                    /**
         * Set the meta data.
         *
         * @param $array,...
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function addMeta()
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->addMeta();
        }
                    /**
         * Set the resource name, to replace 'data' as the root of the collection or item.
         *
         * @param string $resourceName
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function withResourceName($resourceName)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->withResourceName($resourceName);
        }
                    /**
         * Upper limit to how many levels of included data are allowed.
         *
         * @param int $recursionLimit
         * @return \Spatie\Fractal\Fractal 
         * @static 
         */ 
        public static function limitRecursion($recursionLimit)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->limitRecursion($recursionLimit);
        }
                    /**
         * Perform the transformation to json.
         *
         * @param int $options
         * @return string 
         * @static 
         */ 
        public static function toJson($options = 0)
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->toJson($options);
        }
                    /**
         * Perform the transformation to array.
         *
         * @return array 
         * @static 
         */ 
        public static function toArray()
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->toArray();
        }
                    /**
         * Create fractal data.
         *
         * @return \League\Fractal\Scope 
         * @throws \Spatie\Fractalistic\Exceptions\InvalidTransformation
         * @throws \Spatie\Fractalistic\Exceptions\NoTransformerSpecified
         * @static 
         */ 
        public static function createData()
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->createData();
        }
                    /**
         * Get the resource.
         *
         * @return \League\Fractal\Resource\ResourceInterface 
         * @throws \Spatie\Fractalistic\Exceptions\InvalidTransformation
         * @static 
         */ 
        public static function getResource()
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->getResource();
        }
                    /**
         * Return the name of the resource.
         *
         * @return string 
         * @static 
         */ 
        public static function getResourceName()
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->getResourceName();
        }
                    /**
         * Convert the object into something JSON serializable.
         *
         * @static 
         */ 
        public static function jsonSerialize()
        {            //Method inherited from \Spatie\Fractalistic\Fractal         
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->jsonSerialize();
        }
                    /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @return void 
         * @static 
         */ 
        public static function macro($name, $macro)
        {
                        \Spatie\Fractal\Fractal::macro($name, $macro);
        }
                    /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void 
         * @throws \ReflectionException
         * @static 
         */ 
        public static function mixin($mixin, $replace = true)
        {
                        \Spatie\Fractal\Fractal::mixin($mixin, $replace);
        }
                    /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool 
         * @static 
         */ 
        public static function hasMacro($name)
        {
                        return \Spatie\Fractal\Fractal::hasMacro($name);
        }
                    /**
         * Dynamically handle calls to the class.
         *
         * @param string $method
         * @param array $parameters
         * @return mixed 
         * @throws \BadMethodCallException
         * @static 
         */ 
        public static function macroCall($method, $parameters)
        {
                        /** @var \Spatie\Fractal\Fractal $instance */
                        return $instance->macroCall($method, $parameters);
        }
         
    }
     
}

    namespace Illuminate\Http { 
            /**
     * 
     *
     */ 
        class Request {
                    /**
         * 
         *
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestValidation()
         * @param array $rules
         * @param mixed $params
         * @static 
         */ 
        public static function validate($rules, ...$params)
        {
                        return \Illuminate\Http\Request::validate($rules, ...$params);
        }
                    /**
         * 
         *
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestValidation()
         * @param string $errorBag
         * @param array $rules
         * @param mixed $params
         * @static 
         */ 
        public static function validateWithBag($errorBag, $rules, ...$params)
        {
                        return \Illuminate\Http\Request::validateWithBag($errorBag, $rules, ...$params);
        }
                    /**
         * 
         *
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @param mixed $absolute
         * @static 
         */ 
        public static function hasValidSignature($absolute = true)
        {
                        return \Illuminate\Http\Request::hasValidSignature($absolute);
        }
                    /**
         * 
         *
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @static 
         */ 
        public static function hasValidRelativeSignature()
        {
                        return \Illuminate\Http\Request::hasValidRelativeSignature();
        }
         
    }
     
}

    namespace Illuminate\Support { 
            /**
     * 
     *
     */ 
        class Collection {
                    /**
         * 
         *
         * @see \Barryvdh\Debugbar\ServiceProvider::register()
         * @static 
         */ 
        public static function debug()
        {
                        return \Illuminate\Support\Collection::debug();
        }
                    /**
         * 
         *
         * @see \Spatie\Fractal\FractalServiceProvider::setupMacro()
         * @param mixed $transformer
         * @static 
         */ 
        public static function transformWith($transformer)
        {
                        return \Illuminate\Support\Collection::transformWith($transformer);
        }
         
    }
     
}

    namespace Illuminate\Routing { 
            /**
     * 
     *
     * @mixin \Illuminate\Routing\RouteRegistrar
     */ 
        class Router {
                    /**
         * 
         *
         * @see \Laravel\Ui\AuthRouteMethods::auth()
         * @param mixed $options
         * @static 
         */ 
        public static function auth($options = [])
        {
                        return \Illuminate\Routing\Router::auth($options);
        }
                    /**
         * 
         *
         * @see \Laravel\Ui\AuthRouteMethods::resetPassword()
         * @static 
         */ 
        public static function resetPassword()
        {
                        return \Illuminate\Routing\Router::resetPassword();
        }
                    /**
         * 
         *
         * @see \Laravel\Ui\AuthRouteMethods::confirmPassword()
         * @static 
         */ 
        public static function confirmPassword()
        {
                        return \Illuminate\Routing\Router::confirmPassword();
        }
                    /**
         * 
         *
         * @see \Laravel\Ui\AuthRouteMethods::emailVerification()
         * @static 
         */ 
        public static function emailVerification()
        {
                        return \Illuminate\Routing\Router::emailVerification();
        }
         
    }
     
}


namespace  { 
            class Alert extends \Prologue\Alerts\Facades\Alert {}
            class App extends \Illuminate\Support\Facades\App {}
            class Artisan extends \Illuminate\Support\Facades\Artisan {}
            class Auth extends \Illuminate\Support\Facades\Auth {}
            class Blade extends \Illuminate\Support\Facades\Blade {}
            class Bus extends \Illuminate\Support\Facades\Bus {}
            class Cache extends \Illuminate\Support\Facades\Cache {}
            class Carbon extends \Carbon\Carbon {}
            class Config extends \Illuminate\Support\Facades\Config {}
            class Cookie extends \Illuminate\Support\Facades\Cookie {}
            class Crypt extends \Illuminate\Support\Facades\Crypt {}
            class DB extends \Illuminate\Support\Facades\DB {}
            class Eloquent extends \Illuminate\Database\Eloquent\Model {             
                /**
             * Create and return an un-saved model instance.
             *
             * @param array $attributes
             * @return \Illuminate\Database\Eloquent\Model|static 
             * @static 
             */ 
            public static function make($attributes = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->make($attributes);
            }
             
                /**
             * Register a new global scope.
             *
             * @param string $identifier
             * @param \Illuminate\Database\Eloquent\Scope|\Closure $scope
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withGlobalScope($identifier, $scope)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withGlobalScope($identifier, $scope);
            }
             
                /**
             * Remove a registered global scope.
             *
             * @param \Illuminate\Database\Eloquent\Scope|string $scope
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withoutGlobalScope($scope)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withoutGlobalScope($scope);
            }
             
                /**
             * Remove all or passed registered global scopes.
             *
             * @param array|null $scopes
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withoutGlobalScopes($scopes = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withoutGlobalScopes($scopes);
            }
             
                /**
             * Get an array of global scopes that were removed from the query.
             *
             * @return array 
             * @static 
             */ 
            public static function removedScopes()
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->removedScopes();
            }
             
                /**
             * Add a where clause on the primary key to the query.
             *
             * @param mixed $id
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function whereKey($id)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->whereKey($id);
            }
             
                /**
             * Add a where clause on the primary key to the query.
             *
             * @param mixed $id
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function whereKeyNot($id)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->whereKeyNot($id);
            }
             
                /**
             * Add a basic where clause to the query.
             *
             * @param \Closure|string|array|\Illuminate\Database\Query\Expression $column
             * @param mixed $operator
             * @param mixed $value
             * @param string $boolean
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->where($column, $operator, $value, $boolean);
            }
             
                /**
             * Add a basic where clause to the query, and return the first result.
             *
             * @param \Closure|string|array|\Illuminate\Database\Query\Expression $column
             * @param mixed $operator
             * @param mixed $value
             * @param string $boolean
             * @return \Illuminate\Database\Eloquent\Model|static|null 
             * @static 
             */ 
            public static function firstWhere($column, $operator = null, $value = null, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->firstWhere($column, $operator, $value, $boolean);
            }
             
                /**
             * Add an "or where" clause to the query.
             *
             * @param \Closure|array|string|\Illuminate\Database\Query\Expression $column
             * @param mixed $operator
             * @param mixed $value
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function orWhere($column, $operator = null, $value = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->orWhere($column, $operator, $value);
            }
             
                /**
             * Add an "order by" clause for a timestamp to the query.
             *
             * @param string|\Illuminate\Database\Query\Expression $column
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function latest($column = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->latest($column);
            }
             
                /**
             * Add an "order by" clause for a timestamp to the query.
             *
             * @param string|\Illuminate\Database\Query\Expression $column
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function oldest($column = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->oldest($column);
            }
             
                /**
             * Create a collection of models from plain arrays.
             *
             * @param array $items
             * @return \Illuminate\Database\Eloquent\Collection 
             * @static 
             */ 
            public static function hydrate($items)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->hydrate($items);
            }
             
                /**
             * Create a collection of models from a raw query.
             *
             * @param string $query
             * @param array $bindings
             * @return \Illuminate\Database\Eloquent\Collection 
             * @static 
             */ 
            public static function fromQuery($query, $bindings = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->fromQuery($query, $bindings);
            }
             
                /**
             * Find a model by its primary key.
             *
             * @param mixed $id
             * @param array $columns
             * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null 
             * @static 
             */ 
            public static function find($id, $columns = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->find($id, $columns);
            }
             
                /**
             * Find multiple models by their primary keys.
             *
             * @param \Illuminate\Contracts\Support\Arrayable|array $ids
             * @param array $columns
             * @return \Illuminate\Database\Eloquent\Collection 
             * @static 
             */ 
            public static function findMany($ids, $columns = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->findMany($ids, $columns);
            }
             
                /**
             * Find a model by its primary key or throw an exception.
             *
             * @param mixed $id
             * @param array $columns
             * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static|static[] 
             * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
             * @static 
             */ 
            public static function findOrFail($id, $columns = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->findOrFail($id, $columns);
            }
             
                /**
             * Find a model by its primary key or return fresh model instance.
             *
             * @param mixed $id
             * @param array $columns
             * @return \Illuminate\Database\Eloquent\Model|static 
             * @static 
             */ 
            public static function findOrNew($id, $columns = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->findOrNew($id, $columns);
            }
             
                /**
             * Get the first record matching the attributes or instantiate it.
             *
             * @param array $attributes
             * @param array $values
             * @return \Illuminate\Database\Eloquent\Model|static 
             * @static 
             */ 
            public static function firstOrNew($attributes = [], $values = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->firstOrNew($attributes, $values);
            }
             
                /**
             * Get the first record matching the attributes or create it.
             *
             * @param array $attributes
             * @param array $values
             * @return \Illuminate\Database\Eloquent\Model|static 
             * @static 
             */ 
            public static function firstOrCreate($attributes = [], $values = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->firstOrCreate($attributes, $values);
            }
             
                /**
             * Create or update a record matching the attributes, and fill it with values.
             *
             * @param array $attributes
             * @param array $values
             * @return \Illuminate\Database\Eloquent\Model|static 
             * @static 
             */ 
            public static function updateOrCreate($attributes, $values = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->updateOrCreate($attributes, $values);
            }
             
                /**
             * Execute the query and get the first result or throw an exception.
             *
             * @param array $columns
             * @return \Illuminate\Database\Eloquent\Model|static 
             * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
             * @static 
             */ 
            public static function firstOrFail($columns = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->firstOrFail($columns);
            }
             
                /**
             * Execute the query and get the first result or call a callback.
             *
             * @param \Closure|array $columns
             * @param \Closure|null $callback
             * @return \Illuminate\Database\Eloquent\Model|static|mixed 
             * @static 
             */ 
            public static function firstOr($columns = [], $callback = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->firstOr($columns, $callback);
            }
             
                /**
             * Execute the query and get the first result if it's the sole matching record.
             *
             * @param array|string $columns
             * @return \Illuminate\Database\Eloquent\Model 
             * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
             * @throws \Illuminate\Database\MultipleRecordsFoundException
             * @static 
             */ 
            public static function sole($columns = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->sole($columns);
            }
             
                /**
             * Get a single column's value from the first result of a query.
             *
             * @param string|\Illuminate\Database\Query\Expression $column
             * @return mixed 
             * @static 
             */ 
            public static function value($column)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->value($column);
            }
             
                /**
             * Execute the query as a "select" statement.
             *
             * @param array|string $columns
             * @return \Illuminate\Database\Eloquent\Collection|static[] 
             * @static 
             */ 
            public static function get($columns = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->get($columns);
            }
             
                /**
             * Get the hydrated models without eager loading.
             *
             * @param array|string $columns
             * @return \Illuminate\Database\Eloquent\Model[]|static[] 
             * @static 
             */ 
            public static function getModels($columns = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->getModels($columns);
            }
             
                /**
             * Eager load the relationships for the models.
             *
             * @param array $models
             * @return array 
             * @static 
             */ 
            public static function eagerLoadRelations($models)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->eagerLoadRelations($models);
            }
             
                /**
             * Get a lazy collection for the given query.
             *
             * @return \Illuminate\Support\LazyCollection 
             * @static 
             */ 
            public static function cursor()
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->cursor();
            }
             
                /**
             * Get an array with the values of a given column.
             *
             * @param string|\Illuminate\Database\Query\Expression $column
             * @param string|null $key
             * @return \Illuminate\Support\Collection 
             * @static 
             */ 
            public static function pluck($column, $key = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->pluck($column, $key);
            }
             
                /**
             * Paginate the given query.
             *
             * @param int|null $perPage
             * @param array $columns
             * @param string $pageName
             * @param int|null $page
             * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator 
             * @throws \InvalidArgumentException
             * @static 
             */ 
            public static function paginate($perPage = null, $columns = [], $pageName = 'page', $page = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->paginate($perPage, $columns, $pageName, $page);
            }
             
                /**
             * Paginate the given query into a simple paginator.
             *
             * @param int|null $perPage
             * @param array $columns
             * @param string $pageName
             * @param int|null $page
             * @return \Illuminate\Contracts\Pagination\Paginator 
             * @static 
             */ 
            public static function simplePaginate($perPage = null, $columns = [], $pageName = 'page', $page = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->simplePaginate($perPage, $columns, $pageName, $page);
            }
             
                /**
             * Paginate the given query into a cursor paginator.
             *
             * @param int|null $perPage
             * @param array $columns
             * @param string $cursorName
             * @param \Illuminate\Pagination\Cursor|string|null $cursor
             * @return \Illuminate\Contracts\Pagination\CursorPaginator 
             * @static 
             */ 
            public static function cursorPaginate($perPage = null, $columns = [], $cursorName = 'cursor', $cursor = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->cursorPaginate($perPage, $columns, $cursorName, $cursor);
            }
             
                /**
             * Save a new model and return the instance.
             *
             * @param array $attributes
             * @return \Illuminate\Database\Eloquent\Model|$this 
             * @static 
             */ 
            public static function create($attributes = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->create($attributes);
            }
             
                /**
             * Save a new model and return the instance. Allow mass-assignment.
             *
             * @param array $attributes
             * @return \Illuminate\Database\Eloquent\Model|$this 
             * @static 
             */ 
            public static function forceCreate($attributes)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->forceCreate($attributes);
            }
             
                /**
             * Insert new records or update the existing ones.
             *
             * @param array $values
             * @param array|string $uniqueBy
             * @param array|null $update
             * @return int 
             * @static 
             */ 
            public static function upsert($values, $uniqueBy, $update = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->upsert($values, $uniqueBy, $update);
            }
             
                /**
             * Register a replacement for the default delete function.
             *
             * @param \Closure $callback
             * @return void 
             * @static 
             */ 
            public static function onDelete($callback)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                $instance->onDelete($callback);
            }
             
                /**
             * Call the given local model scopes.
             *
             * @param array|string $scopes
             * @return static|mixed 
             * @static 
             */ 
            public static function scopes($scopes)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->scopes($scopes);
            }
             
                /**
             * Apply the scopes to the Eloquent builder instance and return it.
             *
             * @return static 
             * @static 
             */ 
            public static function applyScopes()
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->applyScopes();
            }
             
                /**
             * Prevent the specified relations from being eager loaded.
             *
             * @param mixed $relations
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function without($relations)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->without($relations);
            }
             
                /**
             * Set the relationships that should be eager loaded while removing any previously added eager loading specifications.
             *
             * @param mixed $relations
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withOnly($relations)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withOnly($relations);
            }
             
                /**
             * Create a new instance of the model being queried.
             *
             * @param array $attributes
             * @return \Illuminate\Database\Eloquent\Model|static 
             * @static 
             */ 
            public static function newModelInstance($attributes = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->newModelInstance($attributes);
            }
             
                /**
             * Apply query-time casts to the model instance.
             *
             * @param array $casts
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withCasts($casts)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withCasts($casts);
            }
             
                /**
             * Get the underlying query builder instance.
             *
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function getQuery()
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->getQuery();
            }
             
                /**
             * Set the underlying query builder instance.
             *
             * @param \Illuminate\Database\Query\Builder $query
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function setQuery($query)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->setQuery($query);
            }
             
                /**
             * Get a base query builder instance.
             *
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function toBase()
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->toBase();
            }
             
                /**
             * Get the relationships being eagerly loaded.
             *
             * @return array 
             * @static 
             */ 
            public static function getEagerLoads()
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->getEagerLoads();
            }
             
                /**
             * Set the relationships being eagerly loaded.
             *
             * @param array $eagerLoad
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function setEagerLoads($eagerLoad)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->setEagerLoads($eagerLoad);
            }
             
                /**
             * Get the model instance being queried.
             *
             * @return \Illuminate\Database\Eloquent\Model|static 
             * @static 
             */ 
            public static function getModel()
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->getModel();
            }
             
                /**
             * Set a model instance for the model being queried.
             *
             * @param \Illuminate\Database\Eloquent\Model $model
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function setModel($model)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->setModel($model);
            }
             
                /**
             * Get the given macro by name.
             *
             * @param string $name
             * @return \Closure 
             * @static 
             */ 
            public static function getMacro($name)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->getMacro($name);
            }
             
                /**
             * Checks if a macro is registered.
             *
             * @param string $name
             * @return bool 
             * @static 
             */ 
            public static function hasMacro($name)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->hasMacro($name);
            }
             
                /**
             * Get the given global macro by name.
             *
             * @param string $name
             * @return \Closure 
             * @static 
             */ 
            public static function getGlobalMacro($name)
            {
                                return \Illuminate\Database\Eloquent\Builder::getGlobalMacro($name);
            }
             
                /**
             * Checks if a global macro is registered.
             *
             * @param string $name
             * @return bool 
             * @static 
             */ 
            public static function hasGlobalMacro($name)
            {
                                return \Illuminate\Database\Eloquent\Builder::hasGlobalMacro($name);
            }
             
                /**
             * Clone the Eloquent query builder.
             *
             * @return static 
             * @static 
             */ 
            public static function clone()
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->clone();
            }
             
                /**
             * Add a relationship count / exists condition to the query.
             *
             * @param \Illuminate\Database\Eloquent\Relations\Relation|string $relation
             * @param string $operator
             * @param int $count
             * @param string $boolean
             * @param \Closure|null $callback
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @throws \RuntimeException
             * @static 
             */ 
            public static function has($relation, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->has($relation, $operator, $count, $boolean, $callback);
            }
             
                /**
             * Add a relationship count / exists condition to the query with an "or".
             *
             * @param string $relation
             * @param string $operator
             * @param int $count
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function orHas($relation, $operator = '>=', $count = 1)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->orHas($relation, $operator, $count);
            }
             
                /**
             * Add a relationship count / exists condition to the query.
             *
             * @param string $relation
             * @param string $boolean
             * @param \Closure|null $callback
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function doesntHave($relation, $boolean = 'and', $callback = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->doesntHave($relation, $boolean, $callback);
            }
             
                /**
             * Add a relationship count / exists condition to the query with an "or".
             *
             * @param string $relation
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function orDoesntHave($relation)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->orDoesntHave($relation);
            }
             
                /**
             * Add a relationship count / exists condition to the query with where clauses.
             *
             * @param string $relation
             * @param \Closure|null $callback
             * @param string $operator
             * @param int $count
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function whereHas($relation, $callback = null, $operator = '>=', $count = 1)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->whereHas($relation, $callback, $operator, $count);
            }
             
                /**
             * Add a relationship count / exists condition to the query with where clauses and an "or".
             *
             * @param string $relation
             * @param \Closure|null $callback
             * @param string $operator
             * @param int $count
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function orWhereHas($relation, $callback = null, $operator = '>=', $count = 1)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->orWhereHas($relation, $callback, $operator, $count);
            }
             
                /**
             * Add a relationship count / exists condition to the query with where clauses.
             *
             * @param string $relation
             * @param \Closure|null $callback
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function whereDoesntHave($relation, $callback = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->whereDoesntHave($relation, $callback);
            }
             
                /**
             * Add a relationship count / exists condition to the query with where clauses and an "or".
             *
             * @param string $relation
             * @param \Closure|null $callback
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function orWhereDoesntHave($relation, $callback = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->orWhereDoesntHave($relation, $callback);
            }
             
                /**
             * Add a polymorphic relationship count / exists condition to the query.
             *
             * @param \Illuminate\Database\Eloquent\Relations\MorphTo|string $relation
             * @param string|array $types
             * @param string $operator
             * @param int $count
             * @param string $boolean
             * @param \Closure|null $callback
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function hasMorph($relation, $types, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->hasMorph($relation, $types, $operator, $count, $boolean, $callback);
            }
             
                /**
             * Add a polymorphic relationship count / exists condition to the query with an "or".
             *
             * @param \Illuminate\Database\Eloquent\Relations\MorphTo|string $relation
             * @param string|array $types
             * @param string $operator
             * @param int $count
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function orHasMorph($relation, $types, $operator = '>=', $count = 1)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->orHasMorph($relation, $types, $operator, $count);
            }
             
                /**
             * Add a polymorphic relationship count / exists condition to the query.
             *
             * @param \Illuminate\Database\Eloquent\Relations\MorphTo|string $relation
             * @param string|array $types
             * @param string $boolean
             * @param \Closure|null $callback
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function doesntHaveMorph($relation, $types, $boolean = 'and', $callback = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->doesntHaveMorph($relation, $types, $boolean, $callback);
            }
             
                /**
             * Add a polymorphic relationship count / exists condition to the query with an "or".
             *
             * @param \Illuminate\Database\Eloquent\Relations\MorphTo|string $relation
             * @param string|array $types
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function orDoesntHaveMorph($relation, $types)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->orDoesntHaveMorph($relation, $types);
            }
             
                /**
             * Add a polymorphic relationship count / exists condition to the query with where clauses.
             *
             * @param \Illuminate\Database\Eloquent\Relations\MorphTo|string $relation
             * @param string|array $types
             * @param \Closure|null $callback
             * @param string $operator
             * @param int $count
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function whereHasMorph($relation, $types, $callback = null, $operator = '>=', $count = 1)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->whereHasMorph($relation, $types, $callback, $operator, $count);
            }
             
                /**
             * Add a polymorphic relationship count / exists condition to the query with where clauses and an "or".
             *
             * @param \Illuminate\Database\Eloquent\Relations\MorphTo|string $relation
             * @param string|array $types
             * @param \Closure|null $callback
             * @param string $operator
             * @param int $count
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function orWhereHasMorph($relation, $types, $callback = null, $operator = '>=', $count = 1)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->orWhereHasMorph($relation, $types, $callback, $operator, $count);
            }
             
                /**
             * Add a polymorphic relationship count / exists condition to the query with where clauses.
             *
             * @param \Illuminate\Database\Eloquent\Relations\MorphTo|string $relation
             * @param string|array $types
             * @param \Closure|null $callback
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function whereDoesntHaveMorph($relation, $types, $callback = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->whereDoesntHaveMorph($relation, $types, $callback);
            }
             
                /**
             * Add a polymorphic relationship count / exists condition to the query with where clauses and an "or".
             *
             * @param \Illuminate\Database\Eloquent\Relations\MorphTo|string $relation
             * @param string|array $types
             * @param \Closure|null $callback
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function orWhereDoesntHaveMorph($relation, $types, $callback = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->orWhereDoesntHaveMorph($relation, $types, $callback);
            }
             
                /**
             * Add subselect queries to include an aggregate value for a relationship.
             *
             * @param mixed $relations
             * @param string $column
             * @param string $function
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withAggregate($relations, $column, $function = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withAggregate($relations, $column, $function);
            }
             
                /**
             * Add subselect queries to count the relations.
             *
             * @param mixed $relations
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withCount($relations)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withCount($relations);
            }
             
                /**
             * Add subselect queries to include the max of the relation's column.
             *
             * @param string|array $relation
             * @param string $column
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withMax($relation, $column)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withMax($relation, $column);
            }
             
                /**
             * Add subselect queries to include the min of the relation's column.
             *
             * @param string|array $relation
             * @param string $column
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withMin($relation, $column)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withMin($relation, $column);
            }
             
                /**
             * Add subselect queries to include the sum of the relation's column.
             *
             * @param string|array $relation
             * @param string $column
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withSum($relation, $column)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withSum($relation, $column);
            }
             
                /**
             * Add subselect queries to include the average of the relation's column.
             *
             * @param string|array $relation
             * @param string $column
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withAvg($relation, $column)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withAvg($relation, $column);
            }
             
                /**
             * Add subselect queries to include the existence of related models.
             *
             * @param string|array $relation
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function withExists($relation)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->withExists($relation);
            }
             
                /**
             * Merge the where constraints from another query to the current query.
             *
             * @param \Illuminate\Database\Eloquent\Builder $from
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function mergeConstraintsFrom($from)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->mergeConstraintsFrom($from);
            }
             
                /**
             * Explains the query.
             *
             * @return \Illuminate\Support\Collection 
             * @static 
             */ 
            public static function explain()
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->explain();
            }
             
                /**
             * Chunk the results of the query.
             *
             * @param int $count
             * @param callable $callback
             * @return bool 
             * @static 
             */ 
            public static function chunk($count, $callback)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->chunk($count, $callback);
            }
             
                /**
             * Run a map over each item while chunking.
             *
             * @param callable $callback
             * @param int $count
             * @return \Illuminate\Support\Collection 
             * @static 
             */ 
            public static function chunkMap($callback, $count = 1000)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->chunkMap($callback, $count);
            }
             
                /**
             * Execute a callback over each item while chunking.
             *
             * @param callable $callback
             * @param int $count
             * @return bool 
             * @throws \RuntimeException
             * @static 
             */ 
            public static function each($callback, $count = 1000)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->each($callback, $count);
            }
             
                /**
             * Chunk the results of a query by comparing IDs.
             *
             * @param int $count
             * @param callable $callback
             * @param string|null $column
             * @param string|null $alias
             * @return bool 
             * @static 
             */ 
            public static function chunkById($count, $callback, $column = null, $alias = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->chunkById($count, $callback, $column, $alias);
            }
             
                /**
             * Execute a callback over each item while chunking by ID.
             *
             * @param callable $callback
             * @param int $count
             * @param string|null $column
             * @param string|null $alias
             * @return bool 
             * @static 
             */ 
            public static function eachById($callback, $count = 1000, $column = null, $alias = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->eachById($callback, $count, $column, $alias);
            }
             
                /**
             * Query lazily, by chunks of the given size.
             *
             * @param int $chunkSize
             * @return \Illuminate\Support\LazyCollection 
             * @throws \InvalidArgumentException
             * @static 
             */ 
            public static function lazy($chunkSize = 1000)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->lazy($chunkSize);
            }
             
                /**
             * Query lazily, by chunking the results of a query by comparing IDs.
             *
             * @param int $count
             * @param string|null $column
             * @param string|null $alias
             * @return \Illuminate\Support\LazyCollection 
             * @throws \InvalidArgumentException
             * @static 
             */ 
            public static function lazyById($chunkSize = 1000, $column = null, $alias = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->lazyById($chunkSize, $column, $alias);
            }
             
                /**
             * Execute the query and get the first result.
             *
             * @param array|string $columns
             * @return \Illuminate\Database\Eloquent\Model|object|static|null 
             * @static 
             */ 
            public static function first($columns = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->first($columns);
            }
             
                /**
             * Execute the query and get the first result if it's the sole matching record.
             *
             * @param array|string $columns
             * @return \Illuminate\Database\Eloquent\Model|object|static|null 
             * @throws \Illuminate\Database\RecordsNotFoundException
             * @throws \Illuminate\Database\MultipleRecordsFoundException
             * @static 
             */ 
            public static function baseSole($columns = [])
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->baseSole($columns);
            }
             
                /**
             * Pass the query to a given callback.
             *
             * @param callable $callback
             * @return \Illuminate\Database\Eloquent\Builder|static 
             * @static 
             */ 
            public static function tap($callback)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->tap($callback);
            }
             
                /**
             * Apply the callback if the given "value" is truthy.
             *
             * @param mixed $value
             * @param callable $callback
             * @param callable|null $default
             * @return $this|mixed 
             * @static 
             */ 
            public static function when($value, $callback, $default = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->when($value, $callback, $default);
            }
             
                /**
             * Apply the callback if the given "value" is falsy.
             *
             * @param mixed $value
             * @param callable $callback
             * @param callable|null $default
             * @return $this|mixed 
             * @static 
             */ 
            public static function unless($value, $callback, $default = null)
            {
                                /** @var \Illuminate\Database\Eloquent\Builder $instance */
                                return $instance->unless($value, $callback, $default);
            }
             
                /**
             * Set the columns to be selected.
             *
             * @param array|mixed $columns
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function select($columns = [])
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->select($columns);
            }
             
                /**
             * Add a subselect expression to the query.
             *
             * @param \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|string $query
             * @param string $as
             * @return \Illuminate\Database\Query\Builder 
             * @throws \InvalidArgumentException
             * @static 
             */ 
            public static function selectSub($query, $as)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->selectSub($query, $as);
            }
             
                /**
             * Add a new "raw" select expression to the query.
             *
             * @param string $expression
             * @param array $bindings
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function selectRaw($expression, $bindings = [])
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->selectRaw($expression, $bindings);
            }
             
                /**
             * Makes "from" fetch from a subquery.
             *
             * @param \Closure|\Illuminate\Database\Query\Builder|string $query
             * @param string $as
             * @return \Illuminate\Database\Query\Builder 
             * @throws \InvalidArgumentException
             * @static 
             */ 
            public static function fromSub($query, $as)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->fromSub($query, $as);
            }
             
                /**
             * Add a raw from clause to the query.
             *
             * @param string $expression
             * @param mixed $bindings
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function fromRaw($expression, $bindings = [])
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->fromRaw($expression, $bindings);
            }
             
                /**
             * Add a new select column to the query.
             *
             * @param array|mixed $column
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function addSelect($column)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->addSelect($column);
            }
             
                /**
             * Force the query to only return distinct results.
             *
             * @param mixed $distinct
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function distinct()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->distinct();
            }
             
                /**
             * Set the table which the query is targeting.
             *
             * @param \Closure|\Illuminate\Database\Query\Builder|string $table
             * @param string|null $as
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function from($table, $as = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->from($table, $as);
            }
             
                /**
             * Add a join clause to the query.
             *
             * @param string $table
             * @param \Closure|string $first
             * @param string|null $operator
             * @param string|null $second
             * @param string $type
             * @param bool $where
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->join($table, $first, $operator, $second, $type, $where);
            }
             
                /**
             * Add a "join where" clause to the query.
             *
             * @param string $table
             * @param \Closure|string $first
             * @param string $operator
             * @param string $second
             * @param string $type
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function joinWhere($table, $first, $operator, $second, $type = 'inner')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->joinWhere($table, $first, $operator, $second, $type);
            }
             
                /**
             * Add a subquery join clause to the query.
             *
             * @param \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|string $query
             * @param string $as
             * @param \Closure|string $first
             * @param string|null $operator
             * @param string|null $second
             * @param string $type
             * @param bool $where
             * @return \Illuminate\Database\Query\Builder 
             * @throws \InvalidArgumentException
             * @static 
             */ 
            public static function joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->joinSub($query, $as, $first, $operator, $second, $type, $where);
            }
             
                /**
             * Add a left join to the query.
             *
             * @param string $table
             * @param \Closure|string $first
             * @param string|null $operator
             * @param string|null $second
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function leftJoin($table, $first, $operator = null, $second = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->leftJoin($table, $first, $operator, $second);
            }
             
                /**
             * Add a "join where" clause to the query.
             *
             * @param string $table
             * @param \Closure|string $first
             * @param string $operator
             * @param string $second
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function leftJoinWhere($table, $first, $operator, $second)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->leftJoinWhere($table, $first, $operator, $second);
            }
             
                /**
             * Add a subquery left join to the query.
             *
             * @param \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|string $query
             * @param string $as
             * @param \Closure|string $first
             * @param string|null $operator
             * @param string|null $second
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function leftJoinSub($query, $as, $first, $operator = null, $second = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->leftJoinSub($query, $as, $first, $operator, $second);
            }
             
                /**
             * Add a right join to the query.
             *
             * @param string $table
             * @param \Closure|string $first
             * @param string|null $operator
             * @param string|null $second
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function rightJoin($table, $first, $operator = null, $second = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->rightJoin($table, $first, $operator, $second);
            }
             
                /**
             * Add a "right join where" clause to the query.
             *
             * @param string $table
             * @param \Closure|string $first
             * @param string $operator
             * @param string $second
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function rightJoinWhere($table, $first, $operator, $second)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->rightJoinWhere($table, $first, $operator, $second);
            }
             
                /**
             * Add a subquery right join to the query.
             *
             * @param \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|string $query
             * @param string $as
             * @param \Closure|string $first
             * @param string|null $operator
             * @param string|null $second
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function rightJoinSub($query, $as, $first, $operator = null, $second = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->rightJoinSub($query, $as, $first, $operator, $second);
            }
             
                /**
             * Add a "cross join" clause to the query.
             *
             * @param string $table
             * @param \Closure|string|null $first
             * @param string|null $operator
             * @param string|null $second
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function crossJoin($table, $first = null, $operator = null, $second = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->crossJoin($table, $first, $operator, $second);
            }
             
                /**
             * Add a subquery cross join to the query.
             *
             * @param \Closure|\Illuminate\Database\Query\Builder|string $query
             * @param string $as
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function crossJoinSub($query, $as)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->crossJoinSub($query, $as);
            }
             
                /**
             * Merge an array of where clauses and bindings.
             *
             * @param array $wheres
             * @param array $bindings
             * @return void 
             * @static 
             */ 
            public static function mergeWheres($wheres, $bindings)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                $instance->mergeWheres($wheres, $bindings);
            }
             
                /**
             * Prepare the value and operator for a where clause.
             *
             * @param string $value
             * @param string $operator
             * @param bool $useDefault
             * @return array 
             * @throws \InvalidArgumentException
             * @static 
             */ 
            public static function prepareValueAndOperator($value, $operator, $useDefault = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->prepareValueAndOperator($value, $operator, $useDefault);
            }
             
                /**
             * Add a "where" clause comparing two columns to the query.
             *
             * @param string|array $first
             * @param string|null $operator
             * @param string|null $second
             * @param string|null $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereColumn($first, $operator, $second, $boolean);
            }
             
                /**
             * Add an "or where" clause comparing two columns to the query.
             *
             * @param string|array $first
             * @param string|null $operator
             * @param string|null $second
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereColumn($first, $operator = null, $second = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereColumn($first, $operator, $second);
            }
             
                /**
             * Add a raw where clause to the query.
             *
             * @param string $sql
             * @param mixed $bindings
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereRaw($sql, $bindings = [], $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereRaw($sql, $bindings, $boolean);
            }
             
                /**
             * Add a raw or where clause to the query.
             *
             * @param string $sql
             * @param mixed $bindings
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereRaw($sql, $bindings = [])
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereRaw($sql, $bindings);
            }
             
                /**
             * Add a "where in" clause to the query.
             *
             * @param string $column
             * @param mixed $values
             * @param string $boolean
             * @param bool $not
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereIn($column, $values, $boolean = 'and', $not = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereIn($column, $values, $boolean, $not);
            }
             
                /**
             * Add an "or where in" clause to the query.
             *
             * @param string $column
             * @param mixed $values
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereIn($column, $values)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereIn($column, $values);
            }
             
                /**
             * Add a "where not in" clause to the query.
             *
             * @param string $column
             * @param mixed $values
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereNotIn($column, $values, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereNotIn($column, $values, $boolean);
            }
             
                /**
             * Add an "or where not in" clause to the query.
             *
             * @param string $column
             * @param mixed $values
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereNotIn($column, $values)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereNotIn($column, $values);
            }
             
                /**
             * Add a "where in raw" clause for integer values to the query.
             *
             * @param string $column
             * @param \Illuminate\Contracts\Support\Arrayable|array $values
             * @param string $boolean
             * @param bool $not
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereIntegerInRaw($column, $values, $boolean = 'and', $not = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereIntegerInRaw($column, $values, $boolean, $not);
            }
             
                /**
             * Add an "or where in raw" clause for integer values to the query.
             *
             * @param string $column
             * @param \Illuminate\Contracts\Support\Arrayable|array $values
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereIntegerInRaw($column, $values)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereIntegerInRaw($column, $values);
            }
             
                /**
             * Add a "where not in raw" clause for integer values to the query.
             *
             * @param string $column
             * @param \Illuminate\Contracts\Support\Arrayable|array $values
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereIntegerNotInRaw($column, $values, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereIntegerNotInRaw($column, $values, $boolean);
            }
             
                /**
             * Add an "or where not in raw" clause for integer values to the query.
             *
             * @param string $column
             * @param \Illuminate\Contracts\Support\Arrayable|array $values
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereIntegerNotInRaw($column, $values)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereIntegerNotInRaw($column, $values);
            }
             
                /**
             * Add a "where null" clause to the query.
             *
             * @param string|array $columns
             * @param string $boolean
             * @param bool $not
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereNull($columns, $boolean = 'and', $not = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereNull($columns, $boolean, $not);
            }
             
                /**
             * Add an "or where null" clause to the query.
             *
             * @param string|array $column
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereNull($column)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereNull($column);
            }
             
                /**
             * Add a "where not null" clause to the query.
             *
             * @param string|array $columns
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereNotNull($columns, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereNotNull($columns, $boolean);
            }
             
                /**
             * Add a where between statement to the query.
             *
             * @param string|\Illuminate\Database\Query\Expression $column
             * @param array $values
             * @param string $boolean
             * @param bool $not
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereBetween($column, $values, $boolean = 'and', $not = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereBetween($column, $values, $boolean, $not);
            }
             
                /**
             * Add a where between statement using columns to the query.
             *
             * @param string $column
             * @param array $values
             * @param string $boolean
             * @param bool $not
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereBetweenColumns($column, $values, $boolean = 'and', $not = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereBetweenColumns($column, $values, $boolean, $not);
            }
             
                /**
             * Add an or where between statement to the query.
             *
             * @param string $column
             * @param array $values
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereBetween($column, $values)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereBetween($column, $values);
            }
             
                /**
             * Add an or where between statement using columns to the query.
             *
             * @param string $column
             * @param array $values
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereBetweenColumns($column, $values)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereBetweenColumns($column, $values);
            }
             
                /**
             * Add a where not between statement to the query.
             *
             * @param string $column
             * @param array $values
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereNotBetween($column, $values, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereNotBetween($column, $values, $boolean);
            }
             
                /**
             * Add a where not between statement using columns to the query.
             *
             * @param string $column
             * @param array $values
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereNotBetweenColumns($column, $values, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereNotBetweenColumns($column, $values, $boolean);
            }
             
                /**
             * Add an or where not between statement to the query.
             *
             * @param string $column
             * @param array $values
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereNotBetween($column, $values)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereNotBetween($column, $values);
            }
             
                /**
             * Add an or where not between statement using columns to the query.
             *
             * @param string $column
             * @param array $values
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereNotBetweenColumns($column, $values)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereNotBetweenColumns($column, $values);
            }
             
                /**
             * Add an "or where not null" clause to the query.
             *
             * @param string $column
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereNotNull($column)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereNotNull($column);
            }
             
                /**
             * Add a "where date" statement to the query.
             *
             * @param string $column
             * @param string $operator
             * @param \DateTimeInterface|string|null $value
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereDate($column, $operator, $value = null, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereDate($column, $operator, $value, $boolean);
            }
             
                /**
             * Add an "or where date" statement to the query.
             *
             * @param string $column
             * @param string $operator
             * @param \DateTimeInterface|string|null $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereDate($column, $operator, $value = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereDate($column, $operator, $value);
            }
             
                /**
             * Add a "where time" statement to the query.
             *
             * @param string $column
             * @param string $operator
             * @param \DateTimeInterface|string|null $value
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereTime($column, $operator, $value = null, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereTime($column, $operator, $value, $boolean);
            }
             
                /**
             * Add an "or where time" statement to the query.
             *
             * @param string $column
             * @param string $operator
             * @param \DateTimeInterface|string|null $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereTime($column, $operator, $value = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereTime($column, $operator, $value);
            }
             
                /**
             * Add a "where day" statement to the query.
             *
             * @param string $column
             * @param string $operator
             * @param \DateTimeInterface|string|null $value
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereDay($column, $operator, $value = null, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereDay($column, $operator, $value, $boolean);
            }
             
                /**
             * Add an "or where day" statement to the query.
             *
             * @param string $column
             * @param string $operator
             * @param \DateTimeInterface|string|null $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereDay($column, $operator, $value = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereDay($column, $operator, $value);
            }
             
                /**
             * Add a "where month" statement to the query.
             *
             * @param string $column
             * @param string $operator
             * @param \DateTimeInterface|string|null $value
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereMonth($column, $operator, $value = null, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereMonth($column, $operator, $value, $boolean);
            }
             
                /**
             * Add an "or where month" statement to the query.
             *
             * @param string $column
             * @param string $operator
             * @param \DateTimeInterface|string|null $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereMonth($column, $operator, $value = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereMonth($column, $operator, $value);
            }
             
                /**
             * Add a "where year" statement to the query.
             *
             * @param string $column
             * @param string $operator
             * @param \DateTimeInterface|string|int|null $value
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereYear($column, $operator, $value = null, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereYear($column, $operator, $value, $boolean);
            }
             
                /**
             * Add an "or where year" statement to the query.
             *
             * @param string $column
             * @param string $operator
             * @param \DateTimeInterface|string|int|null $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereYear($column, $operator, $value = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereYear($column, $operator, $value);
            }
             
                /**
             * Add a nested where statement to the query.
             *
             * @param \Closure $callback
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereNested($callback, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereNested($callback, $boolean);
            }
             
                /**
             * Create a new query instance for nested where condition.
             *
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function forNestedWhere()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->forNestedWhere();
            }
             
                /**
             * Add another query builder as a nested where to the query builder.
             *
             * @param \Illuminate\Database\Query\Builder $query
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function addNestedWhereQuery($query, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->addNestedWhereQuery($query, $boolean);
            }
             
                /**
             * Add an exists clause to the query.
             *
             * @param \Closure $callback
             * @param string $boolean
             * @param bool $not
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereExists($callback, $boolean = 'and', $not = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereExists($callback, $boolean, $not);
            }
             
                /**
             * Add an or exists clause to the query.
             *
             * @param \Closure $callback
             * @param bool $not
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereExists($callback, $not = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereExists($callback, $not);
            }
             
                /**
             * Add a where not exists clause to the query.
             *
             * @param \Closure $callback
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereNotExists($callback, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereNotExists($callback, $boolean);
            }
             
                /**
             * Add a where not exists clause to the query.
             *
             * @param \Closure $callback
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereNotExists($callback)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereNotExists($callback);
            }
             
                /**
             * Add an exists clause to the query.
             *
             * @param \Illuminate\Database\Query\Builder $query
             * @param string $boolean
             * @param bool $not
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function addWhereExistsQuery($query, $boolean = 'and', $not = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->addWhereExistsQuery($query, $boolean, $not);
            }
             
                /**
             * Adds a where condition using row values.
             *
             * @param array $columns
             * @param string $operator
             * @param array $values
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @throws \InvalidArgumentException
             * @static 
             */ 
            public static function whereRowValues($columns, $operator, $values, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereRowValues($columns, $operator, $values, $boolean);
            }
             
                /**
             * Adds an or where condition using row values.
             *
             * @param array $columns
             * @param string $operator
             * @param array $values
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereRowValues($columns, $operator, $values)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereRowValues($columns, $operator, $values);
            }
             
                /**
             * Add a "where JSON contains" clause to the query.
             *
             * @param string $column
             * @param mixed $value
             * @param string $boolean
             * @param bool $not
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereJsonContains($column, $value, $boolean = 'and', $not = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereJsonContains($column, $value, $boolean, $not);
            }
             
                /**
             * Add an "or where JSON contains" clause to the query.
             *
             * @param string $column
             * @param mixed $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereJsonContains($column, $value)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereJsonContains($column, $value);
            }
             
                /**
             * Add a "where JSON not contains" clause to the query.
             *
             * @param string $column
             * @param mixed $value
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereJsonDoesntContain($column, $value, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereJsonDoesntContain($column, $value, $boolean);
            }
             
                /**
             * Add an "or where JSON not contains" clause to the query.
             *
             * @param string $column
             * @param mixed $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereJsonDoesntContain($column, $value)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereJsonDoesntContain($column, $value);
            }
             
                /**
             * Add a "where JSON length" clause to the query.
             *
             * @param string $column
             * @param mixed $operator
             * @param mixed $value
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function whereJsonLength($column, $operator, $value = null, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->whereJsonLength($column, $operator, $value, $boolean);
            }
             
                /**
             * Add an "or where JSON length" clause to the query.
             *
             * @param string $column
             * @param mixed $operator
             * @param mixed $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orWhereJsonLength($column, $operator, $value = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orWhereJsonLength($column, $operator, $value);
            }
             
                /**
             * Handles dynamic "where" clauses to the query.
             *
             * @param string $method
             * @param array $parameters
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function dynamicWhere($method, $parameters)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->dynamicWhere($method, $parameters);
            }
             
                /**
             * Add a "group by" clause to the query.
             *
             * @param array|string $groups
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function groupBy(...$groups)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->groupBy(...$groups);
            }
             
                /**
             * Add a raw groupBy clause to the query.
             *
             * @param string $sql
             * @param array $bindings
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function groupByRaw($sql, $bindings = [])
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->groupByRaw($sql, $bindings);
            }
             
                /**
             * Add a "having" clause to the query.
             *
             * @param string $column
             * @param string|null $operator
             * @param string|null $value
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function having($column, $operator = null, $value = null, $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->having($column, $operator, $value, $boolean);
            }
             
                /**
             * Add an "or having" clause to the query.
             *
             * @param string $column
             * @param string|null $operator
             * @param string|null $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orHaving($column, $operator = null, $value = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orHaving($column, $operator, $value);
            }
             
                /**
             * Add a "having between " clause to the query.
             *
             * @param string $column
             * @param array $values
             * @param string $boolean
             * @param bool $not
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function havingBetween($column, $values, $boolean = 'and', $not = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->havingBetween($column, $values, $boolean, $not);
            }
             
                /**
             * Add a raw having clause to the query.
             *
             * @param string $sql
             * @param array $bindings
             * @param string $boolean
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function havingRaw($sql, $bindings = [], $boolean = 'and')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->havingRaw($sql, $bindings, $boolean);
            }
             
                /**
             * Add a raw or having clause to the query.
             *
             * @param string $sql
             * @param array $bindings
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orHavingRaw($sql, $bindings = [])
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orHavingRaw($sql, $bindings);
            }
             
                /**
             * Add an "order by" clause to the query.
             *
             * @param \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Query\Expression|string $column
             * @param string $direction
             * @return \Illuminate\Database\Query\Builder 
             * @throws \InvalidArgumentException
             * @static 
             */ 
            public static function orderBy($column, $direction = 'asc')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orderBy($column, $direction);
            }
             
                /**
             * Add a descending "order by" clause to the query.
             *
             * @param \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Query\Expression|string $column
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orderByDesc($column)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orderByDesc($column);
            }
             
                /**
             * Put the query's results in random order.
             *
             * @param string $seed
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function inRandomOrder($seed = '')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->inRandomOrder($seed);
            }
             
                /**
             * Add a raw "order by" clause to the query.
             *
             * @param string $sql
             * @param array $bindings
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function orderByRaw($sql, $bindings = [])
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->orderByRaw($sql, $bindings);
            }
             
                /**
             * Alias to set the "offset" value of the query.
             *
             * @param int $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function skip($value)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->skip($value);
            }
             
                /**
             * Set the "offset" value of the query.
             *
             * @param int $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function offset($value)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->offset($value);
            }
             
                /**
             * Alias to set the "limit" value of the query.
             *
             * @param int $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function take($value)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->take($value);
            }
             
                /**
             * Set the "limit" value of the query.
             *
             * @param int $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function limit($value)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->limit($value);
            }
             
                /**
             * Set the limit and offset for a given page.
             *
             * @param int $page
             * @param int $perPage
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function forPage($page, $perPage = 15)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->forPage($page, $perPage);
            }
             
                /**
             * Constrain the query to the previous "page" of results before a given ID.
             *
             * @param int $perPage
             * @param int|null $lastId
             * @param string $column
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function forPageBeforeId($perPage = 15, $lastId = 0, $column = 'id')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->forPageBeforeId($perPage, $lastId, $column);
            }
             
                /**
             * Constrain the query to the next "page" of results after a given ID.
             *
             * @param int $perPage
             * @param int|null $lastId
             * @param string $column
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function forPageAfterId($perPage = 15, $lastId = 0, $column = 'id')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->forPageAfterId($perPage, $lastId, $column);
            }
             
                /**
             * Remove all existing orders and optionally add a new order.
             *
             * @param \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Query\Expression|string|null $column
             * @param string $direction
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function reorder($column = null, $direction = 'asc')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->reorder($column, $direction);
            }
             
                /**
             * Add a union statement to the query.
             *
             * @param \Illuminate\Database\Query\Builder|\Closure $query
             * @param bool $all
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function union($query, $all = false)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->union($query, $all);
            }
             
                /**
             * Add a union all statement to the query.
             *
             * @param \Illuminate\Database\Query\Builder|\Closure $query
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function unionAll($query)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->unionAll($query);
            }
             
                /**
             * Lock the selected rows in the table.
             *
             * @param string|bool $value
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function lock($value = true)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->lock($value);
            }
             
                /**
             * Lock the selected rows in the table for updating.
             *
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function lockForUpdate()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->lockForUpdate();
            }
             
                /**
             * Share lock the selected rows in the table.
             *
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function sharedLock()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->sharedLock();
            }
             
                /**
             * Register a closure to be invoked before the query is executed.
             *
             * @param callable $callback
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function beforeQuery($callback)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->beforeQuery($callback);
            }
             
                /**
             * Invoke the "before query" modification callbacks.
             *
             * @return void 
             * @static 
             */ 
            public static function applyBeforeQueryCallbacks()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                $instance->applyBeforeQueryCallbacks();
            }
             
                /**
             * Get the SQL representation of the query.
             *
             * @return string 
             * @static 
             */ 
            public static function toSql()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->toSql();
            }
             
                /**
             * Get the count of the total records for the paginator.
             *
             * @param array $columns
             * @return int 
             * @static 
             */ 
            public static function getCountForPagination($columns = [])
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->getCountForPagination($columns);
            }
             
                /**
             * Concatenate values of a given column as a string.
             *
             * @param string $column
             * @param string $glue
             * @return string 
             * @static 
             */ 
            public static function implode($column, $glue = '')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->implode($column, $glue);
            }
             
                /**
             * Determine if any rows exist for the current query.
             *
             * @return bool 
             * @static 
             */ 
            public static function exists()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->exists();
            }
             
                /**
             * Determine if no rows exist for the current query.
             *
             * @return bool 
             * @static 
             */ 
            public static function doesntExist()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->doesntExist();
            }
             
                /**
             * Execute the given callback if no rows exist for the current query.
             *
             * @param \Closure $callback
             * @return mixed 
             * @static 
             */ 
            public static function existsOr($callback)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->existsOr($callback);
            }
             
                /**
             * Execute the given callback if rows exist for the current query.
             *
             * @param \Closure $callback
             * @return mixed 
             * @static 
             */ 
            public static function doesntExistOr($callback)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->doesntExistOr($callback);
            }
             
                /**
             * Retrieve the "count" result of the query.
             *
             * @param string $columns
             * @return int 
             * @static 
             */ 
            public static function count($columns = '*')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->count($columns);
            }
             
                /**
             * Retrieve the minimum value of a given column.
             *
             * @param string $column
             * @return mixed 
             * @static 
             */ 
            public static function min($column)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->min($column);
            }
             
                /**
             * Retrieve the maximum value of a given column.
             *
             * @param string $column
             * @return mixed 
             * @static 
             */ 
            public static function max($column)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->max($column);
            }
             
                /**
             * Retrieve the sum of the values of a given column.
             *
             * @param string $column
             * @return mixed 
             * @static 
             */ 
            public static function sum($column)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->sum($column);
            }
             
                /**
             * Retrieve the average of the values of a given column.
             *
             * @param string $column
             * @return mixed 
             * @static 
             */ 
            public static function avg($column)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->avg($column);
            }
             
                /**
             * Alias for the "avg" method.
             *
             * @param string $column
             * @return mixed 
             * @static 
             */ 
            public static function average($column)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->average($column);
            }
             
                /**
             * Execute an aggregate function on the database.
             *
             * @param string $function
             * @param array $columns
             * @return mixed 
             * @static 
             */ 
            public static function aggregate($function, $columns = [])
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->aggregate($function, $columns);
            }
             
                /**
             * Execute a numeric aggregate function on the database.
             *
             * @param string $function
             * @param array $columns
             * @return float|int 
             * @static 
             */ 
            public static function numericAggregate($function, $columns = [])
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->numericAggregate($function, $columns);
            }
             
                /**
             * Insert new records into the database.
             *
             * @param array $values
             * @return bool 
             * @static 
             */ 
            public static function insert($values)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->insert($values);
            }
             
                /**
             * Insert new records into the database while ignoring errors.
             *
             * @param array $values
             * @return int 
             * @static 
             */ 
            public static function insertOrIgnore($values)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->insertOrIgnore($values);
            }
             
                /**
             * Insert a new record and get the value of the primary key.
             *
             * @param array $values
             * @param string|null $sequence
             * @return int 
             * @static 
             */ 
            public static function insertGetId($values, $sequence = null)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->insertGetId($values, $sequence);
            }
             
                /**
             * Insert new records into the table using a subquery.
             *
             * @param array $columns
             * @param \Closure|\Illuminate\Database\Query\Builder|string $query
             * @return int 
             * @static 
             */ 
            public static function insertUsing($columns, $query)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->insertUsing($columns, $query);
            }
             
                /**
             * Insert or update a record matching the attributes, and fill it with values.
             *
             * @param array $attributes
             * @param array $values
             * @return bool 
             * @static 
             */ 
            public static function updateOrInsert($attributes, $values = [])
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->updateOrInsert($attributes, $values);
            }
             
                /**
             * Run a truncate statement on the table.
             *
             * @return void 
             * @static 
             */ 
            public static function truncate()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                $instance->truncate();
            }
             
                /**
             * Create a raw database expression.
             *
             * @param mixed $value
             * @return \Illuminate\Database\Query\Expression 
             * @static 
             */ 
            public static function raw($value)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->raw($value);
            }
             
                /**
             * Get the current query value bindings in a flattened array.
             *
             * @return array 
             * @static 
             */ 
            public static function getBindings()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->getBindings();
            }
             
                /**
             * Get the raw array of bindings.
             *
             * @return array 
             * @static 
             */ 
            public static function getRawBindings()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->getRawBindings();
            }
             
                /**
             * Set the bindings on the query builder.
             *
             * @param array $bindings
             * @param string $type
             * @return \Illuminate\Database\Query\Builder 
             * @throws \InvalidArgumentException
             * @static 
             */ 
            public static function setBindings($bindings, $type = 'where')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->setBindings($bindings, $type);
            }
             
                /**
             * Add a binding to the query.
             *
             * @param mixed $value
             * @param string $type
             * @return \Illuminate\Database\Query\Builder 
             * @throws \InvalidArgumentException
             * @static 
             */ 
            public static function addBinding($value, $type = 'where')
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->addBinding($value, $type);
            }
             
                /**
             * Merge an array of bindings into our bindings.
             *
             * @param \Illuminate\Database\Query\Builder $query
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function mergeBindings($query)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->mergeBindings($query);
            }
             
                /**
             * Remove all of the expressions from a list of bindings.
             *
             * @param array $bindings
             * @return array 
             * @static 
             */ 
            public static function cleanBindings($bindings)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->cleanBindings($bindings);
            }
             
                /**
             * Get the database query processor instance.
             *
             * @return \Illuminate\Database\Query\Processors\Processor 
             * @static 
             */ 
            public static function getProcessor()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->getProcessor();
            }
             
                /**
             * Get the query grammar instance.
             *
             * @return \Illuminate\Database\Query\Grammars\Grammar 
             * @static 
             */ 
            public static function getGrammar()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->getGrammar();
            }
             
                /**
             * Use the write pdo for query.
             *
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function useWritePdo()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->useWritePdo();
            }
             
                /**
             * Clone the query without the given properties.
             *
             * @param array $properties
             * @return static 
             * @static 
             */ 
            public static function cloneWithout($properties)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->cloneWithout($properties);
            }
             
                /**
             * Clone the query without the given bindings.
             *
             * @param array $except
             * @return static 
             * @static 
             */ 
            public static function cloneWithoutBindings($except)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->cloneWithoutBindings($except);
            }
             
                /**
             * Dump the current SQL and bindings.
             *
             * @return \Illuminate\Database\Query\Builder 
             * @static 
             */ 
            public static function dump()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->dump();
            }
             
                /**
             * Die and dump the current SQL and bindings.
             *
             * @return void 
             * @static 
             */ 
            public static function dd()
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                $instance->dd();
            }
             
                /**
             * Register a custom macro.
             *
             * @param string $name
             * @param object|callable $macro
             * @return void 
             * @static 
             */ 
            public static function macro($name, $macro)
            {
                                \Illuminate\Database\Query\Builder::macro($name, $macro);
            }
             
                /**
             * Mix another object into the class.
             *
             * @param object $mixin
             * @param bool $replace
             * @return void 
             * @throws \ReflectionException
             * @static 
             */ 
            public static function mixin($mixin, $replace = true)
            {
                                \Illuminate\Database\Query\Builder::mixin($mixin, $replace);
            }
             
                /**
             * Dynamically handle calls to the class.
             *
             * @param string $method
             * @param array $parameters
             * @return mixed 
             * @throws \BadMethodCallException
             * @static 
             */ 
            public static function macroCall($method, $parameters)
            {
                                /** @var \Illuminate\Database\Query\Builder $instance */
                                return $instance->macroCall($method, $parameters);
            }
                    }
            class Event extends \Illuminate\Support\Facades\Event {}
            class File extends \Illuminate\Support\Facades\File {}
            class Gate extends \Illuminate\Support\Facades\Gate {}
            class Hash extends \Illuminate\Support\Facades\Hash {}
            class Javascript extends \Laracasts\Utilities\JavaScript\JavaScriptFacade {}
            class Lang extends \Illuminate\Support\Facades\Lang {}
            class Log extends \Illuminate\Support\Facades\Log {}
            class Mail extends \Illuminate\Support\Facades\Mail {}
            class Notification extends \Illuminate\Support\Facades\Notification {}
            class Password extends \Illuminate\Support\Facades\Password {}
            class Queue extends \Illuminate\Support\Facades\Queue {}
            class Redirect extends \Illuminate\Support\Facades\Redirect {}
            class Redis extends \Illuminate\Support\Facades\Redis {}
            class Request extends \Illuminate\Support\Facades\Request {}
            class Response extends \Illuminate\Support\Facades\Response {}
            class Route extends \Illuminate\Support\Facades\Route {}
            class Schema extends \Illuminate\Support\Facades\Schema {}
            class Session extends \Illuminate\Support\Facades\Session {}
            class Storage extends \Illuminate\Support\Facades\Storage {}
            class URL extends \Illuminate\Support\Facades\URL {}
            class Validator extends \Illuminate\Support\Facades\Validator {}
            class View extends \Illuminate\Support\Facades\View {}
            class Debugbar extends \Barryvdh\Debugbar\Facade {}
            class Flare extends \Facade\Ignition\Facades\Flare {}
            class JavaScript extends \Laracasts\Utilities\JavaScript\JavaScriptFacade {}
            class Fractal extends \Spatie\Fractal\FractalFacade {}
     
}



namespace Illuminate\Support {
    /**
     * Methods commonly used in migrations
     *
     * @method Fluent after(string $column) Add the after modifier
     * @method Fluent charset(string $charset) Add the character set modifier
     * @method Fluent collation(string $collation) Add the collation modifier
     * @method Fluent comment(string $comment) Add comment
     * @method Fluent default($value) Add the default modifier
     * @method Fluent first() Select first row
     * @method Fluent index(string $name = null) Add the in dex clause
     * @method Fluent on(string $table) `on` of a foreign key
     * @method Fluent onDelete(string $action) `on delete` of a foreign key
     * @method Fluent onUpdate(string $action) `on update` of a foreign key
     * @method Fluent primary() Add the primary key modifier
     * @method Fluent references(string $column) `references` of a foreign key
     * @method Fluent nullable(bool $value = true) Add the nullable modifier
     * @method Fluent unique(string $name = null) Add unique index clause
     * @method Fluent unsigned() Add the unsigned modifier
     * @method Fluent useCurrent() Add the default timestamp value
     * @method Fluent change() Add the change modifier
     */
    class Fluent {}
}

