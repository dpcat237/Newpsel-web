<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NPS\CoreBundle\Entity\Later;
use NPS\FrontendBundle\Form\Type\LaterEditType;

/**
 * ItemController
 */
class LabelController extends BaseController
{
    /**
     * List of user's labels
     * @param Request $request
     *
     * @return array
     * @Route("/label/list", name="labels_list")
     * @Template()
     */
    public function listAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('welcome'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $labelRepo = $this->em->getRepository('NPSCoreBundle:Later');
            $labels = $labelRepo->getUserLabel($user->getId());

            $viewData = array(
                'labels' => $labels,
                'title' => 'Labels management'
            );

            return $viewData;
        }
    }

    /**
     * Create label
     * @param Request $request
     *
     * @return Response
     * @Route("/label/new", name="label_create")
     */
    public function createAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('homepage'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $labelRepo = $this->em->getRepository('NPSCoreBundle:Later');
            $labelRepo->createLabel($user, $request->get('label'));

            return new RedirectResponse($this->router->generate('labels_list'));
        }
    }

    /**
     * Create label
     * @param Later $label
     *
     * @return Response
     * @Route("/label/{label_id}/delete", name="label_delete")
     * @ParamConverter("label", class="NPSCoreBundle:Later", options={"id": "label_id"})
     */
    public function deleteAction(Later $label)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('homepage'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            if ($label->getUserId() == $user->getId()) {
                $this->em->remove($label);
                $this->em->flush();
            }

            return new RedirectResponse($this->router->generate('labels_list'));
        }
    }

    /**
     * Create label
     * @param Request $request
     * @param Later $label
     *
     * @return Response
     * @Route("/label/{id}/edit", name="label_edit")
     * @ParamConverter("label", class="NPSCoreBundle:Later")
     * @Template()
     */
    public function editAction(Request $request, Later $label)
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('homepage'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            if ($label->getUserId() == $user->getId()) {
                $formType = new LaterEditType($label);
                $form = $this->createForm($formType, $label);

                if ($request->getMethod() == 'POST') {
                    $this->saveGenericForm('Later', $form->handleRequest($request), 103);

                    return new RedirectResponse($this->router->generate('labels_list'));
                }

                $viewData = array(
                    'title' => 'Edit label',
                    'form' => $form->createView(),
                    'label' => $label,
                );

                return $viewData;
            }

            return new RedirectResponse($this->router->generate('labels_list'));
        }
    }

    /**
     * Menu build
     *
     * @Template()
     */
    public function menuAction()
    {
        if (!$this->get('security.context')->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('welcome'));
        } else {
            $user = $this->get('security.context')->getToken()->getUser();
            $em = $this->getDoctrine()->getManager();
            $labelRepo = $em->getRepository('NPSCoreBundle:Later');

            $labels = array();
            foreach ($user->getLaters() as $lab) {
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
}
