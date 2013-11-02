<?php

namespace NPS\FrontendBundle\Controller;

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
 * ItemController
 *
 * @Route("/label")
 */
class LabelController extends BaseController
{
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
        $user = $this->get('security.context')->getToken()->getUser();
        $labelRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Later');
        $labels = $labelRepo->getUserLabel($user->getId());

        $viewData = array(
            'labels' => $labels,
            'title' => 'Labels management'
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
        $user = $this->get('security.context')->getToken()->getUser();
        $labelRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Later');
        $labelRepo->createLabel($user, $request->get('label'));
        $route = $this->container->get('router')->generate('labels_list');

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
        $user = $this->get('security.context')->getToken()->getUser();
        $route = $this->container->get('router')->generate('labels_list');
        if ($label->getUserId() == $user->getId()) {
            $this->get('label')->removeLabel($label);
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
        $user = $this->get('security.context')->getToken()->getUser();
        $route = $this->container->get('router')->generate('labels_list');

        if ($label->getUserId() == $user->getId()) {
            $formType = new LaterEditType($label);
            $form = $this->createForm($formType, $label);

            if ($request->getMethod() == 'POST') {
                $form->handleRequest($request);
                $this->get('label')->saveFormLabel($form);

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
     * Menu build
     *
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function menuAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $labelRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Later');
        $labelsCollection = $labelRepo->getUserLabel($user->getId());
        $labels = array();

        foreach ($labelsCollection as $lab) {
            $label['id'] = $lab->getId();
            $label['name'] = $lab->getName();
            $label['count'] = $labelRepo->getUnreadCount($label['id']);
            $labels[] = $label;
        }

        $viewData = array(
            'labels' => $labels
        );

        return $viewData;
    }
}
