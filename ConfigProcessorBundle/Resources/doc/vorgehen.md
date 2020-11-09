# eZPlatform Config-Formatter-Extension

eZ-config variablen sind unter folgender Route einsehbar: `$GLOBALS:kernel:container:parameters`

eZ-siteaccesse sind unter folgender Route einsehbar: `$GLOBALS:kernel:container:privates:eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver\SiteAccessGroupConfigResolver:valueHolder`

Der aktuelle eZ-siteaccess kann aber viel leichter eingesehen werden: `$GLOBALS:request:attributes:parameters:siteaccess `

<br/>

**Symfony / eZ-Container und ConfigResolver werden eventuell zum Auslesen benötigt.**

**Pfad:** `{ezinstallation}/vendor/symfony/dependency-injection/Container.php`

### Vorgehensweise und Geplantes:

- [x] Zuerst einmal das Array nehmen und in einem eigenen Bundle sortieren / parsen und dann in einem Twig-Template ausgeben / zur Verfügung stellen

- [x] eventuell direkt Array parsen nach Werten (zuerst dem ersten vor dem ersten Punkt) als Zuordnung
      (zum Beispiel ezsettings in ein eigenes Ding parsen, sowie platform und publish)

- [x] Außerdem filtern nach site_access abhängige Werte (in unterschiedliches Arrays)

- [x] Im Backend Möglichkeit für Vergleich zwischen Site-Access-Werten verschaffen:

  - [x] Site-Accesse vergleichen können (dafür Auswahl von verschiedenen und Schöpfung von Arrays mit zugehörigen Werten)
  - [x] Spezielles Filtern nach gleichen Parametern mit unterschiedlichen Werten zwischen den SAs
  - [x] Möglicherweise auch gleiche Werte zu den Parametern anzeigen können
  - [x] Nicht von den verglichenen SAs abhängige Werte rausfiltern oder gesondert darstellen
  - [x] Dynamische Suche in den Parametern (nach bestimmten Schlüsselworten oder Teilen der Parameter) und Anzeige aller möglichen Treffer mit Hierarchie (wo sie drin liegen (ezsettings -> Treffer oder so)).

- [x] Eventuell prüfen, wann und von wo die Werte eingelesen und verarbeitet werden (aus den YAMLs in den Config-Resolver von eZ / Symfony)

  - ableiten wo die Werte und in welcher Reihenfolge die Werte gelesen werden und verarbeitet werden
  - aus welchen Dateien stammen die Werte
  - in welcher Reihenfolge werden die Dateien eingelesen / aus welcher Datei stammt der dortige Wert

- [ ] Irgendwie im Backend anzeigen und verarbeiten lassen (eventuell Werte verändern können? (Änderungen cachen?), außerdem mitbekommen wann der Cach geändert wird und dann darstellen)

# Erste Schritte:

## Erschaffung der Bundle-Struktur:

- Vendor-Name als Überordner, dann der Name des Bundles als Ordner (ohne Vendor),
- in dem Ordner dann die Unterordner: "Controller, DependencyInjection, Resources und Tests"
- Außerdem die VendornameBundlename.php Datei, welche das Symfony Bundle erweitert, damit das Bundle grundlegend komplettiert wird
- in "Resources" finden sich die Unterordner: "config, doc, public und views"

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

**Pfad:** `{ezinstallation}/vendor/symfony/http-kernel/Kernel.php`

### ParameterBag

!! Der Parameterbag selbst kann durch den Container abgerufen werden!!!!

- mit der Methode: "getParameterBag()",
  diese gibt einen "Frozenparameterbag" zurück. Dieser beinhaltet zwar die Parameter und erlaubt sowohl das Hinzufügen, als auch das Entfernen bestimmter Parameter, aber er hat ebenfalls eine protected Eigenschaft in "parameters"

=> Das bedeutet, dass man die Parameter daraus nicht direkt einlesen kann

**Meine Lösung dafür:**

- Erzeugung einer eigenen Klasse, welche die ursprüngliche Frozen-Parameter-Bag-Klasse beerbt
- Weiterreichung des Containers mit dem Bag an den Konstruktor dieser eigenen Klasse
- Hinzufügen einer Funktion `getParameters()`, welche das Parameter-Array zurückgibt

### Loader-Hierarchie:

> **Config/Loader:**
>
> > DelegatingLoader <br>
> > LoaderResolver <br>

- Scheinbar wird für das Parsen der Konfigurationsdateien beim Aufbauen des Caches zuerst der "DelegatingLoader" aufgerufen
- Dieser ruft dann den "LoaderResolver" auf, welcher seinerseits

> **DependencyInjection/Loader:**
>
> > YamlLoader

- die entsprechenden Datei-Parser durchzugehen scheint (zumindest was die Datei-Kompatibilität angeht).

## Ziel: Siteaccess-aware sein

**Das eigentliche Ziel dabei:**

- Die geparsten Variablen nach site-access dependency sortieren können und basierend
  auf dem aktuellen site-access nur die Variablen in einem Array zeigen, welche zu dem site-access gehören.

- Potenziell darauf aufbauend dann vergleichende Arrays, in denen verschiedene site-accesse zu den gleichen
  Seiten betrachtet werden können

**Voraussetzung:**

