<?php

namespace Hamzi\Vaultic\Data;

class RegistrationResult
{
    /** @var string */
    private $credentialId;

    /** @var string */
    private $publicKey;

    /** @var int */
    private $signCount;

    /** @var string */
    private $transports;

    /** @var string */
    private $aaguid;

    /**
     * @param string $credentialId
     * @param string $publicKey
     * @param int $signCount
     * @param string $transports
     * @param string $aaguid
     */
    public function __construct($credentialId, $publicKey, $signCount, $transports = '', $aaguid = '')
    {
        $this->credentialId = $credentialId;
        $this->publicKey = $publicKey;
        $this->signCount = $signCount;
        $this->transports = $transports;
        $this->aaguid = $aaguid;
    }

    /**
     * @return string
     */
    public function getCredentialId()
    {
        return $this->credentialId;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return int
     */
    public function getSignCount()
    {
        return $this->signCount;
    }

    /**
     * @return string
     */
    public function getTransports()
    {
        return $this->transports;
    }

    /**
     * @return string
     */
    public function getAaguid()
    {
        return $this->aaguid;
    }
}
