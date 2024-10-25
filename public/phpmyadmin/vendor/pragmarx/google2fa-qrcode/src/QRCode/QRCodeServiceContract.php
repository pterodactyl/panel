<?php

namespace PragmaRX\Google2FAQRCode\QRCode;

interface QRCodeServiceContract
{
    /**
     * Generates a QR code data url to display inline.
     *
     * @param string $string
     * @param int    $size
     * @param string $encoding Default to UTF-8
     *
     * @return string
     */
    public function getQRCodeInline($string, $size = 200, $encoding = 'utf-8');
}
