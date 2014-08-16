<?php

namespace NPS\CoreBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\HttpFoundation\Session\Session;
use NPS\CoreBundle\Entity\User;

/**
 * Class OAuthProvider
 *
 * @package NPS\CoreBundle\Provider
 */
class OAuthProvider extends OAuthUserProvider
{
    /**
     * @var Registry
     */
    private  $doctrine;

    /**
     * @var EncoderFactory
     */
    private  $encoderFactory;

    /**
     * @var Session
     */
    private $session;


    /**
     * Constructor
     *
     * @param Session        $session
     * @param Registry       $doctrine
     * @param EncoderFactory $encoderFactory
     */
    public function __construct(Session $session, Registry $doctrine, EncoderFactory $encoderFactory)
    {
        $this->doctrine = $doctrine;
        $this->encoderFactory = $encoderFactory;
        $this->session = $session;
    }

    /**
     * Load user by username
     *
     * @param string $username
     *
     * @return User
     */
    public function loadUserByUsername($username)
    {
        $user = $this->doctrine->getRepository('NPSCoreBundle:User')->findOneByUsername($username);
        if ($user instanceof User) {
            return $user;
        }

        return new User();
    }

    //twitter: callback https://github.com/hwi/HWIOAuthBundle/blob/master/Resources/doc/resource_owners/twitter.md
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        echo 'tut: loadUserByOAuthUserResponse'; exit;
        //Data from Google response
        $google_id = $response->getUsername(); /* An ID like: 112259658235204980084 */
        $email = $response->getEmail();
        $nickname = $response->getNickname();
        $realname = $response->getRealName();
        $avatar = $response->getProfilePicture();
        //set data in session
        $this->session->set('email', $email);
        $this->session->set('nickname', $nickname);
        $this->session->set('realname', $realname);
        $this->session->set('avatar', $avatar);
        //Check if this Google user already exists in our app DB
        $qb = $this->doctrine->getManager()->createQueryBuilder();
        $qb->select('u')
            ->from('FoggylineTickerBundle:User', 'u')
            ->where('u.googleId = :gid')
            ->setParameter('gid', $google_id)
            ->setMaxResults(1);
        $result = $qb->getQuery()->getResult();
        //add to database if doesn't exists
        if (!count($result)) {
            $user = new User();
            $user->setUsername($google_id);
            $user->setRealname($realname);
            $user->setNickname($nickname);
            $user->setEmail($email);
            $user->setGoogleId($google_id);
            //$user->setRoles('ROLE_USER');
            //Set some wild random pass since its irrelevant, this is Google login
            $encoder = $this->encoderFactory->getEncoder($user);
            $password = $encoder->encodePassword(md5(uniqid()), $user->getSalt());
            $user->setPassword($password);
            $em = $this->doctrine->getManager();
            $em->persist($user);
            $em->flush();
        } else {
            $user = $result[0]; /* return User */
        }
        //set id
        $this->session->set('id', $user->getId());
        return $this->loadUserByUsername($response->getUsername());
    }
    public function supportsClass($class)
    {
        return $class === 'NPS\\CoreBundle\\Entity\\User';
    }
} 