<?php

namespace Flock\MainBundle\Repository;

use Doctrine\ORM\EntityRepository,
    Flock\MainBundle\Entity\User,
    Flock\MainBundle\Entity\Flock,
    Flock\MainBundle\Entity\Attendee,
    Flock\MainBundle\Repository\ActivityRepository;

/**
 * AttendeeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AttendeeRepository extends EntityRepository
{
    public function addOrRemoveAttendee(Flock $flock, User $user)
    {
        if ($guest = $this->findOneBy(array('flock' => $flock, 'user' => $user->getId()))) {
            $this->getEntityManager()->remove($guest);

            //add activity
            $this->getEntityManager()->getRepository('FlockMainBundle:Activity')
                ->addActivity($user, $flock, ActivityRepository::ACTIVITY_UNJOINED_FLOCK);

            $message = "You have unjoined ".$flock->getName();
        } else {
            $guest = new Attendee();
            $guest->setFlock($flock);
            $guest->setUser($user);
            $this->getEntityManager()->persist($guest);

            //add activity
            $this->getEntityManager()->getRepository('FlockMainBundle:Activity')
                ->addActivity($user, $flock, ActivityRepository::ACTIVITY_JOINED_FLOCK);

            $message = "You have joined ".$flock->getName();
        }
        $this->getEntityManager()->flush();

        return $message;
    }

    public function getAttendeeCount(Flock $flock)
    {
        $query = $this->getEntityManager()->createQuery('SELECT COUNT(a.id) FROM '.$this->getEntityName().' a WHERE a.flock = :flock');
        $query->setParameter('flock', $flock);

        return $query->getSingleScalarResult();
    }
}
