<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Recipient;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Données de base de l'application.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        echo "\n";

        echo 'Chargement des bénéficiares                            : ';
        $number = $this->loadRecipients($manager);
        echo "{$number}\n";

        echo 'Chargement des catégories                              : ';
        $number = $this->loadCategories($manager);
        echo "{$number}\n";

        $manager->flush();
    }

    /**
     * Chargement des bénéficiaires.
     *
     * @return int
     */
    private function loadRecipients(ObjectManager $manager): int
    {
        $recipient = new Recipient();
        $recipient->setName(Recipient::VIRT_NAME);
        $manager->persist($recipient);
        $this->addReference(Recipient::VIRT_NAME, $recipient);

        return 1;
    }

    /**
     * Chargement des catégories.
     *
     * @return int
     */
    private function loadCategories(ObjectManager $manager): int
    {
        $finance = [];
        $number = 2;

        // Catégorie RECETTE de niveau 1
        $finance[Category::RECETTES] = new Category();
        $finance[Category::RECETTES]->setLevel(1)
            ->setName(Category::PRIMARY_LABEL)
            ->setType(Category::RECETTES)
        ;
        $manager->persist($finance[Category::RECETTES]);
        // Catégorie DEPENSE de niveau 1
        $finance[Category::DEPENSES] = new Category();
        $finance[Category::DEPENSES]->setLevel(1)
            ->setName(Category::PRIMARY_LABEL)
            ->setType(Category::DEPENSES)
        ;
        $manager->persist($finance[Category::DEPENSES]);

        // Catégories de niveau 2
        foreach (Category::$baseCategories as $key => $cat) {
            $type = $cat['type'];
            $category = new Category();

            $category->setName($cat['label'])
                ->setLevel(2)
                ->setType($type)
                ->setCode($cat['code'])
                ->setParent($finance[$type])
            ;
            $manager->persist($category);
            $this->addReference($key, $category);
            ++$number;
        }

        return $number;
    }
}
