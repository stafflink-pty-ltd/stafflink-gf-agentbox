<?php 

class Agentbox_bootstrap
{

    public function start()
    {
        require_once GF_Agentbox_Bootstrap_DIR . '/vendor/autoload.php';

        require_once dirname( __FILE__ ) . '/classes/Inc/abstract-class-connection.php';
        require_once dirname( __FILE__ ) . '/classes/Inc/class-endpoint-configuration.php';
        require_once dirname( __FILE__ ) . '/classes/Inc/interface-connection.php';
        require_once dirname( __FILE__ ) . '/classes/Inc/class-logger.php';

        require_once dirname( __FILE__ ) . '/classes/Agentbox/class-agentbox.php';
        require_once dirname( __FILE__ ) . '/classes/Agentbox/class-agentbox-contact.php';
        require_once dirname( __FILE__ ) . '/classes/Agentbox/class-agentbox-client.php';
    }
}