<?php

namespace NPS\FrontendBundle\Controller;

use Duellsy\Pockpack\Pockpack;
use Duellsy\Pockpack\PockpackAuth;
use Duellsy\Pockpack\PockpackQueue;
use JMS\SecurityExtraBundle\Annotation\Secure;
use NPS\CoreBundle\Constant\ImportConstants;
use NPS\CoreBundle\Helper\ImportHelper;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NPS\CoreBundle\Entity\Feed,
    NPS\CoreBundle\Entity\Item,
    NPS\CoreBundle\Entity\Later,
    NPS\CoreBundle\Entity\LaterItem,
    NPS\CoreBundle\Entity\UserFeed,
    NPS\CoreBundle\Entity\UserItem;
use NPS\CoreBundle\Helper\NotificationHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * ItemController
 */
class ItemController extends Controller
{
    /**
     * List of items
     *
     * @param UserFeed $userFeed
     *
     * @Route("/feed/{user_feed_id}/items_list", name="items_list")
     * @Secure(roles="ROLE_USER")
     * @Template()     *
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "user_feed_id"})
     *
     * @return array
     */
    public function listAction(UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $userItemRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:UserItem');
        $userItems = $userItemRepo->getUnreadByFeedUser($user->getId(), $userFeed->getFeedId());

        $viewData = array(
            'userItems' => $userItems,
            'title' => $userFeed->getTitle(),
            'userFeedId' => $userFeed->getId()
        );

        return $viewData;
    }

