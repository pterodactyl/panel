<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1;

class OID
{
    const RSA_ENCRYPTION                    = '1.2.840.113549.1.1.1';
    const MD5_WITH_RSA_ENCRYPTION           = '1.2.840.113549.1.1.4';
    const SHA1_WITH_RSA_SIGNATURE           = '1.2.840.113549.1.1.5';
    const SHA256_WITH_RSA_SIGNATURE         = '1.2.840.113549.1.1.11';
    const PKCS9_EMAIL                       = '1.2.840.113549.1.9.1';
    const PKCS9_UNSTRUCTURED_NAME           = '1.2.840.113549.1.9.2';
    const PKCS9_CONTENT_TYPE                = '1.2.840.113549.1.9.3';
    const PKCS9_MESSAGE_DIGEST              = '1.2.840.113549.1.9.4';
    const PKCS9_SIGNING_TIME                = '1.2.840.113549.1.9.5';
    const PKCS9_EXTENSION_REQUEST           = '1.2.840.113549.1.9.14';

    // certificate extension identifier
    const CERT_EXT_SUBJECT_DIRECTORY_ATTR   = '2.5.29.9';
    const CERT_EXT_SUBJECT_KEY_IDENTIFIER   = '2.5.29.14';
    const CERT_EXT_KEY_USAGE                = '2.5.29.15';
    const CERT_EXT_PRIVATE_KEY_USAGE_PERIOD = '2.5.29.16';
    const CERT_EXT_SUBJECT_ALT_NAME         = '2.5.29.17';
    const CERT_EXT_ISSUER_ALT_NAME          = '2.5.29.18';
    const CERT_EXT_BASIC_CONSTRAINTS        = '2.5.29.19';
    const CERT_EXT_CRL_NUMBER               = '2.5.29.20';
    const CERT_EXT_REASON_CODE              = '2.5.29.21';
    const CERT_EXT_INVALIDITY_DATE          = '2.5.29.24';
    const CERT_EXT_DELTA_CRL_INDICATOR      = '2.5.29.27';
    const CERT_EXT_ISSUING_DIST_POINT       = '2.5.29.28';
    const CERT_EXT_CERT_ISSUER              = '2.5.29.29';
    const CERT_EXT_NAME_CONSTRAINTS         = '2.5.29.30';
    const CERT_EXT_CRL_DISTRIBUTION_POINTS  = '2.5.29.31';
    const CERT_EXT_CERT_POLICIES            = '2.5.29.32';
    const CERT_EXT_AUTHORITY_KEY_IDENTIFIER = '2.5.29.35';
    const CERT_EXT_EXTENDED_KEY_USAGE       = '2.5.29.37';

    // standard certificate files
    const COMMON_NAME                       = '2.5.4.3';
    const SURNAME                           = '2.5.4.4';
    const SERIAL_NUMBER                     = '2.5.4.5';
    const COUNTRY_NAME                      = '2.5.4.6';
    const LOCALITY_NAME                     = '2.5.4.7';
    const STATE_OR_PROVINCE_NAME            = '2.5.4.8';
    const STREET_ADDRESS                    = '2.5.4.9';
    const ORGANIZATION_NAME                 = '2.5.4.10';
    const OU_NAME                           = '2.5.4.11';
    const TITLE                             = '2.5.4.12';
    const DESCRIPTION                       = '2.5.4.13';
    const POSTAL_ADDRESS                    = '2.5.4.16';
    const POSTAL_CODE                       = '2.5.4.17';
    const AUTHORITY_REVOCATION_LIST         = '2.5.4.38';

    const AUTHORITY_INFORMATION_ACCESS      = '1.3.6.1.5.5.7.1.1';

