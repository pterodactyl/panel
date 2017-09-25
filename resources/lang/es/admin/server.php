<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

return [
    'exceptions' => [
        'no_new_default_allocation' => 'You are attempting to delete the default allocation for this server but there is no fallback allocation to use.',
        'marked_as_failed' => 'This server was marked as having failed a previous installation. Current status cannot be toggled in this state.',
        'bad_variable' => 'There was a validation error with the :name variable.',
        'daemon_exception' => 'There was an exception while attempting to communicate with the daemon resulting in a HTTP/:code response code. This exception has been logged.',
        'default_allocation_not_found' => 'The requested default allocation was not found in this server\'s allocations.',
    ],
    'alerts' => [
        'startup_changed' => 'The startup configuration for this server has been updated. If this server\'s service or option was changed a reinstall will be occuring now.',
        'server_deleted' => 'Server has successfully been deleted from the system.',
        'server_created' => 'Server was successfully created on the panel. Please allow the daemon a few minutes to completely install this server.',
        'build_updated' => 'The build details for this server have been updated. Some changes may require a restart to take effect.',
        'suspension_toggled' => 'Server suspension status has been changed to :status.',
        'rebuild_on_boot' => 'This server has been marked as requiring a Docker Container rebuild. This will happen the next time the server is started.',
        'install_toggled' => 'The installation status for this server has been toggled.',
        'server_reinstalled' => 'This server has been queued for a reinstallation beginning now.',
        'details_updated' => 'Server details have been successfully updated.',
        'docker_image_updated' => 'Successfully changed the default Docker image to use for this server. A reboot is required to apply this change.',
        'node_required' => 'You must have at least one node configured before you can add a server to this panel.',
    ],
];
