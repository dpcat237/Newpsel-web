<?php
namespace NPS\CoreBundle\Helper;

use NPS\CoreBundle\Constant\ImportConstants;
use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Config\Definition\Exception\Exception;

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

    /**
     * Convert a comma separated file into an associated array.
     * The first row should contain the array keys.
     *
     * @param string $filename Path to the CSV file
     * @param string $delimiter The separator used in the file
     * @return array
     * @link http://gist.github.com/385876
     * @author Jay Williams <http://myd3.com/>
     * @copyright Copyright (c) 2010, Jay Williams
     * @license http://www.opensource.org/licenses/mit-license.php MIT License
     */
    public static function csvToArray($filename='', $delimiter=',')
    {
        if(!file_exists($filename) || !is_readable($filename))
            return FALSE;

        $header = NULL;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
            {
                if(!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Prepare Instapaper items to import
     *
     * @param array $collection
     *
     * @return array
     */
    public static function prepareInstapaperItems($collection)
    {
        $items = array();
        foreach ($collection as $line) {
            if ($line['Folder'] != 'Unread') {
                continue;
            }
            $item = array(
                'title' => $line['Title'],
                'url' => $line['URL'],
                'date_add' => 0,
                'is_article' => 1
            );
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Prepare Pocket items to import
     *
     * @param stdObject $list
     *
     * @return array
     */
    public static function preparePocketItems($list)
    {
        $items = array();
        foreach ($list['list'] as $line) {
            $url = (array_key_exists('resolved_url', $line) && strlen($line['resolved_url']) > 1)? $line['resolved_url'] : $line['given_url'];
            if (!$url) {
                continue;
            }

            $title = (array_key_exists('resolved_title', $line) && strlen($line['resolved_title']) > 1)? $line['resolved_title'] : $line['given_title'];
            if (!$title) {
                $str = file_get_contents($url);
                if(strlen($str) < 1){
                    continue;
                }
                preg_match("/\<title\>(.*)\<\/title\>/",$str,$titleWeb);
                $title = $titleWeb[1];
            }

            $isArticle = (array_key_exists('is_article', $line))? $line['is_article'] : 0;
            $item = array(
                'title' => $title,
                'url' => $url,
                'date_add' => $line['time_added'],
                'is_article' => $isArticle
            );
            $items[] = $item;
        }

        return $items;
    }
}
