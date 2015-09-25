<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Bloq\Common\EntitiesBundle\Entity\User as BloqUser;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BloqUser
{
}
