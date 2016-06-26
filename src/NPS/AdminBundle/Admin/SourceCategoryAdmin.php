<?php

namespace NPS\AdminBundle\Admin;

use NPS\CoreBundle\Entity\SourceCategory;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

/**
 * Class SourceCategoryAdmin
 *
 * @package NPS\AdminBundle\Admin
 */
class SourceCategoryAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        /** @var SourceCategory $sourceCategory */
        $sourceCategory   = $this->getSubject();
        $fileFieldOptions = [
            'required' => false,
            'help'     => $this->getImagePath($sourceCategory->getImageName())
        ];

        $formMapper
            ->add('name')
            ->add('imageFile', 'file', $fileFieldOptions);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('name');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->addIdentifier('imageName');
    }

    public function prePersist($image)
    {
        $this->manageFileUpload($image);
    }

    public function preUpdate($image)
    {
        $this->manageFileUpload($image);
    }

    private function manageFileUpload($image)
    {
        $image->uploadImage();
    }

    /**
     * Get image path
     *
     * @param string $imageName
     *
     * @return string
     */
    protected function getImagePath($imageName)
    {
        $container = $this->getConfigurationPool()->getContainer();
        $fullPath  = $container->get('request')->getBasePath() . '/' . SourceCategory::PATH_IMAGES . '/' . $imageName;

        return '<img src="' . $fullPath . '" class="admin-preview" />';
    }
}
