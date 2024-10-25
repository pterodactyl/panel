<?php

namespace Facebook\WebDriver\Remote;

use Facebook\WebDriver\Interactions\Internal\WebDriverCoordinates;
use Facebook\WebDriver\WebDriverMouse;

/**
 * Execute mouse commands for RemoteWebDriver.
 */
class RemoteMouse implements WebDriverMouse
{
    /** @internal */
    const BUTTON_LEFT = 0;
    /** @internal */
    const BUTTON_MIDDLE = 1;
    /** @internal */
    const BUTTON_RIGHT = 2;

    /**
     * @var RemoteExecuteMethod
     */
    private $executor;
    /**
     * @var bool
     */
    private $isW3cCompliant;

    /**
     * @param RemoteExecuteMethod $executor
     * @param bool $isW3cCompliant
     */
    public function __construct(RemoteExecuteMethod $executor, $isW3cCompliant = false)
    {
        $this->executor = $executor;
        $this->isW3cCompliant = $isW3cCompliant;
    }

    /**
     * @param null|WebDriverCoordinates $where
     *
     * @return RemoteMouse
     */
    public function click(WebDriverCoordinates $where = null)
    {
        if ($this->isW3cCompliant) {
            $moveAction = $where ? [$this->createMoveAction($where)] : [];
            $this->executor->execute(DriverCommand::ACTIONS, [
                'actions' => [
                    [
                        'type' => 'pointer',
                        'id' => 'mouse',
                        'parameters' => ['pointerType' => 'mouse'],
                        'actions' => array_merge($moveAction, $this->createClickActions()),
                    ],
                ],
            ]);

            return $this;
        }

        $this->moveIfNeeded($where);
        $this->executor->execute(DriverCommand::CLICK, [
            'button' => self::BUTTON_LEFT,
        ]);

        return $this;
    }

    /**
     * @param WebDriverCoordinates $where
     *
     * @return RemoteMouse
     */
    public function contextClick(WebDriverCoordinates $where = null)
    {
        if ($this->isW3cCompliant) {
            $moveAction = $where ? [$this->createMoveAction($where)] : [];
            $this->executor->execute(DriverCommand::ACTIONS, [
                'actions' => [
                    [
                        'type' => 'pointer',
                        'id' => 'mouse',
                        'parameters' => ['pointerType' => 'mouse'],
                        'actions' => array_merge($moveAction, [
                            [
                                'type' => 'pointerDown',
                                'button' => self::BUTTON_RIGHT,
                            ],
                            [
                                'type' => 'pointerUp',
                                'button' => self::BUTTON_RIGHT,
                            ],
                        ]),
                    ],
                ],
            ]);

            return $this;
        }

        $this->moveIfNeeded($where);
        $this->executor->execute(DriverCommand::CLICK, [
            'button' => self::BUTTON_RIGHT,
        ]);

        return $this;
    }

    /**
     * @param WebDriverCoordinates $where
     *
     * @return RemoteMouse
     */
    public function doubleClick(WebDriverCoordinates $where = null)
    {
        if ($this->isW3cCompliant) {
            $clickActions = $this->createClickActions();
            $moveAction = $where === null ? [] : [$this->createMoveAction($where)];
            $this->executor->execute(DriverCommand::ACTIONS, [
                'actions' => [
                    [
                        'type' => 'pointer',
                        'id' => 'mouse',
                        'parameters' => ['pointerType' => 'mouse'],
                        'actions' => array_merge($moveAction, $clickActions, $clickActions),
                    ],
                ],
            ]);

            return $this;
        }

        $this->moveIfNeeded($where);
        $this->executor->execute(DriverCommand::DOUBLE_CLICK);

        return $this;
    }

