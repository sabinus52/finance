<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Olix\BackOfficeBundle\Model\User as BaseUser;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends BaseUser
{
}
