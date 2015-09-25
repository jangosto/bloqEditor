<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="editor_homepage")
     */
    public function indexAction(Request $request)
    {
        $userManager = $this->container->get('fos_user.user_manager');

        return $this->render('editor/home.html.twig', array(
            'user' => $this->getUser(),
        ));
    }
}
