<?php
namespace Tools;
class IOhelper
{
  protected $running_from_browser;
  protected $newline_char;
  
  public function __construct($running_from_browser)
  {
    $this->running_from_browser = $running_from_browser;
    $this->newline_char = $this->running_from_browser ? '<br>' : PHP_EOL;
  }
  
  public function echo_nl($string, $newline_char=null)
  {
    if(empty($newline_char))
      $newline_char = $this->newline_char;
    if(!$this->running_from_browser)
      $string = strip_tags($string);
    echo $string, $newline_char;
  }
  public function print_array($array)
  {
    if ($this->running_from_browser)
			echo "<pre>";
		print_r($array);
		if ($this->running_from_browser)
			echo "</pre>";
  }

  public function print_xml($xml)
  {
    echo '<br><textarea rows="20" cols="100" style="border:none;">';
    print_r($xml);
    echo '</textarea><br>';
  }
  
  public function nl()
  {
    $this->echo_nl('');
  }
  
  public function array_to_string($array, $newline_char=null)
  {
    if(!isset($newline_char))
    {
      $newline_char = $this->newline_char;
    }
    $string = implode($newline_char, $array);
    echo $string, $newline_char;
  }
}
?>
