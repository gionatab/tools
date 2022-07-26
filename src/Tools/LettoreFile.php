<?php

namespace Up3Up\Tools;

class LettoreFile
{

  /* Il carattere usato per separare i valori in colonne. Esempio: in un .csv il carattere è ',' */
  protected $separatore_celle;

  /* Permette di leggere una parte del file e ignorare il resto */
  protected $partizione_righe;

  /* Permette di saltare l'inizio di un file fino ad una riga descritta da linea_inizio */
  protected $salta_inizio;

  /* Indica a quale riga iniziare la lettura del file, funziona solo se partizione_righe è impostato su 'true' */
  protected $linea_inizio;

  /* Indica a quale riga finire la lettura del file, funziona solo se partizione_righe è impostato su 'true' */
  protected $linea_fine;

  /* Permette di interrompere la lettura di un file alla riga indicata da linea_fine */
  protected $salta_fine; // attiva la funzionalità di finire l'importazione prima dell'EOF

  /** Il file viene letto in un array associativo se vero, o in un array con indici numerici se falso. **/
  protected $use_string_index;

  /** Durante la lettura del file usa i seguenti valori come chiavi dell'array associativo. **/
  protected $string_index;

  /** Indica se la prima riga deve essere saltata o, se use_string_index è true, usata come intestazione. Se false la prima riga del file viene presa come riga leggibile di valori. */
  protected $has_header;

  public function __construct($separatore_celle = ',', $ha_intestazione = true, $usa_intestazione_come_indice = true)
  {
    $this->separatore_celle   = $separatore_celle;
    $this->has_header = $ha_intestazione;
    $this->use_string_index = $usa_intestazione_come_indice;
  }

  public function setSeparator($separatore_celle)
  {
    $this->separatore_celle = $separatore_celle;
  }

  public function setRowPartition($partizione_righe, $linea_inizio = 0, $linea_fine = 0)
  {
    $this->partizione_righe = $partizione_righe;
    $this->linea_inizio     = $linea_inizio;
    $this->linea_fine       = $linea_fine;
    $this->salta_inizio     = $this->partizione_righe && $this->linea_inizio > 0;
    $this->salta_fine       = $this->partizione_righe && $this->linea_fine > 0;
  }

  public function useStringIndex($usa = true)
  {
    $this->use_string_index = $usa;
    $this->has_header = $usa;
  }

  public function setStringIndex($index)
  {
    $this->string_index = $index;
    $this->useStringIndex();
  }

  public function setHasHeader($has_header = true)
  {
    $this->has_header = $has_header;
  }

  /**
   * La funzione prende un file CSV e lo converte in un array.
   * Ogni riga rappresenta un elemento dell'array.
   * Se la prima riga del file è un'intestazione, è possibile usare l'intestazione come chiavi dell'array associativo per ogni elemento, altrimenti viene usata la posizione della colonna.
   * @param string $nome_file Il nome del file da leggere
   * @param bool $trim se true, viene fatto un trim() di ogni valore del csv.
   * @param bool $converti_spazi_index se true, se l'intestazione viene usata come chiavi dell'array associativo e sono presenti spazi, vengono convertiti in '_'.
   * @param int $length Indica il numero di caratteri nella riga più lunga del file. Velocizza la lettura del file, ma lasciare il valore a 0 è sempre meglio in quasi tutte le situazioni.
   * @return array il contenuto del file CSV come array. 
   */
  public function readCSV($nome_file, $trim = true, $converti_spazi_index = false, $length = 0)
  {
    $conta        = 0;
    $elementi     = [];
    $intestazioni = $this->string_index;
    $array_associativo = $this->use_string_index;
    if (($handle = fopen($nome_file, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, $length, $this->separatore_celle)) !== FALSE && ($conta < $this->linea_fine || !$this->salta_fine)) {
        if ($this->has_header && $conta == 0) {
          $intestazioni = $data;
          $carattere    = is_bool($converti_spazi_index) ? '_' : $converti_spazi_index;

          foreach ($intestazioni as $key => $intestazione) {
            $intestazione = trim(preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $intestazione));
            if ($converti_spazi_index)
              $intestazione = str_replace(' ', $carattere, $intestazione);
            $intestazioni[$key] = $intestazione;
          }
        } elseif ($conta >= $this->linea_inizio || !$this->salta_inizio) {
          $temp = [];
          if ($array_associativo && !empty($intestazioni)) {
            for ($i = 0; $i < count($data); $i++) {
              $key          = $intestazioni[$i];
              $value        = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $data[$i]);
              $value        = $trim ? trim($value) : $value;
              $temp[$key] = $value;
            }
          } else {
            foreach ($data as $key => $element) {
              $temp[$key] = trim($element);
            }
            //$temp = $data;
          }

          $elementi[] = $temp;
        }
        $conta++;
      }
      fclose($handle);
    } else {
      echo "ERRORE LETTURA FILE.\n";
    }
    return $elementi;
  }

  /**
   * Funzione che prende un valore con chiavi numeriche e lo trasforma in un array con chiavi personalizzate.
   * Funziona solo su array monodimensionali.
   */
  public function creaIntestazione($arr, $intestazione)
  {
    $risultato = [];
    foreach ($arr as $key => $value) {
      if (isset($intestazione[$key]))
        $risultato[$intestazione[$key]] = $value;
    }
    return $risultato;
  }
}
