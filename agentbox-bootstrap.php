<?php

class Agentbox_bootstrap
{

    public function start()
    {
        require_once dirname(__FILE__) . '/classes/Inc/AbstractBaseConnection.php';
        require_once dirname(__FILE__) . '/classes/Inc/EndpointConfiguration.php';
        require_once dirname(__FILE__) . '/classes/Inc/ConnectionInterface.php';
        require_once dirname(__FILE__) . '/classes/Inc/ClassLogger.php';
        require_once dirname(__FILE__) . '/classes/Inc/AgentboxEPLIntegration.php';

        require_once dirname(__FILE__) . '/classes/Agentbox/AgentboxClass.php';
        require_once dirname(__FILE__) . '/classes/Agentbox/AgentboxContact.php';
        require_once dirname(__FILE__) . '/classes/Agentbox/AgentboxClient.php';
    }
}