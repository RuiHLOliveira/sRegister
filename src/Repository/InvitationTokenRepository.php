<?php

namespace App\Repository;

use App\Entity\InvitationToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InvitationToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvitationToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvitationToken[]    findAll()
 * @method InvitationToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvitationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvitationToken::class);
    }

    // /**
    //  * @return InvitationToken[] Returns an array of InvitationToken objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InvitationToken
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
