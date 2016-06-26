<?php

namespace NPS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Source
 *
 * @ORM\Entity(repositoryClass="NPS\CoreBundle\Repository\SourceCategoryRepository")
 * @ORM\Table(name="source_category")
 * @ORM\HasLifecycleCallbacks
 */
class SourceCategory extends AbstractEntity
{
    const PATH_IMAGES = 'uploads/images/sourceCategory';
    const SERVER_PATH_WEB = __DIR__ . '/../../../../web/' . self::PATH_IMAGES;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="image", type="string", length=255, nullable=false)
     */
    protected $imageName;

    /**
     * @var File $name
     * @Assert\Image(
     *   maxSize = "5M",
     *   mimeTypes = {"image/jpeg", "image/png"},
     *   mimeTypesMessage = "Please upload a valid image"
     * )
     */
    protected $imageFile;

    /**
     * @var Source[]|PersistentCollection
     * @ORM\ManyToMany(targetEntity="Source", mappedBy="categories")
     * @ORM\JoinTable(name="sources_categories")
     */
    private $sources;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set image name
     *
     * @param string $imageName
     *
     * @return $this
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * Sets image file
     *
     * @param File $imageFile
     *
     * @return $this
     */
    public function setImageFile(File $imageFile = null)
    {
        if ($imageFile) {
            $this->imageFile = $imageFile;
        }

        return $this;
    }

    /**
     * Get image file
     *
     * @return UploadedFile
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * Manages the copying of the file to the relevant place on the server
     *
     */
    public function uploadImage()
    {
        if (null === $this->getImageFile()
            || $this->getImageFile()->getClientOriginalName() == $this->getImageName()
        ) {
            return;
        }

        $this->cleanExistsImage();
        $this->getImageFile()->move(
            self::SERVER_PATH_WEB,
            $this->getImageFile()->getClientOriginalName()
        );

        $this->setImageName($this->getImageFile()->getClientOriginalName());
        $this->setImageFile(null);
    }

    /**
     * Remove image if already exists
     */
    protected function cleanExistsImage()
    {
        if ($this->getImageName()) {
            unlink(self::SERVER_PATH_WEB . '/' . $this->getImageName());
        }
    }

    /**
     * Get source categories.
     *
     * @return PersistentCollection|SourceCategory[]
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * Add source category
     *
     * @param Source $source
     *
     * @return $this
     */
    public function addSource(Source $source)
    {
        if (!$this->sources->contains($source)) {
            $this->sources->add($source);
        }

        return $this;
    }

    /**
     * Remove source category from staff collection.
     *
     * @param Source $source
     *
     * @return $this
     */
    public function removeSource(Source $source)
    {
        if ($this->sources->contains($source)) {
            $this->sources->removeElement($source);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }
}
