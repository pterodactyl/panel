<?php

namespace CodeLts\U2F\U2FServer;

class U2FServer
{
    /** Constant for the version of the u2f protocol */
    public const VERSION = 'U2F_V2';

    /** @internal */
    public const PUBKEY_LEN = 65;

    /*
     *  NOTE: If you are lucky and can match this CNs to the hashes please also add it to
     *  the existing test case provider
     *  "CN=Yubico U2F EE Serial 776137165",
     *  "CN=Yubico U2F EE Serial 1086591525",
     *  "CN=Yubico U2F EE Serial 1973679733",
     *  "CN=Yubico U2F EE Serial 13503277888",
     *  "CN=Yubico U2F EE Serial 14803321578"
     */

    /**
     * @internal
     * @see https://books.google.fr/books?id=SF68CwAAQBAJ&lpg=PT96&ots=PCLmrWyu2F&hl=fr&pg=PT96#v=onepage&q&f=false
     * @see https://github.com/Yubico/python-u2flib-server/issues/15#issue-107916981
     */
    private static $FIXCERTS = [
        '349bca1031f8c82c4ceca38b9cebf1a69df9fb3b94eed99eb3fb9aa3822d26e8',
        'dd574527df608e47ae45fbba75a2afdd5c20fd94a02419381813cd55a2a3398f', // "CN=Yubico U2F EE Serial 13831167861"
        '1d8764f0f7cd1352df6150045c8f638e517270e8b5dda1c63ade9c2280240cae',
        'd0edc9a91a1677435a953390865d208c55b3183c6759c9b5a7ff494c322558eb',
        '6073c436dcd064a48127ddbf6032ac1a66fd59a0c24434f070d4e564c124c897',
        'ca993121846c464d666096d35f13bf44c1b05af205f9b4a1e00cf6cc10c5e511',
    ];

    /**
     * @throws U2FException If OpenSSL older than 1.0.0 is used
     * @return true
     */
    public static function checkOpenSSLVersion()
    {
        if (OPENSSL_VERSION_NUMBER < 0x10000000) {
            throw new U2FException(
                'OpenSSL has to be at least version 1.0.0, this is ' . OPENSSL_VERSION_TEXT,
                U2FException::OLD_OPENSSL
            );
        }
        return true;
    }

    /**
     * Called to get a registration request to send to a user.
     * Returns an array of one registration request and a array of sign requests.
     *
     * @param string $appId Application id for the running application, Basically the app's URL
     * @param stdClass[] $registrations List of current registrations for this
     * user, to prevent the user from registering the same authenticator several
     * times.
     * @return array<string,RegistrationRequest|SignRequest[]> An array of two elements, the first containing a
     * RegisterRequest the second being an array of SignRequest
     * @throws U2FException
     */
    public static function makeRegistration($appId, array $registrations = [])
    {
        $request = new RegistrationRequest(static::createChallenge(), $appId);
        $signatures = static::makeAuthentication($registrations, $appId);
        return [
            'request' => $request,
            'signatures' => $signatures,
        ];
    }

