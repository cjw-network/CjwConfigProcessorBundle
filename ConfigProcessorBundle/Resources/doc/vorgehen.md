# eZPlatform Config-Formatter-Extension

eZ-config variablen sind unter folgender Route einsehbar: `$GLOBALS:kernel:container:parameters`

eZ-siteaccesse sind unter folgender Route einsehbar: `$GLOBALS:kernel:container:privates:eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver\SiteAccessGroupConfigResolver:valueHolder`

Der aktuelle eZ-siteaccess kann aber viel leichter eingesehen werden: `$GLOBALS:request:attributes:parameters:siteaccess `

<br/>

**Symfony / eZ-Container und ConfigResolver werden eventuell zum Auslesen benötigt.**

**Pfad:** `{ezinstallation}/vendor/symfony/dependency-injection/Container.php`

### Vorgehensweise und Geplantes:
* [x] Zuerst einmal das Array nehmen und in einem eigenen Bundle sortieren / parsen und dann in einem Twig-Template ausgeben / zur Verfügung stellen

* [x] eventuell direkt Array parsen nach Werten (zuerst dem ersten vor dem ersten Punkt) als Zuordnung 
(zum Beispiel ezsettings in ein eigenes Ding parsen, sowie platform und publish)

* [x] Außerdem filtern nach site_access abhängige Werte (in unterschiedliches Arrays)

* [ ] Im Backend Möglichkeit für Vergleich zwischen Site-Access-Werten verschaffen:
    * [x] Site-Accesse vergleichen können (dafür Auswahl von verschiedenen und Schöpfung von Arrays mit zugehörigen Werten)
    * [ ] Spezielles Filtern nach gleichen Parametern mit unterschiedlichen Werten zwischen den SAs
    * [ ] Möglicherweise auch gleiche Werte zu den Parametern anzeigen können
    * [ ] Nicht von den verglichenen SAs abhängige Werte rausfiltern oder gesondert darstellen
    * [ ] Dynamische Suche in den Parametern (nach bestimmten Schlüsselworten oder Teilen der Parameter) und Anzeige aller möglichen Treffer mit Hierarchie (wo sie drin liegen (ezsettings -> Treffer oder so)).

* [ ] Eventuell prüfen, wann und von wo die Werte eingelesen und verarbeitet werden (aus den YAMLs in den Config-Resolver von eZ / Symfony) 
    * ableiten wo die Werte und in welcher Reihenfolge die Werte gelesen werden und verarbeitet werden 
    * aus welchen Dateien stammen die Werte 
    * in welcher Reihenfolge werden die Dateien eingelesen / aus welcher Datei stammt der dortige Wert

* [ ] Irgendwie im Backend anzeigen und verarbeiten lassen (eventuell Werte verändern können? (Änderungen cachen?), außerdem mitbekommen wann der Cach geändert wird und dann darstellen)

# Erste Schritte:

## Erschaffung der Bundle-Struktur:

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

### Pfade zu den relevanten Dateien

In dem Vendor-Verzeichnis unter symfony kann man den config-Ordner finden, in dem sich die File-Loader befinden und vermutlich auch die Parser selbst, die dann dafür herangezogen werden!

**Pfad:** `{ezinstallation}/vendor/symfony/config/Loader`

Im Vendor-Verzeichnis unter Symfony, aber unter anderen Pfaden, befinden sich die eigentlichen Parser für die Dateien, nämlich unter:

**Pfad:** `{ezinstallation}/vendor/symfony/dependency-injection/Loader`

Der eigentliche Parser für Yaml-Dateien, aber scheinbar auch alle anderen Konfigurationsdateien liegt direkt im Symfony Ordner:

**Pfad:** `{ezinstallation}/vendor/symfony/`

Der tatsächlich direkte und dedizierte Yaml-Parser befindet sich allerdings noch an einem etwas tieferen Ort und im folgenden Verzeichnis als `Parser.php`:

**Pfad:** `{ezinstallation}/vendor/symfony/yaml/`

Unter "Var"-Verzeichnis verbirgt sich das cache-Verzeichnis, das gelöscht werden muss, damit er in den Config-Loader und ähnliches hineingeht.

**Pfad:** `{ezinstallation}/var/cache`

In dem Vendor-Verzeichnis unter Symfony befindet sich die abstrakte Kernel-Klasse, welche den Load-Vorgang der Konfiguration in Gang setzt und
alle Loader instanziiert und außerdem die zu-ladenden Routen angibt.

**Pfad:**  `{ezinstallation}/vendor/symfony/http-kernel/Kernel.php`

### ParameterBag

!! Der Parameterbag selbst kann durch den Container abgerufen werden!!!! 
* mit der Methode: "getParameterBag()", 
diese gibt einen "Frozenparameterbag" zurück. Dieser beinhaltet zwar die Parameter und erlaubt sowohl das Hinzufügen, als auch das Entfernen bestimmter Parameter, aber er hat ebenfalls eine protected Eigenschaft in "parameters"

=> Das bedeutet, dass man die Parameter daraus nicht direkt einlesen kann

**Meine Lösung dafür:**

