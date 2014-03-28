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
            new Mopa\Bundle\BootstrapBundle\MopaBootstrapBundle(),
            new NPS\CoreBundle\NPSCoreBundle(),
            new NPS\FrontendBundle\NPSFrontendBundle(),
            new NPS\ApiBundle\NPSApiBundle(),
            new FOS\ElasticaBundle\FOSElasticaBundle(),
            //new Bc\Bundle\BootstrapBundle\BcBootstrapBundle(),
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
