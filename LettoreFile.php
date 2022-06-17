<?php
namespace Tools;
class LettoreFile {
  /* NON USATO */
  protected $tipo;

  /* NON USATO */
  protected $direzione;

  /* Il carattere usato per separare i valori in colonne. Esempio: in un .csv il carattere è ',' */
  protected $separatore_celle;

  /* Il carattere usato per indicare che i valori di una cella devono essere salvati in un array */
  protected $separatore_valori;

  /* Il formato da usare per le date */
  protected $formato_data;

  /* Il formato da usare per valori numerici che rappresentano un valore monetario. */
  protected $formato_prezzo;

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

  /* Permette di prendere una parte di valori letti dal file e ignorare il resto */
  protected $partizione_elementi;

  /* Indica la quantità di elementi semplici da prendere dal file */
  protected $qta_semplici;

  /* Indica la quantità di elementi dipendenti dagli elementi semplici da prendere dal file */
  protected $qta_configurabili;

  /* 
      Indica il rapporto tra la quantità di elementi semplici indicata in qta_semplici, e la quantità di elementi dipendendi indicata in qta_configurabili. 
      Se si mette a 0 è possibile indicare un valore arbitrario in qta_configurabili. 
  */
  protected $configurabili_per_semplice;
  
  /** Il file viene letto in un array associativo se vero, o in un array con indici numerici se falso. **/
  protected $use_string_index;
  
  /** Durante la lettura del file usa i seguenti valori come chiavi dell'array associativo. **/
  protected $string_index;

  public function __construct( $tipo = '', $direzione = '', $separatore_celle = ',', $separatore_valori = '|', $separatore_titolo = '=', $formato_data = 'Y-m-d H:i:s', $formato_prezzo = '%01.2f' ) {
    $this->tipo               = $tipo;
    $this->direzione          = $direzione;
    $this->separatore_celle   = $separatore_celle;
    $this->separatore_valori  = $separatore_valori;
    $this->separatore_titolo  = $separatore_titolo;
    $this->formato_data       = $formato_data;
    $this->formato_prezzo     = $formato_prezzo;
  }
  
  public function getDateFormat() {
    return $this->formato_data;
  }
  
  public function setSeparator($separatore_celle)
  {
    $this->separatore_celle = $separatore_celle;  
  }
  
  public function setRowPartition( $partizione_righe, $linea_inizio = 0, $linea_fine = 0 ) {
    $this->partizione_righe = $partizione_righe;
    $this->linea_inizio     = $linea_inizio;
    $this->linea_fine       = $linea_fine;
    $this->salta_inizio     = $this->partizione_righe && $this->linea_inizio > 0;
    $this->salta_fine       = $this->partizione_righe && $this->linea_fine > 0;
  }

  public function setElementPartition( $partizione_elementi, $qta_semplici = 0, $configurabili_per_semplice = 0, $qta_configurabili = 0 ) {
    $this->partizione_elementi        = $partizione_elementi;
    $this->qta_semplici               = $qta_semplici;
    $this->configurabili_per_semplice = $configurabili_per_semplice;
    $this->qta_configurabili          = $this->configurabili_per_semplice > 0 ? $this->qta_semplici * $this->configurabili_per_semplice : $qta_configurabili;
  }

  public function useStringIndex($usa = true)
  {
    $this->use_string_index = $usa;
  }
  
  public function setStringIndex($index)
  {
    $this->string_index = $index;
    $this->useStringIndex();
  }
  
  //il parametro length serve per velocizzare la lettura del file (se maggiore di zero), ed indica la lunghezza in caratteri di una riga nel csv.
  public function readCSV( $nome_file, $ha_intestazioni = false, $trim = true, $use_string_index = false, $converti_spazi_index = false, $length = 0 ) {
    $conta        = 0;
    $elementi     = [];
    $intestazioni = $this->string_index;
    $array_associativo = $this->use_string_index || $use_string_index;
    if ( ( $handle = fopen( $nome_file, "r" ) ) !== FALSE )
    {
      while ( ( $data = fgetcsv( $handle, $length, $this->separatore_celle ) ) !== FALSE && ( $conta < $this->linea_fine || !$this->salta_fine ) ) 
      {
        if ( $ha_intestazioni && $conta == 0 ) 
        {
          $intestazioni = $data;
          $carattere    = is_bool( $converti_spazi_index ) ? '_' : $converti_spazi_index;
          
          foreach ( $intestazioni as $key => $intestazione ) 
          {
            $intestazione = trim( preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $intestazione));
            if ( $converti_spazi_index )
              $intestazione = str_replace( ' ', $carattere, $intestazione );
            $intestazioni[ $key ] = $intestazione;
          }
        } 
        elseif ( $conta >= $this->linea_inizio || !$this->salta_inizio) 
        {
          $temp = [];
          if ( $array_associativo && !empty( $intestazioni ) ) 
          {
            for ( $i = 0; $i < count( $data ); $i++ ) 
            {
              $key          = $intestazioni[$i];
              $value        = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $data[$i]);
              $value        = $trim ? trim($value) : $value;
              $temp[ $key ] = $value;
            }
          } 
          else
          {
            foreach($data as $key => $element)
            {
              $temp[$key] = trim($element);
            }
            //$temp = $data;
          }

          $elementi[] = $temp;
        }
        $conta++;
      }
      fclose( $handle );
    }
    else
    {
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
    foreach($arr as $key => $value)
    {
      if(isset($intestazione[$key]))
        $risultato[$intestazione[$key]] = $value;
    }
    return $risultato;
  }
}