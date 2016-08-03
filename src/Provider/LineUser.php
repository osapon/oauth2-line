<?php

namespace Osapon\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class LineUser implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $response;

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function getId()
    {
        return $this->response['mid'];
    }

    /**
     * Get perferred display name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->response['displayName'];
    }

    public function getEmail()
    {
        return false;
    }

    /**
     * Get avatar image URL.
     *
     * @return string|null
     */
    public function getAvatar()
    {
        if (!empty($this->response['pictureUrl'])) {
            return $this->response['pictureUrl'];
        }
    }

    /**
     * Get perferred statusMessage.
     *
     * @return string
     */
    public function getStatusMessage()
    {
        return $this->response['statusMessage'];
    }

    /**
     * Get user data as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
