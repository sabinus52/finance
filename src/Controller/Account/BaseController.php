<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Controller\Account;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Helper\DateRange;
use App\Repository\TransactionRepository;
use App\Transaction\TransactionModelInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur de base pour les transactions et le rapprochement bancaire.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class BaseController extends AbstractController
{
    /**
     * Compte en cours.
     */
    protected Account $account;

    /**
     * @param array<mixed> $parameters
     */
    protected function indexAccount(Request $request, Account $account, string $template, array $parameters = []): Response
    {
        $formFilter = $this->createFormFilter();

        // Remplit le formulaire avec les données de la session
        $session = $request->getSession();
        $filter = $session->get('filter');
        if (null !== $filter) {
            foreach ($filter as $key => $value) {
                if ($formFilter->has($key)) {
                    $formFilter->get($key)->setData($value);
                }
            }
        }

        return $this->render($template, array_merge([
            'forceMenuActiv' => sprintf('account%s', $account->getId()),
            'account' => $account,
            'form' => [
                'filter' => $formFilter->createView(),
            ],
        ], $parameters));
    }

    /**
     * Création d'une transaction.
     */
    protected function createTransaction(Request $request, Account $account, TransactionModelInterface $modelTransaction): Response
    {
        $modelTransaction->setAccount($account);
        $transaction = $modelTransaction->getTransaction();

        $form = $this->createForm($modelTransaction->getFormClass(), $transaction, $modelTransaction->getFormOptions() + ['isNew' => true]);
        if ($modelTransaction->isTransfer()) {
            $form->get('source')->setData($transaction->getAccount());
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $modelTransaction->checkForm($form)) {
            $modelTransaction->insert($form);
            $this->addFlash('success', sprintf('La création %s a bien été prise en compte', $modelTransaction->getMessage()));

            return new Response('OK');
        }

        return $this->render('@OlixBackOffice/Include/modal-form-horizontal.html.twig', [
            'form' => $form,
            'modal' => [
                'title' => sprintf('Créer %s', $modelTransaction->getFormTitle()),
            ],
        ]);
    }

    /**
     * Retourne le formulaire du filtre.
     */
    protected function createFormFilter(): FormInterface
    {
        // Création du formulaire
        $form = $this->createFormBuilder()
            ->add('range', ChoiceType::class, [
                'label' => 'Plage :',
                'choices' => DateRange::getChoices(),
            ])
            ->add('state', ChoiceType::class, [
                'label' => 'Etat :',
                'choices' => [
                    'Tous les états' => null,
                    'Non rapproché' => Transaction::STATE_NONE,
                    'Rapproché' => Transaction::STATE_RECONCILIED,
                ],
                'choice_value' => static fn ($value) => $value,
            ])
            ->getForm()
        ;

        $form->get('range')->setData(DateRange::LAST_90D);
        $form->get('state')->setData(Transaction::STATE_NONE);

        return $form;
    }

    /**
     * Retourne la page de la liste des transactions filtrées.
     */
    #[Route(path: '/account/{id}/transactions/ajax', name: 'account_get_transaction')]
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
            if ('' === $value) {
                continue;
            }
            if (null === $value) {
                continue;
            }
            if ('range' === $key) {
                $range = new DateRange($value);
                $value = $range->getRange();
            }
            $filters[$key] = $value;
        }

        return $this->render('account/transaction-table.html.twig', [
            'account' => $account,
            'transactions' => $repository->findByAccount($account, $filters),
        ]);
    }
}
