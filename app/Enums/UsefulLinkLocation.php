<?php

namespace App\Enums;

enum UsefulLinkLocation:String
{
    /**
     * Top bar
     * Only visible in the dashboard view
     */
    case topbar = "topbar";

    /**
     * Dashboard
     * Only visible in the dashboard view
     */
    case dashboard = "dashboard";

}