* Erzeugung einer eigenen Klasse, welche die ursprüngliche Frozen-Parameter-Bag-Klasse beerbt
* Weiterreichung des Containers mit dem Bag an den Konstruktor dieser eigenen Klasse
* Hinzufügen einer Funktion `getParameters()`, welche das Parameter-Array zurückgibt

### Loader-Hierarchie:

> **Config/Loader:**
> > DelegatingLoader <br>
> > LoaderResolver <br>

* Scheinbar wird für das Parsen der Konfigurationsdateien beim Aufbauen des Caches zuerst der "DelegatingLoader" aufgerufen
* Dieser ruft dann den "LoaderResolver" auf, welcher seinerseits

> **DependencyInjection/Loader:**
> > YamlLoader
* die entsprechenden Datei-Parser durchzugehen scheint (zumindest was die Datei-Kompatibilität angeht).

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

**Neue Entwicklung dazu:**

* _Bisheriges Vorgehen_: Das durch mich erstellte Array durchgehen und in dem Array alle herausnehmen,
die einen Key haben, der zu dem siteaccess passt, herauszunehmen und in ein eigenes Array zu packen.

    * Das Problem dabei ist allerdings, dass ich nicht wusste, wie ich denn nur die bekomme, die auch wirklich
    von site-accesses abhängen und nicht nur einfach den string im Namen haben
    * Weiteres Problem, dass mir nicht bewusst war: Ist nur die halbe Miete, denn eigentlich
    ist eher wichtig, welchen Wert die Parameter tatsächlich haben (da durch die vielen Ebenen der
    site-accesse auch die gleichen Parameter auftauchen können, aber überschrieben werden).

* _Neu_: Die ganze Operation nutzt nun die Objekte, mit denen schon beim Config-Processen gearbeitet wurde.
Das heißt, dass die Liste der ProcessedParamModel für diesen Prozess herangezogen wird. Anschließend werden
diese nach den SAs durchsucht und nur solche, welche die SAs in sich tragen und auch nur die SAs werden dann
in ein Array ausgelagert.
    
    * Die vorhandenen Werte (als ProcessedParamModel) werden zu ihren vollständigen Parameternamen aufgelöst und diese werden
    nach den Namespaces gruppiert in ein Array als Key eingefügt
    * Es werden keine Duplikat-Parameternamen in das Array aufgenommen
    * Mit der Hilfe des eZ-Config-Resolvers wird jeder der Parameterwerte, die als Schlüssel im Array liegen,
     durchgegangen und sein tatsächlicher Wert ermittelt. Dieser wird dann unter dem Parameternamen-Key unter dem
     zugehörigen Namespace gespeichert.
        * Dabei werden alle Werte, die mit dem Resolver nicht aufgelöst werden können, aus dem Array entfernt

**Performance:**

* Für das Verarbeiten der Parameter selbst werden laut Symfony-Stopwatch ungefähr 20 Millisekunden gebraucht
* Für das site-access-spezifische Verarbeiten werden in etwa 40 bis 50 Millisekunden veranschlagt
einer kurzen Folge von Messungen zur Folge verbrauchen die Schritte wie folgt Zeit:
    * Das Heraussuchen aller möglichen Siteaccess-Parameter: ca. 0.6 Millisekunden
    * Das Entfernen von Parameter-Duplikaten und das Zusammenbauen der vollständigen Parameternamen: ca. 0.5 Millisekunden
    * Das Auflösen der Parameter-Werte mit dem eZConfigResolver: ca. 50 Millisekunden
    
Es ist davon auszugehen, dass der ConfigResolver die hohen Zeitkosten durch seine Logik verantwortet. Welcher Teil der Logik dabei
so hohe Kosten verursacht müsste durch Untersuchungen geklärt werden, man kann aber davon ausgehen, dass das Durchsuchen seiner
Parameter und das Herausfinden der aktuell-geltenden Werte die meiste Zeit in Anspruch nimmt.

### Vergleich zwischen Site-Accessen

**Eventuell ist mit der PHP-Funktion** `func_get_args()` doch etwas ohne zwei gleiche, aber wenig unterschiedliche Funktionen machbar.

**Ergebnis bisher:**

Bislang wurde zumindest die Möglichkeit geschaffen die Parameter nach Site-Accessen filtern zu lassen
und für spezifische SAs (Site-Accesse) anzuzeigen. Dafür wurden die vorhandenen Filter- und Duplikat-Entfern-Prozesse herangezogen und der einzige Unterschied ist, dass man nun einen Site-Access angeben kann und eine andere
Methode aufruft, die den Site-Access berücksichtigt, indem im letzten Schritt (dem resolven der Werte) dann das Scope
mit angegeben wird, wodurch der ConfigResolver direkt nach den dazugehörigen Werten sucht.

### Überführung in den Cache

Mittlerweile wurden die verschiedenen Prozesse, welche das Parsen der Parameter durchführen, größtenteils mit dem
Symfony-Cache verknüpft, was bedeutet, dass die Ladezeiten, die durch den Service erzeugt werden, drastisch
reduziert werden und die Cache-Ergebnisse auch andernorts genutzt werden können. Insgesamt hat die Cache-Einführung
eine Reduktion der Ladezeiten (nach dem erstmaligen Aufbau des caches) um 70 Millisekunden auf 0.1 Millisekunden erreicht.

