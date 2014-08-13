<?php
namespace NPS\CoreBundle\Helper;

use NPS\CoreBundle\Constant\ImportConstants;
use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class import static actions
 */
class ImportHelper extends Helper
{
    public $name = 'ImportHelper';


    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set filter data for import later items from Pocket
     *
     * @param Session $session
     * @param int     $tag
     * @param int     $favorite
     * @param int     $contentType
     * @param int     $labelId
     * @param string  $requestToken
     */
    public static function setPocketFilters(Session $session, $requestToken, $tag, $favorite, $contentType, $labelId)
    {
        $session->set(ImportConstants::SESSION_REQUEST_TOKEN, $requestToken);
        $session->set(ImportConstants::SESSION_TAG, $tag);
        $session->set(ImportConstants::SESSION_FAVORITE, $favorite);
        $session->set(ImportConstants::SESSION_CONTENT_TYPE, $contentType);
        $session->set(ImportConstants::SESSION_LABEL_ID, $labelId);
    }

    /**
     * Get filter options to import later items from Pocket
     *
     * @param Session $session
     *
     * @return array
     */
    public static function getFiltersPocket(Session $session)
    {
        $options = array(
            'state'      => 'unread',
            'sort'       => 'newest',
            'detailType' => 'simple'
        );

        //tag
        $tag = $session->get(ImportConstants::SESSION_TAG);
        if ($tag && $tag != ImportConstants::TAG_NOT) {
            $options['tag'] = $tag;
        } elseif ($tag == ImportConstants::TAG_NOT) {
            $options['tag'] = ImportConstants::TAG_NOT;
        }

        //favorite
        switch ($session->get(ImportConstants::SESSION_FAVORITE)) {
            case ImportConstants::FAVORITE_YES:
                $options['favorite'] = 1;
                break;
            case ImportConstants::FAVORITE_NOT:
                $options['favorite'] = 0;
                break;
        }

        //content type
        switch ($session->get(ImportConstants::SESSION_CONTENT_TYPE)) {
            case ImportConstants::CONTENT_ARTICLE:
                $options['contentType'] = 'article';
                break;
            case ImportConstants::CONTENT_VIDEO:
                $options['contentType'] = 'video';
                break;
        }

        return $options;
    }
}
