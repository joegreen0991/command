<?php namespace Command;

Class StdOutput extends Psr\Log\AbstractLogger {
  
  public static $colors = array(
      LOGGER::DEBUG => '0;36', // Cyan
      LOGGER::INFO => '0;32', // Green
      LOGGER::NOTICE => '1;33', // Yellow
      LOGGER::WARNING => '0;35', // Purple
      LOGGER::ERROR => '0;31', // Red
      LOGGER::CRITICAL => array('0;30','43'), // Black/Yellow
      LOGGER::ALERT => array('1;37','45'), // White/Purple
      LOGGER::EMERGENCY => array('1;37','41'), // White/Red
   );
  
  public function log($level, $message, $context)
  {
        $lines = array(
            'Message : ' . $message,
            'Level : ' . $level
        );
        
        if (isset($record['context']['exception'])) {
            $lines[] = 'File : ' . $record['context']['exception']['file'];
            $lines[] = 'Trace : ';
            
            foreach($record['context']['exception']['trace'] as $line){
                $lines[] = self::TAB . $line;
            }
            
            unset($record['context']['exception']);
        }
           
        foreach ($record['context'] as $key => $val) {
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
            return "\n\033[{$colors[0]}m\033[{$colors[1]}m" . $pad . $string . $pad . "\033[0m\n";
        }else{
            return "\n\033[{$colors}m" . $string . "\033[0m\n";
        }
  }
  
}
