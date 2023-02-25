<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'extensions', 'middleware' => 'api.token'], function () {
    // get all extensions that are inside App/Extensions
    // It is important that the extensions are inside a folder with the name of the extension
    // while those folders are inside Folders with the name of the type of extension like PaymentGateways, Themes, etc.
    $extensionNamespaces = glob(app_path() . '/Extensions/*', GLOB_ONLYDIR);
    $extensions = [];
    foreach ($extensionNamespaces as $extensionNamespace) {
        $extensions = array_merge($extensions, glob($extensionNamespace . '/*', GLOB_ONLYDIR));
    }

    foreach ($extensions as $extension) {
        $routesFile = $extension . '/api_routes.php';
        if (file_exists($routesFile)) {
            include_once $routesFile;
        }
    }
});
