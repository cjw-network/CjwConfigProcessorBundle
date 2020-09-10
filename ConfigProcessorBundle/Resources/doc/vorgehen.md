# eZPlatform Config-Formatter-Extension

eZ-config variablen sind unter folgender Route einsehbar: `$GLOBALS:kernel:container:parameters`

eZ-siteaccesse sind unter folgender Route einsehbar: `$GLOBALS:kernel:container:privates:eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver\SiteAccessGroupConfigResolver:valueHolder`

Der aktuelle eZ-siteaccess kann aber viel leichter eingesehen werden: `$GLOBALS:request:attributes:parameters:siteaccess `

<br/>

**Symfony / eZ-Container und ConfigResolver werden eventuell zum Auslesen benötigt.**

**Pfad:** `{ezinstallation}/vendor/symfony/dependency-injection/Container.php`

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
// Bundles allgemein:
	$url1 = "https://symfony.com/doc/current/bundles.html";
// Beste Praktiken bezüglich Bundles:
	$url2 = "https://symfony.com/doc/current/bundles/best_practices.html";
// Konfiguration für Bundles:
	$url3 = "https://symfony.com/doc/current/bundles/configuration.html"; 
```

## Notiz!!

In dem Vendor-Verzeichnis unter symfony kann man den config-Ordner finden, in dem sich die File-Loader befinden und vermutlich auch die Parser selbst, die dann dafür herangezogen werden!

**Pfad:** `{ezinstallation}/vendor/symfony/config`

Unter "Var"-Verzeichnis verbirgt sich das cache-Verzeichnis, dass gelöscht werden muss, damit er in den Config-Loader und ähnliches hineingeht.

**Pfad:** `{ezinstallation}/var/cache`

!! Der Parameterbag selbst kann durch den Container abgerufen werden!!!! 
* mit der Methode: "getParameterBag()", 
diese gibt einen "Frozenparameterbag" zurück. Dieser beinhaltet zwar die Parameter und erlaubt sowohl das Hinzufügen, als auch das Entfernen bestimmter Parameter, aber er hat ebenfalls eine protected Eigenschaft in "parameters"

=> Das bedeutet, dass man die Parameter daraus nicht direkt einlesen kann

**Meine Lösung dafür:**

* Erzeugung einer eigenen Klasse, welche die ursprüngliche Frozen-Parameter-Bag-Klasse beerbt
* Weiterreichung des Containers mit dem Bag an den Konstruktor dieser eigenen Klasse
* Hinzufügen einer Funktion `getParameters()`, welche das Parameter-Array zurückgibt

## Ziel: Siteaccess-aware sein

**Das eigentliche Ziel dabei:**

* Die geparsten Variablen nach site-access dependency sortieren können und basierend
auf dem aktuellen site-access nur die Variablen in einem Array zeigen, welche zu dem site-access gehören.

* Potenziell darauf aufbauend dann vergleichende Arrays, in denen verschiedene site-accesse zu den gleichen
Seiten betrachtet werden können 

**Voraussetzung:**

* Der aktuelle site-access muss erkannt werden können und twig muss das entsprechende Array übergeben werden.
* Dafür müssen die Inhalte der Parameter-Speicherung nach site-access durchsuchbar sein.

```php
// Siteaccesse werden von den Klassen des Pfades:
$path = "{ezinstallation}/vendor/ezsystems/ezplatform-kernel/eZ/Publish/Core/MVC/Symfony";
/* verarbeitet und unter dem Ordner "SiteAccess" in dem Verzeichnis finden sich Klassen, 
die direkt für das Matchen und Ähnliches zuständig sind. */
``` 