    /**
     * Called to verify and unpack a registration message.
     *
     * @param RegistrationRequest $request this is a reply to
     * @param object $response response from a user
     * @param string|null $attestDir The folder to use where trusted CA files are stored (optional)
     * @param bool $includeCert set to true if the attestation certificate should be
     * included in the returned Registration object (optional)
     * @return Registration
     * @throws U2FException
     */
    public static function register(RegistrationRequest $request, $response, $attestDir = null, $includeCert = true)
    {
        // Parameter Checks
        // $request is safe because of the php typehint

        if (!is_object($response)) {
            throw new \InvalidArgumentException('$response of register() method only accepts object.');
        }

        if (property_exists($response, 'errorCode') && $response->errorCode !== 0) {
            throw new U2FException(
                'User-agent returned error. Error code: ' . $response->errorCode,
                U2FException::BAD_UA_RETURNING
            );
        }

        if (!is_bool($includeCert)) {
            throw new \InvalidArgumentException('$include_cert of register() method only accepts boolean.');
        }

        // Unpack the registration data coming from the client-side token
        $rawRegistration = static::base64uDecode($response->registrationData);
        $registrationData = array_values(unpack('C*', $rawRegistration));
        $clientData = static::base64uDecode($response->clientData);
        $clientToken = json_decode($clientData);

        // Check Client's challenge matches the original request's challenge
        if ($clientToken->challenge !== $request->challenge()) {
            throw new U2FException(
                'Registration challenge does not match',
                U2FException::UNMATCHED_CHALLENGE
            );
        }

        // Begin validating and building the registration
        $registration = new Registration();
        $offset = 1;
        $pubKey = substr($rawRegistration, $offset, static::PUBKEY_LEN);
        $offset += static::PUBKEY_LEN;

        // Validate and set the public key
        if (static::publicKeyToPem($pubKey) === null) {
            throw new U2FException(
                'Decoding of public key failed',
                U2FException::PUBKEY_DECODE
            );
        }
        $registration->setPublicKey(base64_encode($pubKey));

        // Build and set the key handle.
        $keyHandleLength = $registrationData[$offset++];
        $keyHandle = substr($rawRegistration, $offset, $keyHandleLength);
        $offset += $keyHandleLength;
        $registration->setKeyHandle(static::base64uEncode($keyHandle));

        // Build certificate
        // Set certificate length
        // Note: length of certificate is stored in byte 3 and 4 (excluding the first 4 bytes)
        $certLength = 4;
        $certLength += ($registrationData[$offset + 2] << 8);
        $certLength += $registrationData[$offset + 3];

        // Write the certificate from the returning registration data
        $rawCert = static::fixSignatureUnusedBits(substr($rawRegistration, $offset, $certLength));
        $offset += $certLength;
        $pemCert  = "-----BEGIN CERTIFICATE-----\r\n";
        $pemCert .= chunk_split(base64_encode($rawCert), 64);
        $pemCert .= '-----END CERTIFICATE-----';
        if ($includeCert) {
            $registration->setCertificate(base64_encode($rawCert));
        }

        // If we've set the attestDir, check the given certificate can be used.
        if ($attestDir) {
            if (openssl_x509_checkpurpose($pemCert, -1, static::getCerts($attestDir)) !== true) {
                throw new U2FException(
                    'Attestation certificate can not be validated',
                    U2FException::ATTESTATION_VERIFICATION
                );
            }
        }

        // Attempt to extract public key from the certificate, if we can't something went wrong in making it.
        if (!openssl_pkey_get_public($pemCert)) {
            throw new U2FException(
                'Decoding of public key failed',
                U2FException::PUBKEY_DECODE
            );
        }

        // Generate signature from the remaining part of the raw registration data
        $signature = substr($rawRegistration, $offset);

        // Build a verification string from the components we've made in this function
        $dataToVerify  = chr(0);
        $dataToVerify .= hash('sha256', $request->appId(), true);
        $dataToVerify .= hash('sha256', $clientData, true);
        $dataToVerify .= $keyHandle;
        $dataToVerify .= $pubKey;

        // Verify our data against the signature and the certificate, on success return the registration object
        if (openssl_verify($dataToVerify, $signature, $pemCert, 'sha256') === 1) {
            return $registration;
        } else {
            throw new U2FException(
                'Attestation signature does not match',
                U2FException::ATTESTATION_SIGNATURE
            );
        }
    }

    /**
     * Called to get an authentication request.
     *
     * @param stdClass[] $registrations An array of the registrations to create authentication requests for.
     * @param string $appId Application id for the running application, Basically the app's URL
     * @return SignRequest[] An array of SignRequest
     * @throws \InvalidArgumentException
     */
    public static function makeAuthentication(array $registrations, $appId)
    {
        $signatures = [];
        $challenge = static::createChallenge();
        foreach ($registrations as $reg) {
            if (!is_object($reg)) {
                throw new \InvalidArgumentException('$registrations of makeAuthentication() method only accepts array of object.');
            }

            $signatures[] = new SignRequest(
                [
                'appId' => $appId,
                'keyHandle' => $reg->keyHandle,
                'challenge' => $challenge,
                ]
            );
        }
        return $signatures;
    }

