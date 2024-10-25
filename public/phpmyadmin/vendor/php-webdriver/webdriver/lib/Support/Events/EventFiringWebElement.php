<?php

namespace Facebook\WebDriver\Support\Events;

use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Interactions\Internal\WebDriverCoordinates;
use Facebook\WebDriver\Internal\WebDriverLocatable;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverDispatcher;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverPoint;

class EventFiringWebElement implements WebDriverElement, WebDriverLocatable
{
    /**
     * @var WebDriverElement
     */
    protected $element;
    /**
     * @var WebDriverDispatcher
     */
    protected $dispatcher;

    /**
     * @param WebDriverElement $element
     * @param WebDriverDispatcher $dispatcher
     */
    public function __construct(WebDriverElement $element, WebDriverDispatcher $dispatcher)
    {
        $this->element = $element;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return WebDriverDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @return WebDriverElement
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @param mixed $value
     * @throws WebDriverException
     * @return $this
     */
    public function sendKeys($value)
    {
        $this->dispatch('beforeChangeValueOf', $this);

        try {
            $this->element->sendKeys($value);
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
        $this->dispatch('afterChangeValueOf', $this);

        return $this;
    }

    /**
     * @throws WebDriverException
     * @return $this
     */
    public function click()
    {
        $this->dispatch('beforeClickOn', $this);

        try {
            $this->element->click();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
        $this->dispatch('afterClickOn', $this);

        return $this;
    }

    /**
     * @param WebDriverBy $by
     * @throws WebDriverException
     * @return EventFiringWebElement
     */
    public function findElement(WebDriverBy $by)
    {
        $this->dispatch(
            'beforeFindBy',
            $by,
            $this,
            $this->dispatcher->getDefaultDriver()
        );

        try {
            $element = $this->newElement($this->element->findElement($by));
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }

        $this->dispatch(
            'afterFindBy',
            $by,
            $this,
            $this->dispatcher->getDefaultDriver()
        );

        return $element;
    }

    /**
     * @param WebDriverBy $by
     * @throws WebDriverException
     * @return array
     */
    public function findElements(WebDriverBy $by)
    {
        $this->dispatch(
            'beforeFindBy',
            $by,
            $this,
            $this->dispatcher->getDefaultDriver()
        );

        try {
            $elements = [];
            foreach ($this->element->findElements($by) as $element) {
                $elements[] = $this->newElement($element);
            }
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
        $this->dispatch(
            'afterFindBy',
            $by,
            $this,
            $this->dispatcher->getDefaultDriver()
        );

        return $elements;
    }

    /**
     * @throws WebDriverException
     * @return $this
     */
    public function clear()
    {
        try {
            $this->element->clear();

            return $this;
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @param string $attribute_name
     * @throws WebDriverException
     * @return string
     */
    public function getAttribute($attribute_name)
    {
        try {
            return $this->element->getAttribute($attribute_name);
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @param string $css_property_name
     * @throws WebDriverException
     * @return string
     */
    public function getCSSValue($css_property_name)
    {
        try {
            return $this->element->getCSSValue($css_property_name);
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @throws WebDriverException
     * @return WebDriverPoint
     */
    public function getLocation()
    {
        try {
            return $this->element->getLocation();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @throws WebDriverException
     * @return WebDriverPoint
     */
    public function getLocationOnScreenOnceScrolledIntoView()
    {
        try {
            return $this->element->getLocationOnScreenOnceScrolledIntoView();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @return WebDriverCoordinates
     */
    public function getCoordinates()
    {
        try {
            return $this->element->getCoordinates();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @throws WebDriverException
     * @return WebDriverDimension
     */
    public function getSize()
    {
        try {
            return $this->element->getSize();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @throws WebDriverException
     * @return string
     */
    public function getTagName()
    {
        try {
            return $this->element->getTagName();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @throws WebDriverException
     * @return string
     */
    public function getText()
    {
        try {
            return $this->element->getText();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @throws WebDriverException
     * @return bool
     */
    public function isDisplayed()
    {
        try {
            return $this->element->isDisplayed();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @throws WebDriverException
     * @return bool
     */
    public function isEnabled()
    {
        try {
            return $this->element->isEnabled();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @throws WebDriverException
     * @return bool
     */
    public function isSelected()
    {
        try {
            return $this->element->isSelected();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @throws WebDriverException
     * @return $this
     */
    public function submit()
    {
        try {
            $this->element->submit();

            return $this;
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @throws WebDriverException
     * @return string
     */
    public function getID()
    {
        try {
            return $this->element->getID();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * Test if two element IDs refer to the same DOM element.
     *
     * @param WebDriverElement $other
     * @return bool
     */
    public function equals(WebDriverElement $other)
    {
        try {
            return $this->element->equals($other);
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    public function takeElementScreenshot($save_as = null)
    {
        try {
            return $this->element->takeElementScreenshot($save_as);
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    public function getShadowRoot()
    {
        try {
            return $this->element->getShadowRoot();
        } catch (WebDriverException $exception) {
            $this->dispatchOnException($exception);
            throw $exception;
        }
    }

    /**
     * @param WebDriverException $exception
     */
    protected function dispatchOnException(WebDriverException $exception)
    {
        $this->dispatch(
            'onException',
            $exception,
            $this->dispatcher->getDefaultDriver()
        );
    }

    /**
     * @param mixed $method
     * @param mixed ...$arguments
     */
    protected function dispatch($method, ...$arguments)
    {
        if (!$this->dispatcher) {
            return;
        }

        $this->dispatcher->dispatch($method, $arguments);
    }

    /**
     * @param WebDriverElement $element
     * @return static
     */
    protected function newElement(WebDriverElement $element)
    {
        return new static($element, $this->getDispatcher());
    }
}
