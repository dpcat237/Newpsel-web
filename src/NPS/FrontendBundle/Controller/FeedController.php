<?php

namespace NPS\FrontendBundle\Controller;

use Celd\Opml\Importer;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Celd\Opml\Importer as OpmlImporter;
use Celd\Opml\Model\FeedList as OpmlFeedList;
use Celd\Opml\Model\Feed as OpmlFeed;
use NPS\CoreBundle\Event\FeedCreatedEvent;
use NPS\CoreBundle\Event\FeedModifiedEvent;
use NPS\CoreBundle\NPSCoreEvents;
use NPS\FrontendBundle\Form\Type\ImportOpmlType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;
use NPS\CoreBundle\Helper\NotificationHelper;
use NPS\CoreBundle\Entity\Feed,
    NPS\CoreBundle\Entity\UserFeed;
use NPS\FrontendBundle\Form\Type\UserFeedEditType;

/**
 * FeedController
 *
 * @Route("/feed")
 */
class FeedController extends Controller
{
    /**
     * Menu build
     *
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function menuAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $userFeedRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:UserItem');
        $menuAll = $this->get('nps.entity.feed')->getMenuAll($user->getId());
        $feedsList = $userFeedRepo->getUserFeedsForMenu($user->getId(), $menuAll);
        $response = array (
            'userFeeds' =>  $feedsList,
            'menuAll'    => $menuAll
        );

        return $response;
    }

    /**
     * Create process form of feeds
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/add", name="feed_add")
     * @Secure(roles="ROLE_USER")
     */
    public function addAction(Request $request)
    {
        $error = false;
        if (!$request->get('feed')) {
            $result = NotificationHelper::ERROR;
            $itemListUrl = '';
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $downloadFeeds = $this->get('download_feeds');
            list($feed, $error) = $downloadFeeds->addFeed($request->get('feed'), $user);
        }

        if ($error) {
            $this->get('system_notification')->setMessage($error);
        } else {
            $result = NotificationHelper::OK;
            $userFeed = $this->get('nps.entity.feed')->getUserFeed($user->getId(), $feed->getId());
            $itemListUrl = $this->container->get('router')->generate('items_list', array('user_feed_id' => $userFeed->getId()), true);

            $this->get('event_dispatcher')->dispatch(NPSCoreEvents::FEED_CREATED, new FeedCreatedEvent($feed));
            //notify other devices about modification
            $this->get('event_dispatcher')->dispatch(NPSCoreEvents::FEED_MODIFIED, new FeedModifiedEvent($user->getId()));
        }
        $response = array (
            'result' => $result,
            'url'    => $itemListUrl
        );

        return new JsonResponse($response);
    }

    /**
     * Edit user's feed
     * @param Request  $request
     * @param UserFeed $userFeed
     *
     * @return Response
     *
     * @Route("/{feed_id}/edit", name="feed_edit")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "feed_id"})
     */
    public function editAction(Request $request, UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $route = $this->container->get('router')->generate('feeds_list');
        if ($userFeed->getUserId() != $user->getId()) {
            return new RedirectResponse($route);
        }

        $formType = new UserFeedEditType($userFeed);
        $form = $this->createForm($formType, $userFeed);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            $this->get('nps.entity.feed')->saveFormUserFeed($form);

            //notify other devices about modification
            $this->get('event_dispatcher')->dispatch(NPSCoreEvents::FEED_MODIFIED, new FeedModifiedEvent($user->getId()));

            return new RedirectResponse($route);
        }

        $viewData = array(
            'title' => 'Edit feed',
            'form' => $form->createView(),
            'userFeed' => $userFeed
        );

