<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Category;
use App\Model\ImportModel;
use Symfony\Component\Console\Input\InputArgument;

class ImportCategoryCommand extends ImportModel
{
    protected static $defaultName = 'app:import:category';
    protected static $defaultDescription = 'Import la liste des catégories au format CSV';

    protected function configure(): void
    {
        $this
            ->addArgument('csv', InputArgument::REQUIRED, 'Fichier CSV à importer')
            ->setHelp('Le format du fichier doit être au format suivant : '."\n"
                .'level;[+/-];categorie')
        ;
    }

    public function purge(): bool
    {
        $this->truncate('category');

        return true;
    }

    protected function parse(): int
    {
        $numberLines = count((array) file($this->fileCSV));
        $this->inOut->progressStart($numberLines);

        $handle = fopen($this->fileCSV, 'r');
        if (false !== $handle) {
            $cat1 = null;
            while (($line = fgetcsv($handle, 1000, ';')) !== false) {
                $category = new Category();
                $category->setName($line[2]);

                // Revenus / Dépenses
                if ('-' === $line[1]) {
                    $category->setType(Category::DEPENSES);
                } elseif ('+' === $line[1]) {
                    $category->setType(Category::RECETTES);
                } else {
                    $this->inOut->warning(sprintf('(+/-) attendu : %s', implode(';', $line)));
                    continue;
                }

                // Niveau de la catégorie
                if ('1' === $line[0]) {
                    $cat1 = null;
                } elseif ('2' === $line[0]) {
                    $category->setParent($cat1);
                } else {
                    $this->inOut->warning(sprintf('(+/-) attendu : %s', implode(';', $line)));
                    continue;
                }

                $this->entityManager->persist($category);

                // Si catégéorie de niveau 1 alors on stocke temporairement cette catégorie
                // pour l"affecter au niveau 2
                if ('1' === $line[0]) {
                    $cat1 = $category;
                }

                $this->inOut->progressAdvance();
            }
            fclose($handle);
        }
        $this->entityManager->flush();
        $this->inOut->progressFinish();

        return $numberLines;
    }
}
