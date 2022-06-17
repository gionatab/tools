<?php
namespace Gionatab\Tools;

use Exception;

class ParseStructure {
    protected $structures;
    protected $structureUsed;
    public function __construct($type, $structureDir = __DIR__.'/struttura/') {
        try {
            $this->loadNewStructureFile($type, $structureDir);
        } catch(Exception $ex) {
            throw $ex;
        }
    }

    public function loadNewStructureFile($type, $structureDir = __DIR__.'/struttura/') {
        $nomeFileStruttura = $structureDir.$type.'.json';
        if(!file_exists($nomeFileStruttura)) {
            throw new Exception("File struttura non esiste");
        }
        $this->structures = json_decode(file_get_contents($nomeFileStruttura), true);
        $this->structureUsed = $this->structures[0];
        if(!$this->checkStructure($this->structureUsed)) {
            throw new Exception("Gli elementi della struttura selezionata non sono conformi alle specifiche.");
        }
    }
    public function switchStructure($val) {
        if(isset($this->structures[$val])){
            $this->structureUsed = $this->structures[$val];
        }
    }
    public function getStructure() {
        return $this->structureUsed;
    }
    protected function checkStructure($structure) {
        $requiredNodes = ["nome", "descrizione", "struttura"];
        $nodes = array_keys($structure);
        foreach($requiredNodes as $requiredNode) {
            if(!in_array($requiredNode, $nodes)) {
                return false;
            }
        }
        return true;
    }

