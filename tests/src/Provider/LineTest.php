<?php

namespace Osapon\OAuth2\Client\Test\Provider;

use Mockery as m;

class LineTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    protected function setUp()
    {
        $this->provider = new \Osapon\OAuth2\Client\Provider\Line([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);

        $this->assertContains('email', $query['scope']);
        $this->assertContains('profile', $query['scope']);
        $this->assertContains('openid', $query['scope']);

        $this->assertAttributeNotEmpty('state', $this->provider);
    }

    public function testBaseAccessTokenUrl()
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $uri = parse_url($url);

        $this->assertEquals('/v1/oauth/accessToken', $uri['path']);
    }

    public function testResourceOwnerDetailsUrl()
    {
        $token = m::mock('League\OAuth2\Client\Token\AccessToken', [['access_token' => 'mock_access_token']]);

        $url = $this->provider->getResourceOwnerDetailsUrl($token);
        $uri = parse_url($url);

        $this->assertEquals('/v1/profile', $uri['path']);
        $this->assertNotContains('mock_access_token', $url);

    }

    public function testResourceOwnerDetailsUrlCustomFields()
    {
        $provider = new \Osapon\OAuth2\Client\Provider\Line([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);

        $token = m::mock('League\OAuth2\Client\Token\AccessToken', [['access_token' => 'mock_access_token']]);

        $url = $provider->getResourceOwnerDetailsUrl($token);
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('fields', $query);
        $this->assertArrayHasKey('alt', $query);

        // Always JSON for consistency
        $this->assertEquals('json', $query['alt']);

        $fields = explode(',', $query['fields']);

        // Default values
        $this->assertContains('mid', $fields);
        $this->assertContains('displayName', $fields);
        $this->assertContains('pictureUrl', $fields);
        $this->assertContains('statusMessage', $fields);

    }

    public function testUserData()
    {
        $response = json_decode('{"mid": "12345","displayName": "mock_name","pictureUrl": "mock_image_url","statusMessage": "mock_message"}', true);

        $provider = m::mock('osapon\OAuth2\Client\Provider\Line[fetchResourceOwnerDetails]')
            ->shouldAllowMockingProtectedMethods();

        $provider->shouldReceive('fetchResourceOwnerDetails')
            ->times(1)
            ->andReturn($response);

        $token = m::mock('League\OAuth2\Client\Token\AccessToken');
        $user = $provider->getResourceOwner($token);

        $this->assertInstanceOf('League\OAuth2\Client\Provider\ResourceOwnerInterface', $user);

        $this->assertEquals(12345, $user->getId());
        $this->assertEquals('mock_name', $user->getName());
        $this->assertEquals('mock_image_url', $user->getAvatar());
        $this->assertEquals('mock_message', $user->getStatusMessage());

        $user = $user->toArray();

        $this->assertArrayHasKey('mid', $user);
        $this->assertArrayHasKey('displayName', $user);
        $this->assertArrayHasKey('pictureUrl', $user);
    }

    /**
     * @expectedException League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function testErrorResponse()
    {
        $response = m::mock('GuzzleHttp\Psr7\Response');

        $response->shouldReceive('getHeader')
            ->with('content-type')
            ->andReturn(['application/json']);

        $response->shouldReceive('getBody')
            ->andReturn('{"error": {"code": 400, "message": "I am an error"}}');

        $provider = m::mock('osapon\OAuth2\Client\Provider\Line[sendRequest]')
            ->shouldAllowMockingProtectedMethods();

        $provider->shouldReceive('sendRequest')
            ->times(1)
            ->andReturn($response);

        $token = m::mock('League\OAuth2\Client\Token\AccessToken');
        $user = $provider->getResourceOwner($token);
    }
}
