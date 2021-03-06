<?php

namespace NPS\ApiBundle\Controller;

use NPS\ApiBundle\DataTransformer\UserFiltersTransformer;
use NPS\ApiBundle\Services\Entity\FilterApiService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Class FilterController
 *
 * @package NPS\ApiBundle\Controller
 */
class FilterController extends ApiController
{
    /**
     * Add feeds to automatically add their articles to dictation
     *
     * @Rest\Post("/add/feed-to-dictation")
     * @Rest\View
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function automaticallyToDictationAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $this->getFilterApiService()->automaticallyToDictation($this->getDeviceUser($request), $json['feeds']);
    }

    /**
     * List filters
     *
     * @Rest\Get("/list")
     * @Rest\View
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listFiltersAction(Request $request)
    {
        return UserFiltersTransformer::transformList(
            $this->getFilterApiService()->getUserFilters($this->getDeviceUser($request))
        );
    }

    /**
     * @return FilterApiService
     */
    protected function getFilterApiService()
    {
        return $this->get('api.filter.service');
    }
}
