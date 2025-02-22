<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Transformation;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Image\ImageWrapper;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

use function __;
use function define;
use function htmlspecialchars;
use function in_array;
use function intval;
use function round;
use function str_replace;
use function stripos;
use function substr;

/**
 * Wrapper script for rendering transformations
 */
class WrapperController extends AbstractController
{
    /** @var Transformations */
    private $transformations;

    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Transformations $transformations,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->transformations = $transformations;
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $cn, $db, $table, $transform_key, $request_params, $size_params, $where_clause, $row;
        global $default_ct, $mime_map, $mime_options, $ct, $mime_type, $srcImage, $srcWidth, $srcHeight;
        global $ratioWidth, $ratioHeight, $destWidth, $destHeight, $destImage;

        define('IS_TRANSFORMATION_WRAPPER', true);

        $relationParameters = $this->relation->getRelationParameters();

        DbTableExists::check();

        /**
         * Sets globals from $_REQUEST
         */
        $request_params = [
            'cn',
            'ct',
            'sql_query',
            'transform_key',
            'where_clause',
        ];
        $size_params = [
            'newHeight',
            'newWidth',
        ];
        foreach ($request_params as $one_request_param) {
            if (! isset($_REQUEST[$one_request_param])) {
                continue;
            }

            if (in_array($one_request_param, $size_params)) {
                $GLOBALS[$one_request_param] = intval($_REQUEST[$one_request_param]);
                if ($GLOBALS[$one_request_param] > 2000) {
                    $GLOBALS[$one_request_param] = 2000;
                }
            } else {
                $GLOBALS[$one_request_param] = $_REQUEST[$one_request_param];
            }
        }

        /**
         * Get the list of the fields of the current table
         */
        $this->dbi->selectDb($db);
        if (isset($where_clause)) {
            if (! Core::checkSqlQuerySignature($where_clause, $_GET['where_clause_sign'] ?? '')) {
                /* l10n: In case a SQL query did not pass a security check  */
                Core::fatalError(__('There is an issue with your request.'));

                return;
            }

            $result = $this->dbi->query(
                'SELECT * FROM ' . Util::backquote($table)
                . ' WHERE ' . $where_clause . ';'
            );
            $row = $result->fetchAssoc();
        } else {
            $result = $this->dbi->query(
                'SELECT * FROM ' . Util::backquote($table) . ' LIMIT 1;'
            );
            $row = $result->fetchAssoc();
        }

        // No row returned
        if ($row === []) {
            return;
        }

        $default_ct = 'application/octet-stream';

        if (
            $relationParameters->columnCommentsFeature !== null
            && $relationParameters->browserTransformationFeature !== null
        ) {
            $mime_map = $this->transformations->getMime($db, $table) ?? [];

            $mime_options = $this->transformations->getOptions(
                $mime_map[$transform_key]['transformation_options'] ?? ''
            );

            foreach ($mime_options as $option) {
                if (substr($option, 0, 10) !== '; charset=') {
                    continue;
                }

                $mime_options['charset'] = $option;
            }
        }

        // Disabling standard response, we are sending binary here
        $this->response->disable();
        $this->response->getHeader()->sendHttpHeaders();

        // [MIME]
        if (isset($ct) && ! empty($ct)) {
            $mime_type = $ct;
        } else {
            $mime_type = (! empty($mime_map[$transform_key]['mimetype'])
                    ? str_replace('_', '/', $mime_map[$transform_key]['mimetype'])
                    : $default_ct)
                . ($mime_options['charset'] ?? '');
        }

        Core::downloadHeader($cn ?? '', $mime_type ?? '');

        if (! isset($_REQUEST['resize'])) {
            if (stripos($mime_type ?? '', 'html') === false) {
                echo $row[$transform_key];
            } else {
                echo htmlspecialchars($row[$transform_key]);
            }
        } else {
            // if image_*__inline.inc.php finds that we can resize,
            // it sets the resize parameter to jpeg or png

            $srcImage = ImageWrapper::fromString($row[$transform_key]);
            if ($srcImage === null) {
                return;
            }

            $srcWidth = $srcImage->width();
            $srcHeight = $srcImage->height();

            // Check to see if the width > height or if width < height
            // if so adjust accordingly to make sure the image
            // stays smaller than the new width and new height

            $ratioWidth = $srcWidth / $_REQUEST['newWidth'];
            $ratioHeight = $srcHeight / $_REQUEST['newHeight'];

            if ($ratioWidth < $ratioHeight) {
                $destWidth = intval(round($srcWidth / $ratioHeight));
                $destHeight = intval($_REQUEST['newHeight']);
            } else {
                $destWidth = intval($_REQUEST['newWidth']);
                $destHeight = intval(round($srcHeight / $ratioWidth));
            }

            if ($_REQUEST['resize']) {
                $destImage = ImageWrapper::create($destWidth, $destHeight);
                if ($destImage === null) {
                    $srcImage->destroy();

                    return;
                }

                // ImageCopyResized($destImage, $srcImage, 0, 0, 0, 0,
                // $destWidth, $destHeight, $srcWidth, $srcHeight);
                // better quality but slower:
                $destImage->copyResampled($srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
                if ($_REQUEST['resize'] === 'jpeg') {
                    $destImage->jpeg(null, 75);
                }

                if ($_REQUEST['resize'] === 'png') {
                    $destImage->png();
                }

                $destImage->destroy();
            }

            $srcImage->destroy();
        }
    }
}
