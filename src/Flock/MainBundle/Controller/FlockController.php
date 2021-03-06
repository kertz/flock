<?php
/**
 * Created by Amal Raghav <amal.raghav@gmail.com>
 * Date: 07/05/11
 */

namespace Flock\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Flock\MainBundle\Entity\Flock;
use Flock\MainBundle\Entity\User;
use Flock\MainBundle\Form\FlockForm;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\HttpFoundation\Request;
use Flock\MainBundle\Repository\ActivityRepository;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
class FlockController extends Controller
{
    /**
     * @Extra\Route("/create", name="flock_create")
     * @Extra\Template("FlockMainBundle:Flock:create.html.twig")
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAction()
    {
        $em = $this->get('doctrine')->getEntityManager();
        $flock = new Flock();

        $form = $this->buildForm($flock);

        if ($this->get('request')->getMethod() === 'POST') {
            $form->bindRequest($this->get('request'));

            if ($form->isValid()) {
                $flock->setUser($this->get('security.context')->getToken()->getUser());
                $em->persist($flock);
                $em->flush();

                //add activity
                $this->getDoctrine()->getRepository('FlockMainBundle:Activity')
                    ->addActivity($this->get('security.context')->getToken()->getUser(), $flock, ActivityRepository::ACTIVITY_CREATED_FLOCK);

                $this->get('session')->setFlash('notice', "There you go! You have created a new flock!");

                return new RedirectResponse($this->generateUrl('flock_show', array('id' => $flock->getId())));
            }
        }

        return array('form' => $form->createView());
    }

    /**
     * @Extra\Route("/list/more", name="flocks_list_ajax")
     * @Extra\Template("FlockMainBundle:Flock:_list_more.html.twig")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return void
     */
    public function ajaxListAction(Request $request)
    {
        $limit = 10;
        if (!$offset = $request->get('offset')) {
            return new Response("Oops! That's weird!");
        }

        $flocks = $this->getDoctrine()->getRepository('FlockMainBundle:Flock')->getActiveFlocks($limit, $offset);
        $flocksCounts = $this->getDoctrine()->getRepository('FlockMainBundle:Flock')->getActiveFlocksCount();

        return array(
            'flocks' => $flocks,
            'offset' => $offset + $limit,
            'showLoadMore' => $flocksCounts > $offset + $limit ? true : false,
        );
    }

    /**
     * @Extra\Route("/list", name="flocks_list")
     * @Extra\Template("FlockMainBundle:Flock:list.html.twig")
     *
     * @return array
     */
    public function listAction()
    {
        $limit = 10;
        $offset = 0;

        $flocks = $this->getDoctrine()->getRepository('FlockMainBundle:Flock')->getActiveFlocks($limit, $offset);
        $flocksCounts = $this->getDoctrine()->getRepository('FlockMainBundle:Flock')->getActiveFlocksCount();

        return array(
            'flocks' => $flocks,
            'offset' => $offset + $limit,
            'showLoadMore' => $flocksCounts > $offset + $limit ? true : false,
        );
    }

    /**
     * @Extra\Route("/myFlocks", name="my_flocks")
     * @Extra\Template("FlockMainBundle:Flock:my_flocks.html.twig")
     *
     * @return array
     */
    public function myFlocksAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();

        $flocksCreated = $this->getDoctrine()->getRepository('FlockMainBundle:Flock')->findBy(array('user' => $user->getId()));
        $flocksAttending = $this->getDoctrine()->getRepository('FlockMainBundle:Attendee')->getFlocksUserIsAttending($user);