- Der aktuelle site-access muss erkannt werden können und twig muss das entsprechende Array übergeben werden.
- Dafür müssen die Inhalte der Parameter-Speicherung nach site-access durchsuchbar sein.

```php
// Siteaccesse werden von den Klassen des Pfades:
$path = "{ezinstallation}/vendor/ezsystems/ezplatform-kernel/eZ/Publish/Core/MVC/Symfony";
/* verarbeitet und unter dem Ordner "SiteAccess" in dem Verzeichnis finden sich Klassen,
die direkt für das Matchen und Ähnliches zuständig sind. */
```

**Neue Entwicklung dazu:**

- _Bisheriges Vorgehen_: Das durch mich erstellte Array durchgehen und in dem Array alle herausnehmen,
  die einen Key haben, der zu dem siteaccess passt, herauszunehmen und in ein eigenes Array zu packen.

      * Das Problem dabei ist allerdings, dass ich nicht wusste, wie ich denn nur die bekomme, die auch wirklich
      von site-accesses abhängen und nicht nur einfach den string im Namen haben
      * Weiteres Problem, dass mir nicht bewusst war: Ist nur die halbe Miete, denn eigentlich
      ist eher wichtig, welchen Wert die Parameter tatsächlich haben (da durch die vielen Ebenen der
      site-accesse auch die gleichen Parameter auftauchen können, aber überschrieben werden).

- _Neu_: Die ganze Operation nutzt nun die Objekte, mit denen schon beim Config-Processen gearbeitet wurde.
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

- Für das Verarbeiten der Parameter selbst werden laut Symfony-Stopwatch ungefähr 20 Millisekunden gebraucht
- Für das site-access-spezifische Verarbeiten werden in etwa 40 bis 50 Millisekunden veranschlagt
  einer kurzen Folge von Messungen zur Folge verbrauchen die Schritte wie folgt Zeit:
  _ Das Heraussuchen aller möglichen Siteaccess-Parameter: ca. 0.6 Millisekunden
  _ Das Entfernen von Parameter-Duplikaten und das Zusammenbauen der vollständigen Parameternamen: ca. 0.5 Millisekunden \* Das Auflösen der Parameter-Werte mit dem eZConfigResolver: ca. 50 Millisekunden

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

- Der Symfony Cache-Component:
  - https://symfony.com/doc/current/components/cache.html
- Cache-Pools und -Adapter:
  - https://symfony.com/doc/current/components/cache/cache_pools.html
- Der PhpFilesAdapter:
  - https://symfony.com/doc/current/components/cache/adapters/php_files_adapter.html
- Cache Invalidierung:
  - https://symfony.com/doc/current/components/cache/cache_invalidation.html
- Cache Items:
  - https://symfony.com/doc/current/components/cache/cache_items.html
- Und der Cache:
  - https://symfony.com/doc/current/cache.html

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

- Zusätzlich zu den Werten der Parameter und der Parameter selbst, sollen auch noch die Dateien, in denen sie auftauchen bekannt sein
- Es soll herauskommen, welche Datei "gewonnen" hat und als letzte eingelesen wurde
- Das Ganze sollte dann auch noch einsehbar sein

**Voraussetzungen:**

- Der Load-Vorgang ist irgendwie so manipulierbar, dass die Locations der Parameter über das eigentliche Laden hinaus gespeichert
  werden können
- Klassen, die am Load-Vorgang beteiligt sind, müssen die Orte sowohl weitergeben als auch speichern können (im Falle des
  Containers zum Beispiel)
- Das Ergebnis des Load-Vorganges (welcher Dateien berücksichtigt), muss nach außen getragen werden können
- Minimale Invasivität ist erforderlich, damit das System stabil und auf längere Zeit kompatibel sein kann (damit außerdem weniger Code
  und Last anfällt)

**Falls kein Eingreifen in den ursprünglichen Load-Vorgang möglich ist:**

- Muss ein separater Load-Vorgang eingesetzt werden, welcher auf die Datei-Pfade achtet
- Dann sollte dieser ressourcen-schonend sein, da doppeltes Auslesen der Werte eigentlich unnötig ist
  - Außerdem (sollte /) wird nicht jede Klasse des Boot-Vorganges von Symfony benötigt (werden), um die Parameter zu laden
- Eine Sicherung der Ergebnisse im Cache ist daher unabdingbar, damit keine zusätzliche Last im Standard-Betrieb entsteht

**Größte Frage: Langlebigkeit?**

Da der zweite Loading-Vorgang sehr stark auf (momentan) vorhandene Symfony-Komponenten und -Vorgänge aufbaut, könnte der Ganze Vorgang
leicht obsolet werden und müsste dann vermutlich stark angepasst werden, damit er wieder funktioniert.
=> Wie kann man dort Langlebigkeit und Sicherheit auf Dauer schaffen?

### Vorgehen dazu

**Die Kernel-Komponente / der LoadInitializer:**

Es wurde für den datei-pfad-berücksichtigenden Lade-Vorgang ein eigener Kernel geschaffen: Der **LoadInitialiser**. Dieser Kernel erfüllt einige wenige Aufgaben:

- Er stellt sicher, dass für den Ladevorgang die richtigen Loader verwendet werden
- Er stellt sicher, dass für den Ladevorgang der richtige ContainerBuilder verwendet wird

In späteren Iterationen des Kernels kamen auch noch kleinere Aufgaben dazu, die mit dem Aufspüren von Lade-Pfaden aus den Bundlen zusammenhängen:

