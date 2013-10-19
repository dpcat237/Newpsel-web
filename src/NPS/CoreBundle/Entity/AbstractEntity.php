<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use NPS\CoreBundle\Entity\Traits\DateTimeTrait;

/**
 * Base Web User
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\MappedSuperclass
 */
abstract class AbstractEntity
{
    use DateTimeTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

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
     * Returns the name of the Entity. This is useful when using inheritance and
     * we don't want to mangle with reflection to get the base class name.
     *
     * A concrete example of this is when the RouteBuilder service need to automagically
     * compose route names based con a particular Entity. If the Entity is using inheritance
     * and we want the *parent* name to be used as the base name to compose the route names,
     * we just have to override this method in the child Entity class.
     *
     * See RouteBuilder::getEntityName and AbstractController child classes
     * Route annotation for more information
     *
     * @return string
     */
    public function getEntityName()
    {
        return get_class($this);
    }
}