        return $viewData;

    }

    /**
     * Delete feed
     * @param UserFeed $userFeed
     *
     * @return RedirectResponse
     *
     * @Route("/{feed_id}/delete", name="feed_delete")
     * @Secure(roles="ROLE_USER")
     *
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "feed_id"})
     */
    public function deleteAction(UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $route = $this->container->get('router')->generate('feeds_list');
        if ($userFeed->getUserId() != $user->getId()) {
            return new RedirectResponse($route);
        }

        $this->get('nps.entity.feed')->removeUserFeed($userFeed);

        //notify other devices about modification
        $this->get('event_dispatcher')->dispatch(NPSCoreEvents::FEED_MODIFIED, new FeedModifiedEvent($user->getId()));

        return new RedirectResponse($route);
    }

    /**
     * List of user's feeds
     *
     * @return array
     *
     * @Route("/list", name="feeds_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function listAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $userFeedRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:UserFeed');
        $userFeeds = $userFeedRepo->getUserFeeds($user->getId());

        $viewData = array(
            'userFeeds' => $userFeeds,
            'title' => 'Feeds management',
            'menuAll' => $this->get('nps.entity.feed')->getMenuAll($user->getId())
        );

        return $viewData;
    }

    /**
     * Import feeds from OPML file
     *
     * @param Request $request
     *
     * @Route("/opml_import", name="opml_import")
     * @Secure(roles="ROLE_USER")
     * @Method("POST")
     *
     * @return RedirectResponse
     */
    public function importOpmlAction(Request $request)
    {
        $opmlType = new ImportOpmlType();
        $opmlForm = $this->createForm($opmlType);
        $opmlForm->handleRequest($request);
        $opmlFile = $opmlForm->getData()['opml_file'];

        /** @var $opmlFile UploadedFile */
        if (!$opmlForm->isValid() || is_null($opmlFile) || !($opmlFile instanceof UploadedFile)) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_opml');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }


        $importer = new Importer(file_get_contents($opmlFile->getRealPath()));
        $feedList = $importer->getFeedList();

        /** if aren't feeds in file */
        $feedsCount = count($feedList->getItems());
        if ($feedsCount < 1) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_quantity_OPML');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $user = $this->get('security.context')->getToken()->getUser();
        $downloadFeeds = $this->get('download_feeds');
        foreach ($feedList->getItems() as $item) {
            if ($item->getType() == 'category') {
                foreach($item->getFeeds() as $feed) {
                    $downloadFeeds->addFeed($feed->getXmlUrl(), $user);
                }

                continue;
            }

            $downloadFeeds->addFeed($item->getXmlUrl(), $user);
        }
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans('_Success_opml', array('%quantity%' => $feedsCount)));

        return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
    }

    /**
     * Export feeds to OPML file
     *
     * @Route("/opml_import", name="opml_export")
     * @Secure(roles="ROLE_USER")
     */
    public function exportOpmlAction()
    {
        $filename = "newpsel.opml";
        $user = $this->get('security.context')->getToken()->getUser();
        $userFeedRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:UserFeed');
        $userFeeds = $userFeedRepo->getUserFeeds($user->getId());

        $feedList = new OpmlFeedList();
        foreach($userFeeds as $userFeed) {
            $feed = $userFeed->getFeed();
            $opmlFeed = new OpmlFeed();
            $opmlFeed->setTitle($feed->getTitle());
            $opmlFeed->setXmlUrl($feed->getUrl());
            $opmlFeed->setType('rss');
            $opmlFeed->setHtmlUrl($feed->getWebsite());

            $feedList->addItem($opmlFeed);
        }

        $response = new Response();
        $importer = new OpmlImporter();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename);
        $response->setContent($importer->export($feedList));

        return $response;
    }

    /**
     * Change status if show all feeds in menu or only with new items
     *
     * @Route("/change_menu_all", name="change_menu_all")
     * @Secure(roles="ROLE_USER")
     */
    public function changeAllFeedsStatusAction()
    {
        $this->get('nps.entity.feed')->changeMenuAll($this->get('security.context')->getToken()->getUser()->getId());

        return new RedirectResponse($this->container->get('router')->generate('feeds_list'));
    }
}