- Der Kernel initialisiert nun nämlich alles nötige für die `ConfigPathUtility`, welche dafür sorgt, dass Config-Pfade aus den Bundlen zwischengespeichert
  und später weiterverwendet werden können.
- Er übernimmt den Aufräumvorgang des Kernel-Caches, wenn, nach dem ersten Einlesen der Konfiguration durch die `ConfigPathUtility`, signalisiert wird,
  dass neue Pfade dazugekommen sind
- Er führt den Neustart des Ladevorganges durch, wenn dies vorher durch die `ConfigPathUtility` signalisiert worden ist

Durch diese Komponente kann der ganze Vorgang überhaupt erst vollzogen werden, denn nur so ist es möglich, die richtigen
Loader und Container einzuschleusen, welche später die geladenen Pfade nachverfolgen und aktiv weitergeben können. Durch die
Nachverfolgung der Bundle-Konfiguration ist er außerdem dafür wichtig geworden, die Parameter aus diesen Pfaden nachverfolgen und
einlesen zu können, da der Ladevorgang mit diesen speziellen Routen von vorne gestartet werden muss, um deren Ursprung zurückverfolgen
zu können.

**Der CustomContainerBuilder:**

Ähnlich wie der LoadInitializer, bietet der CustomContainerBuilder nur sehr begrenzte Funktionalität. Er stellt zunächst sicher, dass der eigens geschaffene
`LocationAwareParameterBag` während des Ladevorganges genutzt wird und dass dieser mit den aktuellen Pfaden versorgt wird, sodass der Ursprung der Parameter
nachverfolgt werden kann.
Der zweitwichtigste Faktor beim ContainerBuilder ist es sicherzustellen, dass bei der zweiten Ladephase des Vorganges, keine Nebeneffekte durch die Bundle-
Konfiguration entstehen, welche zu Fehlern beim Ladevorgang führen könnten.

> Wenn der Pfad aber schon im Container bekannt ist, warum wird er an den Parameterbag weitergegeben und nicht im Container genutzt?

Antwort: Weil der Container Parameter über viele verschiedene Funktionen zugewiesen bekommt, von denen alle editiert werden müssten (wobei das zum Teil gar nicht geht),
damit derselbe Effekt erzielt werden kann, wie wenn der Pfad weitergegeben wird und **alle** Parameter zentral über **eine** Funktion im Parameterbag zugewiesen werden.

Das eigentliche Herzstück der Klasse und die komplizierteste Funktion der Klasse ist Folgendes:

```php
    /**
     * {@override}
     * This is an extension of the function. Its purpose is to not only track the bundle being processed, but to determine
     * its residence in the file system and provide that information to the appropriate classes, in order to allow the bundle's
     * config directories to be tracked.
     *
     * @param string $name The name of the bundle who's extension config to retrieve.
     * @return array Returns the found configuration.
     */
    public function getExtensionConfig(string $name)
    {
        try {
            // Get the class of the extension, then generate a Reflection class out of that, which allows finding the path to the file, then set that path
            $extensionClass = get_class($this->getExtension($name));
            $extensionReflector = new ReflectionClass($extensionClass);
            $extensionLocation = $extensionReflector->getFileName();
            $this->setCurrentLocation($extensionLocation);

            // Following the Symfony-Bundle-Conventions, the config directory of the bundles is being set instead of the extension class
            $extensionLocation = ConfigPathUtility::convertExtensionPathToConfigDirectory($extensionLocation);
            if ($extensionLocation && is_string($extensionLocation)) {
                ConfigPathUtility::addPathToPathlist($extensionLocation);
            }
        } catch (Exception $error) {
            // In the event that something fails in the above procedure to determine the correct paths, just take the name of the extension as a path
            $this->setCurrentLocation($name);
        }

        // continue the typical, normal extension-config-functionality
        return parent::getExtensionConfig($name);
    }
```

Diese Funktion beschafft all die Konfigurationspfade der Bundle, die während des Ladeprozesses auftauchen und sorgt so dafür, dass später auch deren
Parameter gesondert eingelesen und somit nachverfolgt werden können.

**Der CustomParameterBag / LocationAwareParameterBag:**

Dieser Parameterbag erfüllt lediglich den Zweck für alle Parameter, sowohl deren Wert als auch den Pfad aus dem der Wert stammt, an die notwendigen
Klassen weiterzugeben, sodass eine Historie des Einlesens aufgebaut werden kann.

> Warum fließen Parameter, Werte und Pfade an dieser Stelle zusammen und nicht früher?

Der Grund dafür, dass dieser Umweg genommen werden muss ist,
dass dies eine einzige zentrale Stelle ist, an welche Parameter übergeben werden. Alle anderen Klassen bieten keinerlei Möglichkeit so einfach
an alle Werte und, nach den Änderungen, auch an die Pfade zu kommen.

> Warum wird nichts von den Pfaden direkt im Parameterbag oder Container gespeichert?

Der Parameterbag und der Container erfüllen wichtige Zwecke im Ladevorgang und sind so tief darin verwurzelt und eingewickelt, dass es unmöglich ist,
eine Änderung an deren Speicherstruktur vorzunehmen, ohne den ganzen Ladevorgang zu sabotieren und zum Absturz zu bringen. Die Parameter können im Bag nicht
umgebaut werden, weil einige Bundle darauf aufbauen, dass bestimmte Parameter vorhanden sind und wenn die Struktur anders ist, können sie nicht auf deren Werte
zugreifen, also funktioniert das Bundle nicht und führt zum Absturz.

