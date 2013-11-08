<?php

namespace Command;

abstract class Command {

    const OPTIONAL = 1;
    const REQUIRED = 2;
    const VALUE_NONE = 4;
    const VALUE_IS_ARRAY = 8;

    private static $stty = null;
    private $arguments = array();
    private $options = array();
    protected $name;
    protected $app;
    protected $output;

    public function __construct($name, $arguments = array(), $options = array(), LoggerInterface $output = null)
    {
        $this->name = $name;

        $this->output = $output ? : new StdOutput();

        if (isset($options['help']) || isset($options['h']))
        {
            $this->showUsage();
            $this->bail();
        }

        $this->buildArguments($arguments);
        $this->buildOptions($options);

        if($this->getOption('quiet')){
            $this->output = new QuietOutput();
        }
    }

    public function setApplication(Application $app)
    {
        $this->app = $app;
    }

    public function setOutput(LoggerInterface $output)
    {
        $this->output = $output;
    }

    public function getOutput()
    {
        return $this->output;
    }

    private function buildArguments($arguments)
    {

        foreach ($this->getArguments() as $i => $argument) {

            if (!isset($arguments[$i]) && ($argument[1] === self::REQUIRED))
            {
                $this->showUsage();
                $this->bail();
            }

            if (isset($arguments[$i]))
            {
                $this->arguments[$argument[0]] = $arguments[$i];
            }
        }
    }

    private function buildOptions($options)
    {

        foreach ($this->getMergedOptions() as $option) {

            $set = isset($options[$option[0]]) || isset($options[$option[1]]);

            if ($set)
            {
                $value = isset($options[$option[0]]) ? $options[$option[0]] : $options[$option[1]];
            }

            $array = self::VALUE_IS_ARRAY | self::REQUIRED;

            if ($set && (($option[2] & $array) === $array))
            {
                $this->showUsage();
                $this->bail();
            }

            if ($set && !$value && (($option[2] & self::REQUIRED) === self::REQUIRED))
            {
                $this->fatal('Value for option "--' . $option[0] . ' (-' . $option[1] . ')" is required');
            }

            if ($set && strlen($value) > 0 && (($option[2] & self::VALUE_NONE) === self::VALUE_NONE))
            {
                $this->fatal('Cannot set a value for option "--' . $option[0] . ' (-' . $option[1] . ')"');
            }

            if ($set)
            {
                $this->options[$option[0]] = $value;
            }
        }
    }

    private function hasSttyAvailable()
    {
        if (null !== self::$stty)
        {
            return self::$stty;
        }

        exec('stty 2>&1', $output, $exitcode);

        return self::$stty = $exitcode === 0;
    }

    private function showUsage()
    {

        $cmd = '';

        foreach ($this->getArguments() as $argument) {

            if ($argument[1] & self::REQUIRED === self::REQUIRED)
            {
                $cmd .= ' ' . $argument[0];
            } elseif (($argument[1] & self::OPTIONAL) === self::OPTIONAL)
            {
                $cmd .= ' [' . $argument[0] . ']';
            }
        }

        $this->output->info('Usage: ' . $this->name . ' ' . $cmd);

        $required = '';

        foreach ($this->getMergedOptions() as $argument) {

            $required .= "\n\t --" . $argument[0] . "\t" . '(-' . $argument[1];

            if (($argument[2] & self::REQUIRED) === self::REQUIRED)
            {
                $required .= '=""';
            } elseif (($argument[2] & self::OPTIONAL) === self::OPTIONAL)
            {
                $required .= '[=""]';
            }

            $required .= ")\t" . $argument[3];
        }

        $this->output->info($required);
    }

    protected function ask($question)
    {

        $this->output->info($question);

        return $this->readInput();
    }

    protected function secret($question)
    {

        if (defined('PHP_WINDOWS_VERSION_BUILD'))
        {
            $exe = __DIR__ . '/../Resources/bin/hiddeninput.exe';

            // handle code running from a phar
            if ('phar:' === substr(__FILE__, 0, 5))
            {
                $tmpExe = sys_get_temp_dir() . '/hiddeninput.exe';
                copy($exe, $tmpExe);
                $exe = $tmpExe;
            }

            $this->output->info($question);
            $value = rtrim(shell_exec($exe));
            $this->output->info('');

            if (isset($tmpExe))
            {
                unlink($tmpExe);
            }

            return $value;
        }

        if ($this->hasSttyAvailable())
        {
            $this->output->info($question);

            $sttyMode = shell_exec('stty -g');

            shell_exec('stty -echo');
            $value = $this->readInput();
            shell_exec(sprintf('stty %s', $sttyMode));

            if (false === $value)
            {
                throw new \RuntimeException('Aborted');
            }

            $this->output->info('');

            return $value;
        }

        if (false !== $shell = $this->getShell())
        {
            $this->output->info($question);
            $readCmd = $shell === 'csh' ? 'set mypassword = $<' : 'read -r mypassword';
            $command = sprintf("/usr/bin/env %s -c 'stty -echo; %s; stty echo; echo \$mypassword'", $shell, $readCmd);
            $value = rtrim(shell_exec($command));
            $this->output->infoe('');

            return $value;
        }

        return $this->ask($question);
    }

    protected function confirm($question)
    {

        $this->output->info($question . " Y/n");

        $line = strtolower($this->readInput());

        return ($line === 'y' || $line === 'yes');
    }

    private function readInput()
    {
        return trim(fgets(STDIN));
    }

    protected function fatal($output, $exitcode = 1)
    {
        $this->output->critical($output);
        $this->bail($exitcode);
    }

    public function onSigTerm($handler)
    {

        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }

    protected function bail($exitcode = 1)
    {
        exit($exitcode);
    }

    protected function getArgument($name, $default = null)
    {
        return isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
    }

    protected function getOption($name, $default = false)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    private function getMergedOptions()
    {
        return array_merge(array(
            array('help', 'h', self::VALUE_NONE, 'Display help'),
            array('quiet', 'q', self::VALUE_NONE, 'Suppress all output'),
            array('verbose', 'v', self::OPTIONAL, 'Set the verbosity level'),
                ), $this->getOptions());
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array();
    }

    public static function parseArgs($args)
    {
        // Build the arguments list
        $arguments = array();
        $options = array();

        $i = 0;

        foreach ($args as $arg) {

            if ($arg[0] === '-')
            {
                // it's an option
                $parts = explode('=', ltrim($arg, '-'));

                $options[$parts[0]] = isset($parts[1]) ? $parts[1] : true;
            } else
            {
                $arguments[$i++] = $arg;
            }
        }

        return array($arguments, $options);
    }

    public static function createFromCliArgs()
    {

        list($arguments, $options) = static::parseCliArgs(array_slice($_SERVER['argv'], 1));

        return new static($_SERVER['argv'][0], $arguments, $options);
    }

    public function configure()
    {
        
    }

    abstract public function fire();
}
