<?php

namespace Activity\Controller;

use Activity\Form\ActivityCalendarProposal as ActivityCalendarProposalForm;
use Activity\Service\{
    AclService,
    ActivityCalendar as ActivityCalendarService,
    ActivityCalendarForm as ActivityCalendarFormService,
};
use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;
use Laminas\Mvc\I18n\Translator;

class ActivityCalendarController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly ActivityCalendarService $calendarService,
        private readonly ActivityCalendarFormService $calendarFormService,
        private readonly ActivityCalendarProposalForm $calendarProposalForm,
        private readonly array $calendarConfig,
    ) {
    }

    public function indexAction(): ViewModel
    {
        $config = $this->calendarConfig;

        return new ViewModel(
            [
                'options' => $this->calendarService->getUpcomingOptions(),
                'editableOptions' => $this->calendarService->getEditableUpcomingOptions(),
                'APIKey' => $config['google_api_key'],
                'calendarKey' => $config['google_calendar_key'],
                'success' => (bool) $this->params()->fromQuery('success', false),
                'canCreate' => $this->calendarService->canCreateProposal(),
                'canApprove' => $this->calendarService->canApproveOption(),
            ]
        );
    }

    public function deleteAction(): Response|ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->calendarService->deleteOption((int) $request->getPost()['option_id']);
            return $this->redirect()->toRoute('activity_calendar');
        }

        return $this->notFoundAction();
    }

    public function approveAction(): Response|ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->calendarService->approveOption((int) $request->getPost()['option_id']);
            return $this->redirect()->toRoute('activity_calendar');
        }

        return $this->notFoundAction();
    }

    public function createAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'activity_calendar_proposal')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create activity proposals')
            );
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->calendarProposalForm->setData($request->getPost()->toArray());

            if ($this->calendarProposalForm->isValid()) {
                if ($this->calendarService->createProposal($this->calendarProposalForm->getData())) {
                    return $this->redirect()->toRoute(
                        'activity_calendar',
                        [],
                        [
                            'query' => [
                                'success' => 'true',
                            ],
                        ],
                    );
                }
            }
        }

        $period = $this->calendarFormService->getCurrentPeriod();

        return new ViewModel(
            [
                'period' => $period,
                'form' => $this->calendarProposalForm,
            ]
        );
    }
}
