<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NPS\CoreBundle\Entity\AbstractEntity;
use NPS\CoreBundle\Entity\Traits\EnabledTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * AbstractUser
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\MappedSuperclass
 * @UniqueEntity("email")
 */
//@UniqueEntity(fields="username", message="Sorry, this username is not available or allowed")
//@UniqueEntity(fields="username", message="Sorry, this username is not available or allowed", groups={"registration"})
//@UniqueEntity(fields="email", message="Sorry, this email is not available or allowed")
abstract class AbstractUser extends AbstractEntity implements UserInterface
{
    use EnabledTrait;


    /**
     * @var string
     * @ORM\Column(name="username", type="string", length=255, nullable=true, unique=true)
     * @Assert\NotNull(message={"Write an username"})
     */
    protected $username;

    /**
     * @var string
     * @ORM\Column(name="email", type="string", length=255, nullable=false, unique=true)
     * @Assert\Email
     * @Assert\NotBlank
     * @Assert\NotNull(message={"Write an email"})
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     * @Assert\NotNull(groups={"registration"})
     */
    protected $password;

    /**
     * Default user roles
     *
     * @var array
     */
    protected $roles = array(
        'ROLE_USER',
    );



    /**
     * Set email
     *
     * @param string $email
     *
     * @return AbstractUser self Object
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }


    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }


    /**
     * Set username
     *
     * @param String $username Username
     *
     * @return AbstractUser self Object
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }


    /**
     * Return Username
     *
     * @return String Username
     */
    public function getUsername()
    {
        return $this->username;
    }



    /**
     * Set password
     *
     * @param string $password
     *
     * @return AbstractUser self Object
     */
    public function setPassword($password)
    {
        if (null === $password)  {

            return;
        }

        $this->password = $password;

        return $this;
    }


    /**
     * Return password
     *
     * @return string Password
     */
    public function getPassword()
    {
        return $this->password;
    }



    /**
     * Part of UserInterface. Dummy
     *
     * @return string ""
     */
    public function getSalt()
    {
        return "";
    }


    /**
     * Part of UserInterface.
     *
     * Checks if $user is the same user and this instance
     * @param AbstractUser $user
     *
     * @return boolean if the user is the same
     */
    public function equals(AbstractUser $user)
    {
        return $user->getId() === $this->getId();
    }


    /**
     * Part of UserInterface.
     *
     * Dummy function, returns empty string
     *
     * @return string
     */
    public function eraseCredentials()
    {
        return "";
    }


    /**
     * Part of UserInterface.
     *
     * Get the roles this user has. ROLE_USER by default and at least in the
     * first implementation, as we only want to discriminate between logged
     * and not logged
     *
     * @return array with the user roles
     */
    public function getRoles()
    {
        return $this->roles;
    }


    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or &null;
     */
    public function serialize()
    {
        return serialize(array(
            $this->username,
            $this->password,
            $this->salt,
            $this->enabled,
        ));
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized The string representation of the object.
     * @return mixed the original value unserialized.
     */
    public function unserialize($serialized)
    {
        list(
            $this->username,
            $this->password,
            $this->salt,
            $this->enabled
        ) = unserialize($serialized);
    }
}
