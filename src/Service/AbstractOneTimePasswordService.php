<?php

/*
 * This file is part of the otp-api package.
 *
 * (c) Paul Ilea <paul.ilea90@gmail.com>
 *
 * Copyright lorem...
 */

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class AbstractOneTimePasswordService
 */
abstract class AbstractOneTimePasswordService {
    /**
     * @var ParameterBagInterface
     */
    protected $parameters;

    public function __construct(ParameterBagInterface $parameters) {
        $this->parameters = $parameters;
    }

    /**
     * Abstract method for storing of new One-Time Passwords.
     *
     * @param int $userId
     * @return string
     * @throws Exception
     */
    abstract public function create(int $userId): string;

    /**
     * Abstract method for validation of One-Time Passwords.
     *
     * @param int $userId
     * @param string $password
     * @return bool
     */
    abstract public function isValid(int $userId, string $password): bool;

    /**
     * Generates a random One-Time Password based on the app configuration.
     *
     * @return string
     * @throws Exception
     */
    protected function generateRandomPassword(): string {
        $keyspace = $this->parameters->get('app.password_keyspace');
        $length = (int) $this->parameters->get('app.password_length');
        $keyspaceSize = strlen($keyspace);
        $password = '';

        for($i = 0; $i < $length; $i++) {
            $password .= $keyspace[random_int(0, $keyspaceSize - 1)];
        }

        return $password;
    }

    /**
     * One-way encryption for password strings.
     *
     * @param string $password
     * @return string
     */
    protected function encryptPassword(string $password): string {
        $salt = $this->parameters->get('app.password_salt');

        return crypt($password, $salt);
    }
}
