<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Bloq\Common\EditorBundle\Entity\EditorialContentInterface;
use Bloq\Common\EditorBundle\Entity\Article as BloqArticle;

/**
 * @ORM\Entity
 */
class Article extends BloqArticle implements EditorialContentInterface
{
}
