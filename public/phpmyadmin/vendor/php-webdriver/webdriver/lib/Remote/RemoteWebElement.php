<?php

namespace Facebook\WebDriver\Remote;

use Facebook\WebDriver\Exception\ElementNotInteractableException;
use Facebook\WebDriver\Exception\UnsupportedOperationException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Interactions\Internal\WebDriverCoordinates;
use Facebook\WebDriver\Internal\WebDriverLocatable;
use Facebook\WebDriver\Support\ScreenshotHelper;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverPoint;
use ZipArchive;

/**
 * Represents an HTML element.
 */
class RemoteWebElement implements WebDriverElement, WebDriverLocatable
{
    /**
     * @var RemoteExecuteMethod
     */
    protected $executor;
    /**
     * @var string
     */
    protected $id;
    /**
     * @var FileDetector
     */
    protected $fileDetector;
    /**
     * @var bool
     */
    protected $isW3cCompliant;

    /**
     * @param RemoteExecuteMethod $executor
     * @param string $id
     * @param bool $isW3cCompliant
     */
    public function __construct(RemoteExecuteMethod $executor, $id, $isW3cCompliant = false)
    {
        $this->executor = $executor;
        $this->id = $id;
        $this->fileDetector = new UselessFileDetector();
        $this->isW3cCompliant = $isW3cCompliant;
    }

    /**
     * Clear content editable or resettable element
     *
     * @return RemoteWebElement The current instance.
     */
    public function clear()
    {
        $this->executor->execute(
            DriverCommand::CLEAR_ELEMENT,
            [':id' => $this->id]
        );

        return $this;
    }

    /**
     * Click this element.
     *
     * @return RemoteWebElement The current instance.
     */
    public function click()
    {
        try {
            $this->executor->execute(
                DriverCommand::CLICK_ELEMENT,
                [':id' => $this->id]
            );
        } catch (ElementNotInteractableException $e) {
            // An issue with geckodriver (https://github.com/mozilla/geckodriver/issues/653) prevents clicking on a link
            // if the first child is a block-level element.
            // The workaround in this case is to click on a child element.
            $this->clickChildElement($e);
        }

        return $this;
    }

    /**
     * Find the first WebDriverElement within this element using the given mechanism.
     *
     * When using xpath be aware that webdriver follows standard conventions: a search prefixed with "//" will
     * search the entire document from the root, not just the children (relative context) of this current node.
     * Use ".//" to limit your search to the children of this element.
     *
     * @param WebDriverBy $by
     * @return RemoteWebElement NoSuchElementException is thrown in HttpCommandExecutor if no element is found.
     * @see WebDriverBy
     */
    public function findElement(WebDriverBy $by)
    {
        $params = JsonWireCompat::getUsing($by, $this->isW3cCompliant);
        $params[':id'] = $this->id;

        $raw_element = $this->executor->execute(
            DriverCommand::FIND_CHILD_ELEMENT,
            $params
        );

        return $this->newElement(JsonWireCompat::getElement($raw_element));
    }

    /**
     * Find all WebDriverElements within this element using the given mechanism.
     *
     * When using xpath be aware that webdriver follows standard conventions: a search prefixed with "//" will
     * search the entire document from the root, not just the children (relative context) of this current node.
     * Use ".//" to limit your search to the children of this element.
     *
     * @param WebDriverBy $by
     * @return RemoteWebElement[] A list of all WebDriverElements, or an empty
     *    array if nothing matches
     * @see WebDriverBy
     */
    public function findElements(WebDriverBy $by)
    {
        $params = JsonWireCompat::getUsing($by, $this->isW3cCompliant);
        $params[':id'] = $this->id;
        $raw_elements = $this->executor->execute(
            DriverCommand::FIND_CHILD_ELEMENTS,
            $params
        );

        $elements = [];
        foreach ($raw_elements as $raw_element) {
            $elements[] = $this->newElement(JsonWireCompat::getElement($raw_element));
        }

        return $elements;
    }

    /**
     * Get the value of the given attribute of the element.
     * Attribute is meant what is declared in the HTML markup of the element.
     * To read a value of a IDL "JavaScript" property (like `innerHTML`), use `getDomProperty()` method.
     *
     * @param string $attribute_name The name of the attribute.
     * @return string|true|null The value of the attribute. If this is boolean attribute, return true if the element
     *      has it, otherwise return null.
     */
    public function getAttribute($attribute_name)
    {
        $params = [
            ':name' => $attribute_name,
            ':id' => $this->id,
        ];

        if ($this->isW3cCompliant && ($attribute_name === 'value' || $attribute_name === 'index')) {
            $value = $this->executor->execute(DriverCommand::GET_ELEMENT_PROPERTY, $params);

            if ($value === true) {
                return 'true';
            }

            if ($value === false) {
                return 'false';
            }

            if ($value !== null) {
                return (string) $value;
            }
        }

        return $this->executor->execute(DriverCommand::GET_ELEMENT_ATTRIBUTE, $params);
    }