> Wenn das die zweite Welle des Ladevorganges ist und man nichts von den Pfaden in Container oder Bag speichern kann, warum werden dann überhaupt weiter
> Definitionen und Parameter in den Klassen gespeichert?

Einerseits weil, wie gesagt, einige Bundle und Teile des Ladevorganges darauf aufbauen und nicht funktionieren können, wenn die Informationen irgendwie
fehlen oder anders gespeichert werden und andererseits weil es performance-technisch kaum einen Unterschied macht, da die Funktionen so oder so aufgerufen würden
und die Logik ausgeführt würde (daran lässt sich kaum etwas ändern) und am Ende alles gecached wird.

Daher nur zwei Ergänzungen:

```php
    /**
     * A function for the parameterbag to keep track of the path that is currently being loaded.
     * It depends on external intervention in order to be held up to date and used at all.
     * It has to be used either prior to the parameters being given to the parameterbag or just as they are given.
     * In any other case, the location that has been set prior will be used instead!
     *
     * @param string $location The path / file that is being loaded.
     */
    public function setCurrentLocation(string $location) {
        $this->currentLocation = $location;
    }
```

Eine kleine Funktion, welche genutzt wird, um den aktuell geladenen Pfad zu setzen.

```php
    /**
     * @override
     * This is an override of the set function which is typically used to add parameters to the bag.
     * But since it is important to keep track of the parameters's origin too, the function has been edited in
     * order to set the parameter, its value and the path the value stems from at the same time.
     *
     * @param string $name The name of the parameter.
     * @param mixed $value The value being set to the parameter.
     */
    public function set(string $name, $value)
    {
        // Give the parameter, the value and the current location
        CustomValueStorage::addParameterOrLocation($name,$value,$this->currentLocation);

        // Continue with standard parameter setting procedure
        parent::set($name, $value);
    }
```

Eine kleine Ergänzung zur Funktion, mit der der Pfad und alles andere and den `CustomValueStorage` überreicht wird.

**Der CustomDelegatingLoader:**

Ähnlich wie sein Symfony-Pendant, macht auch diese Klasse nicht viel, ist aber von zentraler Bedeutung für den gesamten Vorgang. Der DelegatingLoader ist einer
von zwei Ansatzpunkten (der andere ist der GlobLoader) im gesamten Prozess, welcher es erlaubt alle Pfade, die während des Ladens aufkommen, zu sammeln und effektiv
weiterzuleiten. Denn jede Resource, welche einzeln und direkt geladen wird, fließt durch diesen Loader hindurch. Anders ist das, wenn eine Resource, die mehrere Dateien
in sich vereint (wie zum Beispiel ein Verzeichnis) geladen werden soll.

Die Klasse ist dem entsprechend schlank und die einzigen beiden Änderungen finden sich sogleich in folgenden Schnipseln wieder:

```php
/** @var CustomContainerBuilder A container builder which serves to build the container while keeping track of the files used to do so. */
    private $container;
```

Damit die Pfade überhaupt gesichert werden können, braucht es einen Container, welcher so eine Funktionalität bietet und darüber hinaus muss dem DelegatingLoader überhaupt
erst ein Container zugewiesen werden. Die Originalklasse kennt gar keinen Container, weil seine Funktionalität (Ressourcen an andere Loader weiterreichen) auch ohne auskommt.

```php
    /**
     * @override
     * This override ensures that everytime a resource is loaded (which is not a global pattern) the path to said resource is set
     * in and known by the container.
     */
    public function load($resource, string $type = null)
    {
        // If the given resource is no glob pattern but instead something else and the resource is a string, the path will be given to the container.
        if ($type !== "glob" && !is_object($type) && is_string($resource)) {
           $this->container->setCurrentLocation($resource);
        }

        // Afterwards continue with the standard loading procedure of Symfony.
        return parent::load($resource, $type);
    }
```

Eine einfache Anweisnung zum Weitergeben der Pfade wurde in die load-Funktion eingefügt.

**Der CustomGlobLoader:**

Der CustomGlobLoader ist ähnlich glanzlos wie sein Partner, der `CustomDelegatingLoader`. Auch er kommt mit wenig Funktion aus und tut nichts anderes als
die erhaltenen Pfade einen nach dem anderen beim Laden weiterzureichen. Er ist aber der zweite Ansatzpunkt für die volle Pfad-Abdeckung beim Laden, weil
dieser Loader das Laden jeder einzelnen Ressource aus Glob-Ressourcen (also Verzeichnissen) übernimmt und daher nach und nach jede Datei erhält,
welche sich in dem Verzeichnis befindet.

```php
 /**
     * @override
     * This override is basically a copy of the {@see GlobLoader} load function just with one key difference:
     * It tracks the paths gathered by GlobResources and always relays that path before the loading process
     * of the parameters and services begins.
     */
    public function load($resource, string $type = null)
    {
        // Typical load function of the GlobLoader as of Symfony 5.1.5
        foreach ($this->glob($resource, false, $globResource) as $path => $info) {
            // Relay the path to the CustomContainerBuilder
            $this->container->setCurrentLocation($path);
            // continue with the standard loading procedure
            $this->import($path);
        }

        $this->container->addResource($globResource);
    }
```

