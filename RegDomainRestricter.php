<?php

class RegDomainRestricter extends PluginBase{

    protected $storage = 'DbStorage';
    static protected $description = 'Restrict public signups for Limesurvey. Checks email domain after each beforeRegister event.';
    static protected $name = 'RegDomainRestricter';
    protected $surveyId;

    public function init()
    {
      $this->subscribe('beforeRegister');
      $this->subscribe('beforeSurveySettings');
      $this->subscribe('newSurveySettings');
    }

    protected $settings = array(
      'bUse' => array(
        'type' => 'select',
        'options' => array(
          0 => 'No',
          1 => 'Yes'
        ),
        'default' => 0,
        'label' => 'Restrict domains by default for all registrable surveys?',
        'help' => 'Automatically restrict by the domains below for all survey registrations. This can be changed on a per survey basis.'
      ),
    'sDomains' => array(
      'type' => 'string',
      'default' => '',
      'label' => 'Email domains which are allowed to register for surveys',
      'help' => 'Please enter a comma separated list of domains. You must not include a space.'
    )

  );

  /**
* Add setting on survey level: send hook only for certain surveys / url setting per survey / auth code per survey / send user token / send question response
*/
public function beforeSurveySettings()
{
  $oEvent = $this->event;
  $oEvent->set("surveysettings.{$this->id}", array(
    'name' => get_class($this),
    'settings' => array(
      'bUse' => array(
        'type' => 'select',
        'options' => array(
          0 => 'No',
          1 => 'Yes',
          2 => 'Use site default setting'
        ),
        'default' => 2,
        'label' => 'Restrict domains that can register for this survey (if registration is turned on)?',
        'help' => 'If you want to restrict the email domains that can sign up for this survey, please choose Yes.',
        'current'=> $this->get('bUse','Survey',$oEvent->get('survey')),
      ),
      'bDomainOverwrite' => array(
        'type' => 'select',
        'options' => array(
          0 => 'No',
          1 => 'Yes'
        ),
        'label' => 'Overwrite the global list of allowed email domains?',
        'help' => 'Choose yes if you want to allow a different list of domains for this survey than those specified in the global settings. Will only work if Yes is chosen above, instead of use site global settings. As a best practice, you should choose Yes and explicitly list the domains below to ensure that only the domains you want are allowed.',
        'current'=> $this->get('bDomainOverwrite','Survey',$oEvent->get('survey')),
      ),
      'sDomains' => array(
        'type' => 'string',
        'label' => 'Email domains which are allowed to register this survey',
        'help' => 'Leave blank to use global settings. Please enter a comma separated list of domains. You must not include a space.',
        'current'=> $this->get('sDomains','Survey',$oEvent->get('survey')),
      )
    )
  ));
}

/**
  * Save the settings
  */
  public function newSurveySettings()
{
    $event = $this->event;
    foreach ($event->get('settings') as $name => $value)
    {
        /* In order use survey setting, if not set, use global, if not set use default */
        $default=$event->get($name,null,null,isset($this->settings[$name]['default'])?$this->settings[$name]['default']:NULL);
        $this->set($name, $value, 'Survey', $event->get('survey'),$default);
    }
}

private function isDomainRestrictionDisabled($sSurveyId)
{
  return ($this->get('bUse','Survey',$sSurveyId)==0)||(($this->get('bUse','Survey',$sSurveyId)==2) && ($this->get('bUse',null,null,$this->settings['bUse'])==0));
}


private function _getEmail($iSurveyId){
       Yii::import('application.controllers.RegisterController');
       $RegisterController= new RegisterController('register');
       $aFieldValue=$RegisterController->getFieldValue($iSurveyId);
       return $aFieldValue['sEmail'];

}

public function beforeRegister(){
  $iSurveyId=$this->getEvent()->get('surveyid');
  if($this->isDomainRestrictionDisabled($iSurveyId))
  {
      return;
  }

  $email = $this->_getEmail($iSurveyId);

  if(empty($email)) {
    return;
  }

  $emailDomain = strtolower(substr(strrchr($email, "@"), 1));

  $domains = (($this->get('bDomainOverwrite','Survey',$iSurveyId)==='1') && ($this->get('bUse','Survey',$sSurveyId)==1)) ? $this->get('sDomains','Survey',$iSurveyId) : $this->get('sDomains',null,null,$this->settings['sDomains']);
  $domains = explode(',', $domains);

  if (in_array($emailDomain, $domains)){
    return;
  } else {
    $this->getEvent()->set('aRegisterErrors', array("The email address you have entered is not from an accepted domain for this survey. Please try with an email from an accepted domain."));
  }


}

}
