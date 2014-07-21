<?php
namespace NPS\CoreBundle\Services;

use DOMAttr;
use DOMElement;
use DOMXPath;
use DOMNodeList;
use SimplePie_IRI;
use NPS\CoreBundle\Services\Crawler\libraries\contentextractor\ContentExtractor;
use NPS\CoreBundle\Services\Crawler\libraries\humblehttpagent\HumbleHttpAgent;
use NPS\CoreBundle\Services\Crawler\libraries\readability\Readability;


/**
 * CrawlerManager
 */
class CrawlerManager
{
    private $error;
    private $http;
    private $extractor;


    public function __construct()
    {
        $this->http = new HumbleHttpAgent();
        $this->extractor = new ContentExtractor('', dirname(__FILE__).'/site_config/standard');
    }

    /**
     * Get full article from url
     *
     * @param string $url
     *
     * @return string
     */
    public function getFullArticle($url) {
        $html = '';
        $this->error = false;
        $do_content_extraction = true;
        $extract_result = false;
        $this->error = false;

        if ($url && ($response = $this->http->get($url, true)) && ($response['status_code'] < 300)) {
            $effective_url = $response['effective_url'];
            // check if action defined for returned Content-Type
            $mime_info = $this->get_mime_action_info($response['headers']);
            if (isset($mime_info['action'])) {
                if ($mime_info['action'] != 'exclude' && $mime_info['action'] == 'link') {
                    if ($mime_info['type'] == 'image') {
                        $html = "<a href=\"$effective_url\"><img src=\"$effective_url\" alt=\"{$mime_info['name']}\" /></a>";
                    } else {
                        $html = "<a href=\"$effective_url\">Download {$mime_info['name']}</a>";
                    }
                    $extracted_title = $mime_info['name'];
                    $do_content_extraction = false;
                }
            }
            if ($do_content_extraction) {
                $html = $response['body'];
                // remove strange things
                $html = str_replace('</[>', '', $html);
                $html = $this->convert_to_utf8($html, $response['headers']);
                // check site config for single page URL - fetch it if found
                $is_single_page = false;

                if ($single_page_response = $this->getSinglePage($html, $effective_url)) {

                    $is_single_page = true;
                    $effective_url = $single_page_response['effective_url'];
                    // check if action defined for returned Content-Type
                    $mime_info = $this->get_mime_action_info($single_page_response['headers']);
                    if (isset($mime_info['action'])) {
                        if ($mime_info['action'] != 'exclude' && $mime_info['action'] == 'link') {
                            if ($mime_info['type'] == 'image') {
                                $html = "<a href=\"$effective_url\"><img src=\"$effective_url\" alt=\"{$mime_info['name']}\" /></a>";
                            } else {
                                $html = "<a href=\"$effective_url\">Download {$mime_info['name']}</a>";
                            }
                            $extracted_title = $mime_info['name'];
                            $do_content_extraction = false;
                        }
                    }
                    if ($do_content_extraction) {
                        $html = $single_page_response['body'];
                        // remove strange things
                        $html = str_replace('</[>', '', $html);
                        $html = $this->convert_to_utf8($html, $single_page_response['headers']);
                    }
                    unset($single_page_response);
                }
            }
            if ($do_content_extraction) {
                $extract_result = $this->extractor->process($html, $effective_url);
                $readability = $this->extractor->readability;
                $content_block = ($extract_result) ? $this->extractor->getContent() : null;
                $extracted_title = ($extract_result) ? $this->extractor->getTitle() : '';
                // Deal with multi-page articles
                //die('Next: '.$extractor->getNextPageUrl());
                $is_multi_page = (!$is_single_page && $extract_result && $this->extractor->getNextPageUrl());
                if ($is_multi_page) {
                    $multi_page_urls = array();
                    $multi_page_content = array();
                    while ($next_page_url = $this->extractor->getNextPageUrl()) {
                        // If we've got URL, resolve against $url
                        if ($next_page_url = $this->makeAbsoluteStr($effective_url, $next_page_url)) {
                            // check it's not what we have already!
                            if (!in_array($next_page_url, $multi_page_urls)) {
                                // it's not, so let's attempt to fetch it
                                $multi_page_urls[] = $next_page_url;
                                $_prev_ref = $this->http->referer;
                                if (($response = $this->http->get($next_page_url, true)) && $response['status_code'] < 300) {
                                    // make sure mime type is not something with a different action associated
                                    $page_mime_info = $this->get_mime_action_info($response['headers']);
                                    if (!isset($page_mime_info['action'])) {
                                        $html = $response['body'];
                                        // remove strange things
                                        $html = str_replace('</[>', '', $html);
                                        $html = $this->convert_to_utf8($html, $response['headers']);
                                        if ($this->extractor->process($html, $next_page_url)) {
                                            $multi_page_content[] = $this->extractor->getContent();
                                            continue;
                                        }
                                    }
                                }
                            }
                        }
                        // failed to process next_page_url, so cancel further requests
                        $multi_page_content = array();
                        break;
                    }
                    // did we successfully deal with this multi-page article?
                    if (empty($multi_page_content)) {
                        $_page = $readability->dom->createElement('p');
                        $_page->innerHTML = '<em>This article appears to continue on subsequent pages which we could not extract</em>';
                        $multi_page_content[] = $_page;
                    }
                    foreach ($multi_page_content as $_page) {
                        $_page = $content_block->ownerDocument->importNode($_page, true);
                        $content_block->appendChild($_page);
                    }
                    unset($multi_page_urls, $multi_page_content, $page_mime_info, $next_page_url, $_page);
                }
            }
        }

        if ($do_content_extraction) {
            // if we failed to extract content...
            if (!$extract_result) {
                //TODO: get text sample for language detection
                $this->error = true;
            } else {
                $readability->clean($content_block, 'select');
                $this->makeAbsolute($effective_url, $content_block);
                // normalise
                $content_block->normalize();
                // remove empty text nodes
                foreach ($content_block->childNodes as $_n) {
                    if ($_n->nodeType === XML_TEXT_NODE && trim($_n->textContent) == '') {
                        $content_block->removeChild($_n);
                    }
                }
                // remove nesting: <div><div><div><p>test</p></div></div></div> = <p>test</p>
                while ($content_block->childNodes->length == 1 && $content_block->firstChild->nodeType === XML_ELEMENT_NODE) {
                    // only follow these tag names
                    if (!in_array(strtolower($content_block->tagName), array('div', 'article', 'section', 'header', 'footer'))) break;
                    //$html = $content_block->firstChild->innerHTML; // FTR 2.9.5
                    $content_block = $content_block->firstChild;
                }
                // convert content block to HTML string
                // Need to preserve things like body: //img[@id='feature']
                if (in_array(strtolower($content_block->tagName), array('div', 'article', 'section', 'header', 'footer', 'li', 'td'))) {
                    $html = $content_block->innerHTML;
                    //} elseif (in_array(strtolower($content_block->tagName), array('td', 'li'))) {
                    //	$html = '<div>'.$content_block->innerHTML.'</div>';
                } else {
                    $html = $content_block->ownerDocument->saveXML($content_block); // essentially outerHTML
                }
                //unset($content_block);
                // post-processing cleanup
                $html = preg_replace('!<p>[\s\h\v]*</p>!u', '', $html);

                // get text sample for language detection
                //$text_sample = strip_tags(substr($html, 0, 500));
            }
        }
        $html =(!$this->error)? $html : '';

        return $html;
    }

