<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Bloq\Common\EntitiesBundle\Entity\User as AdminUser;
use Bloq\Common\MultimediaBundle\Entity\Multimedia as MultimediaEntity;

use AppBundle\Form\Type\UserCreationFormType as AdminUserCreationFormType;


/**
 * @Route("/{site}")
 */
class EditorController extends Controller
{
    /**
     * @Route("/", name="site_editor_dashboard")
     */
    public function siteDashboardAction(Request $request, $site)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setContentsDatabaseConfig($siteObjects[0]->getSlug());

        return $this->render('editor/site_dashboard.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $siteObjects[0],
        ));
    }

    /**
     * @Route("/{editorialContentType}/{filter}/", name="site_editor_editorial_content_list", requirements={"filter": "published|removed|saved"})
     */
    public function siteEditorialContentListAction(Request $request, $site, $editorialContentType, $filter)
    {
        $status = array("published", "removed", "saved");
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setContentsDatabaseConfig($siteObjects[0]->getSlug());
        
        $contents = array();
        $numContents = array();
        $editorialContentManager = $this->container->get('editor.article.manager');
        foreach ($status as $state) {
            $contentsTmp = $editorialContentManager->getAllByStatus($state);
            $numContents[$state] = count($contentsTmp);
            if ($filter == $state) {
                $contents = $contentsTmp;
            }
        }

        return $this->render('editor/site_'.$editorialContentType.'_list.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $siteObjects[0],
            'currentEditorialContent' => $editorialContentType,
            'contents' => $contents,
            'numContents' => $numContents
        ));
    }

    /**
     * @Route("/{editorialContentType}/{id}/", name="site_editor_editorial_content_edition")
     */
    public function siteEditorialContentEditionAction(Request $request, $site, $editorialContentType, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setContentsDatabaseConfig($siteObjects[0]->getSlug());

        $editorialContentClass = $this->container->getParameter("editorial_contents.".$editorialContentType.".model_class");

        if ($id == "new") {
            $editorialContent = new $editorialContentClass();
            if (!$request->request->has('save') && !$request->request->has('publish')) {
                $editorialContent = $this->setEdidtorialContentForForm($editorialContent);
            }
        } else {
            $editorialContentManager = $this->container->get('editor.'.$editorialContentType.'.manager');
            $editorialContent = $editorialContentManager->getById($id);
            if (!$request->request->has('save') && !$request->request->has('publish')) {
                $editorialContent = $this->setEdidtorialContentForForm($editorialContent);
            }
        }

        $form = $this->createForm('editor_'.$editorialContentType.'_edition', $editorialContent);
        
        $form->handleRequest($request);

        if ($form->isValid()) {
            $editorialContent = $form->getData();
            if ($form->get('publish')->isClicked()) {
                $editorialContent->setStatus("published");
            } elseif($form->get('save')->isClicked()) {
                $editorialContent->setStatus("saved");
            }

            $editorialContent = $this->saveUploadedMultimedias($editorialContent, $siteObjects[0]);
            $editorialContent = $this->cleanEditorialContentToPersist($editorialContent);
            $editorialContent = $this->setEditorialContentAuthors($editorialContent);
            $editorialContent = $this->setEditorialContentDates($editorialContent, $form->get('publish')->isClicked());
            /*$multimediaManager = $this->container->get('multimedia.multimedia.manager');
            foreach ($editorialContent->getMultimedias() as $multimedia) {
                $multimediaManager->save($multimedia);
            }*/
            $editorialContentManager = $this->container->get('editor.'.$editorialContentType.'.manager');
            $editorialContentManager->save($editorialContent);

            $route = "site_editor_editorial_content_edition";

            $url = $this->container->get('router')->generate($route, array(
                    'site' => $site,
                    'editorialContentType' => $editorialContentType,
                    'id' => $editorialContent->getId()
                ));

            $response = new RedirectResponse($url);
            return $response;
        }

        return $this->render('editor/site_'.$editorialContentType.'_edition.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $siteObjects[0],
            "form" => $form->createView()
        ));
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
                $uploadPath = $this->container->getParameter('multimedia.images.save.path');
                $extension = $multimedia->getFile()->guessExtension();
                $relDir = $uploadPath."/".date("Y/md")."/";
                $absDir = $siteObject->getDomainPath().$relDir;
                $filename = rand(1, 9999999).'.'.$extension;
                $multimedia->getFile()->move($absDir, $filename);
                $editorialObject->getMultimedias()[$key]->setPath($relDir.$filename);
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
}
