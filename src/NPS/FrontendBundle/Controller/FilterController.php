<?php

namespace NPS\FrontendBundle\Controller;

use NPS\CoreBundle\Entity\Filter;
use NPS\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * FilterController
 *
 * @Route("/filter")
 */
class FilterController extends Controller
{
    /**
     * List of user's filters
     *
     * @return array
     *
     * @Route("/list", name="filters_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function listAction()
    {
        $user = $this->getUser();
        $filterRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:Filter');
        $filters = $filterRepo->findByUser($user);

        $viewData = array(
            'filters' => $filters,
            'title' => 'Filters management'
        );

        return $viewData;
    }

    /**
     * Create filter
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/new", name="filter_create")
     * @Secure(roles="ROLE_USER")
     * @Template("NPSFrontendBundle:Filter:edit.html.twig")
     */
    public function createAction(Request $request)
    {
        if (!$this->getUser() instanceof User) {
            return new RedirectResponse($this->container->get('router')->generate('logout'));
        }

        $formType = $this->get('nps.form.type.filter');
        $form = $this->createForm($formType, new Filter());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $filterService = $this->get('nps.entity.filter');
            $filterService->createFilter($form);
            $route = $this->container->get('router')->generate('filters_list');

            return new RedirectResponse($route);
        }

        $viewData = array(
            'form' => $form->createView(),
            'action_url' => $this->container->get('router')->generate('filter_create')
        );

        return $viewData;
    }

    /**
     * Edit filter
     *
     * @param Request $request
     * @param Filter $filter
     *
     * @return Response
     *
     * @Route("/{filter_id}/edit", name="filter_edit")
     * @Secure(roles="ROLE_USER")
     * @Template()
     *
     * @ParamConverter("filter", class="NPSCoreBundle:Filter", options={"id": "filter_id"})
     */
    public function editAction(Request $request, Filter $filter)
    {
        foreach ($filter->getFilterFeeds() as $filterFeed) {
            if ($filterFeed->isDeleted()) {
                continue;
            }
            $filter->addFeedForm($filterFeed->getFeed());
        }

        $formType = $this->get('nps.form.type.filter');
        $form = $this->createForm($formType, $filter);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $filterService = $this->get('nps.entity.filter');
            $filterService->editFilter($filter, $form);
            $route = $this->container->get('router')->generate('filters_list');

            return new RedirectResponse($route);
        }

        $viewData = array(
            'form' => $form->createView(),
            'filter' => $filter,
            'action_url' => $this->container->get('router')->generate('filter_edit', array('filter_id' => $filter->getId()))
        );

        return $viewData;
    }

    /**
     * Delete filter
     *
     * @param Filter $filter
     *
     * @return Response
     *
     * @Route("/{filter_id}/delete", name="filter_delete")
     * @Secure(roles="ROLE_USER")
     *
     * @ParamConverter("filter", class="NPSCoreBundle:Filter", options={"id": "filter_id"})
     */
    public function deleteAction(Filter $filter)
    {
        $user = $this->getUser();
        $route = $this->container->get('router')->generate('filters_list');
        if ($filter->getUserId() != $user->getId()) {
            return new RedirectResponse($route);
        }

        $this->get('nps.entity.filter')->removeFilter($filter);

        return new RedirectResponse($route);
    }
}
