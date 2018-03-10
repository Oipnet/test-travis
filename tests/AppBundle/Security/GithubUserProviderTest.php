<?php
/**
 * Created by PhpStorm.
 * User: arnaud
 * Date: 10/03/18
 * Time: 13:35
 */

namespace Tests\AppBundle\Security;

use AppBundle\Entity\User;
use AppBundle\Security\GithubUserProvider;
use GuzzleHttp\Client;
use JMS\Serializer\Serializer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GithubUserProviderTest extends TestCase
{
    private $client;
    private $serializer;
    private $streamedResponse;
    private $response;

    public function setUp()
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->serializer = $this->getMockBuilder(Serializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->streamedResponse = $this->getMockBuilder(StreamInterface::class)
            ->getMock();

        $this->response = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
    }

    public function tearDown()
    {
        $this->client = null;
        $this->response = null;
        $this->streamedResponse = null;
        $this->serializer = null;
    }

    public function testLoadUserByUsernameReturningAUser()
    {
        $this->client
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->response);

        $this->response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->streamedResponse);

        $userData = [
            'login' => 'My login',
            'name' => 'My name',
            'email' => 'local@localhost.dev',
            'avatar_url' => 'url to my avatar',
            'html_url' => 'url to my profile'
        ];

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($userData);

        $githubUserProvider = new GithubUserProvider($this->client, $this->serializer);
        $user = $githubUserProvider->loadUserByUsername('an-access-token');

        $expectedUser = new User($userData['login'], $userData['name'], $userData['email'], $userData['avatar_url'], $userData['html_url']);

        $this->assertEquals($expectedUser, $user);
        $this->assertEquals(User::class, get_class($user));
    }

    public function testLoadUserByUsernameThrowingException()
    {
        $this->client
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->response);

        $this->response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->streamedResponse);

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn(null);

        $this->expectException(\LogicException::class);

        $githubUserProvider = new GithubUserProvider($this->client, $this->serializer);
        $githubUserProvider->loadUserByUsername('an-access-token');
    }
}
