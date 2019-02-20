<?php

namespace App\Controller;

use App\Service\CacheHelperService;
use App\Service\StatService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    const TIME_FOR_SAVING = 60;

    private $cacheHelperService;
    private $statService;
    private $logger;

    public function __construct(CacheHelperService $cacheHelperService, StatService $statService, LoggerInterface $logger)
    {
        $this->cacheHelperService = $cacheHelperService;
        $this->statService = $statService;
        $this->logger = $logger;
    }

    /**
     * @Route("/{number}", name="page_by_number", requirements={"number"="[1-9]{1}$|([1-9][0-9]{1,5}$|1000000)"})
     */
    public function index(int $number)
    {
        $r1 = $r2 = $r3 = $r4 = $r5 = null;
        for ($i = 1; $i < 6; $i++) {
            $cachValue = $this->cacheHelperService->getValue('r' . $i);
            if (!empty($cachValue)) {
                ${'r' . $i} = $cachValue;
            } else {
                ${'r' . $i} = rand(1, 100);
                $this->cacheHelperService->setValue('r' . $i, ${'r' . $i});
            }
        }

        $pageStatCacheItemArray = $this->cacheHelperService->getValue('page_' . $number);
        $pageIncrement = null;
        if (empty($pageStatCacheItemArray)) {
            $pageStatCacheFromDb = $this->statService->getFromDb('page_' . $number);
            if (empty($pageStatCacheFromDb)) {
                $this->cacheHelperService->setValue('page_' . $number, 1);
                $pageIncrement = 1;
            } else {
                $this->cacheHelperService->setValue('page_' . $number, $pageStatCacheFromDb->getStatValue() + 1);
                $pageIncrement = $pageStatCacheFromDb->getStatValue() + 1;
            }

        } else {
            $pageIncrement = $pageStatCacheItemArray + 1;
            $this->cacheHelperService->setValue('page_' . $number, $pageIncrement);

            $this->checkDatesDiff($this->cacheHelperService->getCretedTimestamp('page_' . $number), 'page_' . $number, $pageIncrement);
        }

        $usersCachItem = $this->cacheHelperService->getValue('today_users');
        $userIp = $this->statService->getUserIpAddr();
        if ((!empty($usersCachItem) && $this->isNewDay($this->cacheHelperService->getCretedTimestamp('today_users'))) || empty($usersCachItem)) {
            $this->cacheHelperService->clearCacheByKey('today_users');
            $userIpArray = [$userIp];
            $this->cacheHelperService->setValue('today_users', implode('|', $userIpArray));
        } else {
            $userIpArray = explode('|', $usersCachItem);
            $userIpArray[] = $userIp;
            $this->cacheHelperService->setValue('today_users', implode('|', $userIpArray));
        }

        $siteVisitorsCacheArray = $this->cacheHelperService->getValue('site_visitors');
        $siteIncrement = null;
        if (empty($siteVisitorsCacheArray)) {
            $siteCacheFromDb = $this->statService->getFromDb('site_visitors');
            if (empty($siteCacheFromDb)) {
                $this->cacheHelperService->setValue('site_visitors', 1);
                $siteIncrement = 1;
            } else {
                if (!in_array($userIp, $userIpArray)) {
                    $siteIncrement = $siteCacheFromDb->getStatValue() + 1;
                    $this->cacheHelperService->setValue('site_visitors', $siteIncrement);
                }
            }
        } else {
            if (!in_array($userIp, $userIpArray)) {
                $siteIncrement = $siteVisitorsCacheArray + 1;
                $this->cacheHelperService->setValue('site_visitors', $siteIncrement);

                $this->checkDatesDiff($this->cacheHelperService->getCretedTimestamp('site_visitors'), 'site_visitors', $siteIncrement);
            }
        }

        $this->logger->info(json_encode([
            "datetime" => (new \DateTime())->format('yyyy-MM-dd HH:mm:ss'),
            'N' => $number,
            'R' => [$r1, $r2, $r3, $r4, $r5],
            'I1' => $siteIncrement,
            'I2' => $pageIncrement,
        ]));

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'r1' => $r1,
            'r2' => $r2,
            'r3' => $r3,
            'r4' => $r4,
            'r5' => $r5,
        ]);
    }

    public function checkDatesDiff($timestamp, $cacheKey, $value): void
    {
        $now = (new \DateTime())->getTimestamp();

        $secondDifference  = $now - $timestamp;
        $minuteDiference = ($secondDifference / 60) % 60;

        if ($minuteDiference >= self::TIME_FOR_SAVING) {
            $this->statService->saveToDb($cacheKey, $value);
        }
    }

    public function isNewDay($timestamp)
    {
        $now = (new \DateTime());
        $date = (new \DateTime())->setTimestamp($timestamp);

        $now->setTime(0, 0, 0);
        $date->setTime(0, 0, 0);

        $dateDiff = $now->diff($date);

        if ($dateDiff->days == 0) {
            return false;
        }

        return true;
    }
}
