<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Bloq\Common\EntitiesBundle\Entity\User as AdminUser;
use Bloq\Common\MultimediaBundle\Entity\Multimedia as MultimediaEntity;
use Bloq\Common\MultimediaBundle\Lib\Globals;

use AppBundle\Form\Type\UserCreationFormType as AdminUserCreationFormType;


/**
 * @Route("/{site}")
 */
class TestViewController extends Controller
{
    /**
     * @Route("/{editorialContentType}/{id}/show/", name="site_editor_editorial_content_show")
     */
    public function siteEditorialContentShowAction(Request $request, $site, $editorialContentType, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setSiteConfig($siteObjects[0]);

        $editorialContentClass = $this->container->getParameter("editorial_contents.".$editorialContentType.".model_class");
        $editorialContentManager = $this->container->get('editor.'.$editorialContentType.'.manager');

        $editorialContent = $editorialContentManager->getById($id);

        $imageCropper = $this->container->get('multimedia.images.image_cropper');
        $imageCropper->setImage($editorialContent->getMultimedias()[0]);
        $imageCropper->generateImageCrops(array('article'));
die;
        return $this->render('editor/site_'.$editorialContentType.'_show.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $siteObjects[0],
            "content" => $editorialContent
        ));
    }


    private function setSiteConfig($siteObject)
    {
        $this->setContentsDatabaseConfig($siteObject->getSlug());
        Globals::setImagesUploadDir(str_replace("{site_domain}", $siteObject->getSlug(), $this->container->getParameter('bloq_multimedia.images.root_dir_rel_path')));
        Globals::setOriginalImagesUploadDir(str_replace("{site_domain}", $siteObject->getSlug(), $this->container->getParameter('bloq_multimedia.images.root_dir_rel_path_originals')));
    }

    private function getRolesForUser()
    {
        $roles = array();
        $tmpRoles = $this->container->getParameter('security.role_hierarchy.roles');
        $formatedRoles = $this->formatRolesForForm($tmpRoles);

        foreach ($formatedRoles as $role) {
            if ($this->get("security.context")->isGranted($role)) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    private function formatRolesForForm($rolesArray)
    {
        $roles = array();
        foreach ($rolesArray as $roleKey => $inheritedRoles) {
            if (!in_array($roleKey, $roles)) {
                $roles[] = $roleKey;
            }
            foreach ($inheritedRoles as $role) {
                if (!in_array($role, $roles)) {
                    $roles[] = $role;
                }
            }
        }
        return $roles;
    }

    private function getCurrentSiteBySlug($slug)
    {
        $siteManager = $this->container->get('app.manager.site');
        $site = $siteManager->getBySlug($slug);

        return $site;
    }

    private function setEdidtorialContentForForm($object)
    {
        $maxNumPrimaryImgs = 1;
        $maxNumTextImgs = 2;
        $maxNumVideos = 1;
        $maxNumAudios = 1;
        $maxNumSummaries = 3;
        $maxNumSubtitles = 2;

        $numPrimaryImgs = 0;
        $numTextImgs = 0;
        $numVideos = 0;
        $numAudios = 0;

        foreach ($object->getMultimedias() as $multimedia) {
            if ($multimedia->getType() == 'image') {
                if ($multimedia->getPosition() == "primary") {
                    $numPrimaryImgs++;
                } else {
                    $numTextImgs++;
                }
            } elseif ($multimedia->getType() == 'video') {
                $numVideos++;
            } elseif ($multimedia->getType() == 'audio') {
                $numAudios++;
            }
        }
        $i = $numPrimaryImgs;
        while ($i < $maxNumPrimaryImgs) {
            $multimedia = new MultimediaEntity();
            $multimedia->setType('image');
            $multimedia->setPosition('primary');
            $object->getMultimedias()->add($multimedia);
            unset($multimedia);
            $i++;
        }
        $i = $numTextImgs;
        while ($i < $maxNumTextImgs) {
            $multimedia = new MultimediaEntity();
            $multimedia->setType('image');
            $object->getMultimedias()->add($multimedia);
            unset($multimedia);
            $i++;
        }
        $i = $numVideos;
        while ($i < $maxNumVideos) {
            $multimedia = new MultimediaEntity();
            $multimedia->setType('video');
            $object->getMultimedias()->add($multimedia);
            unset($multimedia);
            $i++;              
        }
        $i = $numAudios;
        while ($i < $maxNumAudios) {
            $multimedia = new MultimediaEntity();
            $multimedia->setType('audio');
            $object->getMultimedias()->add($multimedia);
            unset($multimedia);
            $i++;              
        }

        $i = count($object->getSummaries());
        while ($i < $maxNumSummaries) {
            $object->addSummary('');
            $i++;
        }
        $i = count($object->getSubtitles());
        while ($i < $maxNumSubtitles) {
            $object->addSubtitle('');
            $i++;
        }

        return $object;
    }

    private function cleanEditorialContentToPersist($object)
    {
        foreach ($object->getMultimedias() as $key => $multimedia) {
            if ($multimedia->getPath() == null && $multimedia->getFile() == null && $multimedia->getHtmlCode() == null) {
                $object->getMultimedias()->remove($key);
            }
        }

        foreach ($object->getSubtitles() as $subtitle) {
            if ($subtitle == null || strlen($subtitle) == 0) {
                $object->removeSubtitle($subtitle);
            }
        }

        foreach ($object->getSummaries() as $summary) {
            if ($summary == null || strlen($summary) == 0) {
                $object->removeSummary($summary);
            }
        }

        return $object;
    }

    private function setContentsDatabaseConfig($site)
    {
        $this->get('doctrine.dbal.dynamic_connection')->forceSwitch(
                $this->container->getParameter($site.'.content.database_name'),
                $this->container->getParameter($site.'.content.database_user'),
                $this->container->getParameter($site.'.content.database_password')
            );
    }

    private function saveUploadedMultimedias($editorialObject, $siteObject)
    {
        foreach ($editorialObject->getMultimedias() as $key => $multimedia) {
            if ($multimedia->getType() == "image" && $multimedia->getFile() !== null) {
                $uploadPath = $this->container->getParameter('bloq_multimedia.images.root_dir_rel_path');
                $domainPath = $this->container->getParameter('editor.domain.path');
                $relImagesDirUrl = $this->container->getParameter('multimedia.images.dir.rel_url');

                $extension = $multimedia->getFile()->guessExtension();
                $dateDirPart = date("Y/md");
                $relDirPath = str_replace("{site_domain}", $siteObject->getSlug(), $uploadPath)."/".$dateDirPart."/";
                $relDirUrl = $relImagesDirUrl."/".$dateDirPart."/";
                $absDir = $domainPath.$relDirPath;
                $filename = rand(1, 9999999).'.'.$extension;
                $multimedia->getFile()->move($absDir, $filename);
                $editorialObject->getMultimedias()[$key]->setPath("/".$dateDirPart."/".$filename);
            }
        }
        
        return $editorialObject;
    }

    private function setEditorialContentAuthors($object)
    {
        $object->addAuthor($this->getUser()->getId());

        return $object;
    }

    private function setEditorialContentDates($object, $isPublished)
    {
        if ($object->getCreatedDT() === null) {
            $object->setCreatedDT(new \DateTime("now"));
        }

        if ($object->getPublishedDT() === null && $isPublished) {
            $object->setPublishedDT(new \DateTime("now"));
        }

        if ($isPublished) {
            $object->setUpdatedDT(new \DateTime("now"));
        }

        return $object;
    }

    private function cleanupManager($manager)
    {
//        $manager->cleanup();
    }
}