    protected function checkStructureElements($structureElements) {
        $requiredNodes = ["nome_colonna", "nome_prestashop", "product_type"];
        $nodes = array_keys($structureElements);
        foreach($requiredNodes as $requiredNode) {
            if(!in_array($requiredNode, $nodes)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Tipi di nodi:
     * 0 - Generale
     * 1 - Sconto
     */
    protected function checkNodeType($nodeName){
        $type = 0;
        $nodeTypes = 
        [
            'discountNodes' => [
                'value' => 1,
                'validation' => ['sconti']
            ],
            'manufacturerNodes' => [
                'value' => 2,
                'validation' => ['manufacturer']
            ],
            'attributeNodes' => [
                'value' => 3,
                'validation' => ['attributi']
            ],
            'featureNodes' => [
                'value' => 4,
                'validation' => ['features']
            ],
        ];
        foreach($nodeTypes as $checkNode) {
            $nodeType = $checkNode['value'];
            $validationNodes = $checkNode['validation'];
            $type = in_array($nodeName, $validationNodes) ? $nodeType : $type;
        }
        //print_r($nodeName . ' - ' . $type.PHP_EOL);
        return $type;
    }

    public function getColumnNames() {
        $columns = [];
        foreach($this->structureUsed['struttura'] as $column) {
            $columns[] = $column['nome_colonna'];
        }
        return $columns;
    }
    protected function formatValue($value, $format_type) {
        $result = null;
        switch($format_type) {
            case "isPrice":
                $result = $this->formatPrice($value);
                break;
            case "isNumber":
                $result = $this->formatNumber($value);
                break;
            default:
                $result = trim($value);
        }
        return $result;
    }

    protected function formatPrice($value, $format="%01.6f") {
        $result = null;
        $replaced = 0;   
        $result = str_ireplace(',', '.', $value, $replaced);
        //Excel o altri programmi potrebbero aver formato il numero con più virgole o punti.
        if($replaced > 1) {
            throw new Exception("Durante la formattazione del prezzo, sono stati sostituiti molteplici valori, controllare il numero di virgole e punti presenti.");
        }
        $result = sprintf($format, $result);
        return $result;

    }

    protected function formatNumber($value, $decimalSeparator='.') {
        $result = null;
        $replaced = 0;
        $result = str_ireplace(',', $decimalSeparator, $value, $replaced);
        if($replaced > 1) {
            throw new Exception("Durante la formattazione del prezzo, sono stati sostituiti molteplici valori, controllare il numero di virgole e punti presenti.");
        }
        return $result;
    }

    protected function convertNode($product, $structureElement) {
        $result = [];
        switch($this->checkNodeType($structureElement['nome_prestashop'])) {
            case 1: 
                $result = $this->convertDiscountNode($product, $structureElement);
                break;
            case 2:
                $result = $this->convertManufacturerNode($product, $structureElement);
                break;
            case 3:
                $result = $this->convertAttributeNode($product, $structureElement);
                break;
            case 4:
                $result = $this->convertFeatureNode($product, $structureElement);
                break;
            default:
                $result = $this->convertGenericNode($product, $structureElement);
        }
        return $result;
    }
    protected function convertGenericNode($product, $structureElement) {
        $result = [];
        $val = $product[$structureElement['nome_colonna']];
        //Alcune celle possono essere vuote e mettiamo noi un valore.
        if(!isset($val) || strlen($val) == 0) {
            if(isset($structureElement['change_null'])) {
                $val = $structureElement['change_null'];
            }
            else {
                return [];
            }
        }
        if(isset($structureElement['separator'])) {
            $val = explode($structureElement['separator'], $val);
        }
        $format_type = $structureElement['format_type'] ?? "default";
        if(is_array($val)) {
            foreach($val as $_key => $_val) {
                $val[$_key] = $this->formatValue($_val, $format_type);
            }
        }
        else {
            $val = $this->formatValue($val, $format_type);
        }
        if(isset($structureElement['language'])) {
            $val = [$structureElement['language'] => $val];
        }
        if(isset($structureElement['shop'])) {
            $val = [$structureElement['shop'] => $val];
        }
        $result = [$structureElement['nome_prestashop'] => $val];
        return $result;
    }

    protected function convertDiscountNode($product, $structureElement) {
        $result = [];
        $val = $product[$structureElement['nome_colonna']];
        $price_initial = 0;
        if(isset($structureElement['nome_colonna_prezzo'])) {
            $price_initial = $product[$structureElement['nome_colonna_prezzo']];
        }
        $price_initial = $this->formatPrice($price_initial);
        if(isset($structureElement['nome_colonna_tasse_prezzo'])) {
            $price_initial = $price_initial * (1+($product[$structureElement['nome_colonna_tasse_prezzo']]/100));
        }
        if(empty($val)) {
            return [];
        }
        if(isset($structureElement['separator'])) {
            $val = explode($structureElement['separator'], $val);
        }
        if(isset($structureElement['format_type'])) {
            if(is_array($val)) {
                foreach($val as $_key => $_val) {
                    $val[$_key] = $this->formatValue($_val, $structureElement['format_type']);
                }
            }
            else {
                $val = $this->formatValue($val, $structureElement['format_type']);
            }
        }
        //controlla se c'è il nodo che indica se calcolare lo sconto, e se il nodo è true.
        if(isset($structureElement['calculate_discount']) && $structureElement['calculate_discount']) {
            if($structureElement['discount_type'] == "percentage")
            {
                $val = $val > 0 ? 1 - ($val / $price_initial) : 0;
            }
            else if($structureElement['discount_type'] == 'amount') {
                $val = $val > 0 ? $price_initial - $val : 0;
            }
            $val = $this->formatPrice($val); //Formatta lo sconto calcolato per rientrare nei limiti imposti.
        }
        else {
            if($structureElement['discount_type'] == "percentage")
            {
                $val = $val / 100; 
            }
        }

        $discount_type = ['discount_type' => $structureElement['discount_type']];
        $sconto = ['special_price' => ['reduction' => $val]];
        $val = array_merge($sconto, $discount_type);
        if(isset($structureElement['shop'])) {
            $val = [$structureElement['shop'] => $val];
        }
        $result = [$structureElement['nome_prestashop'] => $val];
        return $result;
    }

    protected function convertFeatureNode($product, $structureElement) {
        $result = [];
        $val = $product[$structureElement['nome_colonna']];
        if(empty($val)) {
            return [];
        }
        if(isset($structureElement['separator'])) {
            $val = explode($structureElement['separator'], $val);
        }
        if(isset($structureElement['format_type'])) {
            if(is_array($val)) {
                foreach($val as $_key => $_val) {
                    $val[$_key] = $this->formatValue($_val, $structureElement['format_type']);
                }
            }
            else {
                $val = $this->formatValue($val, $structureElement['format_type']);
            }
        }

        $nome_feature = $structureElement['private_name'] ?? $structureElement['nome_colonna'];
        $features = [];
        foreach((array) $val as $_val) {
            $language = ['language' => $structureElement['language']];
            $value = ['value' => trim($_val)];
            $features[$nome_feature][] = array_merge($language, $value); 
        }
        $result = [$structureElement['nome_prestashop'] => $features];
        return $result;
    }

    protected function convertAttributeNode($product, $structureElement) {
        $result = [];
        $val = $product[$structureElement['nome_colonna']];
        //Alcune celle possono essere vuote e mettiamo noi un valore.
        if(empty($val)) {
            if(isset($structureElement['change_null'])) {
                $val = $structureElement['change_null'];
            }
            else {
                return [];
            }
        }
        $format_type = $structureElement['format_type'] ?? "default";
        if(is_array($val)) {
            foreach($val as $_key => $_val) {
                $val[$_key] = $this->formatValue($_val, $format_type);
            }
        }
        else {
            $val = $this->formatValue($val, $format_type);
        }
        if(isset($structureElement['language'])) {
            $val = [$structureElement['language'] => $val];
        }
        if(isset($structureElement['private_name'])) {
            $val = [$structureElement['private_name'] => $val];
        }
        else {
            $val = [$structureElement['nome_colonna'] => $val];
        }
        $result = [$structureElement['nome_prestashop'] => $val];
        return $result;
    }
    /**
     * Il nodo manufacturer_type può essere:
     * - id
     * - name
     * e corrisponde a quale tipo di valore è passato, se id allora è più veloce, se name allora lo si usa come filtro per trovare l'id della risorsa
     */
    protected function convertManufacturerNode($product, $structureElement) {
        $result = [];
        $val = $product[$structureElement['nome_colonna']];
        //Alcune celle possono essere vuote e mettiamo noi un valore.
        if(empty($val)) {
            return [];
        }
        $format_type = $structureElement['format_type'] ?? "default";
        if(is_array($val)) {
            foreach($val as $_key => $_val) {
                $val[$_key] = $this->formatValue($_val, $format_type);
            }
        }
        else {
            $val = $this->formatValue($val, $format_type);
        }
        if(isset($structureElement['language']) && isset($structureElement['manufacturer_type']) && $structureElement['manufacturer_type'] == 'name') {
            $val = [$structureElement['language'] => $val];
        }
        if(isset($structureElement['manufacturer_type'])) {
            $val = [$structureElement['manufacturer_type'] => $val];
        }
        $result = [$structureElement['nome_prestashop'] => $val];
        return $result;
    }

    public function convertProduct($product) {
        $result = [];
        $key = null;
        $validKeys = ['simple', 'combination', 'generic'];
        foreach($this->structureUsed['struttura'] as $structureElements) {
            if(!$this->checkStructureElements($structureElements)) {
                continue;
            }
            $value = $this->convertNode($product, $structureElements);
            $key = $structureElements['product_type'];
            if(!empty($value) && in_array($key, $validKeys)) {
                if(isset($result[$key])) {
                    $result[$key] = array_merge_recursive($result[$key], $value);
                }
                else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }
    /**
     * Prende un elemento sotto forma di array e ci applica le regole della struttura
     */
    public function convertSingle($element, $tipo_prodotto='') {
        $converted = [];
        $valid_tipo_prodotto = ['simple', 'combination'];
        $type = ['global'];
        if(!empty($tipo_prodotto) && in_array($tipo_prodotto, $valid_tipo_prodotto)) {
            $type[] = $tipo_prodotto;
        }       
        foreach($element as $key => $value) {
            foreach($this->structureUsed as $node) {
                if($key == $node["nome_colonna"]) {
                    if(empty($node["nome_prestashop"])) {
                        continue;
                    }
                    $element_temp = $value;
                    //TODO: fai tutte le conversioni e i controlli
                    /**
                     * - Controlla che il "tipo_prodotto" combaci
                     * - Separazione con campo "separatore"
                     * - Prendi l'elemento indicato dal campo "indice_separazione"
                     * - Trasformazioni usando il campo "case_transform"
                     * - Controllo validità dei dati con il campo "convalida", usando "case_sensitive" per il tipo di controllo
                    */
                    $complesso = $node['complesso'] ?? false;
                    if($complesso) {
                        if(isset($node['lingua'])) {
                            $element_temp = [$node['lingua'] => $element_temp];
                        }
                        if(isset($node['nome_attributo'])) {
                            $element_temp = [$node['nome_attributo'] => $element_temp];
                        }
                        if(isset($node['negozio'])) {
                            $element_temp = [$node['negozio'] => $element_temp];
                        }
                    }
                    $converted[$node["nome_prestashop"]] = $element_temp;
                }
            }
        }
    }
}
?>