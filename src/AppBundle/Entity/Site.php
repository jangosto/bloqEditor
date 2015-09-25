<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Bloq\Common\EntitiesBundle\Entity\Site as BloqSite;

/**
 * @ORM\Entity
 * @ORM\Table(name="site")
 */
class Site extends BloqSite
{
}

