<?php


namespace Entrypoint\Http\Controllers\Base;

use Entrypoint\Http\Controllers\Controller;


class IndexController extends Controller
{
    /**
     * @return string
     */
    public function index(){
        return 'success!';
    }
}