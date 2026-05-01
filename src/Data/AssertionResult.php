<?php

namespace Hamzi\Vaultic\Data;

class AssertionResult
{
    /** @var string */
    private $credentialId;

    /** @var int */
    private $signCount;

    /**
     * @param string $credentialId
     * @param int $signCount
     */
    public function __construct($credentialId, $signCount)
    {
        $this->credentialId = $credentialId;
        $this->signCount = $signCount;
    }

    /**
     * @return string
     */
    public function getCredentialId()
    {
        return $this->credentialId;
    }

    /**
     * @return int
     */
    public function getSignCount()
    {
        return $this->signCount;
    }
}
