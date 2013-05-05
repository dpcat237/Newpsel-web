<?php

namespace NPS\ModelBundle\Entity;
use Gedmo\Mapping\Annotation as Gedmo;
use NPS\ModelBundle\Entity\Feed;
use NPS\ModelBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * FeedUser
 *
 * @ORM\Entity(repositoryClass="NPS\ModelBundle\Repository\UserFeedRepository")
 * @ORM\Table(name="user_feed")
 * @ORM\HasLifecycleCallbacks
 */
class UserFeed
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="Feed", inversedBy="userFeeds")
     * @ORM\JoinColumn(name="feed_id", referencedColumnName="id", nullable=false)
     */
    private $feed;

    /**
     * @var integer
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userFeeds")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="date_add", type="integer")
     */
    private $dateAdd;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the feed
     *
     * @return Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * Set the feed
     * @param Feed $feed
     */
    public function setFeed(Feed $feed)
    {
        $this->feed = $feed;
    }

    /**
     * Get the feed id
     *
     * @return integer id
     */
    public function getFeedId()
    {
        if (is_object($this->getFeed())) {
            $feedId = $this->getFeed()->getId();
        } else {
            $feedId = 0;
        }

        return $feedId;
    }

    /**
     * Get the user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the user id
     *
     * @return integer id
     */
    public function getUserId()
    {
        if (is_object($this->getUser())) {
            $userId = $this->getUser()->getId();
        } else {
            $userId = 0;
        }

        return $userId;
    }

    /**
     * Set dateAdd
     * @param int $dateAdd
     *
     * @return User
     */
    public function setDateAdd($dateAdd = null)
    {
        $dateAddNow = $this->getDateAdd();
        $this->dateAdd = (empty($dateAdd) && empty($dateAddNow))? time() : $dateAdd;

        return $this;
    }

    /**
     * Get dateAdd
     *
     * @return int
     */
    public function getDateAdd()
    {
        return $this->dateAdd;
    }
}