    /**
     * Gets the value of a IDL JavaScript property of this element (for example `innerHTML`, `tagName` etc.).
     *
     * @see https://developer.mozilla.org/en-US/docs/Glossary/IDL
     * @see https://developer.mozilla.org/en-US/docs/Web/API/Element#properties
     * @param string $propertyName
     * @return mixed|null The property's current value or null if the value is not set or the property does not exist.
     */
    public function getDomProperty($propertyName)
    {
        if (!$this->isW3cCompliant) {
            throw new UnsupportedOperationException('This method is only supported in W3C mode');
        }

        $params = [
            ':name' => $propertyName,
            ':id' => $this->id,
        ];

        return $this->executor->execute(DriverCommand::GET_ELEMENT_PROPERTY, $params);
    }

    /**
     * Get the value of a given CSS property.
     *
     * @param string $css_property_name The name of the CSS property.
     * @return string The value of the CSS property.
     */
    public function getCSSValue($css_property_name)
    {
        $params = [
            ':propertyName' => $css_property_name,
            ':id' => $this->id,
        ];

        return $this->executor->execute(
            DriverCommand::GET_ELEMENT_VALUE_OF_CSS_PROPERTY,
            $params
        );
    }

    /**
     * Get the location of element relative to the top-left corner of the page.
     *
     * @return WebDriverPoint The location of the element.
     */
    public function getLocation()
    {
        $location = $this->executor->execute(
            DriverCommand::GET_ELEMENT_LOCATION,
            [':id' => $this->id]
        );

        return new WebDriverPoint($location['x'], $location['y']);
    }

    /**
     * Try scrolling the element into the view port and return the location of
     * element relative to the top-left corner of the page afterwards.
     *
     * @return WebDriverPoint The location of the element.
     */
    public function getLocationOnScreenOnceScrolledIntoView()
    {
        if ($this->isW3cCompliant) {
            $script = <<<JS
var e = arguments[0];
e.scrollIntoView({ behavior: 'instant', block: 'end', inline: 'nearest' }); 
var rect = e.getBoundingClientRect(); 
return {'x': rect.left, 'y': rect.top};
JS;

            $result = $this->executor->execute(DriverCommand::EXECUTE_SCRIPT, [
                'script' => $script,
                'args' => [[JsonWireCompat::WEB_DRIVER_ELEMENT_IDENTIFIER => $this->id]],
            ]);
            $location = ['x' => $result['x'], 'y' => $result['y']];
        } else {
            $location = $this->executor->execute(
                DriverCommand::GET_ELEMENT_LOCATION_ONCE_SCROLLED_INTO_VIEW,
                [':id' => $this->id]
            );
        }

        return new WebDriverPoint($location['x'], $location['y']);
    }

    /**
     * @return WebDriverCoordinates
     */
    public function getCoordinates()
    {
        $element = $this;

        $on_screen = null; // planned but not yet implemented
        $in_view_port = static function () use ($element) {
            return $element->getLocationOnScreenOnceScrolledIntoView();
        };
        $on_page = static function () use ($element) {
            return $element->getLocation();
        };
        $auxiliary = $this->getID();

        return new WebDriverCoordinates(
            $on_screen,
            $in_view_port,
            $on_page,
            $auxiliary
        );
    }

    /**
     * Get the size of element.
     *
     * @return WebDriverDimension The dimension of the element.
     */
    public function getSize()
    {
        $size = $this->executor->execute(
            DriverCommand::GET_ELEMENT_SIZE,
            [':id' => $this->id]
        );

        return new WebDriverDimension($size['width'], $size['height']);
    }

    /**
     * Get the (lowercase) tag name of this element.
     *
     * @return string The tag name.
     */
    public function getTagName()
    {
        // Force tag name to be lowercase as expected by JsonWire protocol for Opera driver
        // until this issue is not resolved :
        // https://github.com/operasoftware/operadriver/issues/102
        // Remove it when fixed to be consistent with the protocol.
        return mb_strtolower($this->executor->execute(
            DriverCommand::GET_ELEMENT_TAG_NAME,
            [':id' => $this->id]
        ));
    }