    /**
     * Based on content-type http header, decide what to do
     *
     * @param string $headers HTTP headers string
     *
     * @return array array with keys: 'mime', 'type', 'subtype', 'action', 'name'
     * e.g. array('mime'=>'image/jpeg', 'type'=>'image', 'subtype'=>'jpeg', 'action'=>'link', 'name'=>'Image')
     */
    private function get_mime_action_info($headers) {
        // check if action defined for returned Content-Type
        $info = array();
        if (preg_match('!^Content-Type:\s*(([-\w]+)/([-\w\+]+))!im', $headers, $match)) {
            // look for full mime type (e.g. image/jpeg) or just type (e.g. image)
            // match[1] = full mime type, e.g. image/jpeg
            // match[2] = first part, e.g. image
            // match[3] = last part, e.g. jpeg
            $info['mime'] = strtolower(trim($match[1]));
            $info['type'] = strtolower(trim($match[2]));
            $info['subtype'] = strtolower(trim($match[3]));
        }
        return $info;
    }

    /**
     * Convert $html to UTF8
     * (uses HTTP headers and HTML to find encoding)
     * adapted from http://stackoverflow.com/questions/910793/php-detect-encoding-and-make-everything-utf-8
     *
     * @param string $html
     * @param string $header
     *
     * @return string
     */
    private function convert_to_utf8($html, $header=null) {
        $encoding = null;
        if ($html || $header) {
            if (is_array($header)) $header = implode("\n", $header);
            if (!$header || !preg_match_all('/^Content-Type:\s+([^;]+)(?:;\s*charset=["\']?([^;"\'\n]*))?/im', $header, $match, PREG_SET_ORDER)) {
                // error parsing the response
            } else {
                $match = end($match); // get last matched element (in case of redirects)
                if (isset($match[2])) $encoding = trim($match[2], "\"' \r\n\0\x0B\t");
            }
            // TODO: check to see if encoding is supported (can we convert it?)
            // If it's not, result will be empty string.
            // For now we'll check for invalid encoding types returned by some sites, e.g. 'none'
            // Problem URL: http://facta.co.jp/blog/archives/20111026001026.html
            if (!$encoding || $encoding == 'none') {
                // search for encoding in HTML - only look at the first 50000 characters
                // Why 50000? See, for example, http://www.lemonde.fr/festival-de-cannes/article/2012/05/23/deux-cretes-en-goguette-sur-la-croisette_1705732_766360.html
                // TODO: improve this so it looks at smaller chunks first
                $html_head = substr($html, 0, 50000);
                if (preg_match('/^<\?xml\s+version=(?:"[^"]*"|\'[^\']*\')\s+encoding=("[^"]*"|\'[^\']*\')/s', $html_head, $match)) {
                    $encoding = trim($match[1], '"\'');
                } elseif (preg_match('/<meta\s+http-equiv=["\']?Content-Type["\']? content=["\'][^;]+;\s*charset=["\']?([^;"\'>]+)/i', $html_head, $match)) {
                    $encoding = trim($match[1]);
                } elseif (preg_match_all('/<meta\s+([^>]+)>/i', $html_head, $match)) {
                    foreach ($match[1] as $_test) {
                        if (preg_match('/charset=["\']?([^"\']+)/i', $_test, $_m)) {
                            $encoding = trim($_m[1]);
                            break;
                        }
                    }
                }
            }
            if (isset($encoding)) $encoding = trim($encoding);
            // trim is important here!
            if (!$encoding || (strtolower($encoding) == 'iso-8859-1')) {
                // replace MS Word smart qutoes
                $trans = array();
                $trans[chr(130)] = '&sbquo;';    // Single Low-9 Quotation Mark
                $trans[chr(131)] = '&fnof;';    // Latin Small Letter F With Hook
                $trans[chr(132)] = '&bdquo;';    // Double Low-9 Quotation Mark
                $trans[chr(133)] = '&hellip;';    // Horizontal Ellipsis
                $trans[chr(134)] = '&dagger;';    // Dagger
                $trans[chr(135)] = '&Dagger;';    // Double Dagger
                $trans[chr(136)] = '&circ;';    // Modifier Letter Circumflex Accent
                $trans[chr(137)] = '&permil;';    // Per Mille Sign
                $trans[chr(138)] = '&Scaron;';    // Latin Capital Letter S With Caron
                $trans[chr(139)] = '&lsaquo;';    // Single Left-Pointing Angle Quotation Mark
                $trans[chr(140)] = '&OElig;';    // Latin Capital Ligature OE
                $trans[chr(145)] = '&lsquo;';    // Left Single Quotation Mark
                $trans[chr(146)] = '&rsquo;';    // Right Single Quotation Mark
                $trans[chr(147)] = '&ldquo;';    // Left Double Quotation Mark
                $trans[chr(148)] = '&rdquo;';    // Right Double Quotation Mark
                $trans[chr(149)] = '&bull;';    // Bullet
                $trans[chr(150)] = '&ndash;';    // En Dash
                $trans[chr(151)] = '&mdash;';    // Em Dash
                $trans[chr(152)] = '&tilde;';    // Small Tilde
                $trans[chr(153)] = '&trade;';    // Trade Mark Sign
                $trans[chr(154)] = '&scaron;';    // Latin Small Letter S With Caron
                $trans[chr(155)] = '&rsaquo;';    // Single Right-Pointing Angle Quotation Mark
                $trans[chr(156)] = '&oelig;';    // Latin Small Ligature OE
                $trans[chr(159)] = '&Yuml;';    // Latin Capital Letter Y With Diaeresis
                $html = strtr($html, $trans);
            }
            if (!$encoding) {
                $encoding = 'utf-8';
            } else {
                if (strtolower($encoding) != 'utf-8') {
                    $html = \SimplePie_Misc::change_encoding($html, $encoding, 'utf-8');
                }
            }
        }

        return $html;
    }

