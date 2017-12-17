<?php

namespace Pterodactyl\Exceptions\Repository;

class RecordNotFoundException extends RepositoryException
{
    /**
     * Handle request to render this exception to a user. Returns the default
     * 404 page view.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        if (! config('app.debug')) {
            return response()->view('errors.404', [], 404);
        }
    }
}
