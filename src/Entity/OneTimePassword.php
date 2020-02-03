<?php

/*
 * This file is part of the otp-api package.
 *
 * (c) Paul Ilea <paul.ilea90@gmail.com>
 *
 * Copyright lorem...
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ORM Entity for storing One-Time Passwords to database.
 *
 * @ORM\Entity(repositoryClass="App\Repository\OneTimePasswordRepository")
 */
class OneTimePassword {
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private $userId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $value;

    /**
     * @ORM\Column(type="integer")
     */
    private $createdAt;

    /**
     * Constructor for OneTimePassword
     *
     * @param int $userId
     * @param string $value
     * @param int|null $createdAt
     */
    public function __construct(int $userId, string $value, int $createdAt = null) {
        $this->userId = $userId;
        $this->value = $value;
        $this->createdAt = $createdAt ?? time();
    }

    /**
     * @return int
     */
    public function getUserId(): int {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue(string $value): self {
        $this->value = $value;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedAt(): int {
        return $this->createdAt;
    }

    /**
     * @param int $createdAt
     * @return $this
     */
    public function setCreatedAt(int $createdAt): self {
        $this->createdAt = $createdAt;

        return $this;
    }
}
