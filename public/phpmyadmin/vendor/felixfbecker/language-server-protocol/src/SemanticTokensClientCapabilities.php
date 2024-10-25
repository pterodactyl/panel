<?php

namespace LanguageServerProtocol;

class SemanticTokensClientCapabilities
{
/**
     * Whether implementation supports dynamic registration. If this is set to
     * `true` the client supports the new `(TextDocumentRegistrationOptions &
     * StaticRegistrationOptions)` return value for the corresponding server
     * capability as well.
     *
     * @var bool|null
     */
    public $dynamicRegistration;

    /**
     * Which requests the client supports and might send to the server
     * depending on the server's capability. Please note that clients might not
     * show semantic tokens or degrade some of the user experience if a range
     * or full request is advertised by the client but not provided by the
     * server. If for example the client capability `requests.full` and
     * `request.range` are both set to true but the server only provides a
     * range provider the client might not render a minimap correctly or might
     * even decide to not show any semantic tokens at all.
     *
     * @var SemanticTokensClientCapabilitiesRequests
     */
    public $requests;

    /**
     * The token types that the client supports.
     *
     * @var string[]
     */
    public $tokenTypes;

    /**
     * The token modifiers that the client supports.
     *
     * @var string[]
     */
    public $tokenModifiers;

    /**
     * The formats the clients supports.
     *
     * @var string[]
     * @see TokenFormat
     */
    public $formats;

    /**
     * Whether the client supports tokens that can overlap each other.
     *
     * @var bool|null
     */
    public $overlappingTokenSupport;

    /**
     * Whether the client supports tokens that can span multiple lines.
     *
     * @var bool|null
     */
    public $multilineTokenSupport;

    /**
     * Whether the client allows the server to actively cancel a
     * semantic token request, e.g. supports returning
     * ErrorCodes.ServerCancelled. If a server does the client
     * needs to retrigger the request.
     *
     * @since 3.17.0
     *
     * @var bool|null
     */
    public $serverCancelSupport;

    /**
     * Whether the client uses semantic tokens to augment existing
     * syntax tokens. If set to `true` client side created syntax
     * tokens and semantic tokens are both used for colorization. If
     * set to `false` the client only uses the returned semantic tokens
     * for colorization.
     *
     * If the value is `undefined` then the client behavior is not
     * specified.
     *
     * @since 3.17.0
     *
     * @var bool|null
     */
    public $augmentsSyntaxTokens;

    /**
     * Undocumented function
     *
     * @param SemanticTokensClientCapabilitiesRequests|null $requests
     * @param string[]|null $tokenTypes
     * @param string[]|null $tokenModifiers
     * @param string[]|null $formats
     * @param boolean|null $dynamicRegistration
     * @param boolean|null $overlappingTokenSupport
     * @param boolean|null $multilineTokenSupport
     * @param boolean|null $serverCancelSupport
     * @param boolean|null $augmentsSyntaxTokens
     */
    public function __construct(
        SemanticTokensClientCapabilitiesRequests $requests = null,
        array $tokenTypes = null,
        array $tokenModifiers = null,
        array $formats = null,
        bool $dynamicRegistration = null,
        bool $overlappingTokenSupport = null,
        bool $multilineTokenSupport = null,
        bool $serverCancelSupport = null,
        bool $augmentsSyntaxTokens = null
    ) {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->requests = $requests;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->tokenTypes = $tokenTypes;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->tokenModifiers = $tokenModifiers;
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->formats = $formats;
        $this->dynamicRegistration = $dynamicRegistration;
        $this->overlappingTokenSupport = $overlappingTokenSupport;
        $this->multilineTokenSupport = $multilineTokenSupport;
        $this->serverCancelSupport = $serverCancelSupport;
        $this->augmentsSyntaxTokens = $augmentsSyntaxTokens;
    }
}
