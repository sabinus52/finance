<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Helper\DateRange;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Controleur de base pour les transactions et le rapprochement bancaire.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TransactionBase extends AbstractController
{
    /**
     * Retourne le formulaire du filtre.
     *
     * @return FormInterface
     */
    protected function createFormFilter(bool $isReconcilied): FormInterface
    {
        // Création du formulaire
        $form = $this->createFormBuilder()
            ->add('range', ChoiceType::class, [
                'label' => 'Plage :',
                'choices' => DateRange::getChoices(),
                'disabled' => $isReconcilied,
            ])
            ->add('state', ChoiceType::class, [
                'label' => 'Etat :',
                'choices' => [
                    'Tous les états' => null,
                    'Non rapproché' => Transaction::STATE_NONE,
                    'Rapproché' => Transaction::STATE_RECONCILIED,
                ],
                'choice_value' => fn ($value) => $value,
                'disabled' => $isReconcilied,
            ])
            ->getForm()
        ;

        // Remplit les champs en fonction de la page
        if ($isReconcilied) {
            $form->get('range')->setData(null);
            $form->get('state')->setData(Transaction::STATE_NONE);
        } else {
            $form->get('range')->setData(DateRange::LAST_90D);
            $form->get('state')->setData(Transaction::STATE_NONE);
        }

        return $form;
    }

    /**
     * Retourne le formulaire du solde du rapprochement bancaire.
     *
     * @return FormInterface
     */
    protected function createFormReconBalance(Account $account): FormInterface
    {
        $form = $this->createFormBuilder()
            ->add('balance', MoneyType::class, [
                'label' => 'Solde à rapprocher',
                'required' => false,
                'constraints' => new NotBlank(),
            ])
            ->getForm()
        ;
        $form->get('balance')->setData($account->getReconCurrent());

        return $form;
    }

    /**
     * Retourne la page de la liste des transactions filtrées.
     *
     * @Route("/account/{id}/transactions/ajax", name="account_get_transaction")
     */
    public function getListTransactionAjax(Request $request, TransactionRepository $repository, Account $account): Response
    {
        // Récupération des informations du formulaire du filtre
        $datas = $request->get('form');
        unset($datas['_token']);

        $session = $request->getSession();
        $session->set('filter', $datas);

        // Détermine les filtres
        $filters = [];
        foreach ($datas as $key => $value) {
            // Si null alors pas de filtre
            if ('' === $value || null === $value) {
                continue;
            }
            if ('range' === $key) {
                $range = new DateRange($value);
                $value = $range->getRange();
            }
            $filters[$key] = $value;
        }

        return $this->render('account/transaction-table.html.twig', [
            'transactions' => $repository->findByAccount($account, $filters),
            'isReconcilied' => false,
        ]);
    }
}
