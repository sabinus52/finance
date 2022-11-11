<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Values\Payment;
use ArrayObject;
use DateTime;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Classe pour gérer les statistiques de l'import.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ImportStatistic
{
    /**
     * @var SymfonyStyle
     */
    private $inOut;

    /**
     * Liste des alertes trouvées.
     *
     * @var ArrayObject
     */
    private $alerts;

    /**
     * Liste des comptes trouvés.
     *
     * @var ArrayObject
     */
    private $accounts;

    /**
     * Liste des catégories trouvés.
     *
     * @var ArrayObject
     */
    private $categories;

    /**
     * Constructeur.
     *
     * @param SymfonyStyle|null $style
     */
    public function __construct(SymfonyStyle $style = null)
    {
        if (null !== $style) {
            $this->inOut = $style;
        }
        $this->alerts = new ArrayObject();
        $this->accounts = new ArrayObject();
        $this->categories = new ArrayObject();
    }

    /**
     * Affecte le style de Symfony Command.
     *
     * @param SymfonyStyle $style
     */
    public function setStyle(SymfonyStyle $style): self
    {
        $this->inOut = $style;

        return $this;
    }

    /**
     * Incrémente les données de la transaction.
     *
     * @param Transaction $transaction
     */
    public function incTransaction(Transaction $transaction): void
    {
        // Incrémente les transactions des comptes
        $accountName = $transaction->getAccount()->getFullName();
        if (!$this->accounts->offsetExists($accountName)) {
            $this->addAccount($transaction->getAccount());
        }
        $this->setAccountData($transaction->getAccount());
        ++$this->accounts[$accountName]['transactions'];
        if (Payment::INTERNAL === $transaction->getPayment()->getValue()) {
            if (Category::VIREMENT === $transaction->getCategory()->getCode()) {
                ++$this->accounts[$accountName]['transfers'];
            } else {
                ++$this->accounts[$accountName]['investments'];
            }
        }

        // Incrémente les transactions des catégories
        $categoryName = $transaction->getCategory()->getFullName();
        if (!$this->categories->offsetExists($categoryName)) {
            $this->addCategory($transaction->getCategory());
        }
        ++$this->categories[$categoryName]['transactions'];
    }

    /**
     * Ajoute un message d'alerte.
     *
     * @param string $message
     */
    public function addAlert(DateTime $date, Account $account, float $amount, string $memo, string $message): void
    {
        $this->alerts->append([
            'date' => $date->format('d/m/Y'),
            'account' => $account->getFullName(),
            'amount' => $amount,
            'memo' => $memo,
            'msg' => $message,
        ]);
    }

    /**
     * Affiche le report des alertes.
     */
    public function reportAlerts(): void
    {
        $this->inOut->table(['Date', 'Compte', 'Montant', 'Mémo', 'Alerte'], (array) $this->alerts);
    }

    /**
     * Affiche le rapport des comptes trouvés.
     */
    public function reportAccounts(): void
    {
        $this->accounts->ksort();
        $this->inOut->table(['Compte', 'Type', 'Date', 'Nbr transaction', 'Nbr virement', 'Nbr placement'], (array) $this->accounts);
    }

    /**
     * Affiche le rapport des catégories trouvées.
     */
    public function reportCategories(): void
    {
        $this->categories->ksort();
        $this->inOut->table(['Categorie', 'Type', 'Nbr transaction'], (array) $this->categories);
    }

    /**
     * Ajoute un nouveau compte trouvé.
     *
     * @param Account $account
     */
    private function addAccount(Account $account): void
    {
        $accountName = $account->getFullName();
        $this->accounts[$accountName] = [
            'name' => $accountName,
            'type' => $account->getType()->getLabel(),
            'opened' => $account->getOpenedAt()->format('d/m/Y'),
            'transactions' => 0,
            'transfers' => 0,
            'investments' => 0,
        ];
    }

    /**
     * Met à jour les données du compte.
     *
     * @param Account $account
     */
    private function setAccountData(Account $account): void
    {
        $accountName = $account->getFullName();
        $this->accounts[$accountName]['type'] = $account->getType()->getLabel();
        $this->accounts[$accountName]['opened'] = $account->getOpenedAt()->format('d/m/Y');
    }

    /**
     * Ajoute une nouvelle catégorie trouvée.
     *
     * @param Category $category
     */
    private function addCategory(Category $category): void
    {
        $categoryName = $category->getFullName();
        $this->categories[$categoryName] = [
            'name' => $categoryName,
            'type' => $category->getType() ? '[+]' : '[-]',
            'transactions' => 0,
        ];
    }
}
