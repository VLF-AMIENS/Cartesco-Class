<style>
	.modern-table {
    border-collapse: separate;
    border-spacing: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 14px;
    color: #333;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    overflow: hidden;
    margin: 20px 0;
}

.modern-table th,
.modern-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.modern-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

.modern-table tr:last-child td {
    border-bottom: none;
}

.modern-table tr:hover {
    background-color: #f5f7fa;
    transition: background-color 0.2s ease;
}

.modern-table td:first-child,
.modern-table th:first-child {
    padding-left: 20px;
}

.modern-table td:last-child,
.modern-table th:last-child {
    padding-right: 20px;
}

/* Style pour les cellules avec des chiffres */
.modern-table td.numeric {
    text-align: right;
    font-family: 'Roboto Mono', monospace;
}
</style>



	<?php

/**
 * Calculateur de structure scolaire
 * 
 * Détermine les ouvertures/fermetures de classes selon effectifs et seuils.
 * Gère les dédoublements (GS/CP/CE1) et calcule les moyennes par classe.
 */
class CalculateurStructureScolaire
{
    // Effectifs par niveau
    private int $tps = 0;
    private int $ps = 0;
    private int $ms = 0;
    private int $gs = 0;
    private int $cp = 0;
    private int $ce1 = 0;
    private int $ce2 = 0;
    private int $cm1 = 0;
    private int $cm2 = 0;
    
    // Structure
    private int $nbClassesMat = 0;
    private int $nbClassesElem = 0;
    
    // Seuils
    private int $limiteMat;
    private int $limiteElem;
    private int $limitePrim;
    private int $limite; // Effectif classes dédoublées
    
    // Contrôles dédoublement
    private int $limiteGs = 0;
    private int $limiteCp = 0;
    private int $limiteCe1 = 0;
    
    // Options
    private ?string $noTps = null;
    private ?int $tpsFleches = null;
    private ?string $tpsClasse = null;
    
    public function __construct(
        int $limiteMat,
        int $limiteElem,
        int $limitePrim,
        int $limite
    ) {
        $this->limiteMat = $limiteMat;
        $this->limiteElem = $limiteElem;
        $this->limitePrim = $limitePrim;
        $this->limite = $limite;
    }
    
    /**
     * Configure les effectifs
     */
    public function setEffectifs(
        int $tps = 0,
        int $ps = 0,
        int $ms = 0,
        int $gs = 0,
        int $cp = 0,
        int $ce1 = 0,
        int $ce2 = 0,
        int $cm1 = 0,
        int $cm2 = 0
    ): self {
        $this->tps = $tps;
        $this->ps = $ps;
        $this->ms = $ms;
        $this->gs = $gs;
        $this->cp = $cp;
        $this->ce1 = $ce1;
        $this->ce2 = $ce2;
        $this->cm1 = $cm1;
        $this->cm2 = $cm2;
        return $this;
    }
    
    /**
     * Configure la structure
     */
    public function setStructure(int $nbClassesMat, int $nbClassesElem): self
    {
        $this->nbClassesMat = $nbClassesMat;
        $this->nbClassesElem = $nbClassesElem;
        return $this;
    }
    
    /**
     * Active le contrôle pour GS/CP/CE1 (1 = contrôlé, 0 = non contrôlé)
     */
    public function setControles(int $gs = 0, int $cp = 0, int $ce1 = 0): self
    {
        $this->limiteGs = $gs;
        $this->limiteCp = $cp;
        $this->limiteCe1 = $ce1;
        return $this;
    }
    
    /**
     * Options TPS
     */
    public function setOptionsTps(
        ?string $noTps = null,
        ?int $tpsFleches = null,
        ?string $tpsClasse = null
    ): self {
        $this->noTps = $noTps;
        $this->tpsFleches = $tpsFleches;
        $this->tpsClasse = $tpsClasse;
        return $this;
    }
    