Diese eine Funktion stammt aus dem ursprünglichen GlobLoader von Symfony und stellt dessen einzige "richtige" Verantwortlichkeit dar. Da das Übergeben der Pfade nicht
in die vorhandene Funktion eingebettet werden konnte (da das ganze Prozedere in einer For-Each-Schleife befindlich ist), musste die Funktion eins zu eins aus dem
ursprünglichen GlobLoader kopiert und dann einzeilig abgeändert werden. Alles andere verbleibt daran unverändert.

**Der CustomValueStorage:**

Diese statische Klasse bietet das eigentliche Herz des ganzen veränderten Ladevorganges, denn hier fließen Parameter, Werte und Pfade zusammen und werden dementsprechend
formatiert und archiviert. Dabei ist die Funktion zum Hinzufügen von Parameter- / Wert - Pfad-Paaren der zentrale Teil der Klasse. Denn dort wird sichergestellt,
dass Parameter und deren Pfade gesichert werden, ohne dass Informationen überschrieben oder gar gelöscht werden.

Zeitgleich bietet die Klasse bestimmte "Modi" an, welche dafür sorgen sollen, dass keine falschen oder unnötigen Informationen hinzugefügt werden.

- Der `allowWrite`-Parameter stellt sicher, dass Inhalte nur dann intern geschrieben werden, wenn es erlaubt ist
  - So kann zum Beispiel im Verlauf des Ladens bestimmt werden, dass bei speziellen Abschnitten keine Parameter gesetzt werden
  - Während der Entwicklung des Bundles hat dieser Parameter an Bedeutung verloren und könnte sogar entfernt werden, bietet aber eigentlich eine
    ordentliche Möglichkeit bestimmte Vorgänge aus dem Laden auszuschließen (zum Beispiel wenn ein eigener Compiler-Pass verfasst wird, dessen Parameter nicht darin auftauchen sollen)
- Der `bundeConfig`-Parameter gibt an, ob der Bundle-Konfigurations-Teil des Ladens erreicht worden ist
  - Dieser bestimmt, ob alle Pfade, unter denen ein Parameter auftaucht, gesichert werden sollen und das auch dann, wenn ein Parameterwert unverändert auftaucht
  - Dies wurde getan, da während der Bundle-Konfiguration alle vorhandenen Parameter immer wieder unverändert übernommen, aber dennoch hinzugefügt worden sind,
    was dann zu mehreren völlig überflüssigen Quellen für Werte geführt hat
  - Wird der Modus also aktiviert, wird keine Quelle mehr zur Liste hinzugefügt, es sei denn sie bietet veränderte Werte für den Parameter
  - Er kann (und wird) aber auch wieder deaktiviert werden

Des Weiteren bietet die Klasse noch Methoden, mit denen man sich die gesammelten Informationen beschaffen kann.
Erwähnenswert ist auch noch, dass die Klasse alle angetroffenen Quellen für Parameterwerte gesondert intern in einer Liste (einem Array) speichert.
Diese Information kann auch abgerufen werden, bietet aber keinen weiteren, direkten Parameterbezug.

Hier die zentrale und wichtigste Methode der Klasse:

```php
    public static function addParameterOrLocation(string $parameterName, $value, string $path) {
        // Only if it is currently allowed to write, will the process even begin
        if (self::$allowWrite) {
            // Internally, all encountered Locations are stored separately. Even though it is of no use at the moment, it is still there.
            if (!in_array($path, self::$encounteredLocations)) {
                array_push(self::$encounteredLocations, $path);
            }

            if (!isset(self::$parameterAndTheirLocations[$parameterName])) {
                self::$parameterAndTheirLocations[$parameterName] = [$path => $value];
            } else if (self::$bundleConfig && end(self::$parameterAndTheirLocations[$parameterName]) === $value) {
                return;
            } else {
                self::$parameterAndTheirLocations[$parameterName][$path] = $value;
            }
        }
    }
```

Es bedarf keiner wesentlichen Erklärung, weil die Methode nicht viel mehr macht, als den Pfad, der übergeben wurde, in die Liste aller Pfade einzutragen, falls
der Pfad dort nicht schon enthalten ist. Darüber hinaus wird der Parameter und oder dessen Wert, sowie der Pfad aus dem alles stammt, in eine weitere,
interne Liste (Array) eingetragen, wobei der `bundleConfig`-Parameter noch die Rolle spielt, die zuvor erläutert wurde.

**Die ConfigPathUtility:**

Diese statische Klasse ist eng mit dem Ladevorgang in der Hinsicht verwoben, dass sie für das Aufspüren von Bundle-Konfigurations-Pfaden verantwortlich ist.
Dafür werden während der Ladeprozedur die Pfade zu den Extension-Klassen der Bundle beschafft, weil sich diese anhand der geladenen Bundle identifizieren lassen
und von diesen wird dann auf Basis der Symfony-Bundle-Konventionen ein Pfad zur Konfiguration ermittelt. Es wird auch geprüft, ob diese Pfade zu real existierenden
Verzeichnissen führen. Es ist dabei nicht möglich Dateien außerhalb der Konventionen zu beschaffen, weil sich dies nicht pauschal und bundle-deckend dynamisch
erzeugen lässt. Mit der Klasse können diese Pfade dann gesammelt und anschließend im Cache gespeichert werden.

