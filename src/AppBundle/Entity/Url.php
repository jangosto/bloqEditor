<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Bloq\Common\EditorBundle\Entity\Url as BloqUrl;

/**
 * @ORM\Entity
 * @ORM\Table(name="url")
 */
class Url extends BloqUrl
{
}

