<?php

namespace Activity\Service;

use Activity\Model\Activity as ActivityModel;
use Activity\Model\ActivitySignup;
use Application\Service\AbstractAclService;
use Decision\Model\Member;

class Signup extends AbstractAclService
{
    /**
     * Get the ACL.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('activity_acl');
    }

    /**
     * Get the default resource ID.
     *
     * This is used by {@link isAllowed()} when no resource is specified.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'activitySignup';
    }

    /**
     * Return the form for signing up
     */
    public function getForm()
    {
        if (!$this->isAllowed('signup', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You need to be logged in to sign up for this activity')
            );
        }

        return $this->getServiceManager()->get('activity_form_activity_signup');
    }

    /**
     * Get a list of all the members that are signed up for an activity.
     *
     * @param ActivityModel $activity
     *
     * @return array
     */
    public function getSignedUpUsers(ActivityModel $activity)
    {
        if (!$this->isAllowed('view', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view who is signed up for this activity')
            );
        }

        $signUpMapper = $this->getServiceManager()->get('activity_mapper_signup');
        return $signUpMapper->getSignedUp($activity->getId());
    }

    /**
     * Gets an array of 
     * 
     * @param ActivityModel $activity
     * @return array
     */
    public function getSignedUpData(ActivityModel $activity)
    {
        if (!$this->isAllowed('view', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the sign up data')
            );
        }

        $fieldValueMapper = $this->getServiceManager()->get('activity_mapper_activity_field_value');
        $result = [];
        foreach($activity->getSignUps() as $signup){
            $entry = [];
            $entry['member'] = $signup->getUser()->getMember()->getFullName();
            $entry['values'] = [];
            foreach($fieldValueMapper->getFieldValuesBySignup($signup) as $fieldValue){
                $entry['values'][$fieldValue->get('field')->get('id')] = $fieldValue->get('value');
            }
            $result[] = $entry;            
        }
        return $result;
    }

    /**
     * Check if a member is signed up for an activity.
     *
     * @param ActivityModel          $activity
     * @param \Decision\Model\Member $user
     *
     * @return bool
     */
    public function isSignedUp(ActivityModel $activity, Member $user)
    {
        if (!$this->isAllowed('checkUserSignedUp', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view the activities')
            );
        }

        $signUpMapper = $this->getServiceManager()->get('activity_mapper_signup');

        return $signUpMapper->isSignedUp($activity->getId(), $user->getLidnr());
    }

    /**
     * Sign up  an activity with the specified field values.
     *
     * @param ActivityModel $activity
     * @param array $fieldResults
     */
    public function signUp(ActivityModel $activity, array $fieldResults)
    {
        if (!$this->isAllowed('signup', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You need to be logged in to sign up for this activity')
            );
        }

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        // Find the current user
        $user = $em->merge(
            $this->getServiceManager()->get('user_role')
        );

        $signup = new ActivitySignup();
        $signup->setActivity($activity);
        $signup->setUser($user);
        $optionMapper = $this->getServiceManager()->get('activity_mapper_activity_option');
        foreach ($activity->getFields() as $field){
            $fieldValue = new \Activity\Model\ActivityFieldValue();
            $fieldValue->setField($field);
            $value = $fieldResults[$field->get('id')];
            
            //Change the value into the actual format
            switch ($field->get('type')) {
                case 0://'Text'
                    break;
                case 1://'Yes/No'
                    $value =  ($value) ? 'Yes' : 'No';
                    break;
                case 2://'Number'
                    break;
                case 3://'Choice' 
                    var_dump($value);
                    $value = $optionMapper->getOptionById((int)$value)->get('value');
                    break;
            }
            $fieldValue->setValue($value);
            $fieldValue->setSignup($signup);
            $em->persist($fieldValue);
        }
        $em->persist($signup);
        $em->flush();
    }

    /**
     * Undo an activity sign up.
     *
     * @param ActivityModel $activity
     * @param Member        $user
     */
    public function signOff(ActivityModel $activity, Member $user)
    {
        if (!$this->isAllowed('signoff', 'activitySignup')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You need to be logged in to sign off for this activity')
            );
        }

        $signUpMapper = $this->getServiceManager()->get('activity_mapper_signup');
        $signUp = $signUpMapper->getSignUp($activity->getId(), $user->getLidnr());

        // If the user was not signed up, no need to signoff anyway
        if (is_null($signUp)) {
            return;
        }

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        $values = $this->getActivityFieldValueMapper()->getFieldValuesBySignup($signUp);
        foreach($values as $value){
            $em->remove($value);
        }
        $em->remove($signUp);
        $em->flush();
    }

    /**
     * Is the currently logged in user allowed to signup
     *
     * @return bool
     */
    public function isAllowedToSubscribe()
    {
        return $this->isAllowed('signup', 'activitySignup');
    }

    /**
     * @return \Activity\Mapper\ActivityFieldValue
     */
    public function getActivityFieldValueMapper(){
        
        return $this->getServiceManager()->get('activity_mapper_activity_field_value');
    }
}
