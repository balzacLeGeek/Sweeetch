<?php

namespace App\Repository;

use App\Entity\Apply;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Apply|null find($id, $lockMode = null, $lockVersion = null)
 * @method Apply|null findOneBy(array $criteria, array $orderBy = null)
 * @method Apply[]    findAll()
 * @method Apply[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Apply::class);
    }

    public function checkIfRowExsists($offers, $student) {

        return (boolean)$this->createQueryBuilder('u')
        ->andWhere('u.offers = :offers AND u.student = :student')
        ->setParameter('offers', $offers->getId())
        ->setParameter('student', $student->getId())
        ->getQuery()
        ->getOneOrNullResult();
    }
  
    // public function getOneApply($offers, $student)
    // {
    //     return $this->createQueryBuilder('u')
    //         ->andWhere('u.offers = :offers AND u.student = :student')
    //         ->setParameter('offers', $offers->getId())
    //         ->setParameter('student', $student->getId())
    //         ->getQuery()
    //         ->getResult()
    //     ;
    // }

    public function getOtherApplies($student, $offers)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.offers = :offers AND u.student != :student')
            ->setParameter('offers', $offers)
            ->setParameter('student', $student)
            ->getQuery()
            ->getResult()
        ;
    }
  

    /*
    public function findOneBySomeField($value): ?Apply
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}