    /**
     * Returns the name of the given object identifier.
     *
     * Some OIDs are saved as class constants in this class.
     * If the wanted oidString is not among them, this method will
     * query http://oid-info.com for the right name.
     * This behavior can be suppressed by setting the second method parameter to false.
     *
     * @param string $oidString
     * @param bool $loadFromWeb
     *
     * @see self::loadFromWeb($oidString)
     *
     * @return string
     */
    public static function getName($oidString, $loadFromWeb = true)
    {
        $oids = [
            '1.2' => 'ISO Member Body',
            '1.3' => 'org',
            '1.3.6.1.5.5.8.1.1' => 'hmac-md5',
            '1.3.6.1.5.5.8.1.2' => 'hmac-sha1',
            '1.3.132' => 'certicom-arc',
            '2.23' => 'International Organizations',
            '2.23.43' => 'wap',
            '2.23.43.1' => 'wap-wsg',
            '2.5.1.5' => 'Selected Attribute Types',
            '2.5.1.5.55' => 'clearance',
            '1.2.840' => 'ISO US Member Body',
            '1.2.840.10040' => 'X9.57',
            '1.2.840.10040.4' => 'X9.57 CM ?',
            '1.2.840.10040.4.1' => 'dsaEncryption',
            '1.2.840.10040.4.3' => 'dsaWithSHA1',
            '1.2.840.10045' => 'ANSI X9.62',
            '1.2.840.10045.1' => 'X9-62_id-fieldType',
            '1.2.840.10045.1.1' => 'X9-62_prime-field',
            '1.2.840.10045.1.2' => 'X9-62_characteristic-two-field',
            '1.2.840.10045.1.2.3' => 'X9-62_id-characteristic-two-basis',
            '1.2.840.10045.1.2.3.1' => 'X9-62_onBasis',
            '1.2.840.10045.1.2.3.2' => 'X9-62_tpBasis',
            '1.2.840.10045.1.2.3.3' => 'X9-62_ppBasis',
            '1.2.840.10045.2' => 'X9-62_id-publicKeyType',
            '1.2.840.10045.2.1' => 'X9-62_id-ecPublicKey',
            '1.2.840.10045.3' => 'X9-62_ellipticCurve',
            '1.2.840.10045.3.0' => 'X9-62_c-TwoCurve',
            '1.2.840.10045.3.0.1' => 'X9-62_c2pnb163v1',
            '1.2.840.10045.3.0.2' => 'X9-62_c2pnb163v2',
            '1.2.840.10045.3.0.3' => 'X9-62_c2pnb163v3',
            '1.2.840.10045.3.0.4' => 'X9-62_c2pnb176v1',
            '1.2.840.10045.3.0.5' => 'X9-62_c2tnb191v1',
            '1.2.840.10045.3.0.6' => 'X9-62_c2tnb191v2',
            '1.2.840.10045.3.0.7' => 'X9-62_c2tnb191v3',
            '1.2.840.10045.3.0.8' => 'X9-62_c2onb191v4',
            '1.2.840.10045.3.0.9' => 'X9-62_c2onb191v5',
            '1.2.840.10045.3.0.10' => 'X9-62_c2pnb208w1',
            '1.2.840.10045.3.0.11' => 'X9-62_c2tnb239v1',
            '1.2.840.10045.3.0.12' => 'X9-62_c2tnb239v2',
            '1.2.840.10045.3.0.13' => 'X9-62_c2tnb239v3',
            '1.2.840.10045.3.0.14' => 'X9-62_c2onb239v4',
            '1.2.840.10045.3.0.15' => 'X9-62_c2onb239v5',
            '1.2.840.10045.3.0.16' => 'X9-62_c2pnb272w1',
            '1.2.840.10045.3.0.17' => 'X9-62_c2pnb304w1',
            '1.2.840.10045.3.0.18' => 'X9-62_c2tnb359v1',
            '1.2.840.10045.3.0.19' => 'X9-62_c2pnb368w1',
            '1.2.840.10045.3.0.20' => 'X9-62_c2tnb431r1',
            '1.2.840.10045.3.1' => 'X9-62_primeCurve',
            '1.2.840.10045.3.1.1' => 'X9-62_prime192v1',
            '1.2.840.10045.3.1.2' => 'X9-62_prime192v2',
            '1.2.840.10045.3.1.3' => 'X9-62_prime192v3',
            '1.2.840.10045.3.1.4' => 'X9-62_prime239v1',
            '1.2.840.10045.3.1.5' => 'X9-62_prime239v2',
            '1.2.840.10045.3.1.6' => 'X9-62_prime239v3',
            '1.2.840.10045.3.1.7' => 'X9-62_prime256v1',
            '1.2.840.10045.4' => 'X9-62_id-ecSigType',
            '1.2.840.10045.4.1' => 'ecdsa-with-SHA1',
            '1.2.840.10045.4.2' => 'ecdsa-with-Recommended',
            '1.2.840.10045.4.3' => 'ecdsa-with-Specified',
            '1.2.840.10045.4.3.1' => 'ecdsa-with-SHA224',
            '1.2.840.10045.4.3.2' => 'ecdsa-with-SHA256',
            '1.2.840.10045.4.3.3' => 'ecdsa-with-SHA384',
            '1.2.840.10045.4.3.4' => 'ecdsa-with-SHA512',
            '1.3.132.0' => 'secg_ellipticCurve',
            '2.23.43.1.4' => 'wap-wsg-idm-ecid',
            '2.23.43.1.4.1' => 'wap-wsg-idm-ecid-wtls1',
            '2.23.43.1.4.3' => 'wap-wsg-idm-ecid-wtls3',
            '2.23.43.1.4.4' => 'wap-wsg-idm-ecid-wtls4',
            '2.23.43.1.4.5' => 'wap-wsg-idm-ecid-wtls5',
            '2.23.43.1.4.6' => 'wap-wsg-idm-ecid-wtls6',
            '2.23.43.1.4.7' => 'wap-wsg-idm-ecid-wtls7',
            '2.23.43.1.4.8' => 'wap-wsg-idm-ecid-wtls8',
            '2.23.43.1.4.9' => 'wap-wsg-idm-ecid-wtls9',
            '2.23.43.1.4.10' => 'wap-wsg-idm-ecid-wtls10',
            '2.23.43.1.4.11' => 'wap-wsg-idm-ecid-wtls11',
            '2.23.43.1.4.12' => 'wap-wsg-idm-ecid-wtls12',
            '1.2.840.113533.7.66.10' => 'cast5-cbc',
            '1.2.840.113533.7.66.12' => 'pbeWithMD5AndCast5CBC',
            '1.2.840.113533.7.66.13' => 'password based MAC',
            '1.2.840.113533.7.66.30' => 'Diffie-Hellman based MAC',
            '1.2.840.113549' => 'RSA Data Security, Inc.',
            '1.2.840.113549.1' => 'RSA Data Security, Inc. PKCS',
            '1.2.840.113549.1.1' => 'pkcs1',
            '1.2.840.113549.1.1.1' => 'rsaEncryption',
            '1.2.840.113549.1.1.2' => 'md2WithRSAEncryption',
            '1.2.840.113549.1.1.3' => 'md4WithRSAEncryption',
            '1.2.840.113549.1.1.4' => 'md5WithRSAEncryption',
            '1.2.840.113549.1.1.5' => 'sha1WithRSAEncryption',
            '1.2.840.113549.1.1.7' => 'rsaesOaep',
            '1.2.840.113549.1.1.8' => 'mgf1',
            '1.2.840.113549.1.1.9' => 'pSpecified',
            '1.2.840.113549.1.1.10' => 'rsassaPss',
            '1.2.840.113549.1.1.11' => 'sha256WithRSAEncryption',
            '1.2.840.113549.1.1.12' => 'sha384WithRSAEncryption',
            '1.2.840.113549.1.1.13' => 'sha512WithRSAEncryption',
            '1.2.840.113549.1.1.14' => 'sha224WithRSAEncryption',
            '1.2.840.113549.1.3' => 'pkcs3',
            '1.2.840.113549.1.3.1' => 'dhKeyAgreement',
            '1.2.840.113549.1.5' => 'pkcs5',
            '1.2.840.113549.1.5.1' => 'pbeWithMD2AndDES-CBC',
            '1.2.840.113549.1.5.3' => 'pbeWithMD5AndDES-CBC',
            '1.2.840.113549.1.5.4' => 'pbeWithMD2AndRC2-CBC',
            '1.2.840.113549.1.5.6' => 'pbeWithMD5AndRC2-CBC',
            '1.2.840.113549.1.5.10' => 'pbeWithSHA1AndDES-CBC',
            '1.2.840.113549.1.5.11' => 'pbeWithSHA1AndRC2-CBC',
            '1.2.840.113549.1.5.12' => 'PBKDF2',
            '1.2.840.113549.1.5.13' => 'PBES2',
            '1.2.840.113549.1.5.14' => 'PBMAC1',
            '1.2.840.113549.1.7' => 'pkcs7',
            '1.2.840.113549.1.7.1' => 'pkcs7-data',
            '1.2.840.113549.1.7.2' => 'pkcs7-signedData',
            '1.2.840.113549.1.7.3' => 'pkcs7-envelopedData',
            '1.2.840.113549.1.7.4' => 'pkcs7-signedAndEnvelopedData',
            '1.2.840.113549.1.7.5' => 'pkcs7-digestData',
            '1.2.840.113549.1.7.6' => 'pkcs7-encryptedData',
            '1.2.840.113549.1.9' => 'pkcs9',
            '1.2.840.113549.1.9.1' => 'emailAddress',
            '1.2.840.113549.1.9.2' => 'unstructuredName',
            '1.2.840.113549.1.9.3' => 'contentType',
            '1.2.840.113549.1.9.4' => 'messageDigest',
            '1.2.840.113549.1.9.5' => 'signingTime',
            '1.2.840.113549.1.9.6' => 'countersignature',
            '1.2.840.113549.1.9.7' => 'challengePassword',
            '1.2.840.113549.1.9.8' => 'unstructuredAddress',
            '1.2.840.113549.1.9.9' => 'extendedCertificateAttributes',
            '1.2.840.113549.1.9.14' => 'Extension Request',
            '1.2.840.113549.1.9.15' => 'S/MIME Capabilities',
            '1.2.840.113549.1.9.16' => 'S/MIME',
            '1.2.840.113549.1.9.16.0' => 'id-smime-mod',
            '1.2.840.113549.1.9.16.1' => 'id-smime-ct',
            '1.2.840.113549.1.9.16.2' => 'id-smime-aa',
            '1.2.840.113549.1.9.16.3' => 'id-smime-alg',
            '1.2.840.113549.1.9.16.4' => 'id-smime-cd',
            '1.2.840.113549.1.9.16.5' => 'id-smime-spq',
            '1.2.840.113549.1.9.16.6' => 'id-smime-cti',
            '1.2.840.113549.1.9.16.0.1' => 'id-smime-mod-cms',
            '1.2.840.113549.1.9.16.0.2' => 'id-smime-mod-ess',
            '1.2.840.113549.1.9.16.0.3' => 'id-smime-mod-oid',
            '1.2.840.113549.1.9.16.0.4' => 'id-smime-mod-msg-v3',
            '1.2.840.113549.1.9.16.0.5' => 'id-smime-mod-ets-eSignature-88',
            '1.2.840.113549.1.9.16.0.6' => 'id-smime-mod-ets-eSignature-97',
            '1.2.840.113549.1.9.16.0.7' => 'id-smime-mod-ets-eSigPolicy-88',
            '1.2.840.113549.1.9.16.0.8' => 'id-smime-mod-ets-eSigPolicy-97',
            '1.2.840.113549.1.9.16.1.1' => 'id-smime-ct-receipt',
            '1.2.840.113549.1.9.16.1.2' => 'id-smime-ct-authData',
            '1.2.840.113549.1.9.16.1.3' => 'id-smime-ct-publishCert',
            '1.2.840.113549.1.9.16.1.4' => 'id-smime-ct-TSTInfo',
            '1.2.840.113549.1.9.16.1.5' => 'id-smime-ct-TDTInfo',
            '1.2.840.113549.1.9.16.1.6' => 'id-smime-ct-contentInfo',
            '1.2.840.113549.1.9.16.1.7' => 'id-smime-ct-DVCSRequestData',
            '1.2.840.113549.1.9.16.1.8' => 'id-smime-ct-DVCSResponseData',
            '1.2.840.113549.1.9.16.1.9' => 'id-smime-ct-compressedData',
            '1.2.840.113549.1.9.16.1.27' => 'id-ct-asciiTextWithCRLF',
            '1.2.840.113549.1.9.16.2.1' => 'id-smime-aa-receiptRequest',
            '1.2.840.113549.1.9.16.2.2' => 'id-smime-aa-securityLabel',
            '1.2.840.113549.1.9.16.2.3' => 'id-smime-aa-mlExpandHistory',
            '1.2.840.113549.1.9.16.2.4' => 'id-smime-aa-contentHint',
            '1.2.840.113549.1.9.16.2.5' => 'id-smime-aa-msgSigDigest',
            '1.2.840.113549.1.9.16.2.6' => 'id-smime-aa-encapContentType',
            '1.2.840.113549.1.9.16.2.7' => 'id-smime-aa-contentIdentifier',
            '1.2.840.113549.1.9.16.2.8' => 'id-smime-aa-macValue',
            '1.2.840.113549.1.9.16.2.9' => 'id-smime-aa-equivalentLabels',
            '1.2.840.113549.1.9.16.2.10' => 'id-smime-aa-contentReference',
            '1.2.840.113549.1.9.16.2.11' => 'id-smime-aa-encrypKeyPref',
            '1.2.840.113549.1.9.16.2.12' => 'id-smime-aa-signingCertificate',
            '1.2.840.113549.1.9.16.2.13' => 'id-smime-aa-smimeEncryptCerts',
            '1.2.840.113549.1.9.16.2.14' => 'id-smime-aa-timeStampToken',
            '1.2.840.113549.1.9.16.2.15' => 'id-smime-aa-ets-sigPolicyId',
            '1.2.840.113549.1.9.16.2.16' => 'id-smime-aa-ets-commitmentType',
            '1.2.840.113549.1.9.16.2.17' => 'id-smime-aa-ets-signerLocation',
            '1.2.840.113549.1.9.16.2.18' => 'id-smime-aa-ets-signerAttr',
            '1.2.840.113549.1.9.16.2.19' => 'id-smime-aa-ets-otherSigCert',
            '1.2.840.113549.1.9.16.2.20' => 'id-smime-aa-ets-contentTimestamp',
            '1.2.840.113549.1.9.16.2.21' => 'id-smime-aa-ets-CertificateRefs',
            '1.2.840.113549.1.9.16.2.22' => 'id-smime-aa-ets-RevocationRefs',
            '1.2.840.113549.1.9.16.2.23' => 'id-smime-aa-ets-certValues',
            '1.2.840.113549.1.9.16.2.24' => 'id-smime-aa-ets-revocationValues',
            '1.2.840.113549.1.9.16.2.25' => 'id-smime-aa-ets-escTimeStamp',
            '1.2.840.113549.1.9.16.2.26' => 'id-smime-aa-ets-certCRLTimestamp',
            '1.2.840.113549.1.9.16.2.27' => 'id-smime-aa-ets-archiveTimeStamp',
            '1.2.840.113549.1.9.16.2.28' => 'id-smime-aa-signatureType',
            '1.2.840.113549.1.9.16.2.29' => 'id-smime-aa-dvcs-dvc',
            '1.2.840.113549.1.9.16.3.1' => 'id-smime-alg-ESDHwith3DES',
            '1.2.840.113549.1.9.16.3.2' => 'id-smime-alg-ESDHwithRC2',
            '1.2.840.113549.1.9.16.3.3' => 'id-smime-alg-3DESwrap',
            '1.2.840.113549.1.9.16.3.4' => 'id-smime-alg-RC2wrap',
            '1.2.840.113549.1.9.16.3.5' => 'id-smime-alg-ESDH',
            '1.2.840.113549.1.9.16.3.6' => 'id-smime-alg-CMS3DESwrap',
            '1.2.840.113549.1.9.16.3.7' => 'id-smime-alg-CMSRC2wrap',
            '1.2.840.113549.1.9.16.3.9' => 'id-alg-PWRI-KEK',
            '1.2.840.113549.1.9.16.4.1' => 'id-smime-cd-ldap',
            '1.2.840.113549.1.9.16.5.1' => 'id-smime-spq-ets-sqt-uri',
            '1.2.840.113549.1.9.16.5.2' => 'id-smime-spq-ets-sqt-unotice',
            '1.2.840.113549.1.9.16.6.1' => 'id-smime-cti-ets-proofOfOrigin',
            '1.2.840.113549.1.9.16.6.2' => 'id-smime-cti-ets-proofOfReceipt',
            '1.2.840.113549.1.9.16.6.3' => 'id-smime-cti-ets-proofOfDelivery',
            '1.2.840.113549.1.9.16.6.4' => 'id-smime-cti-ets-proofOfSender',
            '1.2.840.113549.1.9.16.6.5' => 'id-smime-cti-ets-proofOfApproval',
            '1.2.840.113549.1.9.16.6.6' => 'id-smime-cti-ets-proofOfCreation',
            '1.2.840.113549.1.9.20' => 'friendlyName',
            '1.2.840.113549.1.9.21' => 'localKeyID',
            '1.3.6.1.4.1.311.17.1' => 'Microsoft CSP Name',
            '1.3.6.1.4.1.311.17.2' => 'Microsoft Local Key set',
            '1.2.840.113549.1.9.22' => 'certTypes',
            '1.2.840.113549.1.9.22.1' => 'x509Certificate',
            '1.2.840.113549.1.9.22.2' => 'sdsiCertificate',

            '1.2.840.113549.1.9.23' => 'crlTypes',
            '1.2.840.113549.1.9.23.1' => 'x509Crl',
            '1.2.840.113549.1.12' => 'pkcs12',
            '1.2.840.113549.1.12.1' => 'pkcs12-pbeids',
            '1.2.840.113549.1.12.1.1' => 'pbeWithSHA1And128BitRC4',
            '1.2.840.113549.1.12.1.2' => 'pbeWithSHA1And40BitRC4',
            '1.2.840.113549.1.12.1.3' => 'pbeWithSHA1And3-KeyTripleDES-CBC',
            '1.2.840.113549.1.12.1.4' => 'pbeWithSHA1And2-KeyTripleDES-CBC',
            '1.2.840.113549.1.12.1.5' => 'pbeWithSHA1And128BitRC2-CBC',
            '1.2.840.113549.1.12.1.6' => 'pbeWithSHA1And40BitRC2-CBC',
            '1.2.840.113549.1.12.10' => 'pkcs12-Version1',
            '1.2.840.113549.1.12.10.1' => 'pkcs12-BagIds',
            '1.2.840.113549.1.12.10.1.1' => 'keyBag',
            '1.2.840.113549.1.12.10.1.2' => 'pkcs8ShroudedKeyBag',
            '1.2.840.113549.1.12.10.1.3' => 'certBag',
            '1.2.840.113549.1.12.10.1.4' => 'crlBag',
            '1.2.840.113549.1.12.10.1.5' => 'secretBag',
            '1.2.840.113549.1.12.10.1.6' => 'safeContentsBag',
            '1.2.840.113549.2.2' => 'md2',
            '1.2.840.113549.2.4' => 'md4',
            '1.2.840.113549.2.5' => 'md5',
            '1.2.840.113549.2.6' => 'hmacWithMD5',
            '1.2.840.113549.2.7' => 'hmacWithSHA1',
            '1.2.840.113549.2.8' => 'hmacWithSHA224',
            '1.2.840.113549.2.9' => 'hmacWithSHA256',
            '1.2.840.113549.2.10' => 'hmacWithSHA384',
            '1.2.840.113549.2.11' => 'hmacWithSHA512',
            '1.2.840.113549.3.2' => 'rc2-cbc',
            '1.2.840.113549.3.4' => 'rc4',
            '1.2.840.113549.3.7' => 'des-ede3-cbc',
            '1.2.840.113549.3.8' => 'rc5-cbc',
            '1.3.6.1.4.1.311.2.1.14' => 'Microsoft Extension Request',
            '1.3.6.1.4.1.311.2.1.21' => 'Microsoft Individual Code Signing',
            '1.3.6.1.4.1.311.2.1.22' => 'Microsoft Commercial Code Signing',
            '1.3.6.1.4.1.311.10.3.1' => 'Microsoft Trust List Signing',
            '1.3.6.1.4.1.311.10.3.3' => 'Microsoft Server Gated Crypto',
            '1.3.6.1.4.1.311.10.3.4' => 'Microsoft Encrypted File System',
            '1.3.6.1.4.1.311.20.2.2' => 'Microsoft Smartcardlogin',
            '1.3.6.1.4.1.311.20.2.3' => 'Microsoft Universal Principal Name',
            '1.3.6.1.4.1.188.7.1.1.2' => 'idea-cbc',
            '1.3.6.1.4.1.3029.1.2' => 'bf-cbc',
            '1.3.6.1.5.5.7' => 'PKIX',
            '1.3.6.1.5.5.7.0' => 'id-pkix-mod',
            '1.3.6.1.5.5.7.1' => 'id-pe',
            '1.3.6.1.5.5.7.2' => 'id-qt',
            '1.3.6.1.5.5.7.3' => 'id-kp',
            '1.3.6.1.5.5.7.4' => 'id-it',
            '1.3.6.1.5.5.7.5' => 'id-pkip',
            '1.3.6.1.5.5.7.6' => 'id-alg',
            '1.3.6.1.5.5.7.7' => 'id-cmc',
            '1.3.6.1.5.5.7.8' => 'id-on',
            '1.3.6.1.5.5.7.9' => 'id-pda',
            '1.3.6.1.5.5.7.10' => 'id-aca',
            '1.3.6.1.5.5.7.11' => 'id-qcs',
            '1.3.6.1.5.5.7.12' => 'id-cct',
            '1.3.6.1.5.5.7.21' => 'id-ppl',
            '1.3.6.1.5.5.7.48' => 'id-ad',
            '1.3.6.1.5.5.7.0.1' => 'id-pkix1-explicit-88',
            '1.3.6.1.5.5.7.0.2' => 'id-pkix1-implicit-88',
            '1.3.6.1.5.5.7.0.3' => 'id-pkix1-explicit-93',
            '1.3.6.1.5.5.7.0.4' => 'id-pkix1-implicit-93',
            '1.3.6.1.5.5.7.0.5' => 'id-mod-crmf',
            '1.3.6.1.5.5.7.0.6' => 'id-mod-cmc',
            '1.3.6.1.5.5.7.0.7' => 'id-mod-kea-profile-88',
            '1.3.6.1.5.5.7.0.8' => 'id-mod-kea-profile-93',
            '1.3.6.1.5.5.7.0.9' => 'id-mod-cmp',
            '1.3.6.1.5.5.7.0.10' => 'id-mod-qualified-cert-88',
            '1.3.6.1.5.5.7.0.11' => 'id-mod-qualified-cert-93',
            '1.3.6.1.5.5.7.0.12' => 'id-mod-attribute-cert',
            '1.3.6.1.5.5.7.0.13' => 'id-mod-timestamp-protocol',
            '1.3.6.1.5.5.7.0.14' => 'id-mod-ocsp',
            '1.3.6.1.5.5.7.0.15' => 'id-mod-dvcs',
            '1.3.6.1.5.5.7.0.16' => 'id-mod-cmp2000',
            '1.3.6.1.5.5.7.1.1' => 'Authority Information Access',
            '1.3.6.1.5.5.7.1.2' => 'Biometric Info',
            '1.3.6.1.5.5.7.1.3' => 'qcStatements',
            '1.3.6.1.5.5.7.1.4' => 'ac-auditEntity',
            '1.3.6.1.5.5.7.1.5' => 'ac-targeting',
            '1.3.6.1.5.5.7.1.6' => 'aaControls',
            '1.3.6.1.5.5.7.1.7' => 'sbgp-ipAddrBlock',
            '1.3.6.1.5.5.7.1.8' => 'sbgp-autonomousSysNum',
            '1.3.6.1.5.5.7.1.9' => 'sbgp-routerIdentifier',
            '1.3.6.1.5.5.7.1.10' => 'ac-proxying',
            '1.3.6.1.5.5.7.1.11' => 'Subject Information Access',
            '1.3.6.1.5.5.7.1.14' => 'Proxy Certificate Information',
            '1.3.6.1.5.5.7.2.1' => 'Policy Qualifier CPS',
            '1.3.6.1.5.5.7.2.2' => 'Policy Qualifier User Notice',
            '1.3.6.1.5.5.7.2.3' => 'textNotice',
            '1.3.6.1.5.5.7.3.1' => 'TLS Web Server Authentication',
            '1.3.6.1.5.5.7.3.2' => 'TLS Web Client Authentication',
            '1.3.6.1.5.5.7.3.3' => 'Code Signing',
            '1.3.6.1.5.5.7.3.4' => 'E-mail Protection',
            '1.3.6.1.5.5.7.3.5' => 'IPSec End System',
            '1.3.6.1.5.5.7.3.6' => 'IPSec Tunnel',
            '1.3.6.1.5.5.7.3.7' => 'IPSec User',
            '1.3.6.1.5.5.7.3.8' => 'Time Stamping',
            '1.3.6.1.5.5.7.3.9' => 'OCSP Signing',
            '1.3.6.1.5.5.7.3.10' => 'dvcs',
            '1.3.6.1.5.5.7.4.1' => 'id-it-caProtEncCert',
            '1.3.6.1.5.5.7.4.2' => 'id-it-signKeyPairTypes',
            '1.3.6.1.5.5.7.4.3' => 'id-it-encKeyPairTypes',
            '1.3.6.1.5.5.7.4.4' => 'id-it-preferredSymmAlg',
            '1.3.6.1.5.5.7.4.5' => 'id-it-caKeyUpdateInfo',
            '1.3.6.1.5.5.7.4.6' => 'id-it-currentCRL',
            '1.3.6.1.5.5.7.4.7' => 'id-it-unsupportedOIDs',
            '1.3.6.1.5.5.7.4.8' => 'id-it-subscriptionRequest',
            '1.3.6.1.5.5.7.4.9' => 'id-it-subscriptionResponse',
            '1.3.6.1.5.5.7.4.10' => 'id-it-keyPairParamReq',
            '1.3.6.1.5.5.7.4.11' => 'id-it-keyPairParamRep',
            '1.3.6.1.5.5.7.4.12' => 'id-it-revPassphrase',
            '1.3.6.1.5.5.7.4.13' => 'id-it-implicitConfirm',
            '1.3.6.1.5.5.7.4.14' => 'id-it-confirmWaitTime',
            '1.3.6.1.5.5.7.4.15' => 'id-it-origPKIMessage',
            '1.3.6.1.5.5.7.4.16' => 'id-it-suppLangTags',
            '1.3.6.1.5.5.7.5.1' => 'id-regCtrl',
            '1.3.6.1.5.5.7.5.2' => 'id-regInfo',
            '1.3.6.1.5.5.7.5.1.1' => 'id-regCtrl-regToken',
            '1.3.6.1.5.5.7.5.1.2' => 'id-regCtrl-authenticator',
            '1.3.6.1.5.5.7.5.1.3' => 'id-regCtrl-pkiPublicationInfo',
            '1.3.6.1.5.5.7.5.1.4' => 'id-regCtrl-pkiArchiveOptions',
            '1.3.6.1.5.5.7.5.1.5' => 'id-regCtrl-oldCertID',
            '1.3.6.1.5.5.7.5.1.6' => 'id-regCtrl-protocolEncrKey',
            '1.3.6.1.5.5.7.5.2.1' => 'id-regInfo-utf8Pairs',
            '1.3.6.1.5.5.7.5.2.2' => 'id-regInfo-certReq',
            '1.3.6.1.5.5.7.6.1' => 'id-alg-des40',
            '1.3.6.1.5.5.7.6.2' => 'id-alg-noSignature',
            '1.3.6.1.5.5.7.6.3' => 'id-alg-dh-sig-hmac-sha1',
            '1.3.6.1.5.5.7.6.4' => 'id-alg-dh-pop',
            '1.3.6.1.5.5.7.7.1' => 'id-cmc-statusInfo',
            '1.3.6.1.5.5.7.7.2' => 'id-cmc-identification',
            '1.3.6.1.5.5.7.7.3' => 'id-cmc-identityProof',
            '1.3.6.1.5.5.7.7.4' => 'id-cmc-dataReturn',
            '1.3.6.1.5.5.7.7.5' => 'id-cmc-transactionId',
            '1.3.6.1.5.5.7.7.6' => 'id-cmc-senderNonce',
            '1.3.6.1.5.5.7.7.7' => 'id-cmc-recipientNonce',
            '1.3.6.1.5.5.7.7.8' => 'id-cmc-addExtensions',
            '1.3.6.1.5.5.7.7.9' => 'id-cmc-encryptedPOP',
            '1.3.6.1.5.5.7.7.10' => 'id-cmc-decryptedPOP',
            '1.3.6.1.5.5.7.7.11' => 'id-cmc-lraPOPWitness',
            '1.3.6.1.5.5.7.7.15' => 'id-cmc-getCert',
            '1.3.6.1.5.5.7.7.16' => 'id-cmc-getCRL',
            '1.3.6.1.5.5.7.7.17' => 'id-cmc-revokeRequest',
            '1.3.6.1.5.5.7.7.18' => 'id-cmc-regInfo',
            '1.3.6.1.5.5.7.7.19' => 'id-cmc-responseInfo',
            '1.3.6.1.5.5.7.7.21' => 'id-cmc-queryPending',
            '1.3.6.1.5.5.7.7.22' => 'id-cmc-popLinkRandom',
            '1.3.6.1.5.5.7.7.23' => 'id-cmc-popLinkWitness',
            '1.3.6.1.5.5.7.7.24' => 'id-cmc-confirmCertAcceptance',
            '1.3.6.1.5.5.7.8.1' => 'id-on-personalData',
            '1.3.6.1.5.5.7.8.3' => 'Permanent Identifier',
            '1.3.6.1.5.5.7.9.1' => 'id-pda-dateOfBirth',
            '1.3.6.1.5.5.7.9.2' => 'id-pda-placeOfBirth',
            '1.3.6.1.5.5.7.9.3' => 'id-pda-gender',
            '1.3.6.1.5.5.7.9.4' => 'id-pda-countryOfCitizenship',
            '1.3.6.1.5.5.7.9.5' => 'id-pda-countryOfResidence',
            '1.3.6.1.5.5.7.10.1' => 'id-aca-authenticationInfo',
            '1.3.6.1.5.5.7.10.2' => 'id-aca-accessIdentity',
            '1.3.6.1.5.5.7.10.3' => 'id-aca-chargingIdentity',
            '1.3.6.1.5.5.7.10.4' => 'id-aca-group',
            '1.3.6.1.5.5.7.10.5' => 'id-aca-role',
            '1.3.6.1.5.5.7.10.6' => 'id-aca-encAttrs',
            '1.3.6.1.5.5.7.11.1' => 'id-qcs-pkixQCSyntax-v1',
            '1.3.6.1.5.5.7.12.1' => 'id-cct-crs',
            '1.3.6.1.5.5.7.12.2' => 'id-cct-PKIData',
            '1.3.6.1.5.5.7.12.3' => 'id-cct-PKIResponse',
            '1.3.6.1.5.5.7.21.0' => 'Any language',
            '1.3.6.1.5.5.7.21.1' => 'Inherit all',
            '1.3.6.1.5.5.7.21.2' => 'Independent',
            '1.3.6.1.5.5.7.48.1' => 'OCSP',
            '1.3.6.1.5.5.7.48.2' => 'CA Issuers',
            '1.3.6.1.5.5.7.48.3' => 'AD Time Stamping',
            '1.3.6.1.5.5.7.48.4' => 'ad dvcs',
            '1.3.6.1.5.5.7.48.5' => 'CA Repository',
            '1.3.6.1.5.5.7.48.1.1' => 'Basic OCSP Response',
            '1.3.6.1.5.5.7.48.1.2' => 'OCSP Nonce',
            '1.3.6.1.5.5.7.48.1.3' => 'OCSP CRL ID',
            '1.3.6.1.5.5.7.48.1.4' => 'Acceptable OCSP Responses',
            '1.3.6.1.5.5.7.48.1.5' => 'OCSP No Check',
            '1.3.6.1.5.5.7.48.1.6' => 'OCSP Archive Cutoff',
            '1.3.6.1.5.5.7.48.1.7' => 'OCSP Service Locator',
            '1.3.6.1.5.5.7.48.1.8' => 'Extended OCSP Status',
            '1.3.6.1.5.5.7.48.1.9' => 'id-pkix-OCSP_valid',
            '1.3.6.1.5.5.7.48.1.10' => 'id-pkix-OCSP_path',
            '1.3.6.1.5.5.7.48.1.11' => 'Trust Root',
            '1.3.14.3.2' => 'algorithm',
            '1.3.14.3.2.3' => 'md5WithRSA',
            '1.3.14.3.2.6' => 'des-ecb',
            '1.3.14.3.2.7' => 'des-cbc',
            '1.3.14.3.2.8' => 'des-ofb',
            '1.3.14.3.2.9' => 'des-cfb',
            '1.3.14.3.2.11' => 'rsaSignature',
            '1.3.14.3.2.12' => 'dsaEncryption-old',
            '1.3.14.3.2.13' => 'dsaWithSHA',
            '1.3.14.3.2.15' => 'shaWithRSAEncryption',
            '1.3.14.3.2.17' => 'des-ede',
            '1.3.14.3.2.18' => 'sha',
            '1.3.14.3.2.26' => 'sha1',
            '1.3.14.3.2.27' => 'dsaWithSHA1-old',
            '1.3.14.3.2.29' => 'sha1WithRSA',
            '1.3.36.3.2.1' => 'ripemd160',
            '1.3.36.3.3.1.2' => 'ripemd160WithRSA',
            '1.3.101.1.4.1' => 'Strong Extranet ID',
            '2.5' => 'directory services (X.500)',
            '2.5.4' => 'X509',
            '2.5.4.3' => 'commonName',
            '2.5.4.4' => 'surname',
            '2.5.4.5' => 'serialNumber',
            '2.5.4.6' => 'countryName',
            '2.5.4.7' => 'localityName',
            '2.5.4.8' => 'stateOrProvinceName',
            '2.5.4.9' => 'streetAddress',
            '2.5.4.10' => 'organizationName',
            '2.5.4.11' => 'organizationalUnitName',
            '2.5.4.12' => 'title',
            '2.5.4.13' => 'description',
            '2.5.4.14' => 'searchGuide',
            '2.5.4.15' => 'businessCategory',
            '2.5.4.16' => 'postalAddress',
            '2.5.4.17' => 'postalCode',
            '2.5.4.18' => 'postOfficeBox',
            '2.5.4.19' => 'physicalDeliveryOfficeName',
            '2.5.4.20' => 'telephoneNumber',
            '2.5.4.21' => 'telexNumber',
            '2.5.4.22' => 'teletexTerminalIdentifier',
            '2.5.4.23' => 'facsimileTelephoneNumber',
            '2.5.4.24' => 'x121Address',
            '2.5.4.25' => 'internationaliSDNNumber',
            '2.5.4.26' => 'registeredAddress',
            '2.5.4.27' => 'destinationIndicator',
            '2.5.4.28' => 'preferredDeliveryMethod',
            '2.5.4.29' => 'presentationAddress',
            '2.5.4.30' => 'supportedApplicationContext',
            '2.5.4.31' => 'member',
            '2.5.4.32' => 'owner',
            '2.5.4.33' => 'roleOccupant',
            '2.5.4.34' => 'seeAlso',
            '2.5.4.35' => 'userPassword',
            '2.5.4.36' => 'userCertificate',
            '2.5.4.37' => 'cACertificate',
            '2.5.4.38' => 'authorityRevocationList',
            '2.5.4.39' => 'certificateRevocationList',
            '2.5.4.40' => 'crossCertificatePair',
            '2.5.4.41' => 'name',
            '2.5.4.42' => 'givenName',
            '2.5.4.43' => 'initials',
            '2.5.4.44' => 'generationQualifier',
            '2.5.4.45' => 'x500UniqueIdentifier',
            '2.5.4.46' => 'dnQualifier',
            '2.5.4.47' => 'enhancedSearchGuide',
            '2.5.4.48' => 'protocolInformation',
            '2.5.4.49' => 'distinguishedName',
            '2.5.4.50' => 'uniqueMember',
            '2.5.4.51' => 'houseIdentifier',
            '2.5.4.52' => 'supportedAlgorithms',
            '2.5.4.53' => 'deltaRevocationList',
            '2.5.4.54' => 'dmdName',
            '2.5.4.65' => 'pseudonym',
            '2.5.4.72' => 'role',
            '2.5.8' => 'directory services - algorithms',
            '2.5.8.1.1' => 'rsa',
            '2.5.8.3.100' => 'mdc2WithRSA',
            '2.5.8.3.101' => 'mdc2',
            '2.5.29' => 'id-ce',
            '2.5.29.9' => 'X509v3 Subject Directory Attributes',
            '2.5.29.14' => 'X509v3 Subject Key Identifier',
            '2.5.29.15' => 'X509v3 Key Usage',
            '2.5.29.16' => 'X509v3 Private Key Usage Period',
            '2.5.29.17' => 'X509v3 Subject Alternative Name',
            '2.5.29.18' => 'X509v3 Issuer Alternative Name',
            '2.5.29.19' => 'X509v3 Basic Constraints',
            '2.5.29.20' => 'X509v3 CRL Number',
            '2.5.29.21' => 'X509v3 CRL Reason Code',
            '2.5.29.24' => 'Invalidity Date',
            '2.5.29.27' => 'X509v3 Delta CRL Indicator',
            '2.5.29.28' => 'X509v3 Issuing Distribution Point',
            '2.5.29.29' => 'X509v3 Certificate Issuer',
            '2.5.29.30' => 'X509v3 Name Constraints',
            '2.5.29.31' => 'X509v3 CRL Distribution Points',
            '2.5.29.32' => 'X509v3 Certificate Policies',
            '2.5.29.32.0' => 'X509v3 Any Policy',
            '2.5.29.33' => 'X509v3 Policy Mappings',
            '2.5.29.35' => 'X509v3 Authority Key Identifier',
            '2.5.29.36' => 'X509v3 Policy Constraints',
            '2.5.29.37' => 'X509v3 Extended Key Usage',
            '2.5.29.46' => 'X509v3 Freshest CRL',
            '2.5.29.54' => 'X509v3 Inhibit Any Policy',
            '2.5.29.55' => 'X509v3 AC Targeting',
            '2.5.29.56' => 'X509v3 No Revocation Available',
            '2.5.29.37.0' => 'Any Extended Key Usage',
            '2.16.840.1.113730' => 'Netscape Communications Corp.',
            '2.16.840.1.113730.1' => 'Netscape Certificate Extension',
            '2.16.840.1.113730.2' => 'Netscape Data Type',
            '2.16.840.1.113730.1.1' => 'Netscape Cert Type',
            '2.16.840.1.113730.1.2' => 'Netscape Base Url',
            '2.16.840.1.113730.1.3' => 'Netscape Revocation Url',
            '2.16.840.1.113730.1.4' => 'Netscape CA Revocation Url',
            '2.16.840.1.113730.1.7' => 'Netscape Renewal Url',
            '2.16.840.1.113730.1.8' => 'Netscape CA Policy Url',
            '2.16.840.1.113730.1.12' => 'Netscape SSL Server Name',
            '2.16.840.1.113730.1.13' => 'Netscape Comment',
            '2.16.840.1.113730.2.5' => 'Netscape Certificate Sequence',
            '2.16.840.1.113730.4.1' => 'Netscape Server Gated Crypto',
            '1.3.6' => 'dod',
            '1.3.6.1' => 'iana',
            '1.3.6.1.1' => 'Directory',
            '1.3.6.1.2' => 'Management',
            '1.3.6.1.3' => 'Experimental',
            '1.3.6.1.4' => 'Private',
            '1.3.6.1.5' => 'Security',
            '1.3.6.1.6' => 'SNMPv2',
            '1.3.6.1.7' => 'Mail',
            '1.3.6.1.4.1' => 'Enterprises',
            '1.3.6.1.4.1.1466.344' => 'dcObject',
            '1.2.840.113549.1.9.16.3.8' => 'zlib compression',
            '2.16.840.1.101.3' => 'csor',
            '2.16.840.1.101.3.4' => 'nistAlgorithms',
            '2.16.840.1.101.3.4.1' => 'aes',
            '2.16.840.1.101.3.4.1.1' => 'aes-128-ecb',
            '2.16.840.1.101.3.4.1.2' => 'aes-128-cbc',
            '2.16.840.1.101.3.4.1.3' => 'aes-128-ofb',
            '2.16.840.1.101.3.4.1.4' => 'aes-128-cfb',
            '2.16.840.1.101.3.4.1.5' => 'id-aes128-wrap',
            '2.16.840.1.101.3.4.1.6' => 'aes-128-gcm',
            '2.16.840.1.101.3.4.1.7' => 'aes-128-ccm',
            '2.16.840.1.101.3.4.1.8' => 'id-aes128-wrap-pad',
            '2.16.840.1.101.3.4.1.21' => 'aes-192-ecb',
            '2.16.840.1.101.3.4.1.22' => 'aes-192-cbc',
            '2.16.840.1.101.3.4.1.23' => 'aes-192-ofb',
            '2.16.840.1.101.3.4.1.24' => 'aes-192-cfb',
            '2.16.840.1.101.3.4.1.25' => 'id-aes192-wrap',
            '2.16.840.1.101.3.4.1.26' => 'aes-192-gcm',
            '2.16.840.1.101.3.4.1.27' => 'aes-192-ccm',
            '2.16.840.1.101.3.4.1.28' => 'id-aes192-wrap-pad',
            '2.16.840.1.101.3.4.1.41' => 'aes-256-ecb',
            '2.16.840.1.101.3.4.1.42' => 'aes-256-cbc',
            '2.16.840.1.101.3.4.1.43' => 'aes-256-ofb',
            '2.16.840.1.101.3.4.1.44' => 'aes-256-cfb',
            '2.16.840.1.101.3.4.1.45' => 'id-aes256-wrap',
            '2.16.840.1.101.3.4.1.46' => 'aes-256-gcm',
            '2.16.840.1.101.3.4.1.47' => 'aes-256-ccm',
            '2.16.840.1.101.3.4.1.48' => 'id-aes256-wrap-pad',
            '2.16.840.1.101.3.4.2' => 'nist_hashalgs',
            '2.16.840.1.101.3.4.2.1' => 'sha256',
            '2.16.840.1.101.3.4.2.2' => 'sha384',
            '2.16.840.1.101.3.4.2.3' => 'sha512',
            '2.16.840.1.101.3.4.2.4' => 'sha224',
            '2.16.840.1.101.3.4.3' => 'dsa_with_sha2',
            '2.16.840.1.101.3.4.3.1' => 'dsa_with_SHA224',
            '2.16.840.1.101.3.4.3.2' => 'dsa_with_SHA256',
            '2.5.29.23' => 'Hold Instruction Code',
            '0.9' => 'data',
            '0.9.2342' => 'pss',
            '0.9.2342.19200300' => 'ucl',
            '0.9.2342.19200300.100' => 'pilot',
            '0.9.2342.19200300.100.1' => 'pilotAttributeType',
            '0.9.2342.19200300.100.3' => 'pilotAttributeSyntax',
            '0.9.2342.19200300.100.4' => 'pilotObjectClass',
            '0.9.2342.19200300.100.10' => 'pilotGroups',
            '2.23.42' => 'Secure Electronic Transactions',
            '2.23.42.0' => 'content types',
            '2.23.42.1' => 'message extensions',
            '2.23.42.3' => 'set-attr',
            '2.23.42.5' => 'set-policy',
            '2.23.42.7' => 'certificate extensions',
            '2.23.42.8' => 'set-brand',
            '2.23.42.0.0' => 'setct-PANData',
            '2.23.42.0.1' => 'setct-PANToken',
            '2.23.42.0.2' => 'setct-PANOnly',
            '2.23.42.0.3' => 'setct-OIData',
            '2.23.42.0.4' => 'setct-PI',
            '2.23.42.0.5' => 'setct-PIData',
            '2.23.42.0.6' => 'setct-PIDataUnsigned',
            '2.23.42.0.7' => 'setct-HODInput',
            '2.23.42.0.8' => 'setct-AuthResBaggage',
            '2.23.42.0.9' => 'setct-AuthRevReqBaggage',
            '2.23.42.0.10' => 'setct-AuthRevResBaggage',
            '2.23.42.0.11' => 'setct-CapTokenSeq',
            '2.23.42.0.12' => 'setct-PInitResData',
            '2.23.42.0.13' => 'setct-PI-TBS',
            '2.23.42.0.14' => 'setct-PResData',
            '2.23.42.0.16' => 'setct-AuthReqTBS',
            '2.23.42.0.17' => 'setct-AuthResTBS',
            '2.23.42.0.18' => 'setct-AuthResTBSX',
            '2.23.42.0.19' => 'setct-AuthTokenTBS',
            '2.23.42.0.20' => 'setct-CapTokenData',
            '2.23.42.0.21' => 'setct-CapTokenTBS',
            '2.23.42.0.22' => 'setct-AcqCardCodeMsg',
            '2.23.42.0.23' => 'setct-AuthRevReqTBS',
            '2.23.42.0.24' => 'setct-AuthRevResData',
            '2.23.42.0.25' => 'setct-AuthRevResTBS',
            '2.23.42.0.26' => 'setct-CapReqTBS',
            '2.23.42.0.27' => 'setct-CapReqTBSX',
            '2.23.42.0.28' => 'setct-CapResData',
            '2.23.42.0.29' => 'setct-CapRevReqTBS',
            '2.23.42.0.30' => 'setct-CapRevReqTBSX',
            '2.23.42.0.31' => 'setct-CapRevResData',
            '2.23.42.0.32' => 'setct-CredReqTBS',
            '2.23.42.0.33' => 'setct-CredReqTBSX',
            '2.23.42.0.34' => 'setct-CredResData',
            '2.23.42.0.35' => 'setct-CredRevReqTBS',
            '2.23.42.0.36' => 'setct-CredRevReqTBSX',
            '2.23.42.0.37' => 'setct-CredRevResData',
            '2.23.42.0.38' => 'setct-PCertReqData',
            '2.23.42.0.39' => 'setct-PCertResTBS',
            '2.23.42.0.40' => 'setct-BatchAdminReqData',
            '2.23.42.0.41' => 'setct-BatchAdminResData',
            '2.23.42.0.42' => 'setct-CardCInitResTBS',
            '2.23.42.0.43' => 'setct-MeAqCInitResTBS',
            '2.23.42.0.44' => 'setct-RegFormResTBS',
            '2.23.42.0.45' => 'setct-CertReqData',
            '2.23.42.0.46' => 'setct-CertReqTBS',
            '2.23.42.0.47' => 'setct-CertResData',
            '2.23.42.0.48' => 'setct-CertInqReqTBS',
            '2.23.42.0.49' => 'setct-ErrorTBS',
            '2.23.42.0.50' => 'setct-PIDualSignedTBE',
            '2.23.42.0.51' => 'setct-PIUnsignedTBE',
            '2.23.42.0.52' => 'setct-AuthReqTBE',
            '2.23.42.0.53' => 'setct-AuthResTBE',
            '2.23.42.0.54' => 'setct-AuthResTBEX',
            '2.23.42.0.55' => 'setct-AuthTokenTBE',
            '2.23.42.0.56' => 'setct-CapTokenTBE',
            '2.23.42.0.57' => 'setct-CapTokenTBEX',
            '2.23.42.0.58' => 'setct-AcqCardCodeMsgTBE',
            '2.23.42.0.59' => 'setct-AuthRevReqTBE',
            '2.23.42.0.60' => 'setct-AuthRevResTBE',
            '2.23.42.0.61' => 'setct-AuthRevResTBEB',
            '2.23.42.0.62' => 'setct-CapReqTBE',
            '2.23.42.0.63' => 'setct-CapReqTBEX',
            '2.23.42.0.64' => 'setct-CapResTBE',
            '2.23.42.0.65' => 'setct-CapRevReqTBE',
            '2.23.42.0.66' => 'setct-CapRevReqTBEX',
            '2.23.42.0.67' => 'setct-CapRevResTBE',
            '2.23.42.0.68' => 'setct-CredReqTBE',
            '2.23.42.0.69' => 'setct-CredReqTBEX',
            '2.23.42.0.70' => 'setct-CredResTBE',
            '2.23.42.0.71' => 'setct-CredRevReqTBE',
            '2.23.42.0.72' => 'setct-CredRevReqTBEX',
            '2.23.42.0.73' => 'setct-CredRevResTBE',
            '2.23.42.0.74' => 'setct-BatchAdminReqTBE',
            '2.23.42.0.75' => 'setct-BatchAdminResTBE',
            '2.23.42.0.76' => 'setct-RegFormReqTBE',
            '2.23.42.0.77' => 'setct-CertReqTBE',
            '2.23.42.0.78' => 'setct-CertReqTBEX',
            '2.23.42.0.79' => 'setct-CertResTBE',
            '2.23.42.0.80' => 'setct-CRLNotificationTBS',
            '2.23.42.0.81' => 'setct-CRLNotificationResTBS',
            '2.23.42.0.82' => 'setct-BCIDistributionTBS',
            '2.23.42.1.1' => 'generic cryptogram',
            '2.23.42.1.3' => 'merchant initiated auth',
            '2.23.42.1.4' => 'setext-pinSecure',
            '2.23.42.1.5' => 'setext-pinAny',
            '2.23.42.1.7' => 'setext-track2',
            '2.23.42.1.8' => 'additional verification',
            '2.23.42.5.0' => 'set-policy-root',
            '2.23.42.7.0' => 'setCext-hashedRoot',
            '2.23.42.7.1' => 'setCext-certType',
            '2.23.42.7.2' => 'setCext-merchData',
            '2.23.42.7.3' => 'setCext-cCertRequired',
            '2.23.42.7.4' => 'setCext-tunneling',
            '2.23.42.7.5' => 'setCext-setExt',
            '2.23.42.7.6' => 'setCext-setQualf',
            '2.23.42.7.7' => 'setCext-PGWYcapabilities',
            '2.23.42.7.8' => 'setCext-TokenIdentifier',
            '2.23.42.7.9' => 'setCext-Track2Data',
            '2.23.42.7.10' => 'setCext-TokenType',
            '2.23.42.7.11' => 'setCext-IssuerCapabilities',
            '2.23.42.3.0' => 'setAttr-Cert',
            '2.23.42.3.1' => 'payment gateway capabilities',
            '2.23.42.3.2' => 'setAttr-TokenType',
            '2.23.42.3.3' => 'issuer capabilities',
            '2.23.42.3.0.0' => 'set-rootKeyThumb',
            '2.23.42.3.0.1' => 'set-addPolicy',
            '2.23.42.3.2.1' => 'setAttr-Token-EMV',
            '2.23.42.3.2.2' => 'setAttr-Token-B0Prime',
            '2.23.42.3.3.3' => 'setAttr-IssCap-CVM',
            '2.23.42.3.3.4' => 'setAttr-IssCap-T2',
            '2.23.42.3.3.5' => 'setAttr-IssCap-Sig',
            '2.23.42.3.3.3.1' => 'generate cryptogram',
            '2.23.42.3.3.4.1' => 'encrypted track 2',
            '2.23.42.3.3.4.2' => 'cleartext track 2',
            '2.23.42.3.3.5.1' => 'ICC or token signature',
            '2.23.42.3.3.5.2' => 'secure device signature',
            '2.23.42.8.1' => 'set-brand-IATA-ATA',
            '2.23.42.8.30' => 'set-brand-Diners',
            '2.23.42.8.34' => 'set-brand-AmericanExpress',
            '2.23.42.8.35' => 'set-brand-JCB',
            '2.23.42.8.4' => 'set-brand-Visa',
            '2.23.42.8.5' => 'set-brand-MasterCard',
            '2.23.42.8.6011' => 'set-brand-Novus',
            '1.2.840.113549.3.10' => 'des-cdmf',
            '1.2.840.113549.1.1.6' => 'rsaOAEPEncryptionSET',
            '1.0.10118.3.0.55' => 'whirlpool',
            '1.2.643.2.2' => 'cryptopro',
            '1.2.643.2.9' => 'cryptocom',
            '1.2.643.2.2.3' => 'GOST R 34.11-94 with GOST R 34.10-2001',
            '1.2.643.2.2.4' => 'GOST R 34.11-94 with GOST R 34.10-94',
            '1.2.643.2.2.9' => 'GOST R 34.11-94',
            '1.2.643.2.2.10' => 'HMAC GOST 34.11-94',
            '1.2.643.2.2.19' => 'GOST R 34.10-2001',
            '1.2.643.2.2.20' => 'GOST R 34.10-94',
            '1.2.643.2.2.21' => 'GOST 28147-89',
            '1.2.643.2.2.22' => 'GOST 28147-89 MAC',
            '1.2.643.2.2.23' => 'GOST R 34.11-94 PRF',
            '1.2.643.2.2.98' => 'GOST R 34.10-2001 DH',
            '1.2.643.2.2.99' => 'GOST R 34.10-94 DH',
            '1.2.643.2.2.14.1' => 'id-Gost28147-89-CryptoPro-KeyMeshing',
            '1.2.643.2.2.14.0' => 'id-Gost28147-89-None-KeyMeshing',
            '1.2.643.2.2.30.0' => 'id-GostR3411-94-TestParamSet',
            '1.2.643.2.2.30.1' => 'id-GostR3411-94-CryptoProParamSet',
            '1.2.643.2.2.31.0' => 'id-Gost28147-89-TestParamSet',
            '1.2.643.2.2.31.1' => 'id-Gost28147-89-CryptoPro-A-ParamSet',
            '1.2.643.2.2.31.2' => 'id-Gost28147-89-CryptoPro-B-ParamSet',
            '1.2.643.2.2.31.3' => 'id-Gost28147-89-CryptoPro-C-ParamSet',
            '1.2.643.2.2.31.4' => 'id-Gost28147-89-CryptoPro-D-ParamSet',
            '1.2.643.2.2.31.5' => 'id-Gost28147-89-CryptoPro-Oscar-1-1-ParamSet',
            '1.2.643.2.2.31.6' => 'id-Gost28147-89-CryptoPro-Oscar-1-0-ParamSet',
            '1.2.643.2.2.31.7' => 'id-Gost28147-89-CryptoPro-RIC-1-ParamSet',
            '1.2.643.2.2.32.0' => 'id-GostR3410-94-TestParamSet',
            '1.2.643.2.2.32.2' => 'id-GostR3410-94-CryptoPro-A-ParamSet',
            '1.2.643.2.2.32.3' => 'id-GostR3410-94-CryptoPro-B-ParamSet',
            '1.2.643.2.2.32.4' => 'id-GostR3410-94-CryptoPro-C-ParamSet',
            '1.2.643.2.2.32.5' => 'id-GostR3410-94-CryptoPro-D-ParamSet',
            '1.2.643.2.2.33.1' => 'id-GostR3410-94-CryptoPro-XchA-ParamSet',
            '1.2.643.2.2.33.2' => 'id-GostR3410-94-CryptoPro-XchB-ParamSet',
            '1.2.643.2.2.33.3' => 'id-GostR3410-94-CryptoPro-XchC-ParamSet',
            '1.2.643.2.2.35.0' => 'id-GostR3410-2001-TestParamSet',
            '1.2.643.2.2.35.1' => 'id-GostR3410-2001-CryptoPro-A-ParamSet',
            '1.2.643.2.2.35.2' => 'id-GostR3410-2001-CryptoPro-B-ParamSet',
            '1.2.643.2.2.35.3' => 'id-GostR3410-2001-CryptoPro-C-ParamSet',
            '1.2.643.2.2.36.0' => 'id-GostR3410-2001-CryptoPro-XchA-ParamSet',
            '1.2.643.2.2.36.1' => 'id-GostR3410-2001-CryptoPro-XchB-ParamSet',
            '1.2.643.2.2.20.1' => 'id-GostR3410-94-a',
            '1.2.643.2.2.20.2' => 'id-GostR3410-94-aBis',
            '1.2.643.2.2.20.3' => 'id-GostR3410-94-b',
            '1.2.643.2.2.20.4' => 'id-GostR3410-94-bBis',
            '1.2.643.2.9.1.6.1' => 'GOST 28147-89 Cryptocom ParamSet',
            '1.2.643.2.9.1.5.3' => 'GOST 34.10-94 Cryptocom',
            '1.2.643.2.9.1.5.4' => 'GOST 34.10-2001 Cryptocom',
            '1.2.643.2.9.1.3.3' => 'GOST R 34.11-94 with GOST R 34.10-94 Cryptocom',
            '1.2.643.2.9.1.3.4' => 'GOST R 34.11-94 with GOST R 34.10-2001 Cryptocom',
            '1.2.643.2.9.1.8.1' => 'GOST R 3410-2001 Parameter Set Cryptocom',
            '1.2.392.200011.61.1.1.1.2' => 'camellia-128-cbc',
            '1.2.392.200011.61.1.1.1.3' => 'camellia-192-cbc',
            '1.2.392.200011.61.1.1.1.4' => 'camellia-256-cbc',
            '1.2.392.200011.61.1.1.3.2' => 'id-camellia128-wrap',
            '1.2.392.200011.61.1.1.3.3' => 'id-camellia192-wrap',
            '1.2.392.200011.61.1.1.3.4' => 'id-camellia256-wrap',
            '0.3.4401.5' => 'ntt-ds',
            '0.3.4401.5.3.1.9' => 'camellia',
            '0.3.4401.5.3.1.9.1' => 'camellia-128-ecb',
            '0.3.4401.5.3.1.9.3' => 'camellia-128-ofb',
            '0.3.4401.5.3.1.9.4' => 'camellia-128-cfb',
            '0.3.4401.5.3.1.9.6' => 'camellia-128-gcm',
            '0.3.4401.5.3.1.9.7' => 'camellia-128-ccm',
            '0.3.4401.5.3.1.9.9' => 'camellia-128-ctr',
            '0.3.4401.5.3.1.9.10' => 'camellia-128-cmac',
            '0.3.4401.5.3.1.9.21' => 'camellia-192-ecb',
            '0.3.4401.5.3.1.9.23' => 'camellia-192-ofb',
            '0.3.4401.5.3.1.9.24' => 'camellia-192-cfb',
            '0.3.4401.5.3.1.9.26' => 'camellia-192-gcm',
            '0.3.4401.5.3.1.9.27' => 'camellia-192-ccm',
            '0.3.4401.5.3.1.9.29' => 'camellia-192-ctr',
            '0.3.4401.5.3.1.9.30' => 'camellia-192-cmac',
            '0.3.4401.5.3.1.9.41' => 'camellia-256-ecb',
            '0.3.4401.5.3.1.9.43' => 'camellia-256-ofb',
            '0.3.4401.5.3.1.9.44' => 'camellia-256-cfb',
            '0.3.4401.5.3.1.9.46' => 'camellia-256-gcm',
            '0.3.4401.5.3.1.9.47' => 'camellia-256-ccm',
            '0.3.4401.5.3.1.9.49' => 'camellia-256-ctr',
            '0.3.4401.5.3.1.9.50' => 'camellia-256-cmac',
            '1.2.410.200004' => 'kisa',
            '1.2.410.200004.1.3' => 'seed-ecb',
            '1.2.410.200004.1.4' => 'seed-cbc',
            '1.2.410.200004.1.5' => 'seed-cfb',
            '1.2.410.200004.1.6' => 'seed-ofb',
            '1.2.840.10046.2.1' => 'X9.42 DH',
            '1.3.36.3.3.2.8.1.1.1' => 'brainpoolP160r1',
            '1.3.36.3.3.2.8.1.1.2' => 'brainpoolP160t1',
            '1.3.36.3.3.2.8.1.1.3' => 'brainpoolP192r1',
            '1.3.36.3.3.2.8.1.1.4' => 'brainpoolP192t1',
            '1.3.36.3.3.2.8.1.1.5' => 'brainpoolP224r1',
            '1.3.36.3.3.2.8.1.1.6' => 'brainpoolP224t1',
            '1.3.36.3.3.2.8.1.1.7' => 'brainpoolP256r1',
            '1.3.36.3.3.2.8.1.1.8' => 'brainpoolP256t1',
            '1.3.36.3.3.2.8.1.1.9' => 'brainpoolP320r1',
            '1.3.36.3.3.2.8.1.1.10' => 'brainpoolP320t1',
            '1.3.36.3.3.2.8.1.1.11' => 'brainpoolP384r1',
            '1.3.36.3.3.2.8.1.1.12' => 'brainpoolP384t1',
            '1.3.36.3.3.2.8.1.1.13' => 'brainpoolP512r1',
            '1.3.36.3.3.2.8.1.1.14' => 'brainpoolP512t1',
            '1.3.133.16.840.63.0' => 'x9-63-scheme',
            '1.3.132.1' => 'secg-scheme',
            '1.3.133.16.840.63.0.2' => 'dhSinglePass-stdDH-sha1kdf-scheme',
            '1.3.132.1.11.0' => 'dhSinglePass-stdDH-sha224kdf-scheme',
            '1.3.132.1.11.1' => 'dhSinglePass-stdDH-sha256kdf-scheme',
            '1.3.132.1.11.2' => 'dhSinglePass-stdDH-sha384kdf-scheme',
            '1.3.132.1.11.3' => 'dhSinglePass-stdDH-sha512kdf-scheme',
            '1.3.133.16.840.63.0.3' => 'dhSinglePass-cofactorDH-sha1kdf-scheme',
            '1.3.132.1.14.0' => 'dhSinglePass-cofactorDH-sha224kdf-scheme',
            '1.3.132.1.14.1' => 'dhSinglePass-cofactorDH-sha256kdf-scheme',
            '1.3.132.1.14.2' => 'dhSinglePass-cofactorDH-sha384kdf-scheme',
            '1.3.132.1.14.3' => 'dhSinglePass-cofactorDH-sha512kdf-scheme',
            '1.3.6.1.4.1.11129.2.4.2' => 'CT Precertificate SCTs',
            '1.3.6.1.4.1.11129.2.4.3' => 'CT Precertificate Poison',
            '1.3.6.1.4.1.11129.2.4.4' => 'CT Precertificate Signer',
            '1.3.6.1.4.1.11129.2.4.5' => 'CT Certificate SCTs',
            '1.3.6.1.4.1.311.60.2.1.1' => 'jurisdictionLocalityName',
            '1.3.6.1.4.1.311.60.2.1.2' => 'jurisdictionStateOrProvinceName',
            '1.3.6.1.4.1.311.60.2.1.3' => 'jurisdictionCountryName',
            '1.3.6.1.4.1.11591.4.11' => 'id-scrypt',
        ];

        if (array_key_exists($oidString, $oids)) {
            return $oids[$oidString];
        }

        switch ($oidString) {
            case self::RSA_ENCRYPTION:
                return 'RSA Encryption';
            case self::MD5_WITH_RSA_ENCRYPTION:
                return 'MD5 with RSA Encryption';
            case self::SHA1_WITH_RSA_SIGNATURE:
                return 'SHA-1 with RSA Signature';

            case self::PKCS9_EMAIL:
                return 'PKCS #9 Email Address';
            case self::PKCS9_UNSTRUCTURED_NAME:
                return 'PKCS #9 Unstructured Name';
            case self::PKCS9_CONTENT_TYPE:
                return 'PKCS #9 Content Type';
            case self::PKCS9_MESSAGE_DIGEST:
                return 'PKCS #9 Message Digest';
            case self::PKCS9_SIGNING_TIME:
                return 'PKCS #9 Signing Time';

            case self::COMMON_NAME:
                return 'Common Name';
            case self::SURNAME:
                return 'Surname';
            case self::SERIAL_NUMBER:
                return 'Serial Number';
            case self::COUNTRY_NAME:
                return 'Country Name';
            case self::LOCALITY_NAME:
                return 'Locality Name';
            case self::STATE_OR_PROVINCE_NAME:
                return 'State or Province Name';
            case self::STREET_ADDRESS:
                return 'Street Address';
            case self::ORGANIZATION_NAME:
                return 'Organization Name';
            case self::OU_NAME:
                return 'Organization Unit Name';
            case self::TITLE:
                return 'Title';
            case self::DESCRIPTION:
                return 'Description';
            case self::POSTAL_ADDRESS:
                return 'Postal Address';
            case self::POSTAL_CODE:
                return 'Postal Code';
            case self::AUTHORITY_REVOCATION_LIST:
                return 'Authority Revocation List';

            case self::CERT_EXT_SUBJECT_DIRECTORY_ATTR:
                return 'Subject directory attributes';
            case self::CERT_EXT_SUBJECT_KEY_IDENTIFIER:
                return 'Subject key identifier';
            case self::CERT_EXT_KEY_USAGE:
                return 'Key usage certificate extension';
            case self::CERT_EXT_PRIVATE_KEY_USAGE_PERIOD:
                return 'Private key usage';
            case self::CERT_EXT_SUBJECT_ALT_NAME:
                return 'Subject alternative name (SAN)';
            case self::CERT_EXT_ISSUER_ALT_NAME:
                return 'Issuer alternative name';
            case self::CERT_EXT_BASIC_CONSTRAINTS:
                return 'Basic constraints';
            case self::CERT_EXT_CRL_NUMBER:
                return 'CRL number';
            case self::CERT_EXT_REASON_CODE:
                return 'Reason code';
            case self::CERT_EXT_INVALIDITY_DATE:
                return 'Invalidity code';
            case self::CERT_EXT_DELTA_CRL_INDICATOR:
                return 'Delta CRL indicator';
            case self::CERT_EXT_ISSUING_DIST_POINT:
                return 'Issuing distribution point';
            case self::CERT_EXT_CERT_ISSUER:
                return 'Certificate issuer';
            case self::CERT_EXT_NAME_CONSTRAINTS:
                return 'Name constraints';
            case self::CERT_EXT_CRL_DISTRIBUTION_POINTS:
                return 'CRL distribution points';
            case self::CERT_EXT_CERT_POLICIES:
                return 'Certificate policies ';
            case self::CERT_EXT_AUTHORITY_KEY_IDENTIFIER:
                return 'Authority key identifier';
            case self::CERT_EXT_EXTENDED_KEY_USAGE:
                return 'Extended key usage';
            case self::AUTHORITY_INFORMATION_ACCESS:
                return 'Certificate Authority Information Access (AIA)';

            default:
                if ($loadFromWeb) {
                    return self::loadFromWeb($oidString);
                } else {
                    return $oidString;
                }
        }
    }

    public static function loadFromWeb($oidString)
    {
        $ch = curl_init("http://oid-info.com/get/{$oidString}");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        $contents = curl_exec($ch);
        curl_close($ch);

        // This pattern needs to be updated as soon as the website layout of oid-info.com changes
        preg_match_all('#<tt>(.+)\(\d+\)</tt>#si', $contents, $oidName);

        if (empty($oidName[1])) {
            return "{$oidString} (unknown)";
        }

        $oidName = ucfirst(strtolower(preg_replace('/([A-Z][a-z])/', ' $1', $oidName[1][0])));
        $oidName = str_replace('-', ' ', $oidName);

        return "{$oidName} ({$oidString})";
    }
}
