<?php

namespace NPS\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * FeedController
 */
class FeedController extends BaseController
{
    /**
     * List of feeds
     * @param Request $request the current request
     *
     * @return Response
     */
    public function listAction(Request $request)
    {
        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {
            $objectName = 'Feed';
            $routeName = 'feed';
            $routeNameMany = 'feeds';

            return $this->genericListRender($objectName, $routeName, $routeNameMany, $request->get('page'));
        }
    }

    /**
     * Edit/create form of feeds [GET]
     * Route defined in routing.yml
     * @param Request $request the current request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request)
    {
        /*if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($this->router->generate('login'));
        } else {*/
            $objectId = $request->get('id');
            $objectName = 'Feed';
            $routeName = 'feed';
            $routeNameMany = 'feeds';
            $objectClass = 'NPS\ModelBundle\Entity\Feed';
            $objectTypeClass = 'NPS\FrontendBundle\Form\Type\FeedType';

            //depends if it's edit or creation
            $form = $this->createFormEdit($objectId, $objectName, $objectClass, $objectTypeClass);

            return $this->createFormResponse($objectName, $routeName, $routeNameMany, $form);
        //}
    }

    /**
     * Edit/create process form of feeds [POST]
     * Route defined in routing.yml
     * @param Request $request the current request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editProcessAction(Request $request)
    {
        /**
         * http://simplepie.org/wiki/tutorial/start
         * http://simplepie.org/wiki/tutorial/only_display_items_from_the_last_24_hours
         * http://simplepie.org/wiki/reference/start
         * http://net.tutsplus.com/articles/news/extending-simplepie-to-parse-unique-rss-feeds/
         */


        /** Feed
         * http://feeds.feedburner.com/nettuts
         * http://feeds2.feedburner.com/Wwwhatsnew
         * http://feeds.weblogssl.com/xataka2
         * http://feeds.weblogssl.com/xatakafoto
         * http://feeds.weblogssl.com/xatakandroid
         */

        /** XML
         * http://www.mobile-review.com/podcasts/rss.xml
         */

        /*$feed = $this->get('fkr_simple_pie.rss');
        $feed->set_feed_url('http://www.androidcentral.com/rss.xml');
        $feed->set_parser_class();
        $feed->enable_xml_dump();
        $feed->init();
        $new = array();*/

        /*
        //echo "<br />bitrate: ".$feed->get_bitrate();
        //echo "<br />caption: ".$feed->get_caption();
        //echo "<br />captions: ".$feed->get_captions();
        echo "<br />category: ".$feed->get_category();
        echo "<br />categories: ".$feed->get_categories();
        //echo "<br />channels: ".$feed->get_channels();
        echo "<br />copyright: ".$feed->get_copyright();
        //echo "<br />credit: ".$feed->get_credit();
        //echo "<br />credits: ".$feed->get_credits();
        echo "<br />description: ".$feed->get_description();
        //echo "<br />duration: ".$feed->get_duration();
        //echo "<br />expression: ".$feed->get_expression();
        //echo "<br />framerate: ".$feed->get_framerate();
        //echo "<br />hash: ".$feed->get_hash();
        //echo "<br />hashes: ".$feed->get_hashes();
        //echo "<br />height: ".$feed->get_height();
        //echo "<br />keyword: ".$feed->get_keyword();
        //echo "<br />keywords: ".$feed->get_keywords();
        echo "<br />language: ".$feed->get_language();
        //echo "<br />medium: ".$feed->get_medium();
        //echo "<br />player: ".$feed->get_player();
        //echo "<br />rating: ".$feed->get_rating();
        //echo "<br />ratings: ".$feed->get_ratings();
        //echo "<br />restriction: ".$feed->get_restriction();
        //echo "<br />sampling_rate: ".$feed->get_sampling_rate();
        //echo "<br />thumbnail: ".$feed->get_thumbnail();
        //echo "<br />thumbnails: ".$feed->get_thumbnails();
        echo "<br />title: ".$feed->get_title();
        //echo "<br />width: ".$feed->get_width();

        echo "<br />favicon: ".$feed->get_favicon();
        echo "<br />encoding: ".$feed->get_encoding();
        echo "<br />type: ".$feed->get_type();
        echo 'tut: end'; exit();*/

        /*foreach ($feed->get_items() as $item) {

            // Calculate 24 hours ago
            $yesterday = time() - (2*24*60*60);

            // Compare the timestamp of the feed item with 24 hours ago.
            if ($item->get_date('U') > $yesterday) {

                // If the item was posted within the last 24 hours, store the item in our array we set up.
                $new[] = $item;
            }
        }*/

        // Loop through all of the items in the new array and display whatever we want.
        //echo 'tut: '.count($new); exit();
        /*foreach($new as $item) {
            echo '<h3>' . $item->get_title() . '</h3>';
            echo '<p>' . $item->get_date('j M Y, H:i:s O') . '</p>';
            echo $item->get_description();
            echo '<hr />';
        }*/
        /*foreach($new as $item) {
            echo "<br />author: ".$item->get_author();
            echo "<br />authors: ".$item->get_authors();
            echo "<br />categories: ".$item->get_categories();
            echo "<br />category: ".$item->get_category();
            echo "<br />contributor: ".$item->get_contributor();
            echo "<br />contributors: ".$item->get_contributors();
            echo "<br />copyright: ".$item->get_copyright();
            echo "<br />description: ".$item->get_description();
            //echo "<br />image_url: ".$item->get_image_url();
            //echo "<br />item: ".$item->get_item();
            //echo "<br />language: ".$item->get_language();
            echo "<br />link: ".$item->get_link();
            echo "<br />links: ".$item->get_links();
            echo "<br />permalink: ".$item->get_permalink();
            echo "<br />title: ".$item->get_title();
            echo "<br />date: ".$item->get_date();
        }

        echo 'tut: '; exit();*/





















        //depends if it's edit or creation
        $objectId = $request->get('id');
        $objectName = 'Feed';
        $routeName = 'feed';
        $routeNameMany = 'feeds';
        $objectClass = 'NPS\ModelBundle\Entity\Feed';
        $objectTypeClass = 'NPS\FrontendBundle\Form\Type\FeedType';
        $form = $this->createFormEdit($objectId, $objectName, $objectClass, $objectTypeClass);
        $form->bind($request);
        $this->createNotification($objectName);
        $formObject = $form->getData();

        if ($form->isValid()) {
            $feedRepo = $this->em->getRepository('NPSModelBundle:Feed');
            $rss = $this->get('fkr_simple_pie.rss');
            $feedRepo->setRss($rss);
            $checkCreate = $feedRepo->createFeed($formObject->getUrl());


            /*echo 'tut: end'; exit();
            $feedRepo->updateFeedData($rss, 1, true); //TODO: remove line
            if (!$checkCreate['error']) {
                $feedRepo->updateFeedData($checkCreate['feed']->getId(), true);
            } else {
                $this->notification->setNotification($checkCreate['error']);
            }




            $this->saveObject($formObject);*/
        } else {
            $this->notification->setNotification(201);
        }
        echo 'tut: oki'; exit();
        $this->setNotificationMessage();

        return $this->createFormResponse($objectName, $routeName, $routeNameMany, $form);
    }

    /**
     * Change stat of feed: enabled/disabled
     * @param Request $request the current request
     *
     * @return Response
     */
    public function enabledStateAction(Request $request)
    {
        if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $objectName = 'Feed';
            $objectClass = 'NPS\ModelBundle\Entity\Feed';
            $check = $this->genericChangeObjectStatus($objectName, $objectClass, $request->get('id'));
        } else {
            $check = false;
        }

        if ($check) {
            return new RedirectResponse($this->router->generate('feeds'));
        } else {
            return new RedirectResponse($this->router->generate('login'));
        }
    }

}
