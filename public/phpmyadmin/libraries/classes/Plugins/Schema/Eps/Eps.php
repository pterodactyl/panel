<?php
/**
 * Classes to create relation schema in EPS format.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Eps;

use PhpMyAdmin\Core;
use PhpMyAdmin\ResponseRenderer;

use function strlen;

/**
 * This Class is EPS Library and
 * helps in developing structure of EPS Schema Export
 *
 * @see     https://www.php.net/manual/en/book.xmlwriter.php
 */
class Eps
{
    /** @var string */
    public $font = 'Arial';

    /** @var int */
    public $fontSize = 12;

    /** @var string */
    public $stringCommands;

    /**
     * Upon instantiation This starts writing the EPS Document.
     * %!PS-Adobe-3.0 EPSF-3.0 This is the MUST first comment to include
     * it shows/tells that the Post Script document is purely under
     * Document Structuring Convention [DSC] and is Compliant
     * Encapsulated Post Script Document
     */
    public function __construct()
    {
        $this->stringCommands = '';
        $this->stringCommands .= "%!PS-Adobe-3.0 EPSF-3.0 \n";
    }

    /**
     * Set document title
     *
     * @param string $value sets the title text
     */
    public function setTitle($value): void
    {
        $this->stringCommands .= '%%Title: ' . $value . "\n";
    }

    /**
     * Set document author
     *
     * @param string $value sets the author
     */
    public function setAuthor($value): void
    {
        $this->stringCommands .= '%%Creator: ' . $value . "\n";
    }

    /**
     * Set document creation date
     *
     * @param string $value sets the date
     */
    public function setDate($value): void
    {
        $this->stringCommands .= '%%CreationDate: ' . $value . "\n";
    }

    /**
     * Set document orientation
     *
     * @param string $orientation sets the orientation
     */
    public function setOrientation($orientation): void
    {
        $this->stringCommands .= "%%PageOrder: Ascend \n";
        if ($orientation === 'L') {
            $orientation = 'Landscape';
            $this->stringCommands .= '%%Orientation: ' . $orientation . "\n";
        } else {
            $orientation = 'Portrait';
            $this->stringCommands .= '%%Orientation: ' . $orientation . "\n";
        }

        $this->stringCommands .= "%%EndComments \n";
        $this->stringCommands .= "%%Pages 1 \n";
        $this->stringCommands .= "%%BoundingBox: 72 150 144 170 \n";
    }

    /**
     * Set the font and size
     *
     * font can be set whenever needed in EPS
     *
     * @param string $value sets the font name e.g Arial
     * @param int    $size  sets the size of the font e.g 10
     */
    public function setFont(string $value, int $size): void
    {
        $this->font = $value;
        $this->fontSize = $size;
        $this->stringCommands .= '/' . $value . " findfont   % Get the basic font\n";
        $this->stringCommands .= ''
            . $size . ' scalefont            % Scale the font to ' . $size . " points\n";
        $this->stringCommands .= "setfont                 % Make it the current font\n";
    }

    /**
     * Get the font
     *
     * @return string return the font name e.g Arial
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Get the font Size
     *
     * @return int return the size of the font e.g 10
     */
    public function getFontSize(): int
    {
        return $this->fontSize;
    }

    /**
     * Draw the line
     *
     * drawing the lines from x,y source to x,y destination and set the
     * width of the line. lines helps in showing relationships of tables
     *
     * @param int $x_from    The x_from attribute defines the start
     *                       left position of the element
     * @param int $y_from    The y_from attribute defines the start
     *                       right position of the element
     * @param int $x_to      The x_to attribute defines the end
     *                       left position of the element
     * @param int $y_to      The y_to attribute defines the end
     *                       right position of the element
     * @param int $lineWidth Sets the width of the line e.g 2
     */
    public function line(
        $x_from = 0,
        $y_from = 0,
        $x_to = 0,
        $y_to = 0,
        $lineWidth = 0
    ): void {
        $this->stringCommands .= $lineWidth . " setlinewidth  \n";
        $this->stringCommands .= $x_from . ' ' . $y_from . " moveto \n";
        $this->stringCommands .= $x_to . ' ' . $y_to . " lineto \n";
        $this->stringCommands .= "stroke \n";
    }

    /**
     * Draw the rectangle
     *
     * drawing the rectangle from x,y source to x,y destination and set the
     * width of the line. rectangles drawn around the text shown of fields
     *
     * @param int $x_from    The x_from attribute defines the start
     *                       left position of the element
     * @param int $y_from    The y_from attribute defines the start
     *                       right position of the element
     * @param int $x_to      The x_to attribute defines the end
     *                       left position of the element
     * @param int $y_to      The y_to attribute defines the end
     *                       right position of the element
     * @param int $lineWidth Sets the width of the line e.g 2
     */
    public function rect($x_from, $y_from, $x_to, $y_to, $lineWidth): void
    {
        $this->stringCommands .= $lineWidth . " setlinewidth  \n";
        $this->stringCommands .= "newpath \n";
        $this->stringCommands .= $x_from . ' ' . $y_from . " moveto \n";
        $this->stringCommands .= '0 ' . $y_to . " rlineto \n";
        $this->stringCommands .= $x_to . " 0 rlineto \n";
        $this->stringCommands .= '0 -' . $y_to . " rlineto \n";
        $this->stringCommands .= "closepath \n";
        $this->stringCommands .= "stroke \n";
    }

    /**
     * Set the current point
     *
     * The moveto operator takes two numbers off the stack and treats
     * them as x and y coordinates to which to move. The coordinates
     * specified become the current point.
     *
     * @param int $x The x attribute defines the left position of the element
     * @param int $y The y attribute defines the right position of the element
     */
    public function moveTo($x, $y): void
    {
        $this->stringCommands .= $x . ' ' . $y . " moveto \n";
    }

    /**
     * Output/Display the text
     *
     * @param string $text The string to be displayed
     */
    public function show($text): void
    {
        $this->stringCommands .= '(' . $text . ") show \n";
    }

    /**
     * Output the text at specified co-ordinates
     *
     * @param string $text String to be displayed
     * @param int    $x    X attribute defines the left position of the element
     * @param int    $y    Y attribute defines the right position of the element
     */
    public function showXY($text, $x, $y): void
    {
        $this->moveTo($x, $y);
        $this->show($text);
    }

    /**
     * Ends EPS Document
     */
    public function endEpsDoc(): void
    {
        $this->stringCommands .= "showpage \n";
    }

    /**
     * Output EPS Document for download
     *
     * @param string $fileName name of the eps document
     */
    public function showOutput($fileName): void
    {
        // if(ob_get_clean()){
        //ob_end_clean();
        //}
        $output = $this->stringCommands;
        ResponseRenderer::getInstance()
            ->disable();
        Core::downloadHeader(
            $fileName,
            'image/x-eps',
            strlen($output)
        );
        print $output;
    }
}