    /**
     * Called to verify an authentication response
     *
     * @param SignRequest[] $requests An array of outstanding authentication requests
     * @param stdClass[] $registrations An array of current registrations
     * @param object $response A response from the authenticator
     * @return \stdClass
     * @throws U2FException
     *
     * The Registration object returned on success contains an updated counter
     * that should be saved for future authentications.
     * If the Error returned is ERR_COUNTER_TOO_LOW this is an indication of
     * token cloning or similar and appropriate action should be taken.
     */
    public static function authenticate(array $requests, array $registrations, $response)
    {
        // Parameter checks
        if (!is_object($response)) {
            throw new \InvalidArgumentException('$response of authenticate() method only accepts object.');
        }

        if (property_exists($response, 'errorCode') && $response->errorCode !== 0) {
            throw new U2FException(
                'User-agent returned error. Error code: ' . $response->errorCode,
                U2FException::BAD_UA_RETURNING
            );
        }

        // Set default values to null, so we get fails by default
        /** @var object|null $req */
        $req = null;

        /** @var object|null $reg */
        $reg = null;

        // Extract client response data
        $clientData = static::base64uDecode($response->clientData);
        $decodedClient = json_decode($clientData);

        // Check we have a match among the requests and the response
        foreach ($requests as $req) {
            if (!is_object($req)) {
                throw new \InvalidArgumentException('$requests of authenticate() method only accepts an array of objects.');
            }

            if ($req->keyHandle() === $response->keyHandle && $req->challenge() === $decodedClient->challenge) {
                break;
            }

            $req = null;
        }
        if ($req === null) {
            throw new U2FException(
                'No matching request found',
                U2FException::NO_MATCHING_REQUEST
            );
        }

        // Check for a match for the response among a list of registrations
        foreach ($registrations as $reg) {
            if (!is_object($reg)) {
                throw new \InvalidArgumentException('$registrations of authenticate() method only accepts an array of objects.');
            }

            if ($reg->keyHandle === $response->keyHandle) {
                break;
            }
            $reg = null;
        }
        if ($reg === null) {
            throw new U2FException(
                'No matching registration found',
                U2FException::NO_MATCHING_REGISTRATION
            );
        }

        // On Success, check we have a valid public key
        $pemKey = static::publicKeyToPem(static::base64uDecode($reg->publicKey));
        if ($pemKey === null) {
            throw new U2FException(
                'Decoding of public key failed',
                U2FException::PUBKEY_DECODE
            );
        }

        // Build signature and data from response
        $signData = static::base64uDecode($response->signatureData);
        $dataToVerify  = hash('sha256', $req->appId(), true);
        $dataToVerify .= substr($signData, 0, 5);
        $dataToVerify .= hash('sha256', $clientData, true);
        $signature = substr($signData, 5);

        // Verify the response data against the public key
        if (openssl_verify($dataToVerify, $signature, $pemKey, 'sha256') === 1) {
            $ctr = unpack('Nctr', substr($signData, 1, 4));
            $counter = $ctr['ctr'];
            /* TODO: wrap-around should be handled somehow.. */
            if ($counter > $reg->counter) {
                $reg->counter = $counter;
                return $reg;
            } else {
                throw new U2FException(
                    'Counter too low.',
                    U2FException::COUNTER_TOO_LOW
                );
            }
        } else {
            throw new U2FException(
                'Authentication failed',
                U2FException::AUTHENTICATION_FAILURE
            );
        }
    }

    /**
     * Get all files from a directory
     *
     * @param string $attestDir The folder to find files into
     * @return string[]
     */
    private static function getCerts($attestDir)
    {
        $files = [];
        $dir = $attestDir;
        if ($dir && $handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if (is_file($dir . DIRECTORY_SEPARATOR . $entry)) {
                    $files[] = $dir . DIRECTORY_SEPARATOR . $entry;
                }
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     * @param string $data
     * @return string
     */
    private static function base64uEncode($data)
    {
        return trim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param string $data
     * @return string
     */
    private static function base64uDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * @param string $key
     * @return null|string
     */
    private static function publicKeyToPem($key)
    {
        if (strlen($key) !== static::PUBKEY_LEN || $key[0] !== "\x04") {
            return null;
        }

        /*
         * Convert the public key to binary DER format first
         * Using the ECC SubjectPublicKeyInfo OIDs from RFC 5480
         *
         *  SEQUENCE(2 elem)                        30 59
         *   SEQUENCE(2 elem)                       30 13
         *    OID1.2.840.10045.2.1 (id-ecPublicKey) 06 07 2a 86 48 ce 3d 02 01
         *    OID1.2.840.10045.3.1.7 (secp256r1)    06 08 2a 86 48 ce 3d 03 01 07
         *   BIT STRING(520 bit)                    03 42 ..key..
         */
        $der  = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01";
        $der .= "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42";
        $der .= "\0" . $key;

        $pem  = "-----BEGIN PUBLIC KEY-----\r\n";
        $pem .= chunk_split(base64_encode($der), 64);
        $pem .= '-----END PUBLIC KEY-----';

        return $pem;
    }

    /**
     * @return string
     * @throws U2FException
     */
    private static function createChallenge()
    {
        $challenge = openssl_random_pseudo_bytes(32, $crypto_strong);
        if ($crypto_strong !== true) {
            throw new U2FException(
                'Unable to obtain a good source of randomness',
                U2FException::BAD_RANDOM
            );
        }

        $challenge = static::base64uEncode($challenge);

        return $challenge;
    }

    /**
     * Fixes a certificate where the signature contains unused bits.
     *
     * @param string $cert
     * @return string
     */
    private static function fixSignatureUnusedBits($cert)
    {
        if (in_array(hash('sha256', $cert), static::$FIXCERTS)) {
            $cert[strlen($cert) - 257] = "\0"; // Fix the "unused bits" field (should always be 0).
        }
        return $cert;
    }

}
