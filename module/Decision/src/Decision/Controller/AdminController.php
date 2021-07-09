<?php

namespace Decision\Controller;

use Doctrine\ORM\EntityManager;
use Zend\Http\Response;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Console\Request as ConsoleRequest;

class AdminController extends AbstractActionController
{

    /**
     * @var \Decision\Service\Decision
     */
    private $decisionService;

    public function __construct(\Decision\Service\Decision $decisionService)
    {
        $this->decisionService = $decisionService;
    }

    /**
     * Notes upload action.
     */
    public function notesAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($this->decisionService->uploadNotes($request->getPost(), $request->getFiles())) {
                return new ViewModel([
                    'success' => true
                ]);
            }
        }

        return new ViewModel([
            'form' => $this->decisionService->getNotesForm()
        ]);
    }

    /**
     * Document upload action.
     */
    public function documentAction()
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');
        $meetings = $this->decisionService->getMeetingsByType('AV');
        $meetings = array_merge($meetings, $this->decisionService->getMeetingsByType('VV'));
        if (is_null($number) && !empty($meetings)) {
            $number = $meetings[0]->getNumber();
            $type = $meetings[0]->getType();
        }
        $request = $this->getRequest();
        $success = false;
        if ($request->isPost()) {
            if ($this->decisionService->uploadDocument($request->getPost(), $request->getFiles())) {
                $success = true;
            }
        }
        $meeting = $this->getDecisionService()->getMeeting($type, $number);

        return new ViewModel([
            'form' => $this->decisionService->getDocumentForm(),
            'meetings' => $meetings,
            'meeting' => $meeting,
            'number' => $number,
            'success' => $success,
            'reorderDocumentForm' => $this->decisionService->getReorderDocumentForm(),
        ]);
    }

    public function deleteDocumentAction()
    {
        $this->decisionService->deleteDocument($this->getRequest()->getPost());
        return $this->redirect()->toRoute('admin_decision/document');
    }

    public function changePositionDocumentAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->getResponse()->setStatusCode(Response::STATUS_CODE_405); // Method Not Allowed
        }

        $form = $this->decisionService->getReorderDocumentForm()
            ->setData($this->getRequest()->getPost());

        if (!$form->isValid()) {
            return $this->getResponse()
                ->setStatusCode(Response::STATUS_CODE_400) // Bad Request
                ->setContent(Json::encode($form->getMessages()));
        }

        $data = $form->getData();
        $id = $data['document'];
        $moveDown = ($data['direction'] === 'down') ? true : false;

        // Update ordering document
        $this->decisionService->changePositionDocument($id, $moveDown);

        return $this->getResponse()->setStatusCode(Response::STATUS_CODE_204); // No Content (OK)
    }

    public function authorizationsAction()
    {
        $meetings = $this->decisionService->getMeetingsByType('AV');
        $number = $this->params()->fromRoute('number');
        $authorizations = [];
        if (is_null($number) && !empty($meetings)) {
            $number = $meetings[0]->getNumber();
        }

        if (!is_null($number)) {
            $authorizations = $this->decisionService->getAllAuthorizations($number);
        }

        return new ViewModel([
            'meetings' => $meetings,
            'authorizations' => $authorizations,
            'number' => $number
        ]);
    }

    public function sortDocumentsLegacyAction()
    {
        // Set timezone to prevent PHP Warning
        date_default_timezone_set('CET');

        $console = $this->getRequest();

        if (!$console instanceof ConsoleRequest) {
            throw new \RuntimeException('You can only use this action from a console!');
        }

        $legacySort = function (array $documents) {
            usort($documents, function ($a, $b) {
                $aa = preg_split("/(\.|\s)/", $a->getName());
                $bb = preg_split("/(\.|\s)/", $b->getName());
                for ($i = 0; $i < min(count($aa), count($bb)); $i++) {
                    if (!is_numeric($aa[$i]) && is_numeric($bb[$i])) {
                        return -1;
                    } elseif (is_numeric($aa[$i]) && !is_numeric($bb[$i])) {
                        return 1;
                    } elseif ($aa[$i] != $bb[$i]) {
                        return $aa[$i] < $bb[$i] ? -1 : 1;
                    }
                }
                return 0;
            });

            return $documents;
        };

        /** @var EntityManager $entityManager */
        $entityManager = $this->getServiceLocator()->get('decision_doctrine_em');

        // Perform INNER JOIN to only retrieve meetings with associated documents
        $dql = 'SELECT m, d ' .
            'FROM Decision\Model\Meeting m ' .
            'INNER JOIN m.documents d';

        $meetings = $entityManager->createQuery($dql)->getResult();
        foreach ($meetings as $meeting) {
            $unsortedDocuments = iterator_to_array($meeting->getDocuments()->getIterator());

            print(vsprintf('Sorting %d documents of %s %d...' . PHP_EOL, [
                count($unsortedDocuments), $meeting->getType(), $meeting->getNumber()
            ]));

            foreach ($legacySort($unsortedDocuments) as $index => $document) {
                $document->setDisplayPosition($index);
            }
        }

        $entityManager->flush();

        print('Done.' . PHP_EOL);
    }
}
