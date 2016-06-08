<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * LabelController
 */
class LabelController extends ApiController
{
    /**
     * List of tags
     *
     * @Rest\Post("/sync_labels")
     * @ApiDoc(
     *  description="List of tags",
     *  section="Tag area",
     *  resource=true,
     *  output = {
     *     "class" = "\NPS\CoreBundle\Entity\Label",
     *     "collection" = true,
     *     "parsers" = {"Nelmio\ApiDocBundle\Parser\JmsMetadataParser", "Nelmio\ApiDocBundle\Parser\CollectionParser"}
     *  },
     *  statusCodes={
     *      200="Successfully",
     *      401="Authentication failed",
     *      405="Bad request method"
     *  },
     *  authentication=true,
     *  authenticationRoles={"ROLE_USER"},
     *  tags={"experimental"}
     * )
     *
     * @param Request $request the current request
     *
     * @return JsonResponse
     */
    public function syncLabelsAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);
        $labelService = $this->get('api.label.service');
        $responseData = $labelService->syncLabels($json['appKey'], $json['labels']);
        if ($responseData['error']) {
            return $this->plainResponse($responseData['error']);
        }

        return new JsonResponse($responseData['labels']);
    }
}
