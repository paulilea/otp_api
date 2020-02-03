<?php

/*
 * This file is part of the otp-api package.
 *
 * (c) Paul Ilea <paul.ilea90@gmail.com>
 *
 * Copyright lorem...
 */

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use App\Entity\OneTimePassword;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * OneTimePassword Service for session storage mode.
 */
class OneTimePasswordDatabaseService extends AbstractOneTimePasswordService {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * OneTimePasswordSessionService constructor.
     *
     * @param ParameterBagInterface $parameters
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ParameterBagInterface $parameters, EntityManagerInterface $entityManager) {
        parent::__construct($parameters);
        $this->entityManager = $entityManager;
    }

    /**
     * Creates a new One-Time Password and stores it in the database.
     *
     * @param int $userId
     * @return string
     * @throws Exception
     */
    public function create(int $userId): string {
        $password = $this->generateRandomPassword();
        $oneTimePassword = $this->entityManager->getRepository(OneTimePassword::class)->find($userId);

        if ($oneTimePassword) {
            $oneTimePassword->setValue($this->encryptPassword($password));
            $oneTimePassword->setCreatedAt(time());
        } else {
            $oneTimePassword = new OneTimePassword($userId, $this->encryptPassword($password));
            $this->entityManager->persist($oneTimePassword);
        }
        $this->entityManager->flush();

        return $password;
    }

    /**
     * Validates the submitted One-Time Password against the database and app configuration.
     *
     * @param int $userId
     * @param string $password
     * @return bool
     */
    public function isValid(int $userId, string $password): bool {
        /** @var OneTimePassword $oneTimePassword */
        $oneTimePassword = $this->entityManager->getRepository(OneTimePassword::class)->find($userId);
        if ($oneTimePassword === NULL) {
            return FALSE;
        }

        $passwordLifetime = (int) $this->parameters->get('app.password_lifetime');

        if (
            $oneTimePassword->getCreatedAt() + $passwordLifetime >= time() &&
            hash_equals($oneTimePassword->getValue(), $this->encryptPassword($password))
        ) {
            $this->entityManager->remove($oneTimePassword);
            $this->entityManager->flush();

            return TRUE;
        }

        if ($oneTimePassword->getCreatedAt() + $passwordLifetime <= time()) {
            $this->entityManager->remove($oneTimePassword);
            $this->entityManager->flush();
        }

        return FALSE;
    }
}
