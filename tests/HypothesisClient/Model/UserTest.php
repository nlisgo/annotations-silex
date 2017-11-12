<?php

namespace tests\eLife\HypothesisClient\Model;

use eLife\HypothesisClient\Model\ModelInterface;
use eLife\HypothesisClient\Model\User;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;

/**
 * @covers \eLife\HypothesisClient\Model\User
 */
final class UserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_is_a_model()
    {
        $user = new User('username', 'email@email.com', 'Display Name');

        $this->assertInstanceOf(ModelInterface::class, $user);
    }

    /**
     * @test
     */
    public function it_has_an_id()
    {
        $user = new User('username', 'email@email.com', 'Display Name');

        $this->assertEquals('username', $user->getId());
    }

    /**
     * @test
     */
    public function it_has_an_email()
    {
        $user = new User('username', 'email@email.com', 'Display Name');

        $this->assertEquals('username', $user->getId());
    }

    /**
     * @test
     * @dataProvider providerInvalidUserIds
     */
    public function it_rejects_invalid_user_ids($id, $message = null)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->executeExceptionMessageRegExp($message);
        new User($id, 'email@email.com', 'display_name');
    }

    public function providerInvalidUserIds()
    {
        yield 'id too short' => ['aa', 'must be between 3 and 30 characters'];
        yield 'id too long' => ['zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', 'must be between 3 and 30 characters'];
        yield 'id with spaces' => ['aa a', 'does not match expression'];
        yield 'id with invalid punctuation' => ['!!', ['must be between 3 and 30 characters', 'does not match expression']];
    }

    /**
     * @test
     * @dataProvider providerInvalidEmails
     */
    public function it_rejects_invalid_emails($email)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/was expected to be a valid e-mail address\./');
        new User('userid', $email, 'display_name');
    }

    public function providerInvalidEmails()
    {
        yield 'email with spaces' => ['email@email. com'];
        yield 'email no @' => ['hostname.com'];
    }

    /**
     * @test
     * @dataProvider providerInvalidDisplayNames
     */
    public function it_rejects_invalid_display_names($method, $display_name, $message = null)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->executeExceptionMessageRegExp($message);
        new User('userid', 'email@email.com', $display_name);
    }

    public function providerInvalidDisplayNames()
    {
        yield 'display_name too long' => ['zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', 'must be between 1 and 30 characters.'];
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
        new User($id, $email, $display_name);
    }

    /**
     * @test
     */
    public function it_can_be_flagged_as_new()
    {
        $user = new User('username', 'email@email.com', 'Display Name');
        $this->assertFalse($user->isNew());
        $user->setNew();
        $this->assertTrue($user->isNew());
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
