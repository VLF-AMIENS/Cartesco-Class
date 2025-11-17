# Cartesco-Class
# Calculateur de Structure Scolaire

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Classe PHP pour calculer les ouvertures et fermetures de classes dans les écoles françaises selon les effectifs et seuils réglementaires.

## 📋 Fonctionnalités

- Calcul des effectifs par classe (E/C)
- Gestion des dédoublements GS/CP/CE1
- Détection automatique des seuils d'ouverture (IMP) et fermeture (RET)
- Support des écoles maternelles, élémentaires et primaires
- Gestion des dispositifs TPS fléchés
- Génération de tableaux HTML récapitulatifs

## 🚀 Installation

```bash
composer require votre-org/calculateur-structure-scolaire
```

Ou copier directement la classe `CalculateurStructureScolaire.php` dans votre projet.

## 📖 Usage

### Exemple basique

```php
<?php

require 'CalculateurStructureScolaire.php';

$calc = new CalculateurStructureScolaire(
    limiteMat: 25,   // Seuil maternelle
    limiteElem: 24,  // Seuil élémentaire
    limitePrim: 24,  // Seuil primaire
    limite: 12       // Effectif classes dédoublées
);

$result = $calc
    ->setEffectifs(
        ps: 20,
        ms: 22,
        gs: 24,
        cp: 25,
        ce1: 23
    )
    ->setStructure(
        nbClassesMat: 3,
        nbClassesElem: 5
    )
    ->setControles(
        gs: 1,  // GS dédoublé
        cp: 1   // CP dédoublé
    )
    ->calculer();

echo $result['tableauHTML'];
echo "E/C: {$result['ec']}\n";
echo "IMP: {$result['IMP']}, RET: {$result['RET']}\n";
```

### Configuration avancée

```php
// École avec TPS fléchés
$result = $calc
    ->setEffectifs(tps: 10, ps: 15, ms: 18, gs: 20)
    ->setStructure(nbClassesMat: 4, nbClassesElem: 0)
    ->setControles(gs: 1)
    ->setOptionsTps(
        noTps: null,        // null = inclure TPS
        tpsFleches: 1,      // Dispositif TPS fléché actif
        tpsClasse: 'moins1' // Retirer 1 classe
    )
    ->calculer();

// Exclure les TPS du calcul
$result = $calc
    ->setEffectifs(tps: 8, ps: 20, ms: 22)
    ->setStructure(nbClassesMat: 3, nbClassesElem: 0)
    ->setOptionsTps(noTps: 'noTPS') // TPS exclus
    ->calculer();
```

## 🔧 Paramètres

### Constructeur

| Paramètre | Type | Description |
|-----------|------|-------------|
| `limiteMat` | int | Seuil E/C pour écoles maternelles |
| `limiteElem` | int | Seuil E/C pour écoles élémentaires |
| `limitePrim` | int | Seuil E/C pour écoles primaires |
| `limite` | int | Effectif max par classe dédoublée |

### Méthodes de configuration

#### `setEffectifs(...)`
Définit les effectifs par niveau (TPS, PS, MS, GS, CP, CE1, CE2, CM1, CM2).

```php
->setEffectifs(ps: 20, ms: 22, gs: 24)
```

#### `setStructure(int $nbClassesMat, int $nbClassesElem)`
Nombre de classes maternelles et élémentaires.

```php
->setStructure(nbClassesMat: 3, nbClassesElem: 5)
```

#### `setControles(int $gs, int $cp, int $ce1)`
Active le contrôle dédoublement (1 = contrôlé, 0 = non contrôlé).

```php
->setControles(gs: 1, cp: 1, ce1: 0)
```

#### `setOptionsTps(?string $noTps, ?int $tpsFleches, ?string $tpsClasse)`
Options pour la gestion des TPS.

- `noTps`: `'noTPS'` pour exclure les TPS du calcul
- `tpsFleches`: `1` si dispositif TPS fléché actif
- `tpsClasse`: `'moins1'` pour retirer 1 classe (avec TPS fléchés)

## 📊 Résultats

La méthode `calculer()` retourne un tableau associatif :

```php
[
    'effectif_total' => 182,        // Effectif total
    'classes_total' => 8,           // Nombre de classes
    'effectif_dedouble' => 49,      // Effectif en classes dédoublées
    'nb_classe_dedoublee' => 5,     // Nombre de classes dédoublées
    'reste' => 133,                 // Effectif classes restantes
    'classe_reste' => 3,            // Classes restantes
    'ec' => 26.6,                   // E/C actuel
    'ecmoins1' => 44.3,             // E/C si -1 classe
    'ecplus1' => 33.3,              // E/C si +1 classe
    'limite_autre' => 24,           // Seuil applicable
    'alert' => 1,                   // Nombre d'alertes
    'alert_info' => '...',          // Détails alertes
    'IMP' => 1,                     // Implantations (ouvertures)
    'RET' => 0,                     // Retraits (fermetures)
    'tableauHTML' => '...'          // Tableau récapitulatif
]
```

### Alertes IMP/RET

- **IMP** (Implantation) : E/C > seuil + 0.2 → ouverture recommandée
- **RET** (Retrait) : E/C-1 < seuil → fermeture recommandée

## 🎯 Cas d'usage

### Écoles maternelles
```php
$calc = new CalculateurStructureScolaire(25, 24, 24, 12);
$result = $calc
    ->setEffectifs(ps: 30, ms: 28, gs: 26)
    ->setStructure(nbClassesMat: 3, nbClassesElem: 0)
    ->setControles(gs: 1)
    ->calculer();
```

### Écoles élémentaires
```php
$calc = new CalculateurStructureScolaire(25, 24, 24, 12);
$result = $calc
    ->setEffectifs(cp: 50, ce1: 48, ce2: 45, cm1: 43, cm2: 40)
    ->setStructure(nbClassesMat: 0, nbClassesElem: 10)
    ->setControles(cp: 1, ce1: 1)
    ->calculer();
```

### Écoles primaires
```php
$calc = new CalculateurStructureScolaire(25, 24, 24, 12);
$result = $calc
    ->setEffectifs(ps: 20, ms: 22, gs: 24, cp: 25, ce1: 23, ce2: 22)
    ->setStructure(nbClassesMat: 3, nbClassesElem: 3)
    ->setControles(gs: 1, cp: 1)
    ->calculer();
```

## 📝 Prérequis

- PHP >= 8.0
- Extensions : aucune

## 🤝 Contribution

Les contributions sont les bienvenues ! Ouvrez une issue ou soumettez une pull request.

## 📄 Licence

MIT

## 👤 Auteur

Votre nom - [@votre_handle](https://twitter.com/votre_handle)

## 🔗 Liens utiles

- [Documentation complète](https://docs.example.com)
- [Issues](https://github.com/votre-org/calculateur-structure-scolaire/issues)
- [Changelog](CHANGELOG.md)