    /**
     * Get the visible (i.e. not hidden by CSS) innerText of this element,
     * including sub-elements, without any leading or trailing whitespace.
     *
     * @return string The visible innerText of this element.
     */
    public function getText()
    {
        return $this->executor->execute(
            DriverCommand::GET_ELEMENT_TEXT,
            [':id' => $this->id]
        );
    }

    /**
     * Is this element displayed or not? This method avoids the problem of having
     * to parse an element's "style" attribute.
     *
     * @return bool
     */
    public function isDisplayed()
    {
        return $this->executor->execute(
            DriverCommand::IS_ELEMENT_DISPLAYED,
            [':id' => $this->id]
        );
    }

    /**
     * Is the element currently enabled or not? This will generally return true
     * for everything but disabled input elements.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->executor->execute(
            DriverCommand::IS_ELEMENT_ENABLED,
            [':id' => $this->id]
        );
    }

    /**
     * Determine whether this element is selected or not.
     *
     * @return bool
     */
    public function isSelected()
    {
        return $this->executor->execute(
            DriverCommand::IS_ELEMENT_SELECTED,
            [':id' => $this->id]
        );
    }

    /**
     * Simulate typing into an element, which may set its value.
     *
     * @param mixed $value The data to be typed.
     * @return RemoteWebElement The current instance.
     */
    public function sendKeys($value)
    {
        $local_file = $this->fileDetector->getLocalFile($value);

        $params = [];
        if ($local_file === null) {
            if ($this->isW3cCompliant) {
                // Work around the Geckodriver NULL issue by splitting on NULL and calling sendKeys multiple times.
                // See https://bugzilla.mozilla.org/show_bug.cgi?id=1494661.
                $encodedValues = explode(WebDriverKeys::NULL, WebDriverKeys::encode($value, true));
                foreach ($encodedValues as $encodedValue) {
                    $params[] = [
                        'text' => $encodedValue,
                        ':id' => $this->id,
                    ];
                }
            } else {
                $params[] = [
                    'value' => WebDriverKeys::encode($value),
                    ':id' => $this->id,
                ];
            }
        } else {
            if ($this->isW3cCompliant) {
                try {
                    // Attempt to upload the file to the remote browser.
                    // This is so far non-W3C compliant method, so it may fail - if so, we just ignore the exception.
                    // @see https://github.com/w3c/webdriver/issues/1355
                    $fileName = $this->upload($local_file);
                } catch (WebDriverException $e) {
                    $fileName = $local_file;
                }

                $params[] = [
                    'text' => $fileName,
                    ':id' => $this->id,
                ];
            } else {
                $params[] = [
                    'value' => WebDriverKeys::encode($this->upload($local_file)),
                    ':id' => $this->id,
                ];
            }
        }

        foreach ($params as $param) {
            $this->executor->execute(DriverCommand::SEND_KEYS_TO_ELEMENT, $param);
        }

        return $this;
    }

    /**
     * Set the fileDetector in order to let the RemoteWebElement to know that you are going to upload a file.
     *
     * Basically, if you want WebDriver trying to send a file, set the fileDetector
     * to be LocalFileDetector. Otherwise, keep it UselessFileDetector.
     *
     *   eg. `$element->setFileDetector(new LocalFileDetector);`
     *
     * @param FileDetector $detector
     * @return RemoteWebElement
     * @see FileDetector
     * @see LocalFileDetector
     * @see UselessFileDetector
     */
    public function setFileDetector(FileDetector $detector)
    {
        $this->fileDetector = $detector;

        return $this;
    }

    /**
     * If this current element is a form, or an element within a form, then this will be submitted to the remote server.
     *
     * @return RemoteWebElement The current instance.
     */
    public function submit()
    {
        if ($this->isW3cCompliant) {
            // Submit method cannot be called directly in case an input of this form is named "submit".
            // We use this polyfill to trigger 'submit' event using form.dispatchEvent().
            $submitPolyfill = $script = <<<HTXT
                var form = arguments[0];
                while (form.nodeName !== "FORM" && form.parentNode) { // find the parent form of this element
                    form = form.parentNode;
                }
                if (!form) {
                    throw Error('Unable to find containing form element');
                }
                var event = new Event('submit', {bubbles: true, cancelable: true});
                if (form.dispatchEvent(event)) {
                    HTMLFormElement.prototype.submit.call(form);
                }
HTXT;
            $this->executor->execute(DriverCommand::EXECUTE_SCRIPT, [
                'script' => $submitPolyfill,
                'args' => [[JsonWireCompat::WEB_DRIVER_ELEMENT_IDENTIFIER => $this->id]],
            ]);

            return $this;
        }

        $this->executor->execute(
            DriverCommand::SUBMIT_ELEMENT,
            [':id' => $this->id]
        );

        return $this;
    }

