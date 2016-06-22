<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new Fkr\SimplePieBundle\FkrSimplePieBundle(),
            new Snc\RedisBundle\SncRedisBundle(),
            new Mmoreram\RSQueueBundle\RSQueueBundle(),
            new Undf\AngularJsBundle\UndfAngularJsBundle(),
            new NPS\CoreBundle\NPSCoreBundle(),
            new NPS\FrontendBundle\NPSFrontendBundle(),
            new NPS\ApiBundle\NPSApiBundle(),
            new Eko\GoogleTranslateBundle\EkoGoogleTranslateBundle(),
            new Endroid\Bundle\GcmBundle\EndroidGcmBundle(),
            new HWI\Bundle\OAuthBundle\HWIOAuthBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new Dpcat237\LanguageDetectBundle\Dpcat237LanguageDetectBundle(),
            new Dpcat237\CrawlerBundle\Dpcat237CrawlerBundle(),
            new Bmatzner\FontAwesomeBundle\BmatznerFontAwesomeBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),

            /** Sonata */
            // The admin requires some twig functions defined in the security
            // bundle, like is_granted. Register this bundle if it wasn't the case
            // already.
            // These are the other bundles the SonataAdminBundle relies on
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            new Sonata\EasyExtendsBundle\SonataEasyExtendsBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            // And finally, the storage and SonataAdminBundle
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            new Sonata\AdminBundle\SonataAdminBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new Sonata\UserBundle\SonataUserBundle('FOSUserBundle'),
            new NPS\AdminBundle\NPSAdminBundle(),

            //new FOS\ElasticaBundle\FOSElasticaBundle(),
            //new BCC\CronManagerBundle\BCCCronManagerBundle(),
            //new JMS\SerializerBundle\JMSSerializerBundle()
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            //$bundles[] = new Mattsches\VersionEyeBundle\MattschesVersionEyeBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
