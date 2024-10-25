<?php
/**
 * Simple interface for creating OASIS OpenDocument files.
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use DateTime;

use const DATE_W3C;

/**
 * Simplified OpenDocument creator class
 */
class OpenDocument
{
    public const NS = <<<EOT
xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0"
xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
EOT;

    /**
     * Minimalistic creator of OASIS OpenDocument
     *
     * @param string $mime desired MIME type
     * @param string $data document content
     *
     * @return string  OASIS OpenDocument data
     */
    public static function create($mime, $data)
    {
        // Use the same date method as other PHP libs
        // https://github.com/PHPOffice/PhpSpreadsheet/blob/1.22.0/src/PhpSpreadsheet/Writer/Ods/Meta.php#L49
        $dateTimeCreation = (new DateTime())->format(DATE_W3C);
        $data = [
            $mime,
            $data,
            '<?xml version="1.0" encoding="UTF-8"?' . '>'
            . '<office:document-meta '
            . 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            . 'xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" '
            . 'office:version="1.0">'
            . '<office:meta>'
            . '<meta:generator>phpMyAdmin ' . Version::VERSION . '</meta:generator>'
            . '<meta:initial-creator>phpMyAdmin ' . Version::VERSION
            . '</meta:initial-creator>'
            . '<meta:creation-date>' . $dateTimeCreation
            . '</meta:creation-date>'
            . '</office:meta>'
            . '</office:document-meta>',
            '<?xml version="1.0" encoding="UTF-8"?' . '>'
            . '<office:document-styles ' . self::NS
            . ' office:version="1.0">'
            . '<office:font-face-decls>'
            . '<style:font-face style:name="Arial Unicode MS"'
            . ' svg:font-family="\'Arial Unicode MS\'" style:font-pitch="variable"/>'
            . '<style:font-face style:name="DejaVu Sans1"'
            . ' svg:font-family="\'DejaVu Sans\'" style:font-pitch="variable"/>'
            . '<style:font-face style:name="HG Mincho Light J"'
            . ' svg:font-family="\'HG Mincho Light J\'" style:font-pitch="variable"/>'
            . '<style:font-face style:name="DejaVu Serif"'
            . ' svg:font-family="\'DejaVu Serif\'" style:font-family-generic="roman"'
            . ' style:font-pitch="variable"/>'
            . '<style:font-face style:name="Thorndale"'
            . ' svg:font-family="Thorndale" style:font-family-generic="roman"'
            . ' style:font-pitch="variable"/>'
            . '<style:font-face style:name="DejaVu Sans"'
            . ' svg:font-family="\'DejaVu Sans\'" style:font-family-generic="swiss"'
            . ' style:font-pitch="variable"/>'
            . '</office:font-face-decls>'
            . '<office:styles>'
            . '<style:default-style style:family="paragraph">'
            . '<style:paragraph-properties fo:hyphenation-ladder-count="no-limit"'
            . ' style:text-autospace="ideograph-alpha" style:punctuation-wrap="hanging"'
            . ' style:line-break="strict" style:tab-stop-distance="0.4925in"'
            . ' style:writing-mode="page"/>'
            . '<style:text-properties style:use-window-font-color="true"'
            . ' style:font-name="DejaVu Serif" fo:font-size="12pt" fo:language="en"'
            . ' fo:country="US" style:font-name-asian="DejaVu Sans1"'
            . ' style:font-size-asian="12pt" style:language-asian="none"'
            . ' style:country-asian="none" style:font-name-complex="DejaVu Sans1"'
            . ' style:font-size-complex="12pt" style:language-complex="none"'
            . ' style:country-complex="none" fo:hyphenate="false"'
            . ' fo:hyphenation-remain-char-count="2"'
            . ' fo:hyphenation-push-char-count="2"/>'
            . '</style:default-style>'
            . '<style:style style:name="Standard" style:family="paragraph"'
            . ' style:class="text"/>'
            . '<style:style style:name="Text_body" style:display-name="Text body"'
            . ' style:family="paragraph" style:parent-style-name="Standard"'
            . ' style:class="text">'
            . '<style:paragraph-properties fo:margin-top="0in"'
            . ' fo:margin-bottom="0.0835in"/>'
            . '</style:style>'
            . '<style:style style:name="Heading" style:family="paragraph"'
            . ' style:parent-style-name="Standard" style:next-style-name="Text_body"'
            . ' style:class="text">'
            . '<style:paragraph-properties fo:margin-top="0.1665in"'
            . ' fo:margin-bottom="0.0835in" fo:keep-with-next="always"/>'
            . '<style:text-properties style:font-name="DejaVu Sans" fo:font-size="14pt"'
            . ' style:font-name-asian="DejaVu Sans1" style:font-size-asian="14pt"'
            . ' style:font-name-complex="DejaVu Sans1" style:font-size-complex="14pt"/>'
            . '</style:style>'
            . '<style:style style:name="Heading_1" style:display-name="Heading 1"'
            . ' style:family="paragraph" style:parent-style-name="Heading"'
            . ' style:next-style-name="Text_body" style:class="text"'
            . ' style:default-outline-level="1">'
            . '<style:text-properties style:font-name="Thorndale" fo:font-size="24pt"'
            . ' fo:font-weight="bold" style:font-name-asian="HG Mincho Light J"'
            . ' style:font-size-asian="24pt" style:font-weight-asian="bold"'
            . ' style:font-name-complex="Arial Unicode MS"'
            . ' style:font-size-complex="24pt" style:font-weight-complex="bold"/>'
            . '</style:style>'
            . '<style:style style:name="Heading_2" style:display-name="Heading 2"'
            . ' style:family="paragraph" style:parent-style-name="Heading"'
            . ' style:next-style-name="Text_body" style:class="text"'
            . ' style:default-outline-level="2">'
            . '<style:text-properties style:font-name="DejaVu Serif"'
            . ' fo:font-size="18pt" fo:font-weight="bold"'
            . ' style:font-name-asian="DejaVu Sans1" style:font-size-asian="18pt"'
            . ' style:font-weight-asian="bold" style:font-name-complex="DejaVu Sans1"'
            . ' style:font-size-complex="18pt" style:font-weight-complex="bold"/>'
            . '</style:style>'
            . '</office:styles>'
            . '<office:automatic-styles>'
            . '<style:page-layout style:name="pm1">'
            . '<style:page-layout-properties fo:page-width="8.2673in"'
            . ' fo:page-height="11.6925in" style:num-format="1"'
            . ' style:print-orientation="portrait" fo:margin-top="1in"'
            . ' fo:margin-bottom="1in" fo:margin-left="1.25in"'
            . ' fo:margin-right="1.25in" style:writing-mode="lr-tb"'
            . ' style:footnote-max-height="0in">'
            . '<style:footnote-sep style:width="0.0071in"'
            . ' style:distance-before-sep="0.0398in"'
            . ' style:distance-after-sep="0.0398in" style:adjustment="left"'
            . ' style:rel-width="25%" style:color="#000000"/>'
            . '</style:page-layout-properties>'
            . '<style:header-style/>'
            . '<style:footer-style/>'
            . '</style:page-layout>'
            . '</office:automatic-styles>'
            . '<office:master-styles>'
            . '<style:master-page style:name="Standard" style:page-layout-name="pm1"/>'
            . '</office:master-styles>'
            . '</office:document-styles>',
            '<?xml version="1.0" encoding="UTF-8"?' . '>'
            . '<manifest:manifest'
            . ' xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">'
            . '<manifest:file-entry manifest:media-type="' . $mime
            . '" manifest:full-path="/"/>'
            . '<manifest:file-entry manifest:media-type="text/xml"'
            . ' manifest:full-path="content.xml"/>'
            . '<manifest:file-entry manifest:media-type="text/xml"'
            . ' manifest:full-path="meta.xml"/>'
            . '<manifest:file-entry manifest:media-type="text/xml"'
            . ' manifest:full-path="styles.xml"/>'
            . '</manifest:manifest>',
        ];

        $name = [
            'mimetype',
            'content.xml',
            'meta.xml',
            'styles.xml',
            'META-INF/manifest.xml',
        ];

        $zipExtension = new ZipExtension();

        return $zipExtension->createFile($data, $name);
    }
}
