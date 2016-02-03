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
class PreviewController extends Controller
{
    /**
     * @Route("/preview/{editorialContentType}/{id}", name="site_editor_editorial_content_preview")
     */
    public function editorialContentPreviewAction($site, $editorialContentType, $id)
    {
        $siteObjects = $this->getCurrentSiteBySlug($site);
        $this->setSiteConfig($siteObjects[0]);

        $editorialContentClass = $this->container->getParameter("editorial_contents.".$editorialContentType.".model_class");
        $editorialContentManager = $this->container->get('editor.'.$editorialContentType.'.manager');

        $editorialContent = $editorialContentManager->getById($id, true);

        $userManager = $this->container->get('fos_user.user_manager');
        $authors = $userManager->getByIds($editorialContent->getAuthors());

        $counters = new \Bloq\Common\ModulesBundle\Monitors\Counters();
        $counters->getUsedContents()->add($editorialContent->getId());
        
        return $this->render($site.'/editorial_content/'.$editorialContent->getType().'.html.twig', array(
            'user' => $this->getUser(),
            'authors' => $authors,
            'content' => $editorialContent,
            'counters' => $counters,
            'contentType' => '',
            //Global Variables Mocks
            'relative_images_path' => str_replace('{site_domain}', $site, $this->container->getParameter('bloq_multimedia.images.root_dir_rel_path')),
            'relative_images_url' => $this->container->getParameter('multimedia.images.dir.rel_url'),
            'multimedia_domain' => "http://".$siteObjects[0]->getDomain(),
            'multimedia_domain_path' => $siteObjects[0]->getDomainPath(),
            'twitter_account_name' => '',
            'disqus_src_domain_prefix' => '',
            'disqus_editorial_content_id_prefix' => '',
            'baseImport' => $siteObjects[0]->getSlug(),
            'site_name' => $siteObjects[0]->getName(),
            'statics_domain' => "/static/".$siteObjects[0]->getSlug()
        ));
    }


    private function setSiteConfig($siteObject)
    {
        $this->setContentsDatabaseConfig($siteObject->getSlug());
        Globals::setImagesUploadDir(str_replace("{site_domain}", $siteObject->getSlug(), $this->container->getParameter('bloq_multimedia.images.root_dir_rel_path')));
        Globals::setOriginalImagesUploadDir(str_replace("{site_domain}", $siteObject->getSlug(), $this->container->getParameter('bloq_multimedia.images.root_dir_rel_path_originals')));
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
