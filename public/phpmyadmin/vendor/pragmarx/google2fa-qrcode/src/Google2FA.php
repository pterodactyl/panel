<?php

namespace PragmaRX\Google2FAQRCode;

use BaconQrCode\Writer;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Renderer\ImageRenderer;
use PragmaRX\Google2FAQRCode\QRCode\Bacon;
use PragmaRX\Google2FAQRCode\QRCode\Chillerlan;
use BaconQrCode\Renderer\Image\RendererInterface;
use BaconQrCode\Writer as BaconQrCodeWriter;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use PragmaRX\Google2FA\Google2FA as Google2FAPackage;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\ImageBackEndInterface;
use PragmaRX\Google2FAQRCode\Exceptions\MissingQrCodeServiceException;

class Google2FA extends Google2FAPackage
{
    /**
     * @var ImageBackEndInterface|RendererInterface|null $imageBackEnd
     */
    protected $qrCodeService;

    /**
     * Google2FA constructor.
     *
     * @param ImageBackEndInterface|RendererInterface|null $imageBackEnd
     */
    public function __construct($qrCodeService = null)
    {
        $this->setQrCodeService(
            empty($qrCodeService)
                ? $this->qrCodeServiceFactory()
                : $qrCodeService
        );
    }

    /**
     * Generates a QR code data url to display inline.
     *
     * @param string $company
     * @param string $holder
     * @param string $secret
     * @param int    $size
     * @param string $encoding Default to UTF-8
     *
     * @return string
     */
    public function getQRCodeInline(
        $company,
        $holder,
        $secret,
        $size = 200,
        $encoding = 'utf-8'
    ) {
        if (empty($this->getQrCodeService())) {
            throw new MissingQrCodeServiceException(
                'You need to install a service package or assign yourself the service to be used.'
            );
        }

        return $this->qrCodeService->getQRCodeInline(
            $this->getQRCodeUrl($company, $holder, $secret),
            $size,
            $encoding
        );
    }

    /**
     * Service setter
     *
     * @return \PragmaRX\Google2FAQRCode\QRCode\QRCodeServiceContract
     */
    public function getQrCodeService()
    {
        return $this->qrCodeService;
    }

    /**
     * Service setter
     *
     * @return self
     */
    public function setQrCodeService($service)
    {
        $this->qrCodeService = $service;

        return $this;
    }

    /**
     * Create the QR Code service instance
     *
     * @return \PragmaRX\Google2FAQRCode\QRCode\QRCodeServiceContract
     */
    public function qrCodeServiceFactory()
    {
        if (
            class_exists('BaconQrCode\Writer') &&
            class_exists('BaconQrCode\Renderer\ImageRenderer')
        ) {
            return new Bacon();
        }

        if (class_exists('chillerlan\QRCode\QRCode')) {
            return new Chillerlan();
        }

        return null;
    }
}
