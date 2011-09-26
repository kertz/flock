<?php

namespace Flock\MainBundle\Entity;

use Doctrine\ORM\EntityRepository,
    Flock\MainBundle\Entity\User,
    Flock\MainBundle\Entity\Flock,
    Flock\MainBundle\Entity\Activity;

/**
 * ActivityRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ActivityRepository extends EntityRepository
{
    const ACTIVITY_JOINED_FLOCK = 1;
    const ACTIVITY_UNJOINED_FLOCK = 2;
    const ACTIVITY_RESCHEDULED_FLOCK = 3;
    const ACTIVITY_CHANGED_FLOCK_NAME = 4;

    public function addActivity(User $user, Flock $flock, $activity)
    {
        //TODO: add activity
    }

    public function getLatestActivity()
    {
        //TODO: retrieve activity and order by created_at
    }
}
