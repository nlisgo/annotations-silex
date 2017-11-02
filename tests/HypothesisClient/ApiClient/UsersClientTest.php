<?php

namespace tests\eLife\HypothesisClient\HttpClient;

use eLife\HypothesisClient\ApiClient\UsersClient;
use eLife\HypothesisClient\Credentials\Credentials;
use eLife\HypothesisClient\Credentials\CredentialsInterface;
use eLife\HypothesisClient\HttpClient\HttpClientInterface;
use eLife\HypothesisClient\Result\ArrayResult;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use tests\eLife\HypothesisClient\RequestConstraint;
use TypeError;

/**
 * @covers \eLife\HypothesisClient\ApiClient\UsersClient
 */
final class UsersClientTest extends PHPUnit_Framework_TestCase
{
    private $httpClient;
    /** @var UsersClient */
    private $usersClient;

    /**
     * @before
     */
    protected function setUpClient()
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->usersClient = new UsersClient(
            $this->httpClient,
            ['X-Foo' => 'bar']
        );
    }

    /**
     * @test
     */
    public function it_requires_a_http_client()
    {
        try {
            new UsersClient('foo');
            $this->fail('A HttpClient is required');
        } catch (TypeError $error) {
            $this->assertTrue(true, 'A HttpClient is required');
            $this->assertContains(
                'must implement interface '.HttpClientInterface::class.', string given',
                $error->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function it_gets_a_user()
    {
        $request = new Request(
            'PATCH',
            'api/user/user',
            ['X-Foo' => 'bar', 'User-Agent' => 'HypothesisClient'],
            '{}'
        );
        $response = new FulfilledPromise(
            new ArrayResult(['foo' => ['bar', 'baz']])
        );
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertSame($response, $this->usersClient->getUser([], 'user'));
    }

    /**
     * @test
     * @dataProvider providerInvalidUserIds
     */
    public function it_rejects_invalid_user_ids($method, $id, $message = null)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->executeExceptionMessageRegExp($message);
        $this->usersClient->{$method}([], $id, 'email@email.com', 'display_name');
    }

    public function providerInvalidUserIds()
    {
        foreach (['createUser', 'modifyUser'] as $method) {
            yield $method.' id too short' => [$method, 'aa', 'must be between 3 and 30 characters'];
            yield $method.' id too long' => [$method, 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', 'must be between 3 and 30 characters'];
            yield $method.' id with spaces' => [$method, 'aa a', 'does not match expression'];
            yield $method.' id with invalid punctuation' => [$method, '!!', ['must be between 3 and 30 characters', 'does not match expression']];
        }
    }

    /**
     * @test
     * @dataProvider providerInvalidEmails
     */
    public function it_rejects_invalid_emails($method, $email)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/was expected to be a valid e-mail address\./');
        $this->usersClient->{$method}([], 'userid', $email, 'display_name');
    }

    public function providerInvalidEmails()
    {
        foreach (['createUser', 'modifyUser'] as $method) {
            yield $method.' email with spaces' => [$method, 'email@email. com'];
            yield $method.' email no @' => [$method, 'hostname.com'];
        }
    }

    /**
     * @test
     * @dataProvider providerInvalidDisplayNames
     */
    public function it_rejects_invalid_display_names($method, $display_name, $message = null)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->executeExceptionMessageRegExp($message);
        $this->usersClient->{$method}([], 'userid', 'email@email.com', $display_name);
    }

    public function providerInvalidDisplayNames()
    {
        foreach (['createUser', 'modifyUser'] as $method) {
            yield $method.' display_name too long' => [$method, 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', 'must be between 1 and 30 characters.'];
        }
    }

    /**
     * @test
     */
    public function it_collects_all_validation_errors()
    {
        $id = '!';
        $email = 'invalid';
        $display_name = 'This display name is too long!!';
        $messages = [
            '1) User id: Value "!" must be between 3 and 30 characters.',
            '2) User id: Value "!" does not match expression /^[A-Za-z0-9._]+$/.',
            '3) User e-mail: Value "invalid" was expected to be a valid e-mail address.',
            '4) User display name: Value "This display name is too long!!" must be between 1 and 30 characters.',
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->executeExceptionMessageRegExp($messages);
        $this->usersClient->createUser([], $id, $email, $display_name);
    }

    /**
     * @test
     */
    public function it_creates_a_user_with_credentials()
    {
        // Some operations require credentials, react if they are missing.
        try {
            $this->usersClient->createUser([], 'userid', 'email@email.com', 'display_name');
            $this->fail('Credentials are required, if requested');
        } catch (TypeError $error) {
            $this->assertTrue(true, 'Credentials are required, if requested');
            $this->assertContains(CredentialsInterface::class.', null returned', $error->getMessage());
        }
        $this->usersClient->setCredentials(new Credentials('client_id', 'secret_key', 'authority'));
        $request = new Request(
            'POST',
            'api/users',
            ['X-Foo' => 'bar', 'Authorization' => 'Basic '.base64_encode('client_id:secret_key'), 'User-Agent' => 'HypothesisClient'],
            json_encode(['authority' => 'authority', 'username' => 'userid', 'email' => 'email@email.com', 'display_name' => 'display_name'])
        );
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($response, $this->usersClient->createUser([], 'userid', 'email@email.com', 'display_name'));
    }

    /**
     * @test
     */
    public function it_modifies_a_user()
    {
        $this->usersClient->setCredentials(new Credentials('client_id', 'secret_key', 'authority'));
        $request = new Request(
            'PATCH',
            'api/user/userid',
            ['X-Foo' => 'bar', 'Authorization' => 'Basic '.base64_encode('client_id:secret_key'), 'User-Agent' => 'HypothesisClient'],
            json_encode(['email' => 'email@email.com'])
        );
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($response, $this->usersClient->modifyUser([], 'userid', 'email@email.com'));
    }

    /**
     * @test
     * @dataProvider providerEmailOrDisplayName
     */
    public function it_requires_an_email_or_display_name_when_modifying_user($email, $display_name)
    {
        $this->usersClient->setCredentials(new Credentials('client_id', 'secret_key', 'authority'));
        $request = new Request(
            'PATCH',
            'api/user/userid',
            ['X-Foo' => 'bar', 'Authorization' => 'Basic '.base64_encode('client_id:secret_key'), 'User-Agent' => 'HypothesisClient'],
            json_encode(array_filter(['email' => $email, 'display_name' => $display_name]))
        );
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($response, $this->usersClient->modifyUser([], 'userid', $email, $display_name));
        $this->expectException(InvalidArgumentException::class);
        $this->executeExceptionMessageRegExp('User e-mail and display name: Either an e-mail address or display name is required.');
        $this->assertEquals($response, $this->usersClient->modifyUser([], 'userid'));
    }

    public function providerEmailOrDisplayName()
    {
        yield 'e-mail and display name' => ['email@email.com', 'display name'];
        yield 'e-mail, no display name' => ['email@email.com', null];
        yield 'display name, no e-mail' => [null, 'display name'];
    }

    /**
     * @test
     */
    public function it_may_have_credentials()
    {
        $request = new Request(
            'PATCH',
            'api/user/user',
            ['X-Foo' => 'bar', 'Authorization' => 'Basic '.base64_encode('client_id:secret_key'), 'User-Agent' => 'HypothesisClient'],
            '{}'
        );
        $response = new FulfilledPromise(new ArrayResult(['foo' => ['bar', 'baz']]));
        $this->usersClient->setCredentials(new Credentials('client_id', 'secret_key', 'authority'));
        $this->httpClient
            ->expects($this->once())
            ->method('send')
            ->with(RequestConstraint::equalTo($request))
            ->willReturn($response);
        $this->assertEquals($response, $this->usersClient->getUser([], 'user'));
    }

    private function executeExceptionMessageRegExp($message = null, $glue = '.*\n.*')
    {
        if (!empty($message)) {
            $messages = array_map(function ($msg) {
                return preg_quote($msg, '/');
            }, (array) $message);
            $this->expectExceptionMessageRegExp('/'.implode($glue, $messages).'/');
        }
    }
}