    /**
     * Returns single page response, or false if not found
     *
     * @param string $html
     * @param string $url
     *
     * @return bool
     */
    private function getSinglePage($html, $url) {
        $site_config = $this->extractor->buildSiteConfig($url, $html);
        $splink = null;
        if (!empty($site_config->single_page_link)) {
            $splink = $site_config->single_page_link;
        } elseif (!empty($site_config->single_page_link_in_feed)) {
            // single page link xpath is targeted at feed
            $splink = $site_config->single_page_link_in_feed;
            // so let's replace HTML with feed item description
            //$html = $item->get_description();
        }
        if (isset($splink)) {
            // Build DOM tree from HTML
            $readability = new Readability($html, $url);
            $xpath = new DOMXPath($readability->dom);
            // Loop through single_page_link xpath expressions
            $single_page_url = null;
            foreach ($splink as $pattern) {
                $elems = @$xpath->evaluate($pattern, $readability->dom);
                if (is_string($elems)) {
                    $single_page_url = trim($elems);
                    break;
                } elseif ($elems instanceof DOMNodeList && $elems->length > 0) {
                    foreach ($elems as $item) {
                        if ($item instanceof DOMElement && $item->hasAttribute('href')) {
                            $single_page_url = $item->getAttribute('href');
                            break 2;
                        } elseif ($item instanceof DOMAttr && $item->value) {
                            $single_page_url = $item->value;
                            break 2;
                        }
                    }
                }
            }
            // If we've got URL, resolve against $url
            if (isset($single_page_url) && ($single_page_url = $this->makeAbsoluteStr($url, $single_page_url))) {
                // check it's not what we have already!
                if ($single_page_url != $url) {
                    // it's not, so let's try to fetch it...
                    $_prev_ref = $this->http->referer;
                    $this->http->referer = $single_page_url;
                    if (($response = $this->http->get($single_page_url, true)) && $response['status_code'] < 300) {
                        $this->http->referer = $_prev_ref;

                        return $response;
                    }
                    $this->http->referer = $_prev_ref;
                }
            }
        }
        return false;
    }

