<?php

namespace Facebook\WebDriver\Remote;

/**
 * This list of command defined in the WebDriver json wire protocol.
 *
 * @codeCoverageIgnore
 */
class DriverCommand
{
    const GET_ALL_SESSIONS = 'getAllSessions';
    const GET_CAPABILITIES = 'getCapabilities';
    const NEW_SESSION = 'newSession';
    const STATUS = 'status';
    const CLOSE = 'close';
    const QUIT = 'quit';
    const GET = 'get';
    const GO_BACK = 'goBack';
    const GO_FORWARD = 'goForward';
    const REFRESH = 'refresh';
    const ADD_COOKIE = 'addCookie';
    const GET_ALL_COOKIES = 'getCookies';
    const DELETE_COOKIE = 'deleteCookie';
    const DELETE_ALL_COOKIES = 'deleteAllCookies';
    const FIND_ELEMENT = 'findElement';
    const FIND_ELEMENTS = 'findElements';
    const FIND_CHILD_ELEMENT = 'findChildElement';
    const FIND_CHILD_ELEMENTS = 'findChildElements';
    const CLEAR_ELEMENT = 'clearElement';
    const CLICK_ELEMENT = 'clickElement';
    const SEND_KEYS_TO_ELEMENT = 'sendKeysToElement';
    const SEND_KEYS_TO_ACTIVE_ELEMENT = 'sendKeysToActiveElement';
    const SUBMIT_ELEMENT = 'submitElement';
    const UPLOAD_FILE = 'uploadFile';
    const GET_CURRENT_WINDOW_HANDLE = 'getCurrentWindowHandle';
    const GET_WINDOW_HANDLES = 'getWindowHandles';
    const GET_CURRENT_CONTEXT_HANDLE = 'getCurrentContextHandle';
    const GET_CONTEXT_HANDLES = 'getContextHandles';
    // Switching between to window/frame/iframe
    const SWITCH_TO_WINDOW = 'switchToWindow';
    const SWITCH_TO_CONTEXT = 'switchToContext';
    const SWITCH_TO_FRAME = 'switchToFrame';
    const SWITCH_TO_PARENT_FRAME = 'switchToParentFrame';
    const GET_ACTIVE_ELEMENT = 'getActiveElement';
    // Information of the page
    const GET_CURRENT_URL = 'getCurrentUrl';
    const GET_PAGE_SOURCE = 'getPageSource';
    const GET_TITLE = 'getTitle';
    // Javascript API
    const EXECUTE_SCRIPT = 'executeScript';
    const EXECUTE_ASYNC_SCRIPT = 'executeAsyncScript';
    // API getting information from an element.
    const GET_ELEMENT_TEXT = 'getElementText';
    const GET_ELEMENT_TAG_NAME = 'getElementTagName';
    const IS_ELEMENT_SELECTED = 'isElementSelected';
    const IS_ELEMENT_ENABLED = 'isElementEnabled';
    const IS_ELEMENT_DISPLAYED = 'isElementDisplayed';
    const GET_ELEMENT_LOCATION = 'getElementLocation';
    const GET_ELEMENT_LOCATION_ONCE_SCROLLED_INTO_VIEW = 'getElementLocationOnceScrolledIntoView';
    const GET_ELEMENT_SIZE = 'getElementSize';
    const GET_ELEMENT_ATTRIBUTE = 'getElementAttribute';
    const GET_ELEMENT_VALUE_OF_CSS_PROPERTY = 'getElementValueOfCssProperty';
    const ELEMENT_EQUALS = 'elementEquals';
    const SCREENSHOT = 'screenshot';
    // Alert API
    const ACCEPT_ALERT = 'acceptAlert';
    const DISMISS_ALERT = 'dismissAlert';
    const GET_ALERT_TEXT = 'getAlertText';
    const SET_ALERT_VALUE = 'setAlertValue';
    // Timeout API
    const SET_TIMEOUT = 'setTimeout';
    const IMPLICITLY_WAIT = 'implicitlyWait';
    const SET_SCRIPT_TIMEOUT = 'setScriptTimeout';
    /** @deprecated */
    const EXECUTE_SQL = 'executeSQL';
    const GET_LOCATION = 'getLocation';
    const SET_LOCATION = 'setLocation';
    const GET_APP_CACHE = 'getAppCache';
    const GET_APP_CACHE_STATUS = 'getStatus';
    const CLEAR_APP_CACHE = 'clearAppCache';
    const IS_BROWSER_ONLINE = 'isBrowserOnline';
    const SET_BROWSER_ONLINE = 'setBrowserOnline';
    // Local storage
    const GET_LOCAL_STORAGE_ITEM = 'getLocalStorageItem';
    const GET_LOCAL_STORAGE_KEYS = 'getLocalStorageKeys';
    const SET_LOCAL_STORAGE_ITEM = 'setLocalStorageItem';
    const REMOVE_LOCAL_STORAGE_ITEM = 'removeLocalStorageItem';
    const CLEAR_LOCAL_STORAGE = 'clearLocalStorage';
    const GET_LOCAL_STORAGE_SIZE = 'getLocalStorageSize';
    // Session storage
    const GET_SESSION_STORAGE_ITEM = 'getSessionStorageItem';
    const GET_SESSION_STORAGE_KEYS = 'getSessionStorageKey';
    const SET_SESSION_STORAGE_ITEM = 'setSessionStorageItem';
    const REMOVE_SESSION_STORAGE_ITEM = 'removeSessionStorageItem';
    const CLEAR_SESSION_STORAGE = 'clearSessionStorage';
    const GET_SESSION_STORAGE_SIZE = 'getSessionStorageSize';
    // Screen orientation
    const SET_SCREEN_ORIENTATION = 'setScreenOrientation';
    const GET_SCREEN_ORIENTATION = 'getScreenOrientation';
    // These belong to the Advanced user interactions - an element is optional for these commands.
    const CLICK = 'mouseClick';
    const DOUBLE_CLICK = 'mouseDoubleClick';
    const MOUSE_DOWN = 'mouseButtonDown';
    const MOUSE_UP = 'mouseButtonUp';
    const MOVE_TO = 'mouseMoveTo';
    // Those allow interactions with the Input Methods installed on the system.
    const IME_GET_AVAILABLE_ENGINES = 'imeGetAvailableEngines';
    const IME_GET_ACTIVE_ENGINE = 'imeGetActiveEngine';
    const IME_IS_ACTIVATED = 'imeIsActivated';
    const IME_DEACTIVATE = 'imeDeactivate';
    const IME_ACTIVATE_ENGINE = 'imeActivateEngine';
    // These belong to the Advanced Touch API
    const TOUCH_SINGLE_TAP = 'touchSingleTap';
    const TOUCH_DOWN = 'touchDown';
    const TOUCH_UP = 'touchUp';
    const TOUCH_MOVE = 'touchMove';
    const TOUCH_SCROLL = 'touchScroll';
    const TOUCH_DOUBLE_TAP = 'touchDoubleTap';
    const TOUCH_LONG_PRESS = 'touchLongPress';
    const TOUCH_FLICK = 'touchFlick';
    // Window API (beta)
    const SET_WINDOW_SIZE = 'setWindowSize';
    const SET_WINDOW_POSITION = 'setWindowPosition';
    const GET_WINDOW_SIZE = 'getWindowSize';
    const GET_WINDOW_POSITION = 'getWindowPosition';
    const MAXIMIZE_WINDOW = 'maximizeWindow';
    const FULLSCREEN_WINDOW = 'fullscreenWindow';
    // Logging API
    const GET_AVAILABLE_LOG_TYPES = 'getAvailableLogTypes';
    const GET_LOG = 'getLog';
    const GET_SESSION_LOGS = 'getSessionLogs';
    // Mobile API
    const GET_NETWORK_CONNECTION = 'getNetworkConnection';
    const SET_NETWORK_CONNECTION = 'setNetworkConnection';
    // Custom command
    const CUSTOM_COMMAND = 'customCommand';

    // W3C specific
    const ACTIONS = 'actions';
    const GET_ELEMENT_PROPERTY = 'getElementProperty';
    const GET_NAMED_COOKIE = 'getNamedCookie';
    const NEW_WINDOW = 'newWindow';
    const TAKE_ELEMENT_SCREENSHOT = 'takeElementScreenshot';
    const MINIMIZE_WINDOW = 'minimizeWindow';
    const GET_ELEMENT_SHADOW_ROOT = 'getElementShadowRoot';
    const FIND_ELEMENT_FROM_SHADOW_ROOT = 'findElementFromShadowRoot';
    const FIND_ELEMENTS_FROM_SHADOW_ROOT = 'findElementsFromShadowRoot';

    private function __construct()
    {
    }
}
