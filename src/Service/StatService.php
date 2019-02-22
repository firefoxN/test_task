<?php

namespace App\Service;


use App\Entity\Stat;
use Doctrine\ORM\EntityManagerInterface;

class StatService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setStat($statKey, $value = 1)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $stat = $this->getFromDb($statKey);
            if (empty($stat)) {
                $this->saveToDb($statKey, $value);
            } else {
                $stat->setStatValue($stat->getStatValue() + 1);

                $this->entityManager->flush();
            }

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // Rollback on error
            $this->entityManager->getConnection()->rollback();
        }
    }

    public function getFromDb(string $statKey): ?Stat
    {
        $statRepo = $this->entityManager->getRepository(Stat::class);
        $stat = $statRepo->find($statKey);

        return $stat;
    }

    public function getUserIpAddr()
    {
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    private function saveToDb(string $statKey, $value):void
    {
        $stat = new Stat();
        $stat->setId($statKey);
        $stat->setStatValue($value);
        $this->entityManager->persist($stat);
        $this->entityManager->flush();
    }
}