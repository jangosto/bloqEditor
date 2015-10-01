<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use AppBundle\Form\Type\UserCreationFormType as AdminUserCreationFormType;
use AppBundle\Entity\User as AdminUser;


/**
 * @Route("/{site}")
 */
class EditorController extends Controller
{
    /**
     * @Route("/", name="site_editor_dashboard")
     */
    public function dashboardAction(Request $request, $site)
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
    public function dashboardAction(Request $request, $site)
    {
        $siteManager = $this->container->get('app.manager.site');
        $site = $siteManager->getBySlug($site);

        return $this->render('editor/site_dashboard.html.twig', array(
            'user' => $this->getUser(),
            'currentSite' => $site[0],
        ));
    }

    /**
     * @Route("/users/list/", name="admin_users_list")
     */
    public function listUsersAction()
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $users = $userManager->findUsers();

        return $this->render('admin/users_list.html.twig', array(
             'users' => $users
        ));
    }

    /**
     * @Route("/users/create/", name="admin_users_create")
     */
    public function createUsersAction()
    {
        $form = $this->container->get('fos_user.registration.form');
        $formHandler = $this->container->get('fos_user.registration.form.handler');
        $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');

        $process = $formHandler->process($confirmationEnabled);
        if ($process !== false) {
            $user = $form->getData();

            $authUser = false;
            if ($confirmationEnabled) {
                $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
                $route = 'fos_user_registration_check_email';
            } else {
                $authUser = true;
                $route = 'fos_user_registration_confirmed';

                $message = \Swift_Message::newInstance()
                    ->setSubject('Datos de Cuenta de Colaborador')
                    ->setFrom('noreplay@fanatic.futbol')
                    ->setTo($user->getEmailCanonical())
                    ->setBody(
                        $this->renderView(
                            "email/user_creation.email.twig",
                            array(
                                "user" => $user,
                                "pass" => $process,
                            ),
                            "text/html"
                        )
                    );
                $this->get('mailer')->send($message);
            }

//            $this->setFlash('fos_user_success', 'registration.flash.user_created');
            $url = $this->container->get('router')->generate($route);
            $response = new RedirectResponse($url);

/*            if ($authUser) {
                $this->authentificateUser($user, $response);
}*/

            return $response;
        }

        //$roles = $this->getRolesForUser();
        //$form->setRoles($roles);
        //ladybug_dump($form);die;

        return $this->render('admin/users_create.html.twig', array(
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
}

