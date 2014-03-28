<?php

namespace NPS\FrontendBundle\Controller;

use NPS\CoreBundle\Entity\Filter;
use NPS\FrontendBundle\Form\Type\FilterEditType;
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
class FilterController extends BaseController
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
        $user = $this->get('security.context')->getToken()->getUser();
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
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/new", name="filter_create")
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function createAction(Request $request)
    {
        $filter = new Filter();
        $formType = new FilterEditType($filter);
        $form = $this->createForm($formType, $filter);
        $viewData = array(
            'form' => $form->createView(),
        );

        return $viewData;
    }
}
