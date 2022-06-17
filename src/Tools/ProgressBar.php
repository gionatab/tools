<?php
namespace Tools;
class ProgressBar
{
  protected $max_items;
  protected $min_items;
  protected $bar_length;
  protected $done_items;
  protected $active;
  
  public function __construct($max=1, $min=0, $bar_length=50)
  {
    $this->max_items  = $max;
    $this->min_items  = $min;
    $this->bar_length = $bar_length;
    $this->done_items = 1;
    $this->active = true;
  }
  
  public function deactivate() {
    $this->active = false;
  }

  public function activate() {
    $this->active = true;
  }

  public function toggle_bar()
  {
    $this->active = !$this->active;
  }

  /**
   * Permette di indicare se attivare o no la barra.
   * Se il valore non è accettato, la barra viene attivata di default.
   * @param bool $bool Lo stato della barra.
   * @return bool lo stato attuale della barra.
   */
  public function setActivationStatus($bool) {
    $this->active = is_bool($bool) ? $bool : true;
    return $this->active;
  }

  public function set_maxItems($items)
  {
    $this->max_items = $items;
  }
  
  public function clear()
  {
    $this->done_items = 1;
  }
  
  public function reset_bar($items, $bar_length=50)
  {
    $this->set_maxItems($items);
    $this->clear();
    $this->bar_length = $bar_length;
  }
  
  public function progress_bar($messaggio_opzionale = '', $fatti = -1){
    if(!$this->active)
    {
      return;
    }
    if($fatti < 0)
    {
      $fatti = $this->done_items;
    } else 
    {
      $this->done_items = $fatti;
    }
    if($fatti > $this->max_items)
      return false;
    $this->done_items++;
    $percentage = floor($fatti/$this->max_items * 100);
    $fatti_percentage = floor($fatti/$this->max_items * $this->bar_length);
    $mancanti = $this->bar_length - $fatti_percentage;
    $bar = '0% ['.str_repeat('=', $fatti_percentage).str_repeat('-', $mancanti).'] '. $percentage . '% ';
    $string = '(' . $fatti . '/' . $this->max_items . ')';
    if(!empty($messaggio_opzionale))
      $string .= ' '. $messaggio_opzionale;
    echo "\r\033[K"; //Ripulisce la riga, per poter sovrascrivere linee di lunghezza diversa.
    echo $bar . $string;
    
  }
}


?>