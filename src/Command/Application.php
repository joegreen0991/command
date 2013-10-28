<?php namespace Command;

use Pimple;

class Application extends Pimple {

    protected $commands = array();
    protected $commandDescriptions = array();

    public function registerCommand($name, $command, $description = '')
    {

        if (!is_string($command))
        {
            $command = $this->share($command);
        }

        $this->commands[$name] = $command;
        $this->commandDescriptions[$name] = $description;

        return $this;
    }
    
    public function registerCommands(array $commands)
    {

        foreach($commands as $name => $command){

            if(is_array($command)){
                
                if(!isset($command[0]) && $command[1]){
                    throw new Exception("If the command is set as an array, the form must be 'array(\$command,\$description)' for command: [$name]");
                }
                
                list($command, $description) = $command;
            }else{
                $description = '';
            }
            
            $this->registerCommand($name, $command, $description);
        }

        return $this;
    }

    public function getCommand($command)
    {

        if (!isset($this->commands[$command]))
        {
            throw new CommandNotFoundException("Command [$command] does not exist");
        }

        return $this->commands[$command];
    }

    public function runFromArgv()
    {

        if($_SERVER['argc'] < 2){
            throw new CommandNotFoundException("Command not specified");
        }
        
        list($arguments, $options) = Command::parseArgs(array_slice($_SERVER['argv'], 2));

        $this->run($_SERVER['argv'][1], $arguments, $options);
    }

    public function run($command = null, $inputArgs = array(), $inputOptions = array())
    {

        $resolved = $this->getCommand($command);

        if (is_string($resolved))
        {
            $resolved = new $resolved($command, $inputArgs, $inputOptions);
        }

        $resolved->fire();
    }

}