        return array(
            'flocksCreated' => $flocksCreated,
            'flocksAttending' => $flocksAttending,
        );
    }

    /**
     * @Extra\Route("/deleted", name="deleted_flock")
     * @Extra\Template("FlockMainBundle:Flock:deleted_flock.html.twig")
     *
     * @return array
     */
    public function deletedFlockAction()
    {
        return array();
    }

    /**
     * @Extra\Route("/{id}", name="flock_show")
     * @Extra\Template("FlockMainBundle:Flock:show.html.twig")
     *
     * @return array
     */
    public function showAction()
    {
        $flock = $this->getDoctrine()->getRepository('FlockMainBundle:Flock')->findOneBy(array('id' => $this->getRequest()->get('id'), 'ignore_delete' => true));

        if (!($flock instanceof Flock)) {
            throw new NotFoundHttpException("I guess you are looking for something that doesn't exist!");
        }

        if ($flock->isDeleted()) {
            return new RedirectResponse($this->generateUrl('deleted_flock'));
        }

        $defaultTweet = "Join me for ".$flock->getName();
        if ($flock->getHashTag()) {
            $defaultTweet .= " ".$flock->getHashTag();
        }
        if (strlen($defaultTweet) > 140) {
            $diff = strlen($defaultTweet) - 140;
            $refactoredName = substr($flock->getName(), 0, strlen($defaultTweet) - $diff - 3).'...';
            $defaultTweet = "Join me for ".$refactoredName;
            if ($flock->getHashTag()) {
                $defaultTweet .= " ".$flock->getHashTag();
            }
        }

        $isAttending = false;
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user instanceof User) {
            if ($this->getDoctrine()->getRepository('FlockMainBundle:Attendee')->findOneBy(array('flock' => $flock, 'user' => $user->getId()))) {
                $isAttending = true;
            }
        }

        $attendees = $this->getDoctrine()->getRepository('FlockMainBundle:Attendee')->findBy(array('flock' => $flock),array(),10,0);
        $attendeeCount = $this->getDoctrine()->getRepository('FlockMainBundle:Attendee')->getAttendeeCount($flock);

        return array(
            'flock' => $flock,
            'isAttending' => $isAttending,
            'defaultTweet' => $defaultTweet,
            'attendees' => $attendees,
            'attendeeCount' => $attendeeCount,
        );
    }

    /**
     * @Extra\Route("/{id}/edit", name="flock_edit")
     * @Extra\ParamConverter("flock", class="FlockMainBundle:Flock")
     * @Extra\Template("FlockMainBundle:Flock:edit.html.twig")
     *
     * @param \Flock\MainBundle\Entity\Flock $flock
     * @return array
     */
    public function editAction(Flock $flock)
    {
        $user = $this->get('security.context')->getToken()->getUser();

        if ($flock->getUser() != $user) {
            throw new AccessDeniedException();
        }

        $em = $this->get('doctrine')->getEntityManager();
        $form = $this->buildForm($flock);

        if ($this->get('request')->getMethod() === 'POST') {
            $form->bindRequest($this->get('request'));

            if ($form->isValid()) {
                $em->persist($flock);
                $em->flush();

                //add activity
                $this->getDoctrine()->getRepository('FlockMainBundle:Activity')
                    ->addActivity($this->get('security.context')->getToken()->getUser(), $flock, ActivityRepository::ACTIVITY_UPDATED_FLOCK);
                $this->get('session')->setFlash('notice', 'Updated your flock information.');

                return new RedirectResponse($this->generateUrl('flock_show', array('id' => $flock->getId())));
            }
        }

        return array('form' => $form->createView(), 'flock' => $flock);
    }

    /**
     * @Extra\Route("/{id}/delete", name="flock_delete")
     * @Extra\ParamConverter("flock", class="FlockMainBundle:Flock")
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @param \Flock\MainBundle\Entity\Flock $flock
     * @return void
     */
    public function deleteAction(Flock $flock)
    {
        $user = $this->get('security.context')->getToken()->getUser();

        if ($flock->getUser() != $user) {
            throw new AccessDeniedException();
        }

        $flock->delete();
        $this->getDoctrine()->getEntityManager()->persist($flock);
        $this->getDoctrine()->getEntityManager()->flush();

        //add activity
        $this->getDoctrine()->getRepository('FlockMainBundle:Activity')
            ->addActivity($this->get('security.context')->getToken()->getUser(), $flock, ActivityRepository::ACTIVITY_DELETED_FLOCK);
        $this->get('session')->setFlash('notice', 'Delted the flock');

        return new RedirectResponse($this->generateUrl('my_flocks'));
    }

    /**
     * @Extra\Route("/{id}/toggleJoin", name="flock_toggle_join")
     * @Extra\ParamConverter("flock", class="FlockMainBundle:Flock")
     *
     * @param \Flock\MainBundle\Entity\Flock $flock
     * @return array
     */
    public function toggleJoinAction(Flock $flock)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $message = $this->getDoctrine()->getRepository('FlockMainBundle:Attendee')->addOrRemoveAttendee($flock, $user);
        $this->get('session')->setFlash('notice', $message);

        return new RedirectResponse($this->generateUrl('flock_show',array('id' => $flock)));
    }

    /**
     * @Extra\Route("/{id}/attendees", name="flock_attendees")
     * @Extra\ParamConverter("flock", class="FlockMainBundle:Flock")
     * @Extra\Template("FlockMainBundle:Flock:_attendees.html.twig")
     *
     * @param \Flock\MainBundle\Entity\Flock $flock
     * @return array
     */
    public function getAttendeesAction(Flock $flock)
    {
        $attendees = $flock->getAttendees();

        return array('attendees' => $attendees);
    }

    private function buildForm(Flock $flock)
    {
        $factory = $this->get('form.factory');
        $form = $factory->create(new FlockForm());
        $form->setData($flock);

        return $form;
    }
}