    /**
     * Get the opaque ID of the element.
     *
     * @return string The opaque ID.
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Take a screenshot of a specific element.
     *
     * @param string $save_as The path of the screenshot to be saved.
     * @return string The screenshot in PNG format.
     */
    public function takeElementScreenshot($save_as = null)
    {
        return (new ScreenshotHelper($this->executor))->takeElementScreenshot($this->id, $save_as);
    }

    /**
     * Test if two elements IDs refer to the same DOM element.
     *
     * @param WebDriverElement $other
     * @return bool
     */
    public function equals(WebDriverElement $other)
    {
        if ($this->isW3cCompliant) {
            return $this->getID() === $other->getID();
        }

        return $this->executor->execute(DriverCommand::ELEMENT_EQUALS, [
            ':id' => $this->id,
            ':other' => $other->getID(),
        ]);
    }

    /**
     * Get representation of an element's shadow root for accessing the shadow DOM of a web component.
     *
     * @return ShadowRoot
     */
    public function getShadowRoot()
    {
        if (!$this->isW3cCompliant) {
            throw new UnsupportedOperationException('This method is only supported in W3C mode');
        }

        $response = $this->executor->execute(
            DriverCommand::GET_ELEMENT_SHADOW_ROOT,
            [
                ':id' => $this->id,
            ]
        );

        return ShadowRoot::createFromResponse($this->executor, $response);
    }

    /**
     * Attempt to click on a child level element.
     *
     * This provides a workaround for geckodriver bug 653 whereby a link whose first element is a block-level element
     * throws an ElementNotInteractableException could not scroll into view exception.
     *
     * The workaround provided here attempts to click on a child node of the element.
     * In case the first child is hidden, other elements are processed until we run out of elements.
     *
     * @param ElementNotInteractableException $originalException The exception to throw if unable to click on any child
     * @see https://github.com/mozilla/geckodriver/issues/653
     * @see https://bugzilla.mozilla.org/show_bug.cgi?id=1374283
     */
    protected function clickChildElement(ElementNotInteractableException $originalException)
    {
        $children = $this->findElements(WebDriverBy::xpath('./*'));
        foreach ($children as $child) {
            try {
                // Note: This does not use $child->click() as this would cause recursion into all children.
                // Where the element is hidden, all children will also be hidden.
                $this->executor->execute(
                    DriverCommand::CLICK_ELEMENT,
                    [':id' => $child->id]
                );

                return;
            } catch (ElementNotInteractableException $e) {
                // Ignore the ElementNotInteractableException exception on this node. Try the next child instead.
            }
        }

        throw $originalException;
    }

    /**
     * Return the WebDriverElement with $id
     *
     * @param string $id
     *
     * @return static
     */
    protected function newElement($id)
    {
        return new static($this->executor, $id, $this->isW3cCompliant);
    }

    /**
     * Upload a local file to the server
     *
     * @param string $local_file
     *
     * @throws WebDriverException
     * @return string The remote path of the file.
     */
    protected function upload($local_file)
    {
        if (!is_file($local_file)) {
            throw new WebDriverException('You may only upload files: ' . $local_file);
        }

        $temp_zip_path = $this->createTemporaryZipArchive($local_file);

        $remote_path = $this->executor->execute(
            DriverCommand::UPLOAD_FILE,
            ['file' => base64_encode(file_get_contents($temp_zip_path))]
        );

        unlink($temp_zip_path);

        return $remote_path;
    }

    /**
     * @param string $fileToZip
     * @return string
     */
    protected function createTemporaryZipArchive($fileToZip)
    {
        // Create a temporary file in the system temp directory.
        // Intentionally do not use `tempnam()`, as it creates empty file which zip extension may not handle.
        $tempZipPath = sys_get_temp_dir() . '/' . uniqid('WebDriverZip', false);

        $zip = new ZipArchive();
        if (($errorCode = $zip->open($tempZipPath, ZipArchive::CREATE)) !== true) {
            throw new WebDriverException(sprintf('Error creating zip archive: %s', $errorCode));
        }

        $info = pathinfo($fileToZip);
        $file_name = $info['basename'];
        $zip->addFile($fileToZip, $file_name);
        $zip->close();

        return $tempZipPath;
    }
}
