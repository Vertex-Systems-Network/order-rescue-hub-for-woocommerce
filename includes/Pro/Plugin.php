<?php

namespace WPEssential\OrderRescueHub\Pro;

use WPEssential\OrderRescueHub\Repository\CaseRepository;
use WPEssential\OrderRescueHub\Service\CaseService;

final class Plugin
{
    private CaseRepository $repository;
    private CaseService $case_service;

    public function __construct(CaseRepository $repository, CaseService $case_service)
    {
        $this->repository = $repository;
        $this->case_service = $case_service;
    }

    public function register(): void
    {
        $assignment = new AssignmentService($this->repository);
        $alerts = new AlertService($this->repository);
        $sla = new SlaClock($this->repository);
        $workflows = new WorkflowEngine($this->repository, $this->case_service, $assignment);

        add_action('wperh_case_detected', array($sla, 'start'), 10);
        add_action('wperh_exception_sweep', array($sla, 'evaluate_all'), 20);
        $alerts->register();
        $workflows->register();
        (new Controller($this->repository, $assignment, $alerts))->register();

    }
}
