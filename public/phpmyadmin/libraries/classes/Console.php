<?php
/**
 * Used to render the console of PMA's pages
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;

use function __;
use function _ngettext;
use function count;
use function sprintf;

/**
 * Class used to output the console
 */
class Console
{
    /**
     * Whether to display anything
     *
     * @var bool
     */
    private $isEnabled;

    /**
     * Whether we are servicing an ajax request.
     *
     * @var bool
     */
    private $isAjax;

    /** @var Relation */
    private $relation;

    /** @var Template */
    public $template;

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        global $dbi;

        $this->isEnabled = true;
        $this->relation = new Relation($dbi);
        $this->template = new Template();
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     */
    public function setAjax(bool $isAjax): void
    {
        $this->isAjax = $isAjax;
    }

    /**
     * Disables the rendering of the footer
     */
    public function disable(): void
    {
        $this->isEnabled = false;
    }

    /**
     * Renders the bookmark content
     */
    public static function getBookmarkContent(): string
    {
        global $dbi;

        $template = new Template();
        $relation = new Relation($dbi);
        $bookmarkFeature = $relation->getRelationParameters()->bookmarkFeature;
        if ($bookmarkFeature === null) {
            return '';
        }

        $bookmarks = Bookmark::getList($bookmarkFeature, $dbi, $GLOBALS['cfg']['Server']['user']);
        $count_bookmarks = count($bookmarks);
        if ($count_bookmarks > 0) {
            $welcomeMessage = sprintf(
                _ngettext(
                    'Showing %1$d bookmark (both private and shared)',
                    'Showing %1$d bookmarks (both private and shared)',
                    $count_bookmarks
                ),
                $count_bookmarks
            );
        } else {
            $welcomeMessage = __('No bookmarks');
        }

        return $template->render('console/bookmark_content', [
            'welcome_message' => $welcomeMessage,
            'bookmarks' => $bookmarks,
        ]);
    }

    /**
     * Returns the list of JS scripts required by console
     *
     * @return string[] list of scripts
     */
    public function getScripts(): array
    {
        return ['console.js'];
    }

    /**
     * Renders the console
     */
    public function getDisplay(): string
    {
        if ($this->isAjax || ! $this->isEnabled) {
            return '';
        }

        $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
        $image = Html\Generator::getImage('console', __('SQL Query Console'));
        $_sql_history = $this->relation->getHistory($GLOBALS['cfg']['Server']['user']);
        $bookmarkContent = static::getBookmarkContent();

        return $this->template->render('console/display', [
            'has_bookmark_feature' => $bookmarkFeature !== null,
            'image' => $image,
            'sql_history' => $_sql_history,
            'bookmark_content' => $bookmarkContent,
        ]);
    }
}
