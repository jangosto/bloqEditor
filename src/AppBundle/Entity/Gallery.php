<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Bloq\Common\EditorBundle\Entity\EditorialContentInterface;
use Bloq\Common\EditorBundle\Entity\Gallery as BloqGallery;

/**
 * @ORM\Entity
 */
class Gallery extends BloqGallery implements EditorialContentInterface
{
}