Für die Cache-Verknüpfung wurden folgende Dokumente herangezogen:

* Der Symfony Cache-Component:
    * https://symfony.com/doc/current/components/cache.html
* Cache-Pools und -Adapter: 
    * https://symfony.com/doc/current/components/cache/cache_pools.html
* Der PhpFilesAdapter: 
    * https://symfony.com/doc/current/components/cache/adapters/php_files_adapter.html
* Cache Invalidierung: 
    * https://symfony.com/doc/current/components/cache/cache_invalidation.html
* Cache Items: 
    * https://symfony.com/doc/current/components/cache/cache_items.html
* Und der Cache: 
    * https://symfony.com/doc/current/cache.html
    
Als Implementation muss lediglich folgendes gemacht werden:

```php
    use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

     /**
     * @var PhpFilesAdapter
     */
    private $cache;

    // Diese Zeile befindet sich bei mir im Konstruktor
    $this->cache = new PhpFilesAdapter();

    /*
    * Hier kann man nun das Ergebnis des Cache-Aufrufes in eine eigene Variable überführen
    * Durch den Schlüssel gibt man den Schlüssel des zu speichernden Wertes im Cache sowohl für das
    * In-Den-Cache-Schreiben als auch das Herausholen an und mit der Callback-Funktion bestimmt man, was 
    * passieren soll, wenn der Wert nicht im Cache steht, also woher er den Wert nehmen soll, der in den Cache kommt.
    */
     $this->processedParameters = $this->cache->get("processed_params", function(ItemInterface $item) {
        // Wie lang, in Sekunden, soll der Wert im Cache verbleiben    
        $item->expiresAfter(300);
        return $this->parseContainerParameters();
    });
```

Physisch darüber habe ich noch Checks eingebaut, ob die Werte im cach liegen, die ich für die Funktions-Aufrufe benötige 
(geschehen über die `$cache->hasItem(string $key)`-Methode) und wenn nicht, dann werden auch die anderen Werte
meines Services aus dem Cache gelöscht, damit alle Cache-Werte simultan vorliegen und die Abarbeitung der Funktionen
korrekt vonstattengeht.

**Dazu muss eventuell noch eine Überprüfung des Parameter-Bags eingeleitet werden (ein Vergleich zwischen der Anz. Parameter
des Bags, mit dem das Bundle gearbeitet hat und dem Bag, wie er zu dem Zeitpunkt in symfony vorliegt).**

## Ziel: File Locations berücksichtigen

**Der eigentliche Punkt des Ganzen:**

Es ist das Ziel, dass nach dem Load-Vorgang, wenn alle Parameter bereitstehen und fertig geladen worden sind, zusätzlich noch die
Dateien bekannt sind, aus denen der Parameter stammte, sodass schnell eingesehen werden kann, welche Dateien geändert werden sollten,
damit etwaige Fehler behoben werden.

Also:

* Zusätzlich zu den Werten der Parameter und der Parameter selbst, sollen auch noch die Dateien, in denen sie auftauchen bekannt sein
* Es soll herauskommen, welche Datei "gewonnen" hat und als letzte eingelesen wurde
* Das Ganze sollte dann auch noch einsehbar sein

**Voraussetzungen:** 

* Der Load-Vorgang ist irgendwie so manipulierbar, dass die Locations der Parameter über das eigentliche Laden hinaus gespeichert 
werden können
* Klassen, die am Load-Vorgang beteiligt sind, müssen die Orte sowohl weitergeben als auch speichern können (im Falle des
Containers zum Beispiel)
* Das Ergebnis des Load-Vorganges (welcher Dateien berücksichtigt), muss nach außen getragen werden können
* Minimale Invasivität ist erforderlich, damit das System stabil und auf längere Zeit kompatibel sein kann (damit außerdem weniger Code
und Last anfällt)

**Falls kein Eingreifen in den ursprünglichen Load-Vorgang möglich ist:**

* Muss ein separater Load-Vorgang eingesetzt werden, welcher auf die Datei-Pfade achtet
* Dann sollte dieser ressourcen-schonend sein, da doppeltes Auslesen der Werte eigentlich unnötig ist
    * Außerdem (sollte /) wird nicht jede Klasse des Boot-Vorganges von Symfony benötigt (werden), um die Parameter zu laden
* Eine Sicherung der Ergebnisse im Cache ist daher unabdingbar, damit keine zusätzliche Last im Standard-Betrieb entsteht

**Größte Frage: Langlebigkeit?**

Da der zweite Loading-Vorgang sehr stark auf (momentan) vorhandene Symfony-Komponenten und -Vorgänge aufbaut, könnte der Ganze Vorgang
leicht obsolet werden und müsste dann vermutlich stark angepasst werden, damit er wieder funktioniert.
=> Wie kann man dort Langlebigkeit und Sicherheit auf Dauer schaffen?
