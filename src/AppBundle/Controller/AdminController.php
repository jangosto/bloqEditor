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
 * @Route("/{site}/admin")
 */
class AdminController extends Controller
{
    const TAG_CLASS = "Bloq\\Common\\EditorBundle\\Entity\\Tag";
    const CATEGORY_CLASS = "Bloq\\Common\\EditorBundle\\Entity\\Category";

    /**
     * @Route("/category/{id}/", name="site_editor_category_edition")
     * @Security("has_role('ROLE_SUPERVISOR')")
     */
    public function siteCategoryEditionAction(Request $request, $site, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setSiteConfig($siteObjects[0]);
        $categoryManager = $this->container->get('editor.category.manager');

        if ($id == "new") {
            $categoryClass = self::CATEGORY_CLASS;
            $category = new $categoryClass();
        } else {
            $category = $categoryManager->getById($id);
        }

        $form = $this->createForm('editor_category_edition', $category);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $category = $form->getData();

            $categoryManager->save($category);

            $route = "site_editor_category_edition";

            $url = $this->container->get('router')->generate($route, array(
                    'site' => $site,
                    'id' => $category->getId()
                ));

            $response = new RedirectResponse($url);
            return $response;
        }

        if ($request->request->get('submit') == 'set') {
            $outstandings = json_decode($request->request->get('outstandings'));
            $categoryManager->cleanOutstandings();
            foreach ($outstandings as $position => $contentId) {
                $categoryManager->setInOutstandingsPosition($contentId, $position+1);
            }
        }

        $categories = $categoryManager->getAllWithHierarchy();
        $outstandingCategories = $categoryManager->getOutstandings();
        $notOutstandingCategories = $categoryManager->getNotOutstandings();

        return $this->render('editor/site_category_edition.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $siteObjects[0],
            'form' => $form->createView(),
            'categories' => $categories,
            'outstandingCategories' => $outstandingCategories,
            'notOutstandingCategories' => $notOutstandingCategories
        ));
    }

    /**
     * @Route("/category/{id}/enable", name="site_editor_category_enable")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function siteCategoryEnableAction(Request $request, $site, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setSiteConfig($siteObjects[0]);
        $categoryManager = $this->container->get('editor.category.manager');

        $categoryManager->enableById($id);

        $response = new RedirectResponse($this->getRequest()->headers->get('referer'));

        return $response;
    }

    /**
     * @Route("/category/{id}/disable", name="site_editor_category_disable")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function siteCategoryDisableAction(Request $request, $site, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setSiteConfig($siteObjects[0]);
        $categoryManager = $this->container->get('editor.category.manager');

        $categoryManager->disableById($id);

        $response = new RedirectResponse($this->getRequest()->headers->get('referer'));

        return $response;
    }

    /**
     * @Route("/tag/{id}/", name="site_editor_tag_edition")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function siteTagEditionAction(Request $request, $site, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setSiteConfig($siteObjects[0]);
        $tagManager = $this->container->get('editor.tag.manager');

        if ($id == "new") {
            $tagClass = self::TAG_CLASS;
            $tag = new $tagClass();
        } else {
            $tag = $tagManager->getById($id);
        }

        $form = $this->createForm('editor_tag_edition', $tag);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $tag = $form->getData();

            $tagManager->save($tag);

            $route = "site_editor_tag_edition";

            $url = $this->container->get('router')->generate($route, array(
                'site' => $site,
                'id' => $tag->getId()
            ));

            $response = new RedirectResponse($url);
            return $response;
        }

        $tags = $tagManager->getAllWithHierarchy();

        return $this->render('editor/site_tag_edition.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $siteObjects[0],
            "form" => $form->createView(),
            "tags" => $tags,
        ));
    }

    /**
     * @Route("/tag/{id}/enable", name="site_editor_tag_enable")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function siteTagEnableAction(Request $request, $site, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setSiteConfig($siteObjects[0]);
        $tagManager = $this->container->get('editor.tag.manager');

        $tagManager->enableById($id);

        $response = new RedirectResponse($this->getRequest()->headers->get('referer'));

        return $response;
    }

    /**
     * @Route("/tag/{id}/disable", name="site_editor_tag_disable")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function siteTagDisableAction(Request $request, $site, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setSiteConfig($siteObjects[0]);
        $tagManager = $this->container->get('editor.tag.manager');

        $tagManager->disableById($id);

        $response = new RedirectResponse($this->getRequest()->headers->get('referer'));

        return $response;
    }

    /**
     * @Route("/menu/primary/sections", name="site_editor_menu_primary_sections_edition")
     * @Security("has_role('ROLE_SUPERVISOR')")
     */
    public function siteMenuPrimarySectionsEditionAction(Request $request, $site)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setSiteConfig($siteObjects[0]);
        $categoryManager = $this->container->get('editor.category.manager');

        if ($request->request->get('submit') == 'set') {
            $menu = json_decode($request->request->get('menu'));
            $categoryManager->cleanMenu();
            foreach ($menu as $position => $categoryId) {
                $categoryManager->setInMenuPosition($categoryId, $position+1);
            }
        }

        $sections = $categoryManager->getOutOfMenu();
        $menuSections = $categoryManager->getMenuAdded();

        return $this->render('editor/site_menu_primary_sections_edition.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $siteObjects[0],
            'sections' => $sections,
            'menuSections' => $menuSections
        ));
    }

    /**
     * @Route("/outstandings/contents/", name="site_outstanding_contents_edition")
     * @Security("has_role('ROLE_SUPERVISOR')")
     */
    public function siteOutstandingContentsEditionAction(Request $request, $site)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setSiteConfig($siteObjects[0]);
        $contentManager = $this->container->get('editor.editorial_content.manager');

        if ($request->request->get('submit') == 'set') {
            $outstandings = json_decode($request->request->get('outstandings'));
            $contentManager->cleanOutstandings();
            foreach ($outstandings as $position => $contentId) {
                $contentManager->setInOutstandingsPosition($contentId, $position+1);
            }
        }

        $contents = $contentManager->getNotOutstandings();
        $outstandingContents = $contentManager->getOutstandings();

        return $this->render('editor/site_outstanding_editorial_contents.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $siteObjects[0],
            'contents' => $contents,
            'outstandingContents' => $outstandingContents
        ));
    }

    private function setSiteConfig($siteObject)
    {
        $this->setContentsDatabaseConfig($siteObject->getSlug());
        Globals::setImagesUploadDir(str_replace("{site_domain}", $siteObject->getSlug(), Globals::getImagesUploadDir()));
    }

    private function orderObjects($objects)
    {
        $objectsArray = array();
        foreach ($objects as $object) {
            $objectsArray[$object->getId()] = $object;
        }

        return $objectsArray;
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

    private function setContentsDatabaseConfig($site)
    {
        $this->get('doctrine.dbal.dynamic_connection')->forceSwitch(
                $this->container->getParameter($site.'.content.database_name'),
                $this->container->getParameter($site.'.content.database_user'),
                $this->container->getParameter($site.'.content.database_password')
            );
    }
}
