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
        'label' => 'Restrict by domains by default for all registerable surveys?',
        'help' => 'Automatically restrict by the domains below for all survey registrations. This can be changed on a per survey basis.'
      ),
    'domains' => array(
      'type' => 'string',
      'default' => '',
      'label' => 'Email domains which are allowed to register',
      'help' => 'Please enter a comma separated list of domains. You must not include a space.'
    )

  );

}
