<?php

namespace NPS\FrontendBundle\Controller;

use NPS\CoreBundle\Event\LabelModifiedEvent;
use NPS\CoreBundle\NPSCoreEvents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NPS\CoreBundle\Entity\Later;
use NPS\FrontendBundle\Form\Type\LaterEditType;

/**
 * LabelController
 *
 * @Route("/label")
 */
class LabelController extends Controller
{
    /**
     * Menu build
     *
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function menuAction()
    {
        $user = $this->getUser();
        $labelRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Later');
        $menuAll = $this->get('nps.entity.later')->getMenuAll($user->getId());
        if ($menuAll) {
            $labels = $labelRepo->getLabelsForMenu($user->getId(), $menuAll);
            $labelsCount = $labelRepo->getLabelsForMenu($user->getId());
        } else {
            $labels = $labelRepo->getLabelsForMenu($user->getId());
            $labelsCount = array();
        }

        $viewData = array(
            'labels' => $labels,
            'labelsCount' => $labelsCount,
            'menuAll' => $menuAll
        );

        return $viewData;
    }

    /**
     * List of user's labels
     *
     * @return array
     *
     * @Route("/list", name="labels_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function listAction()
    {
        $user = $this->getUser();
        $labelRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Later');
        $labels = $labelRepo->getUserLabel($user->getId());

        $viewData = array(
            'labels' => $labels,
            'title' => 'Labels management',
            'menuAll' => $this->get('nps.entity.later')->getMenuAll($user->getId())
        );

        return $viewData;
    }

    /**
     * Create label
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/new", name="label_create")
     * @Secure(roles="ROLE_USER")
     */
    public function createAction(Request $request)
    {
        $user = $this->getUser();
        $labelRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Later');
        $labelRepo->createLabel($user, $request->get('label'));
        $route = $this->container->get('router')->generate('labels_list');

        //notify about new label
        $labelEvent = new LabelModifiedEvent($user->getId());
        $this->get('event_dispatcher')->dispatch(NPSCoreEvents::LABEL_MODIFIED, $labelEvent);

        return new RedirectResponse($route);
    }

    /**
     * Create label
     * @param Later $label
     *
     * @return Response
     *
     * @Route("/{label_id}/delete", name="label_delete")
     * @Secure(roles="ROLE_USER")
     *
     * @ParamConverter("label", class="NPSCoreBundle:Later", options={"id": "label_id"})
     */
    public function deleteAction(Later $label)
    {
        $user = $this->getUser();
        $route = $this->container->get('router')->generate('labels_list');
        if ($label->getUserId() == $user->getId() && !$label->isBasic()) {
            $this->get('nps.entity.later')->removeLabel($label);

            //notify about delete
            $labelEvent = new LabelModifiedEvent($user->getId());
            $this->get('event_dispatcher')->dispatch(NPSCoreEvents::LABEL_MODIFIED, $labelEvent);
        }

        return new RedirectResponse($route);
    }

    /**
     * Create and edit label
     * @param Request $request
     * @param Later $label
     *
     * @return Response
     *
     * @Route("/{id}/edit", name="label_edit")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @ParamConverter("label", class="NPSCoreBundle:Later")
     */
    public function editAction(Request $request, Later $label)
    {
        $user = $this->getUser();
        $route = $this->container->get('router')->generate('labels_list');

        if ($label->getUserId() == $user->getId()) {
            $form = $this->createForm(LaterEditType::class, $label);

            if ($request->getMethod() == 'POST') {
                $form->handleRequest($request);
                $this->get('nps.entity.later')->saveFormLabel($form);

                //notify about change
                $labelEvent = new LabelModifiedEvent($user->getId());
                $this->get('event_dispatcher')->dispatch(NPSCoreEvents::LABEL_MODIFIED, $labelEvent);

                return new RedirectResponse($route);
            }

            $viewData = array(
                'title' => 'Edit label',
                'form' => $form->createView(),
                'label' => $label,
            );

            return $viewData;
        }

        return new RedirectResponse($route);
    }

    /**
     * Change status if show all labels in menu or only with new items
     *
     * @Route("/change_menu_all", name="change_menu_all_labels")
     * @Secure(roles="ROLE_USER")
     */
    public function changeAllFeedsStatusAction()
    {
        $this->get('nps.entity.later')->changeMenuAll($this->getUser()->getId());

        return new RedirectResponse($this->container->get('router')->generate('labels_list'));
    }
}
