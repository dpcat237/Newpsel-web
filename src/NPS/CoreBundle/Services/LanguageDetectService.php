<?php
namespace NPS\CoreBundle\Services;

use Dpcat237\LanguageDetectBundle\Library\LanguageDetect;
use Eko\GoogleTranslateBundle\Translate\Method\Detector;

/**
 * QueueLauncherService
 */
class LanguageDetectService
{
    /**
     * @var GoogleTranslateBundle
     */
    private $googleDetector;

    /**
     * @var Pear Detector
     */
    private $pearDetector;


    /**
     * @param Detector $googleDetector  Detector
     */
    public function __construct(Detector $googleDetector)
    {
        $this->googleDetector = $googleDetector;
        $this->pearDetector = new LanguageDetect();
        $this->pearDetector->setNameMode(2); // return ISO 639-1 codes (e.g. "en")
    }

    /**
     * Detect language from string
     *
     * @param string $content
     *
     * @return string
     */
    public function detectLanguage($content)
    {
        $languageCode = '';
        $code = $this->pearDetector->detect($content, 1);
        if (count($code)) {
            $languageCode = key($code);
        }
        if (strlen($languageCode) == 2) {
            return $languageCode;
        }

        return $this->googleDetector->detect($content);
    }
}
