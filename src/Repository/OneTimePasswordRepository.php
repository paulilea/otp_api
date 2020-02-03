<?php

/*
 * This file is part of the otp-api package.
 *
 * (c) Paul Ilea <paul.ilea90@gmail.com>
 *
 * Copyright lorem...
 */

namespace App\Repository;

use App\Entity\OneTimePassword;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository for OneTimePassword
 *
 * @method OneTimePassword|null find($userId, $lockMode = null, $lockVersion = null)
 * @method OneTimePassword|null findOneBy(array $criteria, array $orderBy = null)
 * @method OneTimePassword[]    findAll()
 * @method OneTimePassword[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OneTimePasswordRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, OneTimePassword::class);
    }
}
