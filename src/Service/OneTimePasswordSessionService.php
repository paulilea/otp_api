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
use Symfony\Component\HttpFoundation\Session\SessionInterface;


/**
 * OneTimePassword Service for session storage mode.
 */
class OneTimePasswordSessionService extends AbstractOneTimePasswordService {
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * OneTimePasswordSessionService constructor.
     *
     * @param ParameterBagInterface $parameters
     * @param SessionInterface $session
     */
    public function __construct(ParameterBagInterface $parameters, SessionInterface $session) {
        parent::__construct($parameters);
        $this->session = $session;
    }

    /**
     * Creates a new One-Time Password and stores it in the session.
     *
     * @param int $userId
     * @return string
     * @throws Exception
     */
    public function create(int $userId): string {
        $password = $this->generateRandomPassword();
        $passwordData = ['password' => $this->encryptPassword($password), 'createdAt' => time()];
        $this->session->set('otp-' . $userId, serialize($passwordData));

        return $password;
    }

    /**
     * Validates the submitted One-Time Password against the session data and app configuration.
     *
     * @param int $userId
     * @param string $password
     * @return bool
     */
    public function isValid(int $userId, string $password): bool {
        $storedPasswordData = $this->session->get('otp-' . $userId);
        if ($storedPasswordData === NULL) {
            return FALSE;
        }

        $passwordLifetime = (int) $this->parameters->get('app.password_lifetime');
        $storedPasswordData = unserialize($storedPasswordData, ['']);

        if (
            ((int) $storedPasswordData['createdAt']) + $passwordLifetime >= time() &&
            hash_equals($storedPasswordData['password'], $this->encryptPassword($password))
        ) {
            $this->session->set('otp-' . $userId, NULL);

            return TRUE;
        }

        if (((int) $storedPasswordData['createdAt']) + $passwordLifetime <= time()) {
            $this->session->set('otp-' . $userId, NULL);
        }

        return FALSE;
    }
}
