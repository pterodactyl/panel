<?php

namespace Pterodactyl;

use Illuminate\Support\Arr;

class Navigation
{
    /**
     * The route items.
     *
     * @var array
     */
    private $items = [
        /*
         * [
         *      'name' => 'My Route',
         *      'route' => 'route-name',
         *      'icon' => 'fa fa-icon'
         * ]
         */
    ];

    /**
     * Add a navigation item to the current items.
     *
     * @param string $name Display name.
     * @param string $route The route name
     * @param string $icon Icon to display
     * @param bool $append Determine if it needs to appended to the items.
     * @return void
     */
    public function add(string $name, string $route, string $icon = null, bool $append = true)
    {
        if ($append) {
            array_push($this->items, [
                'name' => $name,
                'route' => $route,
                'icon' => $icon,
            ]);
        } else {
            $currentItems = Arr::where($this->items, function ($v, $k) use ($route) {
                return $v['route'] === $route;
            });

            foreach ($currentItems as $key => $value) {
                $this->items[$key] = [
                    'name' => $name,
                    'route' => $route,
                    'icon' => $icon,
                ];
            }
        }
    }

    /**
     * Get all the items.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->items;
    }

    /**
     * Find a navigation item with the specified route.
     *
     * @param string $route The route to search for.
     * @return array
     */
    public function get(string $route)
    {
        return Arr::first($this->items, function ($v, $k) use ($route) {
            return $v['route'] === $route;
        });
    }

    /**
     * Detemine if the route already exists in the current items.
     *
     * @param string $route
     * @return bool
     */
    public function has(string $route)
    {
        $navItem = Arr::first($this->items, function ($v, $k) use ($route) {
            return $v['route'] === $route;
        });

        return $navItem != null;
    }

    /**
     * Alias for getAll().
     */
    public function all()
    {
        return $this->getAll();
    }

    /**
     * Alias for get().
     */
    public function find(string $route)
    {
        return $this->get($route);
    }

    /**
     * Alias for has().
     */
    public function exists(string $route)
    {
        return $this->has($route);
    }
}