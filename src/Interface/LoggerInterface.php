<?php 

namespace Stafflink\Interface;

interface LoggerInterface {
    

    public function log_what_happened( $text ) : void;
}