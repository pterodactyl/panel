<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Qirolab\Theme\Theme;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|Response
     */
    public function index()
    {


        //Get all tabs as laravel view paths
        $tabs = [];
        if(file_exists(Theme::getViewPaths()[0] . '/admin/settings/tabs/')){
            $tabspath = glob(Theme::getViewPaths()[0] . '/admin/settings/tabs/*.blade.php');
        }else{
            $tabspath = glob(Theme::path($path = 'views', $themeName = 'default').'/admin/settings/tabs/*.blade.php');
        }

          foreach ($tabspath as $filename) {
            $tabs[] = 'admin.settings.tabs.'.basename($filename, '.blade.php');
        }


        //Generate a html list item for each tab based on tabs file basename, set first tab as active
        $tabListItems = [];
        foreach ($tabs as $tab) {
            $tabName = str_replace('admin.settings.tabs.', '', $tab);
            $tabListItems[] = '<li class="nav-item">
            <a class="nav-link '.(empty($tabListItems) ? 'active' : '').'" data-toggle="pill" href="#'.$tabName.'">
            '.__(ucfirst($tabName)).'
            </a></li>';
        }

        $themes = array_diff(scandir(base_path('themes')), array('..', '.'));

        return view('admin.settings.index', [
            'tabs' => $tabs,
            'tabListItems' => $tabListItems,
            'themes' => $themes,
            'active_theme' => Theme::active(),
        ]);
    }
}
