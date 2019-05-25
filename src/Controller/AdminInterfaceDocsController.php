<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Form\BambooDocsFluidVhTriggerFormType;
use App\Form\BambooDocsSurf20TriggerFormType;
use App\Form\BambooDocsSurfMasterTriggerFormType;
use App\Service\BambooService;
use App\Service\GraylogService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * "Documentation" menu entry of the web admin interface.
 * Allows to trigger rendering and deployment of documentation repositories
 */
class AdminInterfaceDocsController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @Route("/admin/docs", name="admin_docs_third_party")
     *
     * @param Request $request
     * @param LoggerInterface $logger
     * @param BambooService $bambooService
     * @param GraylogService $graylogService
     * @return Response
     */
    public function index(
        Request $request,
        LoggerInterface $logger,
        BambooService $bambooService,
        GraylogService $graylogService
    ): Response {
        $this->logger = $logger;

        $fluidVhForm = $this->getFluidVhForm($request, $bambooService);
        $surf20Form = $this->getSurf20Form($request, $bambooService);
        $surfMasterForm = $this->getSurfMasterForm($request, $bambooService);

        $recentLogsMessages = $graylogService->getRecentBambooDocsThirdPartyTriggers();

        return $this->render(
            'docs.html.twig',
            [
                'fluidVhForm' => $fluidVhForm->createView(),
                'surf20Form' => $surf20Form->createView(),
                'surfMasterForm' => $surfMasterForm->createView(),
                'logMessages' => $recentLogsMessages,
            ]
        );
    }

    /**
     * @param Request $request
     * @param BambooService $bambooService
     * @return FormInterface
     */
    protected function getFluidVhForm(Request $request, BambooService $bambooService): FormInterface
    {
        $fluidVhForm = $this->createForm(BambooDocsFluidVhTriggerFormType::class);
        $fluidVhForm->handleRequest($request);
        if ($fluidVhForm->isSubmitted() && $fluidVhForm->isValid()) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            $bambooTriggered = $bambooService->triggerDocumentationFluidVhPlan();
            if (!empty($bambooTriggered->buildResultKey)) {
                $this->addFlash(
                    'success',
                    'Triggered fluid view helper build'
                    . ' <a href="https://bamboo.typo3.com/browse/' . $bambooTriggered->buildResultKey . '">' . $bambooTriggered->buildResultKey . '</a>'
                    . ' of plan key "CORE-DRF".'
                );
                $this->logger->info(
                    'Triggered fluid view helper build "' . $bambooTriggered->buildResultKey . '".',
                    [
                        'type' => 'triggerBambooDocsThirdParty',
                        'bambooKey' => $bambooTriggered->buildResultKey,
                        'triggeredBy' => 'interface',
                    ]
                );
            } else {
                $this->addFlash(
                    'danger',
                    'Bamboo trigger not successful'
                    . ' of plan key "CORE-DRF".'
                );
            }
        }
        return $fluidVhForm;
    }

    /**
     * @param Request $request
     * @param BambooService $bambooService
     * @return FormInterface
     */
    protected function getSurf20Form(Request $request, BambooService $bambooService): FormInterface
    {
        $surfForm = $this->createForm(BambooDocsSurf20TriggerFormType::class);
        $surfForm->handleRequest($request);
        if ($surfForm->isSubmitted() && $surfForm->isValid()) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            $bambooTriggered = $bambooService->triggerDocumentationSurf20Plan();
            if (!empty($bambooTriggered->buildResultKey)) {
                $this->addFlash(
                    'success',
                    'Triggered fluid view helper build'
                    . ' <a href="https://bamboo.typo3.com/browse/' . $bambooTriggered->buildResultKey . '">' . $bambooTriggered->buildResultKey . '</a>'
                    . ' of plan key "CORE-DRS".'
                );
                $this->logger->info(
                    'Triggered TYPO3 Surf 2.0 build "' . $bambooTriggered->buildResultKey . '".',
                    [
                        'type' => 'triggerBambooDocsThirdParty',
                        'bambooKey' => $bambooTriggered->buildResultKey,
                        'triggeredBy' => 'interface',
                    ]
                );
            } else {
                $this->addFlash(
                    'danger',
                    'Bamboo trigger not successful'
                    . ' of plan key "CORE-DRS".'
                );
            }
        }
        return $surfForm;
    }

    /**
     * @param Request $request
     * @param BambooService $bambooService
     * @return FormInterface
     */
    protected function getSurfMasterForm(Request $request, BambooService $bambooService): FormInterface
    {
        $surfForm = $this->createForm(BambooDocsSurfMasterTriggerFormType::class);
        $surfForm->handleRequest($request);
        if ($surfForm->isSubmitted() && $surfForm->isValid()) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            $bambooTriggered = $bambooService->triggerDocumentationSurfMasterPlan();
            if (!empty($bambooTriggered->buildResultKey)) {
                $this->addFlash(
                    'success',
                    'Triggered fluid view helper build'
                    . ' <a href="https://bamboo.typo3.com/browse/' . $bambooTriggered->buildResultKey . '">' . $bambooTriggered->buildResultKey . '</a>'
                    . ' of plan key "CORE-DRSM".'
                );
                $this->logger->info(
                    'Triggered TYPO3 Surf master build "' . $bambooTriggered->buildResultKey . '".',
                    [
                        'type' => 'triggerBambooDocsThirdParty',
                        'bambooKey' => $bambooTriggered->buildResultKey,
                        'triggeredBy' => 'interface',
                    ]
                );
            } else {
                $this->addFlash(
                    'danger',
                    'Bamboo trigger not successful'
                    . ' of plan key "CORE-DRSM".'
                );
            }
        }
        return $surfForm;
    }
}
