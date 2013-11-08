<?php namespace Command;

use Pimple;

class Application extends Pimple {

    protected $commands = array();
    protected $commandDescriptions = array();
    
    protected $autoResolveNamespaceSeparator = ':';
    
    protected $autoResolveCommands = false;
    
    public function setAutoResolveNamespaceSeparator($sep)
    {
        $this->autoResolveNamespaceSeparator = $sep;
    }
    
    public function setAutoResolveCommands($status)
    {
        $this->autoResolveCommands = (bool)$status;
    }

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

        if(isset($this->commands[$command]))
        {
            return $this->commands[$command];
        }
        
        if($this->autoResolveCommands) {
            
            $class = str_replace(' ','\\', ucwords(str_replace($this->autoResolveNamespaceSeparator,' ',$command)));

            if(class_exists($class))
            {
                if(!is_subclass_of($class, __NAMESPACE__ . '\\Command'))
                {
                    throw new CommandNotFoundException("Command [$command] must extend " .  __NAMESPACE__ . '\\Command');
                }
                
                return $class;
            }
        }
        
        throw new CommandNotFoundException("Command [$command] does not exist");
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
        
        $resolved->setApplication($this);

        $resolved->fire();

    }

}
