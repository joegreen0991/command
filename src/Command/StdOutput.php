<?php namespace Command;

Class StdOutput extends \Psr\Log\AbstractLogger {
  
  // Set up shell colors
    private $foreground_colors = array(
        'black' => '0;30',
        'dark_gray' => '1;30',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'green' => '0;32',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'red' => '0;31',
        'light_red' => '1;31',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'light_gray' => '0;37',
        'white' => '1;37',
    );
    // Set up shell colors
    private $background_colors = array(
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'light_gray' => '47',
    );
    
    // Returns colored string
    public function getColoredString($string, $foreground_color = null, $background_color = null)
    {
        $colored_string = "";

        // Check if given foreground color found
        if (isset($this->foreground_colors[$foreground_color]))
        {
            $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if (isset($this->background_colors[$background_color]))
        {
            $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .= $string . "\033[0m";

        return $colored_string;
    }
  
  public static $colors = array(
      LOGGER::DEBUG => 'cyan', // Cyan
      LOGGER::INFO => 'green', // Green
      LOGGER::NOTICE => 'yellow', // Yellow
      LOGGER::WARNING => 'purple', // Purple
      LOGGER::ERROR => 'red', // Red
      LOGGER::CRITICAL => array('black','yellow'), // Black/Yellow
      LOGGER::ALERT => array('white','purple'), // White/Purple
      LOGGER::EMERGENCY => array('white','red'), // White/Red
   );
  
  public function log($level, $message, $context)
  {
      die('here');
        $lines = array(
            'Message : ' . $message,
            'Level : ' . $level
        );
        
        if (isset($context['exception'])) {
            $lines[] = 'File : ' . $context['exception']['file'];
            $lines[] = 'Trace : ';
            
            foreach($context['exception']['trace'] as $line){
                $lines[] = self::TAB . $line;
            }
            
            unset($context['exception']);
        }
           
        foreach ($context as $key => $val) {
            $lines[] = $key . ' : ' . (is_scalar($val) ? $val : $this->toJson($val));
        }
        
        
        // Wrap the whole thing in a nice red square
        
        // Get the max row length
        $max = max(array_map('strlen', $lines));
        
        // Pad each of the rows to this length
        foreach($lines as $i => $line){
            $lines[$i] = self::TAB . str_pad($line, $max + 5);
        }
        
        $string = implode(PHP_EOL, $lines);
        
        $colors = self::$colors[$level];
        
        if(is_array($colors)){
            // Create a padding string of empty spaces the same length as the max row
            $pad = PHP_EOL . str_repeat(self::TAB . str_repeat(" ", $max + 5) . PHP_EOL, 2);

            // Create the coloured string
            $string = PHP_EOL . $this->getColouedString($pad . $string . $pad, $colors[0], $colors[1]) . PHP_EOL;
            
        }else{
            $string = $this->getColoredString($string, $colors);
        }
        
        echo $string;
  }
  
}