Als Folge des Abspeicherns der erhaltenen Pfade, wird intern `restartLoadingProcess` gesetzt, welches per `isSupposedToRestart`-Funktion abgerufen werden kann und
somit angibt, ob sich die aufgespürten Konfigurationspfade verändert haben und somit für eine vollständige Abdeckung der Pfade der Load-Vorgang neugestartet werden
muss. Gleichzeitig signalisiert der Parameter erst am Ende der gesamten ersten Boot-Phase, dass es Änderungen gegeben hat, sodass der Vorgang nicht vorschnell
erneut gestartet wird.

Zuerst wird in der Klasse allerdings eine Initialisierung vorgenommen:

```php
    /**
     * Serves to set up and initialise all major internal attributes in order to allow the class to function properly.
     * It initiates the cache (if it hasn't already), retrieves the routes from the cache, parses the manually defined routes
     * and sets the internal boolean attributes to their initial value.
     */
    public static function initializePathUtility(): void
    {
        if (!self::$cacheInitialized) {
            // If the cache has not yet been instantiated
            if (!isset(self::$configPathCache)) {
                self::$configPathCache = new PhpFilesAdapter();
            }

            self::$restartLoadProcess = false;
            self::$pathsChanged = false;

            try {
                // Retrieve the cached routes
                self::$configPaths = self::$configPathCache->get("cjw_config_paths", function (CacheItemInterface $item) {
                    return [];
                });

                self::$cacheInitialized = true;

                // Parse the manual path_config-file
                self::getUserDefinedPaths();
            } catch (InvalidArgumentException $e) {
                self::$configPaths = [];
            } catch (Exception $error) {
                self::$configPaths = [];
            }
        }
    }
```

Sie dient dazu den Ausgangs-Zustand der Klasse sicherzustellen, damit im Anschluss alle wichtigen Vorgänge erfolgen können.
So wird etwa der Cache instanziiert und alle Pfade, die bereits gecached worden sind, werden in die interne Pfad-List übernommen.
Außerdem werden einige "Flags" gesetzt (das heißt ein paar boolean-Werte werden in den Ausgangszustand gebracht) und zuletzt wird
die Datei für die manuellen Pfade eingelesen und verarbeitet.

```php
    /**
     * Takes a given path and adds that path to the internal path-list. Also signals internally,
     * that the path list has been changed.
     *
     * @param string $configPath The path to be added to the list.
     * @param bool $isGlobPattern A boolean stating whether the path is a glob-resource / pattern which will have to be loaded differently from non-glob-pattern.
     */
    public static function addPathToPathlist(string $configPath, bool $isGlobPattern = true): void {
        // If the cache has not been initialised, initialise it.
        if (!self::$cacheInitialized) {
            self::initializePathUtility();
        }

        // Only if the path does not yet exist in the list, add it
        if (!empty($configPath) && !key_exists($configPath,self::$configPaths)) {
            self::$pathsChanged = true;
            self::$configPaths[$configPath] = $isGlobPattern;
        }
    }
```

Dadurch, dass erst überprüft wird, ob bereits der gleiche Pfad in der internen Liste existiert, wird sichergestellt, dass der Boot-Vorgang nicht unnötigerweise
neugestartet wird, denn eine Änderung der Pfade wird nur dann signalisiert, wenn mindestens ein gänzlich neuer hinzugefügt wird.

`pathsChanged` wirkt sich im Anschluss auch auf das Abspeichern der Pfade im Cache und den Neustart des Boot-Vorganges aus:

```php
    /**
     * Persists the paths that have been added during the load process in the cache. But only if there have been changes
     * to the internal paths, otherwise the already existing cached paths will not be overwritten.
     *
     * <br> Also signals, that a restart of the load process is useful / necessary.
     */
    public static function storePaths(): void {
        if (self::$cacheInitialized && self::$pathsChanged) { //Nur dann, wenn sich die Pfade geändert haben, wird auch tatsächlich etwas abgespeichert und der Neustart empfohen
            try {
                self::$configPathCache->delete("cjw_config_paths");
                self::$configPathCache->get("cjw_config_paths", function (CacheItemInterface $item) {
                    return self::$configPaths;
                });
                self::$restartLoadProcess = true;
            } catch (InvalidArgumentException $e) {
            }
        }
    }
```

Um die Konfigurations-Pfade überhaupt erst zu beschaffen wird eine weitere Funktion der Klasse genutzt:

```php
 /**
     * Takes a given path (which belongs to a bundle, most of the times to an Extension class in the DependencyInjection subdirectory) and
     * changes the path so that it points towards the bundle's config directory. This is based on Symfony's bundle conventions
     * and best practices (as the ExtensionClass is always present in the DependencyInjection directory and the config is
     * present under Resources/config **as of Symfony 5.1.5**)
     *
     * <br> But, paths which point to a file / directory which does not exist, are not added to the paths list.
     *
     * @param string $extensionPath The path pointing to a bundle's ExtensionClass.
     * @return string|null Returns the converted string or null, if the path does not point to the DependencyInjection or a directory which does not exist.
     */
    public static function convertExtensionPathToConfigDirectory(string $extensionPath) {
        // Get the index in the string where "DependencyInjection" is present
        $diPosition = strpos($extensionPath,"DependencyInjection");

        if(!$diPosition) {
            return null;
        }

        // Change it from DependencyInjection to the config directory
        $configDirPath = substr($extensionPath,0,$diPosition)."Resources/config/";

        if (!file_exists($configDirPath)) {
            return null;
        }

        // Since the entire directory is added as a glob resource, the "*" signals that all files within the directory are
        // to be looked at (only one level deep) and the extensions signal that only files which end on one of the config
        // extensions are considered.
        return $configDirPath."*".self::$configExtensions;
    }
```

