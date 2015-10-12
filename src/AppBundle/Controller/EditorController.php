<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Bloq\Common\EntitiesBundle\Entity\User as AdminUser;

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
     * @Route("/{editorialContentType}/", name="site_editor_editorial_content_list")
     */
    public function siteEditorialContentListAction(Request $request, $site, $editorialContentType)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setContentsDatabaseConfig($siteObjects[0]->getSlug());

        return $this->render('editor/site_'.$editorialContentType.'_list.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $siteObjects[0],
            'currentEditorialContent' => $editorialContentType
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
        } else {
            $editorialContentManager = $this->container->get('editor.'.$editorialContentType.'.manager');
            $editorialContent = $editorialContentManager->getById($id);
        }

        $form = $this->createForm('editor_'.$editorialContentType.'_edition', $editorialContent); 

        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $editorialContent = $form->getData();
            
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

    private function setContentsDatabaseConfig($site)
    {
        $this->get('doctrine.dbal.dynamic_connection')->forceSwitch(
                $this->container->getParameter($site.'.content.database_name'),
                $this->container->getParameter($site.'.content.database_user'),
                $this->container->getParameter($site.'.content.database_password')
            );
    }
}

