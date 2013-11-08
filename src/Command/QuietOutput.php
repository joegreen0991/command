<?php

namespace Command;

use Psr\Log\AbstractLogger;

Class QuietOutput extends AbstractLogger {

    public function log($level, $message, array $context = array())
    {

    }

}