    /**
     * Make absolute string
     *
     * @param string $base base url
     * @param string $url
     *
     * @return bool|false|\IRI
     */
    private function makeAbsoluteStr($base, $url) {
        $base = new SimplePie_IRI($base);
        // remove '//' in URL path (causes URLs not to resolve properly)
        if (isset($base->path)) $base->path = preg_replace('!//+!', '/', $base->path);
        if (preg_match('!^https?://!i', $url)) {
            // already absolute
            return $url;
        } else {
            if ($absolute = SimplePie_IRI::absolutize($base, $url)) {
                return $absolute;
            }

            return false;
        }
    }

    /**
     * Make absolute
     *
     * @param string $base
     * @param string $elem content block
     */
    private function makeAbsolute($base, $elem) {
        $base = new SimplePie_IRI($base);
        // remove '//' in URL path (used to prevent URLs from resolving properly)
        // TODO: check if this is still the case
        if (isset($base->path)) $base->path = preg_replace('!//+!', '/', $base->path);
        foreach(array('a'=>'href', 'img'=>'src') as $tag => $attr) {
            $elems = $elem->getElementsByTagName($tag);
            for ($i = $elems->length-1; $i >= 0; $i--) {
                $e = $elems->item($i);
                //$e->parentNode->replaceChild($articleContent->ownerDocument->createTextNode($e->textContent), $e);
                $this->makeAbsoluteAttr($base, $e, $attr);
            }
            if (strtolower($elem->tagName) == $tag) {
                $this->makeAbsoluteAttr($base, $elem, $attr);
            }
        }
    }

    /**
     * Make absolute attribute
     *
     * @param string $base
     * @param $e
     * @param $attr
     */
    private function makeAbsoluteAttr($base, $e, $attr) {
        if ($e->hasAttribute($attr)) {
            // Trim leading and trailing white space. I don't really like this but
            // unfortunately it does appear on some sites. e.g.  <img src=" /path/to/image.jpg" />
            $url = trim(str_replace('%20', ' ', $e->getAttribute($attr)));
            $url = str_replace(' ', '%20', $url);
            if (!preg_match('!https?://!i', $url)) {
                if ($absolute = SimplePie_IRI::absolutize($base, $url)) {
                    $e->setAttribute($attr, $absolute);
                }
            }
        }
    }
}
