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
        $siteManager = $this->container->get('app.manager.site');
        $site = $siteManager->getBySlug($site);

        return $this->render('editor/site_dashboard.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $site[0],
        ));
    }

    /**
     * @Route("/articles/", name="site_editor_articles_list")
     */
    public function siteArticlesListAction(Request $request, $site)
    {
        $siteManager = $this->container->get('app.manager.site');
        $site = $siteManager->getBySlug($site);

        return $this->render('editor/site_dashboard.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $site[0],
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
}

