<?php

namespace NPS\FrontendBundle\Controller;

use NPS\FrontendBundle\Form\Type\ImportOpmlType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Session\Session;
use Celd\Opml\Importer as OpmlImporter;
use Celd\Opml\Model\FeedList as OpmlFeedList;
use Celd\Opml\Model\Feed as OpmlFeed;
use Celd\Opml\Importer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Duellsy\Pockpack\Pockpack;
use Duellsy\Pockpack\PockpackAuth;
use NPS\CoreBundle\Constant\ImportConstants;
use NPS\CoreBundle\Helper\ImportHelper;


/**
 * ImportExportController
 *
 * @Route("/impexp")
 */
class ImportExportController extends Controller
{
    /**
     * Subscribe to newsletter
     *
     * @return Response
     * @Route("/preference/imp_exp", name="preference_imp_exp")
     * @Secure(roles="ROLE_USER")
     * @Template("NPSFrontendBundle:Preference:imp_exp.html.twig")
     */
    public function impExpAction()
    {
        $opmlForm = $this->createForm(ImportOpmlType::class);

        $pocketType = $this->get('nps.form.type.import.pocket');
        $pocketForm = $this->createForm($pocketType);

        $instapaperType = $this->get('nps.form.type.import.instapaper');
        $instapaperForm = $this->createForm($instapaperType);

        $readabilityType = $this->get('nps.form.type.import.readability');
        $readabilityForm = $this->createForm($readabilityType);

        $viewData = array(
            'opml_form' => $opmlForm->createView(),
            'pocket_form' => $pocketForm->createView(),
            'instapaper_form' => $instapaperForm->createView(),
            'readability_form' => $readabilityForm->createView(),
        );

        return $viewData;
    }

    /**
     * Import feeds from OPML file
     *
     * @param Request $request
     *
     * @Route("/opml_import", name="opml_import")
     * @Secure(roles="ROLE_USER")
     * @Method("POST")
     *
     * @return RedirectResponse
     */
    public function importOpmlAction(Request $request)
    {
        $opmlForm = $this->createForm(ImportOpmlType::class);
        $opmlForm->handleRequest($request);
        $opmlFile = $opmlForm->getData()['opml_file'];

        /** @var $opmlFile UploadedFile */
        if (!$opmlForm->isValid() || is_null($opmlFile) || !($opmlFile instanceof UploadedFile)) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_opml');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }


        $importer = new Importer(file_get_contents($opmlFile->getRealPath()));
        $feedList = $importer->getFeedList();

        /** if aren't feeds in file */
        $feedsCount = count($feedList->getItems());
        if ($feedsCount < 1) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_quantity_OPML');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $user = $this->getUser();
        $downloadFeeds = $this->get('download_feeds');
        foreach ($feedList->getItems() as $item) {
            if ($item->getType() == 'category') {
                foreach($item->getFeeds() as $feed) {
                    $downloadFeeds->addFeed($feed->getXmlUrl(), $user);
                }

                continue;
            }

            $downloadFeeds->addFeed($item->getXmlUrl(), $user);
        }
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans('_Success_opml', array('%quantity%' => $feedsCount)));