    /**
     * List of items to read later
     *
     * @param Later   $label
     *
     * @Route("/label/{label_id}/items_list", name="items_later_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     * @ParamConverter("label", class="NPSCoreBundle:Later", options={"id": "label_id"})
     *
     * @return array
     */
    public function laterListAction(Later $label)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($label->getUserId() == $user->getId()) {
            $labelItemRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:LaterItem');
            $itemsList = $labelItemRepo->getUnread($label->getId());

            $viewData = array(
                'items' => $itemsList,
                'title' => 'Label '.$label->getName()
            );

            return $viewData;
        }
    }

    /**
     * Show item
     *
     * @param UserItem $userItem user's item
     * @param UserFeed $userFeed user's feed
     *
     * @Route("/feed/{user_feed_id}/item/{user_item_id}", name="item_view")
     * @Secure(roles="ROLE_USER")
     * @Template()     *
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     * @ParamConverter("userFeed", class="NPSCoreBundle:UserFeed", options={"id": "user_feed_id"})
     *
     * @return array
     */
    public function viewAction(UserItem $userItem, UserFeed $userFeed)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() != $userItem->getUserId()) {
            $route = $this->container->get('router')->generate('items_list', array('user_feed_id' => $userFeed->getFeedId()));
            return new RedirectResponse($route);
        }

        $this->get('nps.entity.user_item')->changeUserItemStatus($userItem, "isUnread", "setUnread", 2);
        $renderData = array(
            'userItem' => $userItem,
            'title' => $userFeed->getTitle()
        );

        return $renderData;
    }

    /**
     * Show item
     *
     * @param LaterItem $laterItem
     *
     * @Route("/label/{label_id}/item/{later_item_id}", name="item_later_view")
     * @Secure(roles="ROLE_USER")
     * @Template()
     * @ParamConverter("laterItem", class="NPSCoreBundle:LaterItem", options={"mapping": {"later_item_id": "id", "label_id": "later"}})
     *
     * @return array
     */
    public function viewLaterAction(LaterItem $laterItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($laterItem->getLater()->getUserId() == $user->getId()) {
            $laterItemService = $this->get('nps.entity.later_item');
            $item = $laterItemService->readItem($laterItem);
            $title = $laterItemService->getViewTitle($laterItem, $user);

            $renderData = array(
                'item' => $item,
                'title' => $title
            );

            return $renderData;
        }
    }

    /**
     * Change stat of item to read/unread
     *
     * @param Request  $request  Request
     * @param UserItem $userItem user's item
     *
     * @return JsonResponse
     *
     * @Route("/feed/{user_feed_id}/item/{user_item_id}/mark_read/{status}", name="mark_read", defaults={"status" = null})
     * @Secure(roles="ROLE_USER")
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     */
    public function readAction(Request $request, UserItem $userItem)
    {
        $status = $request->get('status');
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() != $userItem->getUserId()) {

            return new JsonResponse(false);
        }

        $status = $this->get('nps.entity.user_item')->changeUserItemStatus($userItem, "isUnread", "setUnread", $status);
        $result = ($status)? NotificationHelper::OK_IS_UNREAD : NotificationHelper::OK_IS_READ ;

        $response = array (
            'result' => $result
        );

        return new JsonResponse($response);
    }

    /**
     * Change stat of later item to read
     *
     * @param LaterItem $laterItem
     *
     * @Route("/label/{label_id}/item/{later_item_id}/mark_read", name="mark_later_read")
     * @Secure(roles="ROLE_USER")
     * @ParamConverter("laterItem", class="NPSCoreBundle:LaterItem", options={"mapping": {"later_item_id": "id", "label_id": "later"}})
     *
     * @return JsonResponse
     */
    public function readLaterAction(LaterItem $laterItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($laterItem->getLater()->getUserId() == $user->getId()) {
            $this->get('nps.entity.later_item')->makeLaterRead($laterItem);

            $item = $laterItem->getUserItem()->getItem();
            $this->get('nps.entity.user_item')->changeStatus($user, $item, "isUnread", "setUnread", 2);

            $renderData = array(
                'result' => NotificationHelper::OK_IS_READ
            );

            return new JsonResponse($renderData);
        }
    }

    /**
     * Add/remove star to item
     * @param UserItem $userItem user's item
     *
     * @Route("/feed/{user_feed_id}/item/{user_item_id}/mark_star", name="mark_star")
     * @Secure(roles="ROLE_USER")
     * @ParamConverter("userItem", class="NPSCoreBundle:UserItem", options={"mapping": {"user_item_id": "id"}})
     *
     * @return JsonResponse
     */
    public function starAction(UserItem $userItem)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->getId() != $userItem->getUserId()) {

            return new JsonResponse(false);
        }

        $status = $this->get('nps.entity.user_item')->changeUserItemStatus($userItem, "isStared", "setStared");
        $result =($status)? NotificationHelper::OK_IS_NOT_STARED : NotificationHelper::OK_IS_STARED ;

        $response = array (
            'result' => $result
        );

        return new JsonResponse($response);
    }

    /**
     * Request for GetPocket auth to import later items
     *
     * @param Request  $request  Request
     *
     * @Route("/label/import/getpocket/request", name="import_getpocket_request")
     * @Secure(roles="ROLE_USER")
     * @Method("POST")
     *
     * @return RedirectResponse
     */
    public function getpocketImportRequestAction(Request $request)
    {
        $pocketType = $this->get('nps.form.type.import.pocket');
        $pocketForm = $this->createForm($pocketType);
        $pocketForm->handleRequest($request);
        if (!$pocketForm->isValid()) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_pocket_form');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $pockpathAuth = new PockpackAuth();
        $requestToken = $pockpathAuth->connect($this->container->getParameter('getpocket_key'));
        if (!$requestToken) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_pocket_token_request');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $pocketData = $pocketForm->getData();
        ImportHelper::setPocketFilters(new Session(), $requestToken, $pocketData['tag'], $pocketData['favorite'], $pocketData['contentType'], $pocketData['later']->getId());

        $url = "https://getpocket.com/auth/authorize?request_token=".$requestToken."&redirect_uri=".$this->generateUrl('import_getpocket', array(), true);

        return new RedirectResponse($url);
    }

    /**
     * Import later items from GetPocket
     *
     * @param Request  $request  Request
     *
     * @Route("/label/import/getpocket", name="import_getpocket")
     * @Secure(roles="ROLE_USER")
     *
     * @return RedirectResponse
     */
    public function getpocketImportAction(Request $request)
    {
        $session = new Session();
        $requestToken = $session->get(ImportConstants::SESSION_REQUEST_TOKEN);
        $consumerKey = $this->container->getParameter('getpocket_key');

        $pockpackAuth = new PockpackAuth();
        $accessToken = $pockpackAuth->receiveToken($this->container->getParameter('getpocket_key'), $requestToken);
        if (!$accessToken) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_pocket_token_request');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $pockpack = new Pockpack($consumerKey, $accessToken);
        $options = ImportHelper::getFiltersPocket($session);
        $list = $pockpack->retrieve($options);
        if ($list->status == 2) { // zero results
            $request->getSession()->getFlashBag()->add('alert', '_Invalid_pocket_filter');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $count = 0;
        $laterItemService = $this->get('nps.entity.later_item');
        $user = $this->get('security.context')->getToken()->getUser();
        $labelId = $session->get(ImportConstants::SESSION_LABEL_ID);
        foreach ($list->list as $item) {
            $laterItemService->importItemFromPocket($user, $labelId, $item->resolved_title, $item->resolved_url, $item->time_added, $item->is_article);
            $count++;
        }
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans('_Valid_pocket_imported', array('%quantity%' => $count)));

        return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
    }
}
