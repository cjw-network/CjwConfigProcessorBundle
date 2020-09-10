# eZPlatform Config-Formatter-Extension

ez-config variablen sind unter folgender Route einsehbar: `$_GLOBALS:kernel:container:parameters`
<br/>
<br/>
**Symfony / eZ-Container und ConfigResolver werden eventuell zum Auslesen benötigt.**

**Pfad:** `/mnt/data/htdocs/bicycleLearning/ezplatform/vendor/symfony/dependency-injection/Container.php`

**Vorgehensweise und Geplantes:**
* Zuerst einmal das Array nehmen und in einem eigenen Bundle sortieren / parsen und dann in einem Twig-Template ausgeben / zur Verfügung stellen

* eventuell direkt Array parsen nach Werten (zuerst dem ersten vor dem ersten Punkt) als Zuordnung 
(zum Beispiel ezsettings in ein eigenes Ding parsen, sowie platform und publish)

* Eventuell sortieren nach Standard-Symfony und eZ-Werten in unterschiedliche Arrays

* Außerdem sortieren in site_access abhängige und nicht-site_access abhängige Werte (in unterschiedliche Arrays)

* Eventuell prüfen, wann und von wo die Werte eingelesen und verarbeitet werden (aus den YAMLs in den Config-Resolver von eZ / Symfony) => ableiten wo die Werte und in welcher Reihenfolge die Werte gelesen werden und verarbeitet werden => aus welchen Dateien stammen die Werte 

* Irgendwie im Backend anzeigen und verarbeiten lassen (eventuell Werte verändern können? (Änderungen cachen?), außerdem mitbekommen wann der Cach geändert wird und dann darstellen)

# Erste Schritte:

##Erschaffung der Bundle-Struktur:

* Vendor-Name als Überordner, dann der Name des Bundles als Ordner (ohne Vendor),
* in dem Ordner dann die Unterordner: "Controller, DependencyInjection, Resources und Tests"
* Außerdem die VendornameBundlename.php Datei, welche das Symfony Bundle erweitert, damit das Bundle grundlegend komplettiert wird
* in "Resources" finden sich die Unterordner: "config, doc, public und views"

=> refer to Symfony documentation for more info on the matter:

```php
Bundles allgemein:
	https://symfony.com/doc/current/bundles.html
Beste Praktiken bezüglich Bundles:
	https://symfony.com/doc/current/bundles/best_practices.html
Konfiguration für Bundles:
	https://symfony.com/doc/current/bundles/configuration.html 
```

## Notiz!!

In dem Vendor-Verzeichnis unter symfony kann man den config-Ordner finden, in dem sich die File-Loader befinden und vermutlich auch die Parser selbst, die dann dafür herangezogen werden!

**Pfad:** `mnt/data/htdocs/bicycleLearning/ezplatform/vendor/symfony/config`

Unter "Var"-Verzeichnis verbirgt sich das cache-Verzeichnis, dass gelöscht werden muss, damit er in den Config-Loader und ähnliches hineingeht.

**Pfad:** `/mnt/data/htdocs/bicycleLearning/ezplatform/var/cache`

!! Der Parameterbag selbst kann durch den Container abgerufen werden!!!! 
* mit der Methode: "getParameterBag()", 
diese gibt einen "Frozenparameterbag" zurück. Dieser beinhaltet zwar die Parameter und erlaubt sowohl das Hinzufügen, als auch das Entfernen bestimmter Parameter, aber er hat ebenfalls eine protected Eigenschaft in "parameters"

=> Das bedeutet, dass man die Parameter daraus nicht direkt einlesen darf