    /**
     * @param WebDriverCoordinates $where
     *
     * @return RemoteMouse
     */
    public function mouseDown(WebDriverCoordinates $where = null)
    {
        if ($this->isW3cCompliant) {
            $this->executor->execute(DriverCommand::ACTIONS, [
                'actions' => [
                    [
                        'type' => 'pointer',
                        'id' => 'mouse',
                        'parameters' => ['pointerType' => 'mouse'],
                        'actions' => [
                            $this->createMoveAction($where),
                            [
                                'type' => 'pointerDown',
                                'button' => self::BUTTON_LEFT,
                            ],
                        ],
                    ],
                ],
            ]);

            return $this;
        }

        $this->moveIfNeeded($where);
        $this->executor->execute(DriverCommand::MOUSE_DOWN);

        return $this;
    }

    /**
     * @param WebDriverCoordinates $where
     * @param int|null $x_offset
     * @param int|null $y_offset
     *
     * @return RemoteMouse
     */
    public function mouseMove(
        WebDriverCoordinates $where = null,
        $x_offset = null,
        $y_offset = null
    ) {
        if ($this->isW3cCompliant) {
            $this->executor->execute(DriverCommand::ACTIONS, [
                'actions' => [
                    [
                        'type' => 'pointer',
                        'id' => 'mouse',
                        'parameters' => ['pointerType' => 'mouse'],
                        'actions' => [$this->createMoveAction($where, $x_offset, $y_offset)],
                    ],
                ],
            ]);

            return $this;
        }

        $params = [];
        if ($where !== null) {
            $params['element'] = $where->getAuxiliary();
        }
        if ($x_offset !== null) {
            $params['xoffset'] = $x_offset;
        }
        if ($y_offset !== null) {
            $params['yoffset'] = $y_offset;
        }

        $this->executor->execute(DriverCommand::MOVE_TO, $params);

        return $this;
    }

    /**
     * @param WebDriverCoordinates $where
     *
     * @return RemoteMouse
     */
    public function mouseUp(WebDriverCoordinates $where = null)
    {
        if ($this->isW3cCompliant) {
            $moveAction = $where ? [$this->createMoveAction($where)] : [];

            $this->executor->execute(DriverCommand::ACTIONS, [
                'actions' => [
                    [
                        'type' => 'pointer',
                        'id' => 'mouse',
                        'parameters' => ['pointerType' => 'mouse'],
                        'actions' => array_merge($moveAction, [
                            [
                                'type' => 'pointerUp',
                                'button' => self::BUTTON_LEFT,
                            ],
                        ]),
                    ],
                ],
            ]);

            return $this;
        }

        $this->moveIfNeeded($where);
        $this->executor->execute(DriverCommand::MOUSE_UP);

        return $this;
    }

    /**
     * @param WebDriverCoordinates $where
     */
    protected function moveIfNeeded(WebDriverCoordinates $where = null)
    {
        if ($where) {
            $this->mouseMove($where);
        }
    }

    /**
     * @param WebDriverCoordinates $where
     * @param int|null $x_offset
     * @param int|null $y_offset
     *
     * @return array
     */
    private function createMoveAction(
        WebDriverCoordinates $where = null,
        $x_offset = null,
        $y_offset = null
    ) {
        $move_action = [
            'type' => 'pointerMove',
            'duration' => 100, // to simulate human delay
            'x' => $x_offset === null ? 0 : $x_offset,
            'y' => $y_offset === null ? 0 : $y_offset,
        ];

        if ($where !== null) {
            $move_action['origin'] = [JsonWireCompat::WEB_DRIVER_ELEMENT_IDENTIFIER => $where->getAuxiliary()];
        } else {
            $move_action['origin'] = 'pointer';
        }

        return $move_action;
    }

    /**
     * @return array
     */
    private function createClickActions()
    {
        return [
            [
                'type' => 'pointerDown',
                'button' => self::BUTTON_LEFT,
            ],
            [
                'type' => 'pointerUp',
                'button' => self::BUTTON_LEFT,
            ],
        ];
    }
}
