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
     * Add a tree menu item.
     *
     * @param string $name
     * @param string $id
     * @param string $icon
     * @return void
     */
    public function addTree(string $name, string $id, string $icon = null)
    {
        if (!$this->has($id)) {
            array_push($this->items, [
                'id' => $id,
                'name' => $name,
                'icon' => $icon,
                'children' => [],
            ]);
        }
    }

    /**
     * Add a child route to a previously created tree.
     *
     * @param string $itemId
     * @param string $name
     * @param string $route
     * @param string $icon
     * @return void
     */
    public function addChild(string $itemId, string $name, string $route, string $icon = null)
    {
        if ($this->has($itemId)) {
            $tree = $this->get($itemId);

            array_push($tree['children'], [
                'name' => $name,
                'route' => $route,
                'icon' => $icon,
            ]);

            $this->setTreeItem($tree);
        } else {
            $this->addTree($itemId, $itemId);
            $this->addChild($itemId, $name, $route, $icon);
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
            return (array_key_exists('route', $v) && $v['route'] === $route) || (array_key_exists('id', $v) && $v['id'] === $route);
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
            return (array_key_exists('route', $v) && $v['route'] === $route) || (array_key_exists('id', $v) && $v['id'] === $route);
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

    /**
     * Persist the tree item itself to the class' variable.
     *
     * @param array $item
     * @return void
     */
    private function setTreeItem(array $item)
    {
        $currentTrees = Arr::where($this->items, function ($v, $k) use ($item) {
            return (array_key_exists('id', $v) && $v['id'] === $item['id']);
        });

        foreach($currentTrees as $key => $tree) {
            $this->items[$key] = $item;
        }
    }
}