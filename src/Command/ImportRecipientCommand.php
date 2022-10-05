<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Recipient;
use App\Model\ImportModel;
use Symfony\Component\Console\Input\InputArgument;

class ImportRecipientCommand extends ImportModel
{
    protected static $defaultName = 'app:import:recipient';
    protected static $defaultDescription = 'Import la liste des bénéficiaires au format CSV';

    protected function configure(): void
    {
        $this
            ->addArgument('csv', InputArgument::REQUIRED, 'Fichier CSV à importer')
            ->setHelp('Le format du fichier doit être au format suivant : '."\n"
                .'beneficiaire;categorie')
        ;
    }

    public function purge(): bool
    {
        $this->truncate('recipient');

        return true;
    }

    protected function parse(): int
    {
        $numberLines = count((array) file($this->fileCSV));
        $this->inOut->progressStart($numberLines);

        $handle = fopen($this->fileCSV, 'r');
        if (false !== $handle) {
            while (($line = fgetcsv($handle, 1000, ';')) !== false) {
                $recipient = new Recipient();
                $recipient->setName($line[0]);
                $this->entityManager->persist($recipient);
                $this->inOut->progressAdvance();
            }
            fclose($handle);
        }
        $this->entityManager->flush();
        $this->inOut->progressFinish();

        return $numberLines;
    }
}