        return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
    }

    /**
     * Export feeds to OPML file
     *
     * @Route("/opml_import", name="opml_export")
     * @Secure(roles="ROLE_USER")
     */
    public function exportOpmlAction()
    {
        $filename = "newpsel.opml";
        $user = $this->getUser();
        $userFeedRepo = $this->getDoctrine()->getRepository('NPSCoreBundle:UserFeed');
        $userFeeds = $userFeedRepo->getUserFeeds($user->getId());

        $feedList = new OpmlFeedList();
        foreach($userFeeds as $userFeed) {
            $feed = $userFeed->getFeed();
            $opmlFeed = new OpmlFeed();
            $opmlFeed->setTitle($feed->getTitle());
            $opmlFeed->setXmlUrl($feed->getUrl());
            $opmlFeed->setType('rss');
            $opmlFeed->setHtmlUrl($feed->getWebsite());

            $feedList->addItem($opmlFeed);
        }

        $response = new Response();
        $importer = new OpmlImporter();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename);
        $response->setContent($importer->export($feedList));

        return $response;
    }

    /**
     * Request for GetPocket auth to import later items
     *
     * @param Request  $request  Request
     *
     * @Route("/label/import/getpocket/request", name="import_getpocket_request")
     * @Secure(roles="ROLE_USER")
     * @Method("POST")
     *
     * @return RedirectResponse
     */
    public function getpocketImportRequestAction(Request $request)
    {
        $pocketType = $this->get('nps.form.type.import.pocket');
        $pocketForm = $this->createForm($pocketType);
        $pocketForm->handleRequest($request);
        if (!$pocketForm->isValid()) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_pocket_form');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $pockpathAuth = new PockpackAuth();
        $requestToken = $pockpathAuth->connect($this->container->getParameter('getpocket_key'));
        if (!$requestToken) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_pocket_token_request');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $pocketData = $pocketForm->getData();
        ImportHelper::setPocketFilters(new Session(), $requestToken, $pocketData['tag'], $pocketData['favorite'], $pocketData['contentType'], $pocketData['later']->getId());

        $url = "https://getpocket.com/auth/authorize?request_token=".$requestToken."&redirect_uri=".$this->generateUrl('import_getpocket', array(), true);

        return new RedirectResponse($url);
    }

    /**
     * Import later items from GetPocket
     *
     * @param Request  $request  Request
     *
     * @Route("/label/import/getpocket", name="import_getpocket")
     * @Secure(roles="ROLE_USER")
     *
     * @return RedirectResponse
     */
    public function getpocketImportAction(Request $request)
    {
        $session = new Session();
        $requestToken = $session->get(ImportConstants::SESSION_REQUEST_TOKEN);
        $consumerKey = $this->container->getParameter('getpocket_key');

        $pockpackAuth = new PockpackAuth();
        $accessToken = $pockpackAuth->receiveToken($this->container->getParameter('getpocket_key'), $requestToken);
        if (!$accessToken) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_pocket_token_request');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $pockpack = new Pockpack($consumerKey, $accessToken);
        $options = ImportHelper::getFiltersPocket($session);
        $list = $pockpack->retrieve($options, true);
        if ($list['status'] == 2) { // zero results
            $request->getSession()->getFlashBag()->add('alert', '_Invalid_pocket_filter');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $user = $this->getUser();
        $labelId = $session->get(ImportConstants::SESSION_LABEL_ID);
        $items = ImportHelper::preparePocketItems($list);
        $this->get('nps.entity.later_item')->prepareToImport($user->getId(), $labelId, $items);
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans('_Valid_will_import'));

        return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
    }

    /**
     * Import later items from Instapaper
     *
     * @param Request $request
     *
     * @Route("/label/import/instapaper", name="import_instapaper")
     * @Secure(roles="ROLE_USER")
     * @Method("POST")
     *
     * @return RedirectResponse
     */
    public function importInstapaperAction(Request $request)
    {
        $instapaperType = $this->get('nps.form.type.import.instapaper');
        $instapaperForm = $this->createForm($instapaperType);
        $instapaperForm->handleRequest($request);
        $instapaperFile = $instapaperForm->getData()['csv_file'];
        $labelId = $instapaperForm->getData()['later']->getId();

        if (!$instapaperForm->isValid() || is_null($instapaperFile) || !($instapaperFile instanceof UploadedFile)) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_csv');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $user = $this->getUser();
        $items = ImportHelper::prepareInstapaperItems(ImportHelper::csvToArray($instapaperFile->getRealPath()));
        if (count($items) < 1) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_quantity');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $this->get('nps.entity.later_item')->prepareToImport($user->getId(), $labelId, $items);
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans('_Valid_will_import'));

        return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
    }

    /**
     * Import later items from Readability
     *
     * @param Request $request
     *
     * @Route("/label/import/readability", name="import_readability")
     * @Secure(roles="ROLE_USER")
     * @Method("POST")
     *
     * @return RedirectResponse
     */
    public function importReadabilityAction(Request $request)
    {
        $readabilityType = $this->get('nps.form.type.import.readability');
        $readabilityForm = $this->createForm($readabilityType);
        $readabilityForm->handleRequest($request);
        $readabilityFile = $readabilityForm->getData()['json_file'];
        $labelId = $readabilityForm->getData()['later']->getId();

        if (!$readabilityFile->isValid() || is_null($readabilityFile) || !($readabilityFile instanceof UploadedFile)) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_csv');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $user = $this->getUser();
        $items = ImportHelper::prepareReadabilityItems(json_decode(file_get_contents($readabilityFile->getRealPath()), true));
        if (count($items) < 1) {
            $request->getSession()->getFlashBag()->add('error', '_Invalid_quantity');

            return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
        }

        $this->get('nps.entity.later_item')->prepareToImport($user->getId(), $labelId, $items);
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans('_Valid_will_import'));

        return new RedirectResponse($this->get('router')->generate('preference_imp_exp'));
    }
}