Weiterhin bietet die Klasse die Möglichkeit neue Pfade manuell über eine eigene Datei zum Einlese-Vorgang hinzuzufügen. Dies ist eine Option, deren Nutzen
sicherlich begrenzt ist, welche aber die Option offen lässt, "blinde" Flecke des automatischen Prozesses manuell nochmals einlesen lassen zu können.

> **Die manuellen Pfade werden allerdings vor den automatisch erlangten Bundle-Konfigurations-Pfaden eingelesen!**
> Es sei denn sie werden manuell erst dann nachgetragen, wenn die automatisch erlangten Pfade bereits gecached wurden.

```php
/**
     * This function parses the paths defined by users of the bundle in the config_paths.yaml and adds them to the path
     * array.
     */
    private static function getUserDefinedPaths(): void
    {
        $parser = new Parser();

        // Go from this path to the config path of the bundle
        $pathToConfigRoutes = substr(__DIR__, 0, strpos(__DIR__, "src",-3)) . "Resources/config/config_paths.yaml";
        $userDefinedConfigPaths = $parser->parseFile($pathToConfigRoutes);

        // Are there even parameters set in the file? If not, then just initiate the variable as an empty array
        $configPaths = (is_array($userDefinedConfigPaths) && key_exists("parameters",$userDefinedConfigPaths))? $userDefinedConfigPaths["parameters"] : [];

        foreach ($configPaths as $pathName => $pathInfo) {
            // First check, whether some basic information is set for the defined routes (to see whether they can be worked with)
            if (self::checkUserDefinedPath($pathInfo)) {
                if ($pathInfo["addConfExt"]) {
                    $pathInfo["path"] .= self::$configExtensions;
                }
                self::addPathToPathlist($pathInfo["path"], $pathInfo["glob"]);
            }
        }
    }
```

Die Überprüfungen der definierten Pfade ist rudimentär und soll zunächst nur feststellen, ob sie nach dem Muster definiert worden sind,
welches in der Datei vorgegeben wurde.

```php
/**
     * Checks the user defined paths for any kind of errors with regards to the definition of said paths.
     *
     * @param array $path A path array (hopefully with 3 items under the keys of "path", "glob" and "addConfExt").
     * @return bool Boolean which states whether the path at least passes the most basic checks regarding their structure.
     */
    private static function checkUserDefinedPath(array $path): bool {
        if (is_array($path) && count($path) === 3) {
            if (!(key_exists("path",$path) && is_string($path["path"]) && !empty($path["path"]))) {
                 return false;
            }

            if (!(key_exists("glob", $path) && is_bool($path["glob"]))) {
                return false;
            }

            if (!(key_exists("addConfExt",$path)) && is_bool($path["addConfExt"])) {
                return false;
            }

            return true;
        }

        return false;
    }
```

### Ergebnis

In Folge der geschilderten Klassen und derer Methoden, ist es nun möglich für viele Parameter mindestens eine Quelle zu bestimmen.
Damit ist das Ziel größtenteils erfüllt. Es handelt sich zwar um eine recht fragile Implementation, weil viele Methoden und Klassen
auf der derzeitigen Struktur des Ladevorganges basieren, aber zum jetzigen Zeitpunkt lässt sich das kaum ändern.

Die Dokumentation, die sich hier in dem Dokument findet, ist nicht in dem üblichen Format gehalten, bei dem viele Schritte und die
Denkweise hinter diesen Schritten festgehalten werden, sondern eher in retrospektive gehalten, weil es vorher nicht klar war, ob sich
meine Vorstellungen / das Ziel überhaupt würden umsetzen lassen. Da zusätzlich dazu noch viele Fehler und Probleme im Verlauf des Prozesses
aufgetreten sind, war es schwer möglich zeitgleich eine entsprechende Dokumentation festzuhalten. Insbesondere weil viele Klassen und
Methoden in Folge dessen ständigen Änderungen unterworfen waren.

## Übertragung ins Frontend

**Der Gedanke dahinter:**

Nun da die Funktionalität, welche sich mit der Beschaffung von Pfaden und leserlichen Parametern beschäftigt, erstmal umgesetzt wurde,
ist es wichtig, dass die Ergebnisse der Funkion nach "vorn" in die Web-Oberflächen getragen werden und somit mehr Informationen
besser aufbereitet werden können und darüber hinaus kleinere Wunsch-Funktionen hinzugefügt werden können.

**Voraussetzung:**

- Abgesehen davon, dass sich das meiste nun mit einem Controller erfüllen lassen muss,
- müssen adäquate Javascript-Frontend-Strukturen geschaffen werden, damit sich die Daten nicht nur visualisieren lassen,
  - sondern auch bestimmte Vergleiche / Funktionen geschaffen werden können.
