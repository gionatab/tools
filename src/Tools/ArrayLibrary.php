<?php
namespace Up3Up\Tools;

class ArrayLibrary {

    /**
     * Ordina e filtra l'array secondo l'ordine fornito.
     * Dall'array di origine prende solo le chiavi presenti in $sortOrder.
     * Se $sortOrder ha una valore che non esiste tra le chiavi di $array, viene creata la chiave con valore null.
     * 
     * @param array $array l'array da ordinare
     * @param array $sortOrder la lista che contiene l'ordinamento di $array.
     * @return array Un array ordinato (e filtrato) secondo l'ordine indicato da $sortOrder. 
     */
    public static function sortArrayCustomOrder($array, $sortOrder) {
        $result = [];
        foreach($sortOrder as $_key) {
            if($_key !== '') {
                $result[$_key] = $array[$_key] ?? null;
            }
        }
        return $result;
    }
}