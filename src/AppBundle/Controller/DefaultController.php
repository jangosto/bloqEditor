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
        $userManager = $this->container->get('entities.user.manager');

        return $this->render('editor/home.html.twig', array(
            'user' => $this->getUser(),
        ));
    }
}