- Dafür ist es auch nötig, die gesammelten Informationen an das Frontend weiterzugeben und das zum Beispiel im JSON-Format oder direkt
  durch Templates.

### Erste Schritte

**Controller:**

Um die Inhalte, die im Backend durch das übrige Bundle generiert werden, nach vorn tragen zu können, bedarf es einer Klasse (eines Controllers),
die dazu in der Lage ist, die Inhalte zu besorgen und an Templates im Frontend weiterzutragen. Zumindest sollte sie allerdings bestimmte
Templates rendern können, damit beim Aufrufen der Route im Backend (im eZ-Admin-Interface) letztlich die Möglichkeit besteht, überhaupt
etwas anzeigen zu können.

- **WICHTIGE INFORMATION:** Mein Controller beerbt den Abstract-Controller von Symfony. Dieser verlangt es aber, dass dem Controller per Constructor
  ein Container gesetzt wird (derjenige der Applikation). Das heißt, dass `$this->container` nicht null sein darf! Diese Bedingung wird vom `ControllerResolver` des
  `Framework`-Bundles geprüft und der sorgt dafür, dass die Anfrage fehlerhaft abgebrochen wird, wenn der Controller keinen Container hat.
  _ Theoretisch ist es möglich, den Container auf eine Zahl oder einen String oder irgendwas von `null` verschiedenes zu setzen, aber
  das birgt das Risiko, dass diese Prüfung auf die Klasse doch irgendwann eingeführt wird und der Workaround somit nicht funktioniert
  _ Da der Controller im Bundle liegt, muss nicht nur per Route definiert werden, dass der Controller existiert, wo er existiert
  und das er für die Route verantwortlich ist, sondern auch, am besten, dass er ein Service des Bundles ist und als solcher den Container
  autogewired bekommt \* In meinem Fall benötige ich jedoch noch weitaus mehr Parameter, da der Controller sicherstellt, dass die `ConfigProcessCoordinator`-Klasse
  gestartet ist, wofür neben `Container` auch noch `Resolver` und `RequestStack` benötigt werden, diese lass ich per Service-Definition
  nach [Symfony best practices für Bundle](https://symfony.com/doc/current/bundles/best_practices.html) an den Controller übergeben

Die Konfiguration für den Controller als Service und für die Route, welche den Service deklariert, sehen folgendermaßen aus:

routing.yaml im Bundle:

```yaml
cjw_processed_parameters.list:
  path: /cjw/config-processing
  controller: cjw_config_processor.controller::retrieveProcessedParameters
  methods: [GET]
```

Der Controller-Name ist dabei derjenige, der für den Controller bei der Service-Definition in der

services.yaml angegeben wurde:

```yaml
cjw_config_processor.controller:
  class: App\CJW\ConfigProcessorBundle\Controller\ConfigProcessController
  arguments:
    ["@service_container", "@ezpublish.config.resolver", "@request_stack"]
  public: true
```

**eZ-Backoffice Side-Menüs erstellen:**

Um die Side-Menus von dem eZ-Backoffice für eigenen Menüpunkte nutzen zu können, benötigt man Eventlisteners, die mit denjenigen aus
dem Knp-MenuBuilder-Bundle zusammenarbeiten, um das Menü zu bauen.

## Überführung des Bundles in den Live-Betrieb

### Konfiguration

**Bundle aktivieren**

Die Bundle müssen darüber hinaus auch noch manuell aktiviert werden. Dafür muss die `{Symfony-Installation}/config/bundles.php` bearbeitet werden.
Das Folgende muss dabei in die Datei eingetragen werden:

```php
    CJW\ConfigProcessorBundle\CJWConfigProcessorBundle::class => ["all" => true],
    CJW\LocationAwareConfigLoadBundle\CJWLocationAwareConfigLoadBundle::class => ["all" => true],
```

Diese beiden Zeilen werden schlicht in das Array, das sonst schon in der Datei vorhanden ist, eingetragen. Damit sind die Bundle aktiviert
und sowohl die Konfiguration als auch die eigentlichen Services, können ihre Arbeit aufnehmen.

**Routing**

Wie auch bei den Bundlen von Netgen, müssen die Routen, welche über die Bundle-Controller bearbeitet werden, erst noch eingetragen werden.
Dafür wird lediglich eine Datei in `{Symfony-Installation}/config/routes/` benötigt, welche folgenden Inhalt hat:

```yaml
cjw_config_processor_bundle:
  resource: "@CJWConfigProcessorBundle/Resources/config/routing.yaml"
```

Dadurch wird Symfony signalisiert, wo es die Routen (und damit die Controller) des Bundles herbekommt. Damit können die eigentlichen
Routen direkt im Bundle konfiguriert werden.

**Assets:**

Um die Seite im Frontend effektiv nutzen zu können, benötigt das Frontend assets, welche letztlich
die Javascript-Dateien und die CSS-Dateien beinhalten. Diese verschiedenen Seiten werden in der Regel
vom Bundle mitgeliefert.

Damit diese allerdings ausgeliefert und in den:
`{symfony_installation}/public/bundles`-
Ordner und -Pfad gelangen, scheinen andere Bundles Skripte oder Symfony-Commandos einzubauen, welche
dann Symlinks zu dem bundle-internen Assets-Ordner in den (oben angegebenen) Pfad einbetten.
