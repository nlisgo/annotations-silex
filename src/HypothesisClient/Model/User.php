<?php

namespace eLife\HypothesisClient\Model;

use Assert\Assert;

final class User implements ModelInterface, CanBeValidated
{
    use ModelTrait;

    protected $username;
    private $email;
    private $displayName;

    /**
     * @internal
     */
    public function __construct(
        string $username,
        string $email,
        string $displayName
    ) {
        $this->username = $username;
        $this->email = $email;
        $this->displayName = $displayName;
        $this->validate();
    }

    public function getId() : string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getEmail() : string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getDisplayName() : string
    {
        return $this->displayName;
    }

    /**
     * {@inheritdoc}
     */
    public function validate() : bool
    {
        return Assert::lazy()
            // Id must be between 3 and 30 characters.
            ->that($this->getId(), 'User id')
            ->minLength(3, 'Value "%s" must be between 3 and 30 characters.')
            ->maxLength(30, 'Value "%s" must be between 3 and 30 characters.')
            // Id is limited to a small set of characters.
            ->that($this->getId(), 'User id')
            ->regex('/^[A-Za-z0-9._]+$/', 'Value "%s" does not match expression /^[A-Za-z0-9._]+$/.')
            ->that(array_filter([$this->getEmail(), $this->getDisplayName()]), 'User e-mail and display name')
            ->notEmpty('Either an e-mail address or display name is required.')
            // Email must be valid.
            ->that($this->getEmail(), 'User e-mail')
            ->nullOr()
            ->email()
            // Display name must be no more than 30 characters long.
            ->that($this->getDisplayName(), 'User display name')
            ->nullOr()
            ->minLength(1, 'Value "%s" must be between 1 and 30 characters.')
            ->maxLength(30, 'Value "%s" must be between 1 and 30 characters.')
            ->verifyNow();
    }
}