    /**
     * Calcule la structure et retourne les résultats
     */
    public function calculer(): array
    {
        $imp = 0;
        $ret = 0;
        
        // Total classes
        $classesTotal = $this->nbClassesMat + $this->nbClassesElem;
        if ($this->tpsFleches > 0 && $this->tpsClasse === 'moins1') {
            $classesTotal--;
        }
        
        // Effectif total
        $effectifTotal = $this->ps + $this->ms + $this->gs + $this->cp 
                       + $this->ce1 + $this->ce2 + $this->cm1 + $this->cm2;
        
        if ($this->noTps !== 'noTPS') {
            $effectifTotal += $this->tps;
        }
        
        // Seuil applicable
        if ($this->nbClassesMat > 0 && $this->nbClassesElem == 0) {
            $limiteAutre = $this->limiteMat;
        } elseif ($this->nbClassesMat == 0 && $this->nbClassesElem > 0) {
            $limiteAutre = $this->limiteElem;
        } elseif ($this->nbClassesMat > 0 && $this->nbClassesElem > 0) {
            $limiteAutre = $this->limitePrim;
        } else {
            $limiteAutre = 1000;
        }
        
        // Effectifs dédoublés
        $effectifDedouble = 0;
        if ($this->limiteGs > 0) $effectifDedouble += $this->gs;
        if ($this->limiteCp > 0) $effectifDedouble += $this->cp;
        if ($this->limiteCe1 > 0) $effectifDedouble += $this->ce1;
        
        $nbClasseDedoublee = $effectifDedouble > 0 
            ? ceil($effectifDedouble / $this->limite) 
            : 0;
        
        $optimise = $nbClasseDedoublee * $this->limite;
        
        // Calcul reste
        if ($optimise < $effectifTotal) {
            $reste = $effectifTotal - $optimise;
        } else {
            $reste = $effectifTotal;
        }
        
        $classeReste = $classesTotal - $nbClasseDedoublee;
        
        // Moyennes par classe
        $ec = $classeReste > 0 ? round($reste / $classeReste, 1) : 9999;
        $ecMoins1 = $classeReste > 1 ? round($reste / ($classeReste - 1), 1) : 9999;
        $ecMoins2 = $classeReste > 2 ? round($reste / ($classeReste - 2), 1) : 9999;
        $ecPlus1 = round($reste / ($classeReste + 1), 1);
        $ecPlus2 = round($reste / ($classeReste + 2), 1);
        
        // Alertes IMP/RET
        $alert = 0;
        $alertInfo = '';
        $marge = 0.2;
        
        if ($classeReste > 1 && $ecMoins1 < $limiteAutre) {
            $alert++;
            $alertInfo .= "E/C-1 sous barème => RET [{$limiteAutre}>{$ecMoins1}]<br>";
            $ret++;
        }
        
        if ($ec > ($limiteAutre + $marge)) {
            $alert++;
            $alertInfo .= "E/C sup à barème => IMP [{$limiteAutre}<{$ec}]<br>";
            $imp++;
        }
        
        // HTML
        $html = $this->genererTableau($effectifTotal, $classesTotal, $effectifDedouble, 
                                      $nbClasseDedoublee, $optimise, $reste, 
                                      $classeReste, $ec, $ecMoins1, $ecPlus1, $limiteAutre);
        
        return [
            'effectif_total' => $effectifTotal,
            'classes_total' => $classesTotal,
            'effectif_dedouble' => $effectifDedouble,
            'nb_classe_dedoublee' => $nbClasseDedoublee,
            'optimise' => $optimise,
            'reste' => $reste,
            'classe_reste' => $classeReste,
            'ec' => $ec,
            'ecmoins1' => $ecMoins1,
            'ecmoins2' => $ecMoins2,
            'ecplus1' => $ecPlus1,
            'ecplus2' => $ecPlus2,
            'limite_autre' => $limiteAutre,
            'alert' => $alert,
            'alert_info' => $alertInfo,
            'IMP' => $imp,
            'RET' => $ret,
            'tableauHTML' => $html
        ];
    }
    
    /**
     * Génère le tableau HTML
     */
    private function genererTableau(
        int $effectifTotal,
        int $classesTotal,
        int $effectifDedouble,
        int $nbClasseDedoublee,
        int $optimise,
        float $reste,
        int $classeReste,
        float $ec,
        float $ecMoins1,
        float $ecPlus1,
        int $limiteAutre
    ): string {
        $niveaux = [];
        if ($this->limiteGs > 0) $niveaux[] = 'GS';
        if ($this->limiteCp > 0) $niveaux[] = 'CP';
        if ($this->limiteCe1 > 0) $niveaux[] = 'CE1';
        $niveauxStr = implode(' ', $niveaux);
        
        $html = '<table class="modern-table">
            <thead>
                <th>Calcul</th>
                <th>Valeurs</th>
            </thead>
            <tbody>
                <tr><td>Eff. total</td><td>' . $effectifTotal . ' / ' . $classesTotal . '</td></tr>
                <tr><td>Niveaux dédoublés</td><td>' . $niveauxStr;
        
        if ($nbClasseDedoublee > 0) {
            $html .= ' [' . $effectifDedouble . ' élèves]';
        }
        
        $html .= '</td></tr>
                <tr><td>Cl. dédoublées</td><td>';
        
        if ($nbClasseDedoublee > 0) {
            $html .= $nbClasseDedoublee . ' cl. [' . $optimise . ' él.]';
        }
        
        $html .= '</td></tr>
                <tr><td>E/C</td><td>' . $ec . ' [' . $reste . '/' . $classeReste . ']</td></tr>
                <tr><td>E/C-1</td><td>';
        
        if ($classeReste > 1) {
            $html .= $ecMoins1 . ' [' . $reste . '/' . ($classeReste - 1) . ']';
        }
        
        $html .= '</td></tr>
                <tr><td>E/C+1</td><td>' . $ecPlus1 . ' [' . $reste . '/' . ($classeReste + 1) . ']</td></tr>
                <tr><td>Limite classe</td><td>' . $limiteAutre . '</td></tr>
            </tbody>
        </table>';
        
        return $html;
    }
}
?>





<?php
// ========== EXEMPLE D'UTILISATION ==========

$calc = new CalculateurStructureScolaire(
    limiteMat: 25,
    limiteElem: 24,
    limitePrim: 24,
    limite: 12  // Effectif classes dédoublées
);

$result = $calc
    ->setEffectifs(
        tps: 5, // self-explanatory
        ps: 20,
        ms: 22,
        gs: 24,
        cp: 25,
        ce1: 23,
        ce2: 22,
        cm1: 21,
        cm2: 20
    )
    ->setStructure(
        nbClassesMat: 4, // self-explanatory
        nbClassesElem: 7
    )
    ->setControles(
        gs: 1,   // GS contrôlé
        cp: 1,   // CP contrôlé
        ce1: 0   // CE1 non contrôlé
    )
    ->setOptionsTps(
        noTps: null, // NULL = on compte les TPS |||| 1 = on retire les TPS des effectifs
        tpsFleches: 1, // NULL = pas de dispositif TPS fléchés |||| 1 = dispositif TPS fléchés
        tpsClasse: 'moins1' // NULL = on ne retire pas de classe ||||  moins1 = on retire une classe aux classes comptabilisées
    )
    ->calculer();



// ------------------------------- Affichage
echo $result['tableauHTML'];
echo "<br>";
echo "IMP: {$result['IMP']}, RET: {$result['RET']}<br>";
echo "E/C: {$result['ec']}<br>";

if ($result['alert'] > 0) {
    echo $result['alert_info'];
}

?>