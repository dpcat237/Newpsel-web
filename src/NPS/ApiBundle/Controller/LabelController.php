<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;

/**
 * LabelController
 */
class LabelController extends ApiController
{
    /**
     * List of tags
     *
     * @Rest\Post("/sync")
     * @Rest\View
     *
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function syncLabelsAction(Request $request)
    {
        $json         = json_decode($request->getContent(), true);
        $labelService = $this->get('api.label.service');
        $tags = (isset($json['tags'])) ? $json['tags'] : [];

        return $labelService->syncTags($this->getDeviceUser($request), $tags);
    }
}
