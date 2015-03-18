<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Storage;

use Perimeter\RateLimitBundle\Entity\RateLimitMeter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

class DoctrineMeterStorage implements MeterStorageInterface, MeterStorageAdminInterface
{
    protected $_em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->_em = $em;
    }

    public function getMeter($meterId, $meterType = null)
    {
        // find specific meter for company and login
        $meter = $this->findOneByMeterId($meterId, $meterType);

        // try finding by wildcard user
        if (!$meter) {
            $meter = $this->findOneByMeterId(self::DEFAULT_METER_ID, $meterType);
        }

        // if it still doesn't return, throw an exception, as our default meter has not been set up
        if (!$meter) {
            throw new \Exception('default meter not found - set one up by creating a meter named ::DEFAULT::, or run bin/console ratelimit:create-default');
        }

        //set the meter name
        $meter['meter_id'] = $meterId;

        return $meter;
    }

    public function findOneByMeterId($meterId, $meterType = null, $hydrationMode = Query::HYDRATE_ARRAY)
    {
        $query = $this->_em->createQuery('SELECT m FROM Perimeter\RateLimitBundle\Entity\RateLimitMeter m WHERE m.meter_id = ?1');
        $query->setParameter(1, $meterId);

        return $query->getOneOrNullResult($hydrationMode);
    }

    public function findBySearch($search, $limit = 10, $page = 1)
    {
        $offset = ($page-1) * $limit;

        $query = $this->_em->createQuery('SELECT m FROM Perimeter\RateLimitBundle\Entity\RateLimitMeter m WHERE m.meter_id like ?1');
        $query->setParameter(1, '%'.$search.'%')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $query->getArrayResult();
    }

    public function findAll($limit = 10, $page = 1)
    {
        $offset = ($page-1) * $limit;

        $query = $this->_em->createQueryBuilder('m')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        return $query->getArrayResult();
    }

    public function addMeter(array $meterData)
    {
        $meter = new RateLimitMeter();
        $meter->fromArray($meterData);
        $this->_em->persist($meter);
        $this->_em->flush();

        return $meter->toArray();
    }

    public function saveMeterData(array $meterData)
    {
        if (!isset($meterData['meter_id'])) {
            throw new \Exception('meter_id is a required parameter');
        }

        if (!$meter = $this->findOneByMeterId($meterData['meter_id'], null, Query::HYDRATE_OBJECT)) {
            throw new \Exception('meter_id is not valid');
        }

        $meter->fromArray($meterData);
        $this->_em->persist($meter);
        $this->_em->flush();

        return $meter->toArray();
    }

    public function deleteMeter($meterId)
    {
        if ($meter = $this->findOneByMeterId($meterId, null, Query::HYDRATE_OBJECT)) {
            $this->_em->remove($meter);
            $this->_em->flush();

            return true;
        }

        return false;
    }
}
