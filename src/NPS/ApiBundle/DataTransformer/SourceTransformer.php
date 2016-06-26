<?php

namespace NPS\ApiBundle\DataTransformer;

use NPS\CoreBundle\Entity\Source;
use NPS\CoreBundle\Entity\SourceCategory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SourceTransformer
 *
 * @package NPS\ApiBundle\DataTransformer
 */
class SourceTransformer
{
    protected $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    protected function transform(SourceCategory $sourceCategory)
    {
        $sources = $this->getSources($sourceCategory->getSources());
        if (!$sources) {
            return null;
        }

        return [
            'categories' => [
                'id' => $sourceCategory->getId(),
                'name' => $sourceCategory->getName(),
                'image' => $this->request->getUriForPath('/'.SourceCategory::PATH_IMAGES.'/'.$sourceCategory->getImageName()),
                'date_up' => $sourceCategory->getDateUp(),
                'sources' => $this->getSources($sourceCategory->getSources()),
            ]
        ];
    }

    public function transformList($sourceCategories)
    {
        return array_map(
            function (SourceCategory $sourceCategory) {
                return $this->transform($sourceCategory);
            },
            $sourceCategories
        );
    }

    protected function getSources($sources)
    {
        $response = [];
        /** @var Source $source */
        foreach ($sources as $source) {
            $response[] = [
                'id' => $source->getId(),
                'name' => $source->getName(),
                'web' => $source->getWeb(),
                'feed_url' => $source->getFeedUrl(),
                'date_up' => $source->getDateUp()
            ];
        }

        return $response;
    }
}
