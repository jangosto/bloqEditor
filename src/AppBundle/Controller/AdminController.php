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
class AdminController extends Controller
{
    const TAG_CLASS = "Bloq\\Common\\EditorBundle\\Entity\\Tag";
    const CATEGORY_CLASS = "Bloq\\Common\\EditorBundle\\Entity\\Category";

    /**
     * @Route("/category/{id}/", name="site_editor_category_edition")
     */
    public function siteCategoryEditionAction(Request $request, $site, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setContentsDatabaseConfig($siteObjects[0]->getSlug());
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

        $categories = $categoryManager->getAllWithHierarchy();
//        $categories = $categoryManager->getAll();
//        $categoriesArray = $this->orderObjects($categories);

        return $this->render('editor/site_category_edition.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $siteObjects[0],
            "form" => $form->createView(),
            "categories" => $categoriesArray,
        ));
    }

    /**
     * @Route("/category/{id}/enable", name="site_editor_category_enable")
     */
    public function siteCategoryEnableAction(Request $request, $site, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setContentsDatabaseConfig($siteObjects[0]->getSlug());
        $categoryManager = $this->container->get('editor.category.manager');

        $categoryManager->enableById($id);

        $response = new RedirectResponse($this->getRequest()->headers->get('referer'));

        return $response;
    }

    /**
     * @Route("/category/{id}/disable", name="site_editor_category_disable")
     */
    public function siteCategoryDisableAction(Request $request, $site, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setContentsDatabaseConfig($siteObjects[0]->getSlug());
        $categoryManager = $this->container->get('editor.category.manager');

        $categoryManager->disableById($id);

        $response = new RedirectResponse($this->getRequest()->headers->get('referer'));

        return $response;
    }

    /**
     * @Route("/tag/{id}/", name="site_editor_tag_edition")
     */
    public function siteTagEditionAction(Request $request, $site, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setContentsDatabaseConfig($siteObjects[0]->getSlug());
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

        $tags = $tagManager->getAll();
        $tagsArray = $this->orderObjects($tags);

        return $this->render('editor/site_tag_edition.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $siteObjects[0],
            "form" => $form->createView(),
            "tags" => $tagsArray,
        ));